<?php

namespace App\Service;

use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Psr\Container\ContainerInterface;
use App\Service\EnergyHandler;

class CommandBus implements ServiceSubscriberInterface
{
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'App\Service\EnergyHandler' => EnergyHandler::class
        ];
    }

    public function handle(Command $command)
    {
        $commandClass = get_class($command);

        if($this->locator->has($commandClass)) {
            $handler = $this->locator->get($commandClass);

            return $handler->handle($command);
        }
    }
}