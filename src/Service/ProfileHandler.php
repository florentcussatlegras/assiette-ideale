<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
    )
    {}

    public function currentStep()
    {
        $user = $this->security->getUser();
        
        if(!$user instanceof User) {
            throw new \LogicException("L'utilisateur doit être un objet User");
        }

        // Récupère toutes les étapes
        $steps = ProfileHandler::STEPS;
        $currentStep = $steps[0];

        // Récupère les steps validés par l'utilisateur
        $validSteps = $user->getValidStepProfiles();

        // Dernière étape validée
        $lastValidStep = !empty($validSteps) ? $validSteps[array_key_last($validSteps)] : null;

        // Parcours toutes les étapes pour déterminer la prochaine étape à remplir
        foreach ($steps as $key => $step) {
            if ($step === $lastValidStep) {
                // On prend l'étape suivante si elle existe
                $currentStep = $steps[$key + 1];
            }
        }

        return $currentStep;
    }

    public function proportionCompleted(): float
    {
        $user = $this->security->getUser();
        
        $q = 0;
        foreach(self::STEPS as $element) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $value = $propertyAccessor->getValue($user, $element);
            if(null !== $value && $value instanceof \Traversable) {
                $value = !empty($value->toArray());
            }
            if($value) {
                $q++;
            }
        }

        return $q/count(self::STEPS);
    }

    public function recalcUserProfile(): void
    {
        /** @var App\Entity\User|null $user */
        $user = $this->security->getUser();

        // Recalcul IMC, poids idéal
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