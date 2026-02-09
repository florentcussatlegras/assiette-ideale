<?php

namespace App\Controller;

use App\Entity\Alert\LevelAlert;
use App\Repository\ImcMessageRepository;
use App\Service\AlertFeature;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/energy-adjustment', name: 'app_energy_adjustment')]
class EnergyAdjustmentController extends AbstractController
{
    #[Route('/message', name: '_message', methods: ['GET'])]
    public function message(AlertFeature $alertFeature, ImcMessageRepository $imcMessageRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');
        
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $showWeightGoalProposal = false;
        $weightGoalMessage = null;

        $imcAlert = $alertFeature->getImcAlert($user->getImc());
        $message = $imcMessageRepository->findByAlertCode($imcAlert->getCode())->getMessage();

        if ($imcAlert->getCode() === LevelAlert::BALANCE_WELL) {
            if (!$user->isWeightGoalActive()) {
                $weightGoalMessage = "Vous avez un apport calorique équilibré";
            } else {
                $weightGoalMessage = "Nous suivons votre apport calorique pour maintenir votre équilibre.";
            }
        } else {
            if (!$user->isWeightGoalActive()) {
                $showWeightGoalProposal = true;
                $weightGoalMessage = "Voulez-vous ajuster vos apports caloriques ?";
            } else {
                // S'il est déjà en réajustement calorique
                $weightGoalMessage = "Vos besoins énergétiques sont réajustés pour favoriser un retour vers un IMC équilibré.";
            }
        }

        return $this->render('energy_adjustment/message.html.twig', [
            'showWeightGoalProposal' => $showWeightGoalProposal,
            'weightGoalMessage' => $weightGoalMessage,
            'isWeightGoalActive' => $user->isWeightGoalActive(),
            'imcAlert' => $imcAlert,
            'message' => $message,
        ]);
    }

    #[Route('/accept', name: '_accept', methods: ['GET', 'POST'])]
    public function accept(EntityManagerInterface $em, AlertFeature $alertFeature, EnergyHandler $energyHandler): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $imcAlert = $alertFeature->getImcAlert($user->getImc());
        $adjustmentPercent = $alertFeature->getCalorieAdjustmentPercent($imcAlert);

        $user->setCalorieAdjustmentPercent($adjustmentPercent);
        $user->setIsWeightGoalActive(true);

        // Forcer le calcul automatique de l'énergie
        $user->setAutomaticCalculateEnergy(true);

        // Recalcul immédiat de l'énergie selon la méthode evaluateEnergy()
        try {
            $energyEstimate = $energyHandler->evaluateEnergy();

            // Appliquer le pourcentage d'ajustement
            // Exemple : adjustmentPercent = 10 -> energyAdjusted = energyEstimate * (1 - 0.10)
            $energyAdjusted = $energyEstimate * (1 + $adjustmentPercent / 100);

            $user->setEnergy($energyAdjusted);
            $user->setEnergyCalculate($energyEstimate); // On garde l'énergie "non ajustée" pour référence

            // Recalculer l'IMC et le poids idéal
            $user->setValueImc();
            $user->setValueIdealWeight();
            $user->setValueIdealImc();
        } catch (\App\Exception\MissingElementForEnergyEstimationException $e) {
            // Optionnel : gérer le cas où il manque des infos
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('info', 'Vos besoins energétiques ont été réévalués afin de corriger votre poids et votre IMC');

        return $this->redirectToRoute('app_dashboard_index');
    }

    #[Route('/explanation', name: '_explanation', methods: ['GET'])]
    public function explication(AlertFeature $alertFeature, ImcMessageRepository $imcMessageRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $imcAlert = $alertFeature->getImcAlert($user->getImc());
        $imcMessage = $imcMessageRepository->findByAlertCode($imcAlert->getCode());

        $height = $user->getHeight() / 100;

        $idealImc = $user->getIdealImc();
        $weightTarget = round($height * $height * $idealImc, 1);

        return $this->render('energy_adjustment/explanation.html.twig', [
            'imcMessage'    => $imcMessage,
            'imcAlert'      => $imcAlert,
            'energyBase'    => $user->getEnergyCalculate(),
            'energyAdjusted'=> $user->getEnergy(),
            'weightTarget'  => $weightTarget,
        ]);
    }

    #[Route('/stop', name: '_stop', methods: ['POST', 'GET'])]
    public function stop(EntityManagerInterface $em, EnergyHandler $energyHandler)
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $user->setIsWeightGoalActive(false);
        $user->setCalorieAdjustmentPercent(0);

        // On repasse en calcul automatique
        $user->setAutomaticCalculateEnergy(true);

        // 🔁 Recalcul de l’énergie "normale"
        try {
            $baseEnergy = $energyHandler->evaluateEnergy();
            $user->setEnergy((int) $baseEnergy);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de recalculer votre besoin énergétique.');
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Le suivi régime a été arrêté. Votre besoin énergétique a été recalculé.');

        return $this->redirectToRoute('app_dashboard_index');
    }

}
