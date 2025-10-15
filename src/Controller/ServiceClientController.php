<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/service-client')]
class ServiceClientController extends AbstractController
{
    #[Route('/contact', name: 'service_contact')]
    public function contact(): Response
    {
        return $this->render('service_client/contact.html.twig');
    }

    #[Route('/livraison-paiements', name: 'service_livraison_paiements')]
    public function livraisonPaiements(): Response
    {
        return $this->render('service_client/livraison_paiements.html.twig');
    }

    #[Route('/retours-retractation', name: 'service_retours_retractation')]
    public function retoursRetractation(): Response
    {
        return $this->render('service_client/retours_retractation.html.twig');
    }
}
