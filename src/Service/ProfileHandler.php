<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * ProfileHandler.php
 * 
 * Service de gestion et de traitement du profil utilisateur.
 *
 * Fonctionnalités principales :
 *  - Déterminer l'étape actuelle du profil à compléter.
 *  - Calculer la proportion de profil complété.
 *  - Recalculer automatiquement les valeurs et recommandations nutritionnelles de l'utilisateur.
 *
 * Fonctionnement :
 *  - `currentStep()` : renvoie la prochaine étape à remplir selon les étapes définies et les étapes déjà validées.
 *  - `proportionCompleted()` : retourne le pourcentage de profil complété par l'utilisateur.
 *  - `recalcUserProfile()` : recalcul des indicateurs IMC, poids idéal, recommandations nutritionnelles
 *    et recommandations par groupe d’aliment.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class ProfileHandler
{
    public const PARAMETERS = 'parameters';
    
    public const GENDER = 'gender';
    public const AGE_RANGE = 'ageRange';
    public const HEIGHT = 'height';
    public const WEIGHT = 'weight';
    public const HOURS = 'hour';
    public const WORK = 'workingType';
    public const SPORT = 'sportingTime';
    public const FORBIDDEN_FOODS = 'forbiddenFoods';
    public const DIETS = 'diets';
    public const ENERGY = 'energy';

    public const STEPS = [
        self::GENDER,
        self::AGE_RANGE,
        self::HEIGHT,
        self::WEIGHT,
        self::HOURS,
        self::WORK,
        self::SPORT,
        self::FORBIDDEN_FOODS,
        self::DIETS,
        self::ENERGY
    ];
    
    public function __construct(
        private Security $security,
        private NutrientHandler $nutrientHandler,
        private FoodGroupHandler $foodGroupHandler,
    ) {}

    /**
     * Détermine la prochaine étape du profil utilisateur à compléter.
     *
     * @return string L'alias de l'étape suivante
     */
    public function currentStep(): string
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            throw new \LogicException("L'utilisateur doit être un objet User");
        }

        $steps = self::STEPS;
        $currentStep = $steps[0];

        $validSteps = $user->getValidStepProfiles();
        $lastValidStep = !empty($validSteps) ? $validSteps[array_key_last($validSteps)] : null;

        foreach ($steps as $key => $step) {
            if ($step === $lastValidStep && isset($steps[$key + 1])) {
                $currentStep = $steps[$key + 1];
            }
        }

        return $currentStep;
    }

    /**
     * Calcule la proportion du profil utilisateur complété.
     *
     * @return float Proportion entre 0 et 1
     */
    public function proportionCompleted(): float
    {
        $user = $this->security->getUser();
        $completed = 0;

        foreach (self::STEPS as $element) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $value = $accessor->getValue($user, $element);

            if ($value !== null && $value instanceof \Traversable) {
                $value = !empty($value->toArray());
            }

            if ($value) {
                $completed++;
            }
        }

        return $completed / count(self::STEPS);
    }

    /**
     * Recalcule automatiquement les valeurs de profil et recommandations nutritionnelles.
     *
     * - IMC, poids idéal, IMC idéal
     * - Recommandations nutritionnelles par nutriment
     * - Recommandations par groupe d'aliments
     */
    public function recalcUserProfile(): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        // Recalcul des indicateurs IMC et poids idéal
        $user->setValueImc();
        $user->setValueIdealWeight();
        $user->setValueIdealImc();

        // Recalcul recommandations nutritionnelles
        $nutrientRecommendations = $this->nutrientHandler->getRecommendations($user);
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($nutrientRecommendations as $nutrientAlias => $value) {
            $accessor->setValue($user, $nutrientAlias, $value);
        }

        // Recalcul recommandations par groupe d'aliment
        $user->setRecommendedQuantities($this->foodGroupHandler->getRecommendations($user));
    }
}