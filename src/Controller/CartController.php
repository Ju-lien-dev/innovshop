<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CartController extends AbstractController
{
    #[Route('/panier', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session): Response
    {
        $cart = $session->get('panier', [
            'id'           => [],
            'nom_produit'  => [],
            'prix'         => [],
            'quantite'     => [],
            'image'        => [],
        ]);

        return $this->render('cart/show.html.twig', [
            'items' => $cart,
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_addCart', requirements: ['id' => '\d+'], methods: ['POST', 'GET'])]
    public function addArticleToCart(
        int $id,
        Request $request,
        ProduitRepository $produitRepository
    ): Response {
        $session = $request->getSession();

        $cart = $session->get('panier', [
            'id'           => [],
            'nom_produit'  => [],
            'prix'         => [],
            'quantite'     => [],
            'image'        => [],
        ]);

        $article = $produitRepository->find($id);
        if (!$article) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $stockRestant = (int) $article->getQuantiteRestante();

        if ($stockRestant <= 0) {
            $this->addFlash('danger', sprintf('Le produit "%s" est en rupture de stock.', $article->getTitre()));
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_catalogue'));
        }

        $index = array_search($article->getId(), $cart['id'], true);

        if ($index !== false) {
            $nouvelleQuantite = (int)$cart['quantite'][$index] + 1;

            if ($nouvelleQuantite > $stockRestant) {
                $nouvelleQuantite = $stockRestant;
                $this->addFlash('warning', sprintf(
                    'Stock limité : la quantité de "%s" a été ajustée à %d.',
                    $article->getTitre(),
                    $stockRestant
                ));
            }

            $cart['quantite'][$index] = $nouvelleQuantite;
        } else {
            $quantiteInitiale = min(1, $stockRestant);

            $cart['id'][]          = $article->getId();
            $cart['nom_produit'][] = $article->getTitre();
            $cart['prix'][]        = (float)$article->getPrix();
            $cart['quantite'][]    = $quantiteInitiale;
            $cart['image'][]       = $article->getImage();

            if ($stockRestant < 1) {
                $this->addFlash('warning', sprintf('Le produit "%s" n’a plus de stock disponible.', $article->getTitre()));
            }
        }

        $session->set('panier', $cart);

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('app_catalogue'));
    }

    #[Route('/panier/delete/{id}', name: 'app_cart_delete', methods: ['POST'])]
    public function deleteCartProduct(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('panier', [
            'id'           => [],
            'nom_produit'  => [],
            'prix'         => [],
            'quantite'     => [],
            'image'        => [],
        ]);

        $index = array_search($id, $cart['id'], true);
        if ($index !== false) {
            foreach (['id', 'nom_produit', 'prix', 'quantite', 'image'] as $key) {
                unset($cart[$key][$index]);
                $cart[$key] = array_values($cart[$key]);
            }
            $session->set('panier', $cart);
        }

        return $this->redirectToRoute('app_cart', [], 303);
    }

    #[Route('/panier/vide', name: 'app_cart_clear', methods: ['POST'])]
    public function clearCart(Request $request): Response
    {
        $request->getSession()->remove('panier');
        return $this->redirectToRoute('app_cart', [], 303);
    }

    #[Route('/reset-panier', name: 'reset_panier')]
    public function reset(SessionInterface $session): Response
    {
        $session->remove('panier');
        return new Response('Panier vidé !');
    }

    #[Route('/panier/increment/{id}', name: 'app_cart_increment', methods: ['POST', 'GET'])]
    public function increment(int $id, Request $request, ProduitRepository $produitRepository): Response
    {
        $session = $request->getSession();
        $cart = $session->get('panier', []);
        $index = array_search($id, $cart['id'] ?? [], true);

        if ($index !== false) {
            $produit = $produitRepository->find($id);
            if (!$produit) {
                throw $this->createNotFoundException('Produit introuvable.');
            }

            $stockRestant = (int) $produit->getQuantiteRestante();
            $nouvelleQuantite = (int)$cart['quantite'][$index] + 1;

            if ($nouvelleQuantite > $stockRestant) {
                $nouvelleQuantite = $stockRestant;
                $this->addFlash('warning', sprintf(
                    'Stock limité : "%s" ne peut pas dépasser %d unité%s.',
                    $produit->getTitre(),
                    $stockRestant,
                    $stockRestant > 1 ? 's' : ''
                ));
            }

            $cart['quantite'][$index] = $nouvelleQuantite;
            $session->set('panier', $cart);
        }

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/decrement/{id}', name: 'app_cart_decrement', methods: ['POST', 'GET'])]
    public function decrement(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('panier', []);
        $index = array_search($id, $cart['id'] ?? [], true);

        if ($index !== false) {
            $cart['quantite'][$index] = max(0, (int)$cart['quantite'][$index] - 1);

            if ($cart['quantite'][$index] === 0) {
                foreach (['id', 'nom_produit', 'prix', 'quantite', 'image'] as $key) {
                    unset($cart[$key][$index]);
                    $cart[$key] = array_values($cart[$key]);
                }
            }

            $session->set('panier', $cart);
        }

        return $this->redirectToRoute('app_cart');
    }
}
