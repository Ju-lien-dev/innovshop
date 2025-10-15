<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;


class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Affiche "12 345.67 €" à partir de 123456 (centimes)
            new TwigFilter('price_eur', [$this, 'formatPrice'], ['is_safe' => ['html']]),
        ];
    }

    public function formatPrice(int|float|null $cents): string
    {
        if ($cents === null) {
            return '—';
        }
        // On accepte int ou float
        $eur = ((float)$cents) / 100;
        // Espace insécable pour thousands
        return str_replace(' ', '&nbsp;', number_format($eur, 2, '.', ' ')) . '&nbsp;€';
    }
}
