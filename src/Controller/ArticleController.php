<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(ArticleRepository $repo): Response
    {
        $articleList = $repo->findall();

        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
            'articleList' => $articleList,
        ]);
    }
}
