<?php

namespace App\Controller;

use App\Form\ShippingFormType;
use App\Entity\ShippingMethod;
use App\Repository\ShippingMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
use App\Entity\Adresse;
use App\Repository\AdresseRepository;
use Doctrine\ORM\EntityManagerInterface;


#[IsGranted('ROLE_USER')]
final class CheckoutController extends AbstractController
{
    public function __construct(
        private ShippingMethodRepository $shippingRepo,
        private AdresseRepository $adresseRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/checkout/review', name: 'app_checkout_review', methods: ['GET', 'POST'])]
    public function review(Request $request): Response
    {
        $session = $request->getSession();

        // 1) Panier depuis la session
        $cart = $session->get('panier', [
            'id' => [],
            'nom_produit' => [],
            'prix' => [],
            'quantite' => [],
            'image' => [],
        ]);
        if (empty($cart['id'])) {
            return $this->redirectToRoute('app_cart');
        }

        // 2) Préremplir le formulaire
        $defaults = $session->get('shipping', [
            'full_name' => '',
            'address'   => '',
            'zip'       => '',
            'city'      => '',
            'country'   => 'FR',
            'notes'     => '',
            'accept'    => false,
            // 'delivery' sera mis plus bas (objet)
        ]);

        // Nom complet de l'utilisateur connecté
        $user = $this->getUser();
        $fullForHeader = ''; // pour le template
        if ($user instanceof User) {
            $prenom = trim((string) $user->getPrenom());
            $nom    = trim((string) $user->getNom());
            $full   = trim($prenom . ' ' . $nom);
            $fallback = method_exists($user, 'getUserIdentifier') ? (string) $user->getUserIdentifier() : 'Client';

            // Préremplir le champ du formulaire si vide dans la session
            if (empty($defaults['full_name'])) {
                $defaults['full_name'] = $full !== '' ? $full : $fallback;
            }

            // Valeur à afficher dans l'en-tête (si tu l’utilises dans review.html.twig)
            $fullForHeader = $full !== '' ? $full : $fallback;
        }

        // Si une livraison a déjà été choisie (ID en session), on repasse l'objet au form
        if (!empty($defaults['delivery_id']) && empty($defaults['delivery'])) {
            $chosen = $this->shippingRepo->find((int) $defaults['delivery_id']);
            if ($chosen instanceof ShippingMethod) {
                $defaults['delivery'] = $chosen;
            }
        }

        // À défaut, présélectionner la moins chère active
        if (empty($defaults['delivery'])) {
            $cheapest = $this->shippingRepo->findOneBy(['isActive' => true], ['amountCents' => 'ASC']);
            if ($cheapest instanceof ShippingMethod) {
                $defaults['delivery'] = $cheapest;
            }
        }
        if ($user instanceof User && empty($defaults['address'])) {
            $last = $this->adresseRepo->findOneBy(
                ['user' => $user, 'type' => 'livraison'],
                ['id' => 'DESC']
            );
            if ($last instanceof Adresse) {
                // full_name déjà préparé plus haut (prenom/nom). Si vide, on prend l'adresse.nom
                if (empty($defaults['full_name'])) {
                    $defaults['full_name'] = trim((string) $last->getNom());
                }
                $defaults['address'] = (string) $last->getAdresse();
                $defaults['zip']     = (string) $last->getCodePostal();
                $defaults['city']    = (string) $last->getVille();
                // Ton entité Adresse n'a pas "country" → on garde FR dans le formulaire
                // $defaults['country'] = 'FR';
            }
        }

        $form = $this->createForm(ShippingFormType::class, $defaults);
        $form->handleRequest($request);

        // 3) Soumission : sauver en session puis aller sur "ready"
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{full_name:string,address:string,zip:string,city:string,country:string,delivery:ShippingMethod,notes:?string,accept:bool} $data */
            $data = $form->getData();

            // Sauvegarde des infos expédition en session (toujours)
            $session->set('shipping', [
                'full_name'   => $data['full_name'],
                'address'     => $data['address'],
                'zip'         => $data['zip'],
                'city'        => $data['city'],
                'country'     => $data['country'],
                'delivery_id' => $data['delivery']->getId(),
                'notes'       => $data['notes'],
                'accept'      => $data['accept'],
            ]);
            $session->set('shipping_amount_cents', $data['delivery']->getAmountCents());
            $session->set('shipping_name',        $data['delivery']->getName());
            $session->set('shipping_min_days',    $data['delivery']->getMinDays());
            $session->set('shipping_max_days',    $data['delivery']->getMaxDays());

            // --- NOUVEAU : Respecter sélection OU enregistrement optionnel ---
            $selectedId = $request->request->get('saved_address_id');        // radio des adresses existantes
            $saveNew    = (bool) $request->request->get('save_new_address'); // checkbox “Enregistrer cette adresse”

            if ($user instanceof User && $selectedId && ctype_digit((string)$selectedId)) {
                // L’utilisateur a choisi une adresse existante : on l’utilise SANS la modifier
                $addr = $this->adresseRepo->find((int) $selectedId);

                if ($addr instanceof Adresse && $addr->getUser()?->getId() === $user->getId()) {
                    // Priorité à l’adresse choisie : on remplace les champs d’expédition en session
                    $session->set('shipping', array_merge($session->get('shipping', []), [
                        'full_name' => (string) $addr->getNom(),
                        'address'   => (string) $addr->getAdresse(),
                        'zip'       => (string) $addr->getCodePostal(),
                        'city'      => (string) $addr->getVille(),
                    ]));
                    $session->set('shipping_address_id', $addr->getId());
                }
                // Pas de persistance ici → on ne touche pas à la BDD
            } elseif ($user instanceof User && $saveNew) {
                // Aucune adresse existante sélectionnée, et l’utilisateur a demandé d’enregistrer la saisie
                $addr = (new Adresse())
                    ->setUser($user)
                    ->setType('livraison')
                    ->setNom($data['full_name'])
                    ->setAdresse($data['address'])
                    ->setCodePostal($data['zip'])
                    ->setVille($data['city']);

                $this->em->persist($addr);
                $this->em->flush();

                $session->set('shipping_address_id', $addr->getId());
            } else {
                // Ni adresse sélectionnée, ni enregistrement demandé → rien à persister
                $session->remove('shipping_address_id');
            }

            return $this->redirectToRoute('app_checkout_ready', ['auto' => 1], 303);
        }



        // 4) Pour l'affichage dans review : livraison sélectionnée et son prix
        $shippingData   = $session->get('shipping', []);
        $shippingMethod = null;
        $shippingAmount = 0;

        if (!empty($shippingData['delivery_id'])) {
            $shippingMethod = $this->shippingRepo->find((int) $shippingData['delivery_id']);
            if ($shippingMethod instanceof ShippingMethod) {
                $shippingAmount = $shippingMethod->getAmountCents(); // centimes
            }
        } elseif (!empty($defaults['delivery']) && $defaults['delivery'] instanceof ShippingMethod) {
            $shippingMethod = $defaults['delivery'];
            $shippingAmount = $shippingMethod->getAmountCents();
        }

        $savedAddresses = [];
        if ($user instanceof User) {
            $savedAddresses = $this->adresseRepo->findBy(
                ['user' => $user],
                ['id' => 'DESC']
            );
        }

        return $this->render('checkout/review.html.twig', [
            'fullName'        => $fullForHeader,
            'items'           => $cart,
            'form'            => $form->createView(),
            'shipping'        => $shippingMethod,
            'shipping_amount' => $shippingAmount,
            'savedAddresses'  => $savedAddresses,
        ]);
    }

    // Petite page avec bouton POST vers /create-checkout-session
    #[Route('/checkout/ready', name: 'app_checkout_ready', methods: ['GET'])]
    public function ready(Request $request): Response
    {
        $session = $request->getSession();

        $cart = $session->get('panier', []);
        if (empty($cart['id'])) {
            return $this->redirectToRoute('app_cart');
        }

        // Charger la livraison choisie pour afficher le total final
        $shippingData   = $session->get('shipping', []);
        $shippingMethod = null;
        $shippingAmount = 0;

        if (!empty($shippingData['delivery_id'])) {
            $shippingMethod = $this->shippingRepo->find((int) $shippingData['delivery_id']);
            if ($shippingMethod instanceof ShippingMethod) {
                $shippingAmount = $shippingMethod->getAmountCents();
            }
        }

        return $this->render('checkout/ready.html.twig', [

            'items'           => $cart,
            'shipping'        => $shippingMethod,
            'shipping_amount' => $shippingAmount,
        ]);
    }
}
