<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CartExtension extends AbstractExtension implements GlobalsInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getGlobals(): array
    {
        $session = $this->requestStack->getSession();

        $cart = $session?->get('panier', [
            'id'          => [],
            'nom_produit' => [],
            'prix'        => [],
            'quantite'    => [],
        ]) ?? ['id' => [], 'nom_produit' => [], 'prix' => [], 'quantite' => []];

        return [
            'cart_count' => array_sum($cart['quantite']),
        ];
    }
}
