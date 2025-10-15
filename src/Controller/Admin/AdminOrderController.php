<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use App\Form\CommandeStatusType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/orders', name: 'admin_orders_')]
final class AdminOrderController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CommandeRepository $repo, Request $request): Response
    {
        // ultra simple (tri par date desc). Si tu as Pagerfanta, on pourra ajouter la pagination.
        $orders = $repo->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        // mini-formulaire inline pour changer le statut
        $form = $this->createForm(CommandeStatusType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Statut mis à jour.');
            return $this->redirectToRoute('admin_orders_show', ['id' => $commande->getId()]);
        }

        return $this->render('admin/orders/show.html.twig', [
            'order' => $commande,
            'form'  => $form->createView(),
        ]);
    }

    // Option: endpoint “changement rapide” si tu veux faire un bouton en liste
    #[Route('/{id}/status/{status}', name: 'quick_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function quickStatus(Commande $commande, string $status, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->isCsrfTokenValid('order_quick_status_' . $commande->getId(), $request->request->get('_token'))
            || throw $this->createAccessDeniedException();

        $allowed = ['paid', 'processing', 'shipped', 'delivered', 'refunded', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw $this->createNotFoundException();
        }

        $commande->setStatus($status);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour.');
        return $this->redirectToRoute('admin_orders_index');
    }
}
