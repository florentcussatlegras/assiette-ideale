<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\User;
use App\Entity\Gender;
use App\Service\FoodUtil;
use App\Entity\UnitMeasure;
use App\Service\ProfileHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;
use App\Exception\MissingElementForEnergyEstimationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnergyHandler
{
    public const KJ = 'kJ';

    public const KCAL = 'kCal';

    public const MULTIPLICATOR_CONVERT_KJ_IN_KCAL = 0.2388;

    public const PROFILE_LIST_NEEDED = [
        ProfileHandler::GENDER,
        ProfileHandler::AGE_RANGE,
        ProfileHandler::HEIGHT,
        ProfileHandler::WEIGHT,
        ProfileHandler::SPORT,
        ProfileHandler::WORK,
    ];

    public function __construct(
        private Security $security,
        private FoodUtil $foodUtil,
        private EntityManagerInterface $manager
    ) {}

    public function evaluateEnergy()
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException("L'utilisateur dont vous essayez de calculer l'énergie doit etre un objet UserInterface");
        }

        if (count($this->profileMissingForEnergy()) > 0) {
            throw new MissingElementForEnergyEstimationException('Il manque des informations pour estimer votre besoin énergétique journalier');
        }

        $coeffPI = Gender::MALE === $user->getGender()->getAlias() ? 110 : 100;

        $perfectWeight = 22 * ($user->getHeight() / 100) * ($user->getHeight() / 100);

        $weightForEnergy = ($user->getWeight() > $perfectWeight) ? $user->getWeight() : $perfectWeight;

        $energy = $coeffPI * $user->getAgeRange()->getCoeffEnergy() * $weightForEnergy * $user->getPhysicalActivity();

        // Le résultat obtenu est en Kj, on le renvoit en Kcal en * 0.2388
        return $energy * self::MULTIPLICATOR_CONVERT_KJ_IN_KCAL;
    }

    public function profileMissingForEnergy(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException("L'utilisateur dont vous essayez de calculer l'énergie doit etre un objet UserInterface");
        }

        $elements = [];
        foreach (self::PROFILE_LIST_NEEDED as $element) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $value = $propertyAccessor->getValue($user, $element);
            if (null !== $value && $value instanceof \Traversable) {
                $value = !empty($value->toArray());
            }
            if (!$value) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    public function getEnergyForDishOrFoodSelected(int|Food|Dish $dishOrFood, $type, float $quantity, int|string|UnitMeasure|null $unitMeasureObjectOrIdOrAlias = null)
    {
        // $item example = [▼
        //     "type" => "Food"
        //     "id" => "680"
        //     "quantity" => "20"
        //     "measureUnit" => "93"
        //     "measureUnitAlias" => "ml"
        // ]

        /*
            Food:

            [▼
                "type" => "Food"
                "id" => "680"
                "quantity" => "20"
                "measureUnit" => "93"
                "measureUnitAlias" => "ml"
            ]

            Pour 100g                                  =>            $food->getEnergy()
            $item['quantity'] convertit en gramme      =>            energy à calculer

            energy = ($food->getEnergy() * (convertInGr($item['quantity'])) / 100

            Dish:

            [▼
                "type" => "Dish"
                "id" => "1"
                "quantity" => "1"
            ]

            Pour chaque aliment du plat ($dishOrFood->getDishFoods()->toArray() => $dishFood):

                $quantiteG = ($dishFood->getQuantityG() * quantity) / dish->getLengthPersonForRecipe()

                100g            =>      $food->getEnergy
                $quantiteG      =>      energy à calculer pour chaque aliment présent dans le plat

                energy = ($quantiteG * $food->getEnergy()) / 100

        */

        switch ($type) {
            case 'Food':
                if (!$dishOrFood instanceof Food) {
                    if (null === $dishOrFood = $this->manager->getRepository(Food::class)->findOneById($dishOrFood)) {
                        throw new NotFoundHttpException('Cet aliment n\'existe pas');
                    }
                }
                $quantityInGr = $this->foodUtil->convertInGr($quantity, $dishOrFood, $unitMeasureObjectOrIdOrAlias);
                return ($quantityInGr * $dishOrFood->getNutritionalTable()->getEnergy()) / 100;

                break;

            case 'Dish':
                if (!$dishOrFood instanceof Dish) {
                    if (null === $dishOrFood = $this->manager->getRepository(Dish::class)->findOneById($dishOrFood)) {
                        throw new NotFoundHttpException('Ce plat n\'existe pas');
                    }
                }

                $energy = 0;
                foreach ($dishOrFood->getDishFoods()->toArray() as $dishFood) {
                    $quantiteG = ($dishFood->getQuantityG() * $quantity) / $dishOrFood->getLengthPersonForRecipe();
                    $energy += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getEnergy()) / 100;
                }

                return $energy;

                break;

            default:
                return null;
        }
    }
}
