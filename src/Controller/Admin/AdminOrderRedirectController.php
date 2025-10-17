<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AdminOrderRedirectController extends AbstractController
{
    #[Route('/admin/commande/{id}', name: 'admin_order_pretty_show', requirements: ['id' => '\d+'])]
    public function __invoke(int $id): RedirectResponse
    {
        return $this->redirectToRoute('admin', [
            'crudAction'           => 'detail',
            'crudControllerFqcn'   => \App\Controller\Admin\CommandeCrudController::class,
            'entityId'             => $id,
        ]);
    }
}
