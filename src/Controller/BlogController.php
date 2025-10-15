<?php

namespace App\Controller;

use App\Repository\BlogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route('/blog', name: 'app_blog')]
    public function index(BlogRepository $blogRepository): Response
    {

        $articles = $blogRepository->findBy([], ['creeLe' => 'DESC']);
        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/blog/{id}', name: 'blog_detail')]
    public function detail(BlogRepository $blogRepository, int $id): Response
    {
        $article = $blogRepository->find($id);
        return $this->render('blog/detail.html.twig', [
            'article' => $article,
        ]);
    }
}
