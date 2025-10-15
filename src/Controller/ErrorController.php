<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsController]
class ErrorController extends AbstractController
{
    public function __invoke(\Throwable $exception): Response
    {
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        if ($statusCode === 403) {
            return $this->render('bundles/TwigBundle/Exception/error403.html.twig', [], new Response('', 403));
        }

        if ($statusCode === 404) {
            return $this->render('bundles/TwigBundle/Exception/error404.html.twig', [], new Response('', 404));
        }

        return $this->render('bundles/TwigBundle/Exception/error.html.twig', [
            'status_code' => $statusCode,
        ], new Response('', $statusCode));
    }
}
