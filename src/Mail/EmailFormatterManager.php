<?php

namespace App\Mail;

class EmailFormatterManager
{
    public function getEnabledFormatters(): array
    {
        $enabledFormatters = ['formatter1', 'formatter2'];

        return $enabledFormatters;
    }
}