<?php
// src/Service/MessageGenerator.php
namespace App\Service;

use Psr\Log\LoggerInterface;

class MessageGenerator
{
    private $logger;

    public function withLogger(LoggerInterface $logger): self
    {
        $new = clone $this;
        $new->logger = $logger;

        return $new;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}