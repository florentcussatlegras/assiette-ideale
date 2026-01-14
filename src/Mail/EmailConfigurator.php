<?php

namespace App\Mail;

class EmailConfigurator
{
    private $formatterManager;

    public function __construct(EmailFormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }

    public function __invoke(EmailFormatterAwareInterface $manager)
    {
        $manager->setEnabledFormatters(
            $this->formatterManager->getEnabledFormatters()
        );
    }
}