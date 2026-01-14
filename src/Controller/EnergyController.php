<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\Food;
use App\Service\FoodUtil;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/energy')]
class EnergyController extends AbstractController
{
    #[Route('/calculate', name: 'app_energy_estimate')]
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
