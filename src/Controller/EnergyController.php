<?php

namespace App\Controller;

use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/energy')]
class EnergyController extends AbstractController
{
    #[Route('/calculate', name: 'app_energy_estimate', methods: ['GET'])]
    public function calculateEnergy(EnergyHandler $energyHandler, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        
        $user->setAutomaticCalculateEnergy(true);
        $energyHandler->evaluateEnergy($user);
        $user->setEnergy($energyHandler->evaluateEnergy($user));
        
        $manager->flush();
        
        $this->addFlash('success', 'Votre energie quotidienne a bien été mise à jour.');

        return $this->redirectToRoute('app_profile_index');
    }
}
