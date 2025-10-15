<?php

namespace App\Controller;

use App\Entity\Commande;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderController extends AbstractController
{
    #[Route('/commande/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        $user = $this->getUser();

        // sécurité : empêcher un client de voir la commande d’un autre
        if ($commande->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/show.html.twig', [
            'order' => $commande,
        ]);
    }
}
