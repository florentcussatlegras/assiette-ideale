<?php

namespace App\Mail;
class NewsletterManager implements EmailFormatterAwareInterface
{
    private $enabledFormatters;

    public function setEnabledFormatters(array $enabledFormatters): void
    {
        $this->enabledFormatters = $enabledFormatters;
    }
}