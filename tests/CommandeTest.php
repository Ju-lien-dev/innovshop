<?php

namespace App\Tests;

use App\Entity\Commande;
use App\Entity\ArticleCommande;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    public function testTotalCommandeEstCorrect(): void
    {
        // Article 1 : 2 x 10.00 = 20.00
        $a1 = new ArticleCommande();
        $a1->setPrix('10.00');       // DECIMAL -> string
        $a1->setQuantite(2);
        $a1->setSousTotal('20.00');  // optionnel pour la cohérence

        // Article 2 : 3 x 5.00 = 15.00
        $a2 = new ArticleCommande();
        $a2->setPrix('5.00');
        $a2->setQuantite(3);
        $a2->setSousTotal('15.00');

        $commande = new Commande();
        // ✅ tes méthodes exactes : addArticle() / getArticles()
        $commande->addArticle($a1);
        $commande->addArticle($a2);

        // Calcule le total en € (float) à partir des strings
        $total = 0.0;
        foreach ($commande->getArticles() as $article) {
            $total += (float)$article->getPrix() * (int)$article->getQuantite();
        }

        $this->assertEqualsWithDelta(35.00, $total, 0.001, 'Le total attendu est 35.00 €');
    }

    public function testSousTotalParLigneEstCorrect(): void
    {
        $a = new ArticleCommande();
        $a->setPrix('12.30');
        $a->setQuantite(3);

        // Simule le calcul du sous-total côté appli (comme tu le fais au webhook)
        $calc = number_format((float)$a->getPrix() * (int)$a->getQuantite(), 2, '.', '');
        $a->setSousTotal($calc);

        $this->assertSame('36.90', $a->getSousTotal(), 'Le sous-total doit être formaté à 2 décimales');
    }
}
