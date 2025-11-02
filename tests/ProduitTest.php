<?php

namespace App\Tests;

use App\Entity\Produit;
use PHPUnit\Framework\TestCase;

class ProduitTest extends TestCase
{
    public function testDecrementStock()
    {
        $produit = new Produit();
        $produit->setQuantiteRestante(10);

        $produit->decrementStock(3);

        $this->assertEquals(7, $produit->getQuantiteRestante(), 'Le stock doit être décrémenté de 3');
    }

    public function testDecrementStockCannotBeNegative()
    {
        $produit = new Produit();
        $produit->setQuantiteRestante(2);

        $produit->decrementStock(5);

        $this->assertEquals(0, $produit->getQuantiteRestante(), 'Le stock ne doit jamais être négatif');
    }
}
