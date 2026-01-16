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

    public function nextElement($element)
    {
        $user = $this->security->getUser();
        
        if(!$user instanceof User) {
            throw new \LogicException("L'utilisateur doit être un objet User");
        }

        $value = null;
        $index = (int)array_search($element, self::STEPS);

        do {
            $index++;
            if($index > array_key_last(self::STEPS)) {
                break;
            }
            $accessor = PropertyAccess::createPropertyAccessor();
            $value = $accessor->getValue($user, self::STEPS[$index]);
            if($value instanceof \Traversable){
                $value = $value->toArray();
            }
        }while(!empty($value));

        if(!$value && $index <= array_key_last(self::STEPS)) {
            return self::STEPS[$index];
        }

        return false;
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