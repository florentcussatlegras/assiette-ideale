<?php

namespace App\Mail;

interface EmailFormatterAwareInterface
{
    public function setEnabledFormatters(array $enabledFormatters): void;
}