<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

#[AsController]
final class ErrorController extends AbstractController
{
    public function __invoke(\Throwable $exception): Response
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $template = match ($status) {
            403 => 'bundles/TwigBundle/Exception/error403.html.twig',
            404 => 'bundles/TwigBundle/Exception/error404.html.twig',
            default => 'bundles/TwigBundle/Exception/error.html.twig',
        };

        return $this->render($template, ['status_code' => $status], new Response('', $status));
    }
}
