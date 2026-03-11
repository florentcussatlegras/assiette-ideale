<?php

namespace App\Controller;

use App\Entity\Gender;
use App\Service\EnergyHandler;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnergyController.php
 * 
 * Contrôleur responsable de la gestion et de l'explication de l'énergie quotidienne de l'utilisateur.
 * 
 * Fonctionnalités principales :
 *  - Calcul automatique de l'énergie quotidienne selon le poids, l'âge, le sexe et l'activité physique.
 *  - Fournit une explication détaillée du calcul de l'énergie pour l'utilisateur.
 *  - Permet d'afficher toutes les informations nécessaires dans le template pour la compréhension du calcul.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
#[Route('/energy')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class EnergyController extends AbstractController
{
    /**
     * Calcule et met à jour l'énergie quotidienne de l'utilisateur.
     * 
     * @param EnergyHandler $energyHandler Service pour calculer l'énergie selon les données de l'utilisateur
     * @param EntityManagerInterface $manager Pour persister les modifications en base
     * 
     * @return RedirectResponse Redirection vers le profil après mise à jour
     */
    #[Route('/calculate', name: 'app_energy_estimate', methods: ['GET'])]
    public function calculateEnergy(EnergyHandler $energyHandler, EntityManagerInterface $manager): RedirectResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // On active le calcul automatique de l'énergie
        $user->setAutomaticCalculateEnergy(true);

        // Calcul de l'énergie en joules (J)
        $energyHandler->evaluateEnergy($user);

        // Enregistrement de l'énergie calculée dans l'utilisateur
        $user->setEnergy($energyHandler->evaluateEnergy($user));

        // Persiste les changements en base
        $manager->flush();

        // Message flash pour confirmation
        $this->addFlash('success', 'Votre energie quotidienne a bien été mise à jour.');

        // Redirection vers la page profil
        return $this->redirectToRoute('app_profile_index');
    }

    /**
     * Fournit une explication détaillée du calcul de l'énergie quotidienne.
     * @return Response Rendu de la vue explicative
     */
    #[Route('/explanation', name: 'app_energy_explanation', methods: ['GET'])]
    public function explanation(): Response
    {
        /** @var \App\Entity\User|null $user Récupération de l'utilisateur connecté */
        $user = $this->getUser();

        // Coefficient PI dépendant du genre (homme/femme)
        $coeffPI = $user->getGender()->getAlias() === Gender::MALE ? 110 : 100;

        // Poids idéal basé sur un IMC de 22
        $perfectWeight = 22 * ($user->getHeight()/100) * ($user->getHeight()/100);

        // Choix du poids à utiliser pour le calcul d'énergie
        // Si le poids réel est supérieur au poids idéal, on prend le poids réel
        $weightForEnergy = ($user->getWeight() > $perfectWeight) ? $user->getWeight() : $perfectWeight;

        // Coefficient selon la tranche d'âge
        $ageCoeff = $user->getAgeRange()->getCoeffEnergy(); 

        // Coefficient lié à l'activité physique
        $physicalActivityCoeff = $user->getPhysicalActivity();

        // Calcul de l'énergie en joules
        $energyJ = $coeffPI * $ageCoeff * $weightForEnergy * $physicalActivityCoeff;

        // Conversion en kilocalories
        $energyKcal = $energyJ * EnergyHandler::MULTIPLICATOR_CONVERT_KJ_IN_KCAL;

        // Récupération d'informations complémentaires pour le template
        $workingType = $user->getWorkingType();
        $sportingTime = $user->getSportingTime();

        // Rendu de la vue explicative avec tous les paramètres calculés
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