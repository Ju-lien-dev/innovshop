<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use App\Entity\Commande;
use App\Entity\ArticleCommande;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;
use Stripe\Checkout\Session as StripeCheckoutSession;

final class StripeController extends AbstractController
{
    public function __construct(
        #[Autowire('%stripe.secret_key%')] private string $stripeSecret,
        private ProduitRepository $produitRepository,
        private EntityManagerInterface $em,
        private UserRepository $userRepo,
        private MailerInterface $mailer,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
    ) {}

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/create-checkout-session', name: 'app_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        $sfSession = $request->getSession();

        // 1) Panier (session)
        $cart = $sfSession->get('panier', []);
        if (empty($cart['id'])) {
            return $this->redirectToRoute('app_cart');
        }

        // 2) Re-price + contrôle stock
        $ids       = $cart['id'];
        $quantites = $cart['quantite'] ?? [];
        $noms      = $cart['nom_produit'] ?? [];

        $lineItems = [];
        foreach ($ids as $idx => $productId) {
            $produit = $this->produitRepository->find($productId);
            if (!$produit) {
                continue;
            }

            $qtyAsked = max(1, (int)($quantites[$idx] ?? 1));

            // Bride au stock disponible
            $available = max(0, (int) ($produit->getQuantiteRestante() ?? 0));
            if ($available <= 0) {
                // produit en rupture → on l’ignore (ou on redirige avec message, au choix)
                $this->addFlash('error', sprintf('"%s" est en rupture de stock.', $produit->getTitre() ?? 'Produit'));
                continue;
            }
            $qty = min($qtyAsked, $available);

            // Prix attendu par Stripe : CENTIMES
            $unitAmount = (int) $produit->getPrix();

            $name = method_exists($produit, 'getTitre')
                ? (string) $produit->getTitre()
                : (string) ($noms[$idx] ?? 'Produit');

            // Ajout d’un metadata "product_id" côté Stripe (fiabilise le webhook)
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'     => $name,
                        'metadata' => ['product_id' => (string) $productId],
                    ],
                    'unit_amount'  => $unitAmount,
                ],
                'quantity' => $qty,
            ];
        }

        if (!$lineItems) {
            throw $this->createNotFoundException('Aucun article valide dans le panier.');
        }

        // 3) Livraison déposée par CheckoutController::review()
        $shipAmount = (int) $sfSession->get('shipping_amount_cents', 0);
        $shipName   = (string) $sfSession->get('shipping_name', 'Livraison');

        if ($shipAmount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => ['name' => $shipName],
                    'unit_amount'  => $shipAmount,
                ],
                'quantity' => 1,
            ];
        }

        // 4) Adresse & user
        $shippingData = $sfSession->get('shipping', []);
        $addrFullName = (string) ($shippingData['full_name'] ?? '');
        $addr         = (string) ($shippingData['address']   ?? '');
        $zip          = (string) ($shippingData['zip']       ?? '');
        $city         = (string) ($shippingData['city']      ?? '');
        $country      = (string) ($shippingData['country']   ?? 'FR');

        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $userId = (string) $user->getId();
        $email  = $user->getEmail();

        // 5) Créer la session Checkout Stripe
        Stripe::setApiKey($this->stripeSecret);

        $params = [
            'mode'                 => 'payment',
            'line_items'           => $lineItems,
            'success_url'          => $urlGenerator->generate('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url'           => $urlGenerator->generate('app_checkout_cancel',  [], UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id'  => $userId,
            'metadata'             => [
                'user_id'   => $userId,
                'cart_ids'  => implode(',', array_map('strval', $ids)), // fallback par ordre si besoin
                'ship_name' => $addrFullName,
                'ship_addr' => $addr,
                'ship_zip'  => $zip,
                'ship_city' => $city,
                'ship_ctry' => $country,
                'ship_notes' => (string) $sfSession->get('shipping')['notes'] ?? '',
            ],
        ];

        if (!empty($email)) {
            $params['customer_email'] = $email;
        }

        $checkoutSession = StripeCheckoutSession::create($params);

        return $this->redirect($checkoutSession->url, 303);
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        // 1) Validation
        try {
            if ($secret) {
                $event = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
                if ($event->type !== 'checkout.session.completed') {
                    return new Response('OK', 200);
                }
                /** @var \Stripe\Checkout\Session $session */
                $session = $event->data->object;
            } else {
                $this->logger->warning('Webhook sans STRIPE_WEBHOOK_SECRET (DEV)');
                $data = json_decode($payload, false);
                if (($data->type ?? null) !== 'checkout.session.completed') {
                    return new Response('OK', 200);
                }
                $session = $data->data->object;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook invalide', ['error' => $e->getMessage()]);
            return new Response('Invalid', 400);
        }

        $this->logger->info('Webhook: checkout.session.completed reçu', [
            'session_id' => $session->id ?? null,
        ]);

        // 2) Récupération des lignes
        Stripe::setApiKey($this->stripeSecret);
        try {
            $items = StripeCheckoutSession::allLineItems($session->id, ['limit' => 100]);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook: impossible de récupérer les line_items', [
                'session_id' => $session->id ?? null,
                'error'      => $e->getMessage(),
            ]);
            return new Response('OK', 200);
        }

        // 3) Total €
        $totalEuros = isset($session->amount_total)
            ? number_format(((int)$session->amount_total) / 100, 2, '.', '')
            : '0.00';

        // 4) User
        $userIdMeta = $session->metadata->user_id ?? null;
        if (!ctype_digit((string) $userIdMeta)) {
            $this->logger->error('Webhook: user_id manquant ou invalide');
            return new Response('OK', 200);
        }

        $user = $this->userRepo->find((int)$userIdMeta);
        if (!$user) {
            $this->logger->error('Webhook: utilisateur introuvable', ['user_id_meta' => $userIdMeta]);
            return new Response('OK', 200);
        }

        $customerEmail = $session->customer_details->email
            ?? $session->customer_email
            ?? $user->getEmail();

        // 5) Meta livraison
        $shipMeta = [
            'ship_name' => $session->metadata->ship_name ?? null,
            'ship_addr' => $session->metadata->ship_addr ?? null,
            'ship_zip'  => $session->metadata->ship_zip  ?? null,
            'ship_city' => $session->metadata->ship_city ?? null,
            'ship_ctry' => $session->metadata->ship_ctry ?? null,
        ];

        $notes = $session->metadata->ship_notes ?? null;

        $commande->setShipNotes($notes);

        // 6) Crée la commande + lignes + décrémente le stock
        try {
            // Commande
            $commande = new Commande();
            $commande->setReference(date('YmdHis') . '-' . substr((string)mt_rand(), 0, 4));
            $commande->setCreatedAt(new \DateTimeImmutable());
            $commande->setStatus('paid');
            $commande->setTotal($totalEuros ?: '0.00');
            $commande->setUser($user);
            $commande->setShipFullName($shipMeta['ship_name'] ?? null);
            $commande->setShipAddress($shipMeta['ship_addr'] ?? null);
            $commande->setShipZip($shipMeta['ship_zip'] ?? null);
            $commande->setShipCity($shipMeta['ship_city'] ?? null);
            $commande->setShipCountry($shipMeta['ship_ctry'] ?? null);
            $this->em->persist($commande);

            // Lignes de commande
            foreach ($items->data as $it) {
                $unitCents = (int) ($it->price?->unit_amount ?? 0);
                $lineCents = (int) ($it->amount_total ?? ($unitCents * (int)$it->quantity));

                $nomProduit = (string) ($it->description ?? 'Article');
                if ($nomProduit === '') {
                    $nomProduit = 'Article';
                }

                $ac = new ArticleCommande();
                $ac->setCommande($commande);
                $ac->setNomProduit($nomProduit);
                $ac->setQuantite((int) ($it->quantity ?? 1));
                $ac->setPrix(number_format($unitCents / 100, 2, '.', ''));
                $ac->setSousTotal(number_format($lineCents / 100, 2, '.', ''));
                $this->em->persist($ac);
            }

            // Décrémentation du stock
            $cartIdsCsv = (string)($session->metadata->cart_ids ?? '');
            $cartIds    = array_values(array_filter(array_map('trim', explode(',', $cartIdsCsv)), 'strlen'));
            $shipLineName = (string)($session->metadata->ship_name ?? 'Livraison');

            $idxProduit = 0;
            foreach ($items->data as $it) {
                $desc = (string)($it->description ?? '');
                $qty  = (int)($it->quantity ?? 1);

                // ignorer la ligne livraison
                if ($desc === $shipLineName) {
                    continue;
                }

                // 1) Essayer via metadata.product_id (fiable)
                $productIdFromStripe = null;
                try {
                    // $it->price->product est une id produit stripe → on peut récupérer les metadata du product
                    // mais Stripe renvoie aussi "price.product" en string; on n'appelle pas l’API supplémentaire ici.
                    // Par contre, "allLineItems" inclut "price" et "price.product" (string),
                    // pas forcément product metadata. On se base sur price.product_data.metadata si présent.
                    $productIdFromStripe = $it->price?->product_data?->metadata?->product_id ?? null;
                } catch (\Throwable $e) {
                    $productIdFromStripe = null;
                }

                $produit = null;
                if ($productIdFromStripe && ctype_digit((string)$productIdFromStripe)) {
                    $produit = $this->produitRepository->find((int)$productIdFromStripe);
                }

                // 2) Fallback: aligner sur l’ordre du panier
                if (!$produit) {
                    $prodId = isset($cartIds[$idxProduit]) ? (int)$cartIds[$idxProduit] : null;
                    $idxProduit++;
                    if ($prodId) {
                        $produit = $this->produitRepository->find($prodId);
                    }
                }

                if (!$produit) {
                    $this->logger->warning('Webhook: produit introuvable pour décrémentation', [
                        'desc' => $desc,
                        'idx'  => $idxProduit - 1,
                    ]);
                    continue;
                }

                if (method_exists($produit, 'decrementStock')) {
                    $produit->decrementStock($qty);
                } else {
                    $current = (int)($produit->getQuantiteRestante() ?? 0);
                    $produit->setQuantiteRestante(max(0, $current - $qty));
                }
                $this->em->persist($produit);
            }

            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Webhook: échec persistance commande/stock', [
                'session_id' => $session->id ?? null,
                'error'      => $e->getMessage(),
            ]);
            return new Response('OK', 200);
        }

        // 7) Emails (non bloquant)
        try {
            $adminEmail = (string) $this->params->get('app.admin_email');
            if (!$adminEmail) {
                throw new \RuntimeException('Paramètre app.admin_email manquant');
            }

            // Admin
            $emailAdmin = (new TemplatedEmail())
                ->from(new Address('no-reply@votresite.fr', 'InnovShop'))
                ->to($adminEmail)
                ->subject('Nouvelle commande #' . $commande->getReference())
                ->htmlTemplate('emails/order_new.html.twig')
                ->context([
                    'order'    => $commande,
                    'shipMeta' => $shipMeta,
                ]);
            $this->mailer->send($emailAdmin);

            // Client
            $customerEmail = $customerEmail ?? null;
            if ($customerEmail) {
                $displayName = trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? '')) ?: null;

                $emailClient = (new TemplatedEmail())
                    ->from(new Address('no-reply@votresite.fr', 'InnovShop'))
                    ->to(new Address($customerEmail, $displayName))
                    ->subject('Confirmation de votre commande #' . $commande->getReference())
                    ->htmlTemplate('emails/order_confirmation.html.twig')
                    ->context([
                        'order'    => $commande,
                        'shipMeta' => $shipMeta,
                    ]);
                $this->mailer->send($emailClient);
            } else {
                $this->logger->warning('Webhook: email client indisponible, confirmation non envoyée', [
                    'order_id' => $commande->getId(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Webhook: échec envoi email', [
                'order_id' => $commande->getId(),
                'error'    => $e->getMessage(),
            ]);
        }

        return new Response('OK', 200);
    }

    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $request->getSession()->remove('panier');
        $request->getSession()->remove('shipping');
        $request->getSession()->remove('shipping_amount_cents');
        $request->getSession()->remove('shipping_name');
        $request->getSession()->remove('shipping_min_days');
        $request->getSession()->remove('shipping_max_days');
        $request->getSession()->remove('shipping_address_id');

        return $this->render('stripe/success.html.twig');
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }
}
