<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\CategoryRepository;
use App\Repository\ProduitRepository;
use App\Entity\Produit;

final class FrontController extends AbstractController
{
    #[Route('/', name: 'app_front')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findBy([], ['createdAt' => 'DESC']);
        $derniersProduits = $produitRepository->findBy([], ['createdAt' => 'DESC'], 4);

        return $this->render('front/index.html.twig', [
            'produits' => $produits,
            'derniersProduits' => $derniersProduits,
        ]);
    }

    #[Route('/catalogue', name: 'app_catalogue')]
    public function catalogue(
        ProduitRepository $produitRepository,
        CategoryRepository $categoryRepository,
        Request $request
    ): Response {
        $categories = $categoryRepository->findAll();

        $q   = $request->query->get('q');   // recherche par titre
        $min = $request->query->get('min'); // prix min
        $max = $request->query->get('max'); // prix max
        $cat = $request->query->get('cat'); // catégorie

        $queryBuilder = $produitRepository->createQueryBuilder('p');

        if ($q) {
            $queryBuilder->andWhere('p.titre LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($min !== null && $min !== '') {
            $queryBuilder->andWhere('p.prix >= :min')
                ->setParameter('min', (int) round(((float) $min) * 100));
        }

        if ($max !== null && $max !== '') {
            $queryBuilder->andWhere('p.prix <= :max')
                ->setParameter('max', (int) round(((float) $max) * 100));
        }

        if ($cat) {
            $queryBuilder
                ->join('p.categorie', 'c')
                ->andWhere('c.id = :cat')
                ->setParameter('cat', (int) $cat);
        }

        $produits = $queryBuilder->getQuery()->getResult();

        return $this->render('front/catalogue.html.twig', [
            'produits'   => $produits,
            'categories' => $categories,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit_show', requirements: ['id' => '\d+'])]
    public function show(Produit $produit): Response
    {
        return $this->render('front/detailProduit.html.twig', [
            'produit' => $produit,
        ]);
    }
}
