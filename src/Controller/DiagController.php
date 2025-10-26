<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DiagController
{
    #[Route('/_where', name: 'diag_where', methods: ['GET'])]
    public function __invoke(): Response
    {
        $lines = [
            'APP_ENV=' . (getenv('APP_ENV') ?: 'null'),
            'APP_DEBUG=' . (getenv('APP_DEBUG') ?: 'null'),
            'kernel.project_dir=' . (\dirname(__DIR__, 2)),
            'kernel.cache_dir=' . (\dirname(__DIR__, 2) . '/var/cache'),
            'kernel.log_dir=' . (\dirname(__DIR__, 2) . '/var/log'),
            'php_sapi=' . php_sapi_name(),
            'user=' . get_current_user(),
            '__DIR__=' . __DIR__,
        ];
        return new Response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}
