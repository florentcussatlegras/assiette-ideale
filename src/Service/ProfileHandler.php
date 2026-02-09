<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\PersistentCollection;
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
    
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

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
}