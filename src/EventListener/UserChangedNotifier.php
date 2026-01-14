<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UserChangedNotifier
{
    private $energyHandler;
    private $manager;

    public function __construct(EntityManagerInterface $manager, EnergyHandler $energyHandler)
    {
        $this->energyHandler = $energyHandler;
        $this->manager = $manager;
    }

    public function postUpdate(User $user, LifecycleEventArgs $event): void
    {
        dd('toto');
        $energyCalculate = $this->energyHandler->evaluateEnergy($user);
        $user->setEnergyCalculate($energyCalculate);

        // Si l'utilisateur souhaite que le programme calcule son energie on le refait à 
        // chaque update du user
        if($user->getAutomaticCalculateEnergy()) {
            $user->setEnergy($energyCalculate);
        }

        $this->manager->persist($user);
        $this->manager->flush();
    }
}