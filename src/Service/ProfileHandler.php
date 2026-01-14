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

        // $currentIndex = (int)array_search($element, self::STEPS);
        // $nextIndex = $currentIndex + 1;

        // if(array_key_exists($nextIndex, self::STEPS)) {
        //     $accessor = PropertyAccess::createPropertyAccessor();

        //     if(null !== $accessor->getValue($user, self::STEPS[$nextIndex])) {
        //         return self::STEPS[$nextIndex];
        //     }
        // }


        // do 
        // {
        //     $currentIndex = (int)array_search($element, self::STEPS);
        //     $nextIndex = $currentIndex + 1;

        //     if (array_key_exists($nextIndex, self::STEPS)) {
        //         $element = self::STEPS[$nextIndex];
        //         $accessor = PropertyAccess::createPropertyAccessor();
        //         $value = $accessor->getValue($user, element);
        //     }else{
        //         break;
        //     }
        // }while(null === $value);

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

        // $r = 0;
        // $countProperty = 8;

        // if($user->getGender()) $r++;
        // if($user->getAgeRange()) $r++;
        // if($user->getHeight()) $r++;
        // if($user->getWeight()) $r++;
        // if($user->getSportingTime()) $r++;
        // if($user->getWorkingType()) $r++;
        
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