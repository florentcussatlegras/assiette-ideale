<?php

namespace App\Mail;

class GreetCardManager implements EmailFormatterAwareInterface
{
    private $enabledFormatters;
    
    public function setEnabledFormatters(array $enabledFormatters): void
    {
        $this->enabledFormatters = $enabledFormatters;
    }
}