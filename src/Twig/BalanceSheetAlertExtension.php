<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BalanceSheetAlertExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatMessageBalanceSheetAlert', [AppRuntime::class, 'getMessageBalanceSheetAlert']),
        ];
    }
}