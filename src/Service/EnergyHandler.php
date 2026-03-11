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

/**
 * EnergyHandler.php
 *
 * Service chargé de gérer tous les calculs énergétiques de l'application.
 *
 * Il permet :
 * - d'estimer le besoin énergétique journalier d'un utilisateur
 * - de vérifier que les informations nécessaires au calcul sont présentes
 * - de calculer l'énergie apportée par un aliment ou un plat
 *
 * Les calculs reposent sur :
 * - le profil utilisateur (sexe, âge, taille, poids, activité)
 * - les données nutritionnelles des aliments
 * - les quantités consommées
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class EnergyHandler
{
    // label "energy'
    public const ENERGY = 'energy';

    // Unités énergétiques
    public const KJ = 'kJ';
    public const KCAL = 'kCal';

    // Coefficient de conversion kilojoules → kilocalories
    public const MULTIPLICATOR_CONVERT_KJ_IN_KCAL = 0.2388;

    /**
     * Liste des informations de profil nécessaires
     * pour estimer le besoin énergétique.
     */
    public const PROFILE_LIST_NEEDED = [
        ProfileHandler::GENDER,
        ProfileHandler::AGE_RANGE,
        ProfileHandler::HEIGHT,
        ProfileHandler::WEIGHT,
        ProfileHandler::SPORT,
        ProfileHandler::WORK,
    ];

    /**
     * Injection des dépendances via constructeur
     */
    public function __construct(
        private Security $security,              // Accès à l'utilisateur connecté
        private FoodUtil $foodUtil,              // Utilitaires pour conversion des aliments (grammes, unités)
        private EntityManagerInterface $manager  // Accès aux repositories Doctrine
    ) {}

    /**
     * Évalue le besoin énergétique journalier de l'utilisateur.
     *
     * Le calcul repose sur :
     * - le sexe
     * - l'âge
     * - la taille
     * - le poids
     * - l'activité physique
     *
     * Le résultat final est exprimé en kilocalories (kcal).
     *
     * @return float
     */
    public function evaluateEnergy(): float
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException("L'utilisateur dont vous essayez de calculer l'énergie doit etre un objet UserInterface");
        }

        // Vérifie que toutes les informations nécessaires sont présentes
        if (count($this->profileMissingForEnergy()) > 0) {
            throw new MissingElementForEnergyEstimationException(
                'Il manque des informations pour estimer votre besoin énergétique journalier'
            );
        }

        // Coefficient selon le sexe
        $coeffPI = Gender::MALE === $user->getGender()->getAlias() ? 110 : 100;

        // Calcul du poids idéal (IMC 22)
        $perfectWeight = 22 * ($user->getHeight() / 100) * ($user->getHeight() / 100);

        // Utilise le poids réel ou idéal selon le cas
        $weightForEnergy = ($user->getWeight() > $perfectWeight)
            ? $user->getWeight()
            : $perfectWeight;

        // Calcul énergétique en kilojoules
        $energy = $coeffPI
            * $user->getAgeRange()->getCoeffEnergy()
            * $weightForEnergy
            * $user->getPhysicalActivity();

        // Conversion en kilocalories
        return $energy * self::MULTIPLICATOR_CONVERT_KJ_IN_KCAL;
    }

    /**
     * Vérifie les informations de profil manquantes
     * nécessaires au calcul énergétique.
     *
     * @return array Liste des champs manquants
     */
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

            // Gestion des collections Doctrine
            if (null !== $value && $value instanceof \Traversable) {
                $value = !empty($value->toArray());
            }

            if (!$value) {
                $elements[] = $element;
            }
        }

        return $elements;
    }

    /**
     * Calcule l'énergie apportée par un aliment ou un plat.
     *
     * @param int|Food|Dish $dishOrFood Aliment ou plat (objet ou ID)
     * @param string $type "Food" ou "Dish"
     * @param float $quantity Quantité consommée
     * @param UnitMeasure|int|string|null $unitMeasureObjectOrIdOrAlias
     *
     * @return float|null
     */
    public function getEnergyForDishOrFoodSelected(
        int|Food|Dish $dishOrFood,
        $type,
        float $quantity,
        int|string|UnitMeasure|null $unitMeasureObjectOrIdOrAlias = null
    )
    {

        switch ($type) {

            /**
             * Cas d'un aliment simple
             */
            case 'Food':

                if (!$dishOrFood instanceof Food) {
                    if (null === $dishOrFood = $this->manager
                        ->getRepository(Food::class)
                        ->findOneById($dishOrFood)) {

                        throw new NotFoundHttpException('Cet aliment n\'existe pas');
                    }
                }

                // Conversion de la quantité en grammes
                $quantityInGr = $this->foodUtil->convertInGr(
                    $quantity,
                    $dishOrFood,
                    $unitMeasureObjectOrIdOrAlias
                );

                // Calcul énergétique proportionnel
                return ($quantityInGr * $dishOrFood
                        ->getNutritionalTable()
                        ->getEnergy()) / 100;

            /**
             * Cas d'un plat composé
             */
            case 'Dish':

                if (!$dishOrFood instanceof Dish) {
                    if (null === $dishOrFood = $this->manager
                        ->getRepository(Dish::class)
                        ->findOneById($dishOrFood)) {

                        throw new NotFoundHttpException('Ce plat n\'existe pas');
                    }
                }

                $energy = 0;

                // Calcul énergie pour chaque aliment du plat
                foreach ($dishOrFood->getDishFoods()->toArray() as $dishFood) {

                    $quantiteG =
                        ($dishFood->getQuantityG() * $quantity)
                        / $dishOrFood->getLengthPersonForRecipe();

                    $energy += (
                        $quantiteG
                        * $dishFood->getFood()
                        ->getNutritionalTable()
                        ->getEnergy()
                    ) / 100;
                }

                return $energy;

            default:
                return null;
        }
    }
}