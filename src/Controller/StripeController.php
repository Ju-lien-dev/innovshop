<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArticleCommande;
use App\Entity\Commande;
use App\Entity\User;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;
use Stripe\Webhook as StripeWebhook;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


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

    # ------------------------------------------------------------
    # CHECKOUT
    # ------------------------------------------------------------
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/create-checkout-session', name: 'app_create_checkout_session', methods: ['POST'])]
    public function createCheckoutSession(Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        $session = $request->getSession();

        /** @var array{
         *   id?: int[],
         *   quantite?: int[],
         *   nom_produit?: string[]
         * } $cart
         */
        $cart = (array) $session->get('panier', []);
        if (empty($cart['id']) || !\is_array($cart['id'])) {
            return $this->redirectToRoute('app_cart');
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // 1) Articles
        $lineItems = $this->buildProductLineItems(
            ids: $cart['id'],
            quantities: (array)($cart['quantite'] ?? []),
            fallbackNames: (array)($cart['nom_produit'] ?? [])
        );

        if ($lineItems === []) {
            $this->addFlash('error', 'Aucun article valide dans le panier.');
            return $this->redirectToRoute('app_cart');
        }

        // 2) Livraison (optionnelle)
        $shippingLine = $this->buildShippingLineItem(
            amountCents: (int) $session->get('shipping_amount_cents', 0),
            name: (string) $session->get('shipping_name', 'Livraison')
        );

        if ($shippingLine) {
            $lineItems[] = $shippingLine;
        }

        // 3) Métadonnées expédition (affichage/confirmation)
        /** @var array{
         *   full_name?: string, address?: string, zip?: string, city?: string, country?: string, notes?: string
         * } $shippingData
         */
        $shippingData = (array) $session->get('shipping', []);
        $checkoutParams = $this->buildCheckoutParams(
            userId: (string) $user->getId(),
            userEmail: (string) ($user->getEmail() ?? ''),
            lineItems: $lineItems,
            successUrl: $urlGenerator->generate('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            cancelUrl: $urlGenerator->generate('app_checkout_cancel',  [], UrlGeneratorInterface::ABSOLUTE_URL),
            cartIds: array_map('strval', (array) $cart['id']),
            shippingMeta: [
                'ship_name'  => (string) ($shippingData['full_name'] ?? ''),
                'ship_addr'  => (string) ($shippingData['address']   ?? ''),
                'ship_zip'   => (string) ($shippingData['zip']       ?? ''),
                'ship_city'  => (string) ($shippingData['city']      ?? ''),
                'ship_ctry'  => (string) ($shippingData['country']   ?? 'FR'),
                'ship_notes' => (string) ($shippingData['notes']     ?? ''),
            ]
        );

        Stripe::setApiKey($this->stripeSecret);
        $checkoutSession = StripeCheckoutSession::create($checkoutParams);

        return new RedirectResponse($checkoutSession->url, 303);
    }

    # ------------------------------------------------------------
    # WEBHOOK
    # ------------------------------------------------------------
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $payload   = (string) $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $secret    = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        try {
            if ($secret) {
                $event   = StripeWebhook::constructEvent($payload, $sigHeader, $secret);
                if ($event->type !== 'checkout.session.completed') {
                    return new Response('OK', 200);
                }
                /** @var \Stripe\Checkout\Session $session */
                $session = $event->data->object;
            } else {
                $this->logger->warning('Webhook sans STRIPE_WEBHOOK_SECRET (DEV)');
                $data = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
                if (($data->type ?? null) !== 'checkout.session.completed') {
                    return new Response('OK', 200);
                }
                /** @var \Stripe\Checkout\Session $session */
                $session = $data->data->object;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook invalide', ['error' => $e->getMessage()]);
            return new Response('Invalid', 400);
        }

        $this->logger->info('Webhook: checkout.session.completed reçu', ['session_id' => $session->id ?? null]);

        Stripe::setApiKey($this->stripeSecret);
        try {
            /** @var \Stripe\Collection<\Stripe\LineItem> $items */
            $items = StripeCheckoutSession::allLineItems($session->id, ['limit' => 100]);
        } catch (\Throwable $e) {
            $this->logger->error('Webhook: impossible de récupérer les line_items', [
                'session_id' => $session->id ?? null,
                'error'      => $e->getMessage(),
            ]);
            return new Response('OK', 200);
        }

        $totalEuros = isset($session->amount_total)
            ? number_format(((int) $session->amount_total) / 100, 2, '.', '')
            : '0.00';

        $userIdMeta = (string) ($session->metadata->user_id ?? '');
        if ($userIdMeta === '' || !ctype_digit($userIdMeta)) {
            $this->logger->error('Webhook: user_id manquant ou invalide');
            return new Response('OK', 200);
        }

        $user = $this->userRepo->find((int) $userIdMeta);
        if (!$user) {
            $this->logger->error('Webhook: utilisateur introuvable', ['user_id_meta' => $userIdMeta]);
            return new Response('OK', 200);
        }

        $customerEmail = $session->customer_details->email
            ?? $session->customer_email
            ?? $user->getEmail();

        $shipMeta = [
            'ship_name' => $session->metadata->ship_name ?? null,
            'ship_addr' => $session->metadata->ship_addr ?? null,
            'ship_zip'  => $session->metadata->ship_zip  ?? null,
            'ship_city' => $session->metadata->ship_city ?? null,
            'ship_ctry' => $session->metadata->ship_ctry ?? null,
        ];
        $notes = $session->metadata->ship_notes ?? null;

        // Persistance + stock
        try {
            $commande = $this->persistOrder($user, $totalEuros, $shipMeta);

            $this->persistOrderLinesAndDecreaseStock(
                items: $items,
                commande: $commande,
                cartIdsCsv: (string) ($session->metadata->cart_ids ?? ''),
                shippingLineName: (string) ($session->metadata->ship_name ?? 'Livraison')
            );

            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Webhook: échec persistance commande/stock', [
                'session_id' => $session->id ?? null,
                'error'      => $e->getMessage(),
            ]);
            return new Response('OK', 200);
        }

        // Emails (non bloquant)
        $this->sendOrderEmailsSafely(
            commande: $commande,
            shipMeta: $shipMeta,
            customerEmail: (string) ($customerEmail ?? '')
        );

        return new Response('OK', 200);
    }

    # ------------------------------------------------------------
    # SUCCESS / CANCEL
    # ------------------------------------------------------------
    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $s = $request->getSession();
        foreach (
            [
                'panier',
                'shipping',
                'shipping_amount_cents',
                'shipping_name',
                'shipping_min_days',
                'shipping_max_days',
                'shipping_address_id',
            ] as $key
        ) {
            $s->remove($key);
        }

        return $this->render('stripe/success.html.twig');
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('stripe/cancel.html.twig');
    }

    # ------------------------------------------------------------
    # Helpers
    # ------------------------------------------------------------

    /**
     * @param int[]         $ids
     * @param int[]         $quantities
     * @param string[]      $fallbackNames
     * @return array<int, array<string, mixed>>
     */
    private function buildProductLineItems(array $ids, array $quantities, array $fallbackNames): array
    {
        $lineItems = [];

        foreach ($ids as $idx => $productId) {
            $produit = $this->produitRepository->find((int) $productId);
            if (!$produit) {
                continue;
            }

            $qtyAsked  = max(1, (int) ($quantities[$idx] ?? 1));
            $available = max(0, (int) ($produit->getQuantiteRestante() ?? 0));
            if ($available <= 0) {
                $this->addFlash('error', sprintf('"%s" est en rupture de stock.', $produit->getTitre() ?? 'Produit'));
                continue;
            }
            $qty = min($qtyAsked, $available);

            $unitAmount = (int) $produit->getPrix(); // prix déjà en centimes
            $name       = $produit->getTitre() ?: ($fallbackNames[$idx] ?? 'Produit');

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'     => (string) $name,
                        'metadata' => ['product_id' => (string) $productId],
                    ],
                    'unit_amount'  => $unitAmount,
                ],
                'quantity' => $qty,
            ];
        }

        return $lineItems;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildShippingLineItem(int $amountCents, string $name): ?array
    {
        if ($amountCents <= 0) {
            return null;
        }

        return [
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => ['name' => $name],
                'unit_amount'  => $amountCents,
            ],
            'quantity' => 1,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $lineItems
     * @param array<string, string>            $shippingMeta
     * @param string[]                         $cartIds
     * @return array<string, mixed>
     */
    private function buildCheckoutParams(
        string $userId,
        string $userEmail,
        array $lineItems,
        string $successUrl,
        string $cancelUrl,
        array $cartIds,
        array $shippingMeta
    ): array {
        $params = [
            'mode'                => 'payment',
            'line_items'          => $lineItems,
            'success_url'         => $successUrl,
            'cancel_url'          => $cancelUrl,
            'client_reference_id' => $userId,
            'metadata'            => array_merge($shippingMeta, [
                'user_id'  => $userId,
                'cart_ids' => implode(',', $cartIds),
            ]),
        ];

        if ($userEmail !== '') {
            $params['customer_email'] = $userEmail;
        }

        return $params;
    }

    /**
     * @param array<string, ?string> $shipMeta
     */
    private function persistOrder(User $user, string $totalEuros, array $shipMeta): Commande
    {
        $commande = new Commande();
        $commande->setReference(date('YmdHis') . '-' . substr((string) mt_rand(), 0, 4));
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

        return $commande;
    }

    /**
     * @param \Stripe\Collection<\Stripe\LineItem> $items
     */
    private function persistOrderLinesAndDecreaseStock(
        \Stripe\Collection $items,
        Commande $commande,
        string $cartIdsCsv,
        string $shippingLineName
    ): void {
        // Lignes
        foreach ($items->data as $it) {
            $unitCents = (int) ($it->price?->unit_amount ?? 0);
            $lineCents = (int) ($it->amount_total ?? ($unitCents * (int) $it->quantity));

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

        // Stock
        $cartIds = array_values(array_filter(array_map('trim', explode(',', $cartIdsCsv)), 'strlen'));
        $idxProduit = 0;

        foreach ($items->data as $it) {
            $desc = (string) ($it->description ?? '');
            $qty  = (int) ($it->quantity ?? 1);

            if ($desc === $shippingLineName) {
                continue; // ignorer la ligne livraison
            }

            // 1) Essayer via metadata.product_id (si présent)
            $productIdFromStripe = null;
            try {
                /** @phpstan-ignore-next-line (Stripe types dynamiques) */
                $productIdFromStripe = $it->price?->product_data?->metadata?->product_id ?? null;
            } catch (\Throwable) {
                $productIdFromStripe = null;
            }

            $produit = null;
            if ($productIdFromStripe && ctype_digit((string) $productIdFromStripe)) {
                $produit = $this->produitRepository->find((int) $productIdFromStripe);
            }

            // 2) Fallback: alignement avec l’ordre du panier
            if (!$produit) {
                $prodId = isset($cartIds[$idxProduit]) ? (int) $cartIds[$idxProduit] : null;
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
                $current = (int) ($produit->getQuantiteRestante() ?? 0);
                $produit->setQuantiteRestante(max(0, $current - $qty));
            }
            $this->em->persist($produit);
        }
    }

    /**
     * @param array<string, ?string> $shipMeta
     */
    private function sendOrderEmailsSafely(Commande $commande, array $shipMeta, string $customerEmail): void
    {
        try {
            $adminEmail = (string) $this->params->get('app.admin_email');
            if ($adminEmail === '') {
                throw new \RuntimeException('Paramètre app.admin_email manquant');
            }
            $from = new Address(
                (string) $this->params->get('app.mail_from'),
                (string) $this->params->get('app.mail_from_name')
            );


            // Admin
            $emailAdmin = (new TemplatedEmail())
                ->from($from)
                ->to($adminEmail)
                ->subject('Nouvelle commande #' . $commande->getReference())
                ->htmlTemplate('emails/order_new.html.twig')
                ->context([
                    'order'    => $commande,
                    'shipMeta' => $shipMeta,
                ]);
            $this->mailer->send($emailAdmin);

            // Client
            if ($customerEmail !== '') {
                $user = $commande->getUser();
                $displayName = $user
                    ? trim(($user->getPrenom() ?? '') . ' ' . ($user->getNom() ?? ''))
                    : '';

                if ($displayName === '') {
                    $displayName = 'Client InnovShop';
                }

                $emailClient = (new TemplatedEmail())
                    ->from($from)
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
    }
}
