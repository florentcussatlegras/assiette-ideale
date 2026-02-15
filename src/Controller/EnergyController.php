<?php

namespace App\Controller;

use App\Entity\Gender;
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

    #[Route('/explanation', name: 'app_energy_explanation', methods: ['GET'])]
    public function explanation(EnergyHandler $energyHandler, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        $coeffPI = $user->getGender()->getAlias() === Gender::MALE ? 110 : 100;

        $perfectWeight = 22 * ($user->getHeight()/100) * ($user->getHeight()/100);

        $weightForEnergy = ($user->getWeight() > $perfectWeight) ? $user->getWeight() : $perfectWeight;

        $ageCoeff = $user->getAgeRange()->getCoeffEnergy(); 

        $physicalActivityCoeff = $user->getPhysicalActivity();

        $energyJ = $coeffPI * $ageCoeff * $weightForEnergy * $physicalActivityCoeff;
        $energyKcal = $energyJ * EnergyHandler::MULTIPLICATOR_CONVERT_KJ_IN_KCAL;

        // 7️⃣ Récupération des détails pour le template
        $workingType = $user->getWorkingType();
        $sportingTime = $user->getSportingTime();

        return $this->render('energy/explanation.html.twig', [
            'user' => $user,
            'coeffPI' => $coeffPI,
            'perfectWeight' => round($perfectWeight, 1),
            'weightForEnergy' => round($weightForEnergy, 1),
            'ageCoeff' => $ageCoeff,
            'physicalActivityCoeff' => $physicalActivityCoeff,
            'energyKcal' => round($energyKcal, 0),
            'energyJ' => round($energyJ, 0),
            'workingType' => $workingType,
            'sportingTime' => $sportingTime,
        ]);
    }
}
