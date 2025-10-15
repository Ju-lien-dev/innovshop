<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/a-propos')]
class AProposController extends AbstractController
{
    #[Route('/', name: 'a_propos')]
    public function index(): Response
    {
        return $this->render('a_propos/index.html.twig');
    }

    #[Route('/plan-du-site', name: 'plan_site')]
    public function planDuSite(): Response
    {
        return $this->render('a_propos/plan_site.html.twig');
    }
}
