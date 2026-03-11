<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\Meal;
use App\Service\MealUtil;
use App\Repository\MealRepository;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Repository\FoodGroupParentRepository;
use Symfony\Component\Security\Core\Security;

/**
 * BalanceSheetFeature.php
 *
 * Service principal pour calculer les bilans nutritionnels sur une période donnée.
 * Fournit des fonctions pour :
 * - Calculer l'énergie moyenne quotidienne
 * - Calculer les nutriments moyens quotidiens
 * - Calculer les consommations moyennes par groupe alimentaire
 * - Identifier les plats et aliments favoris
 * - Identifier le repas le plus calorique
 * 
 * Ce service prend en compte les repas de l'utilisateur, leurs quantités et leurs compositions,
 * et utilise les repositories et utilitaires associés pour obtenir les données nécessaires.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class BalanceSheetFeature
{

    // Injection des dépendances via constructeur
    public function __construct(
        private Security $security,                         // Service de sécurité pour récupérer l'utilisateur courant
        private MealRepository $mealRepository,            // Repository pour accéder aux repas de l'utilisateur
        private DishRepository $dishRepository,            // Repository pour accéder aux plats
        private FoodRepository $foodRepository,            // Repository pour accéder aux aliments
        private MealUtil $mealUtil,                        // Service utilitaire pour gérer les repas (énergie, nutriments, FGP)
        private FoodUtil $foodUtil,                        // Service utilitaire pour gérer les aliments (conversion, nutriments)
        private FoodGroupParentRepository $foodGroupParentRepository, // Repository pour les groupes alimentaires principaux et leurs métadonnées
    ) {}

    /**
     * Calcule la moyenne quotidienne d'une série de valeurs extraites d'un repas sur une période.
     *
     * @param string $start Date de début (YYYY-MM-DD)
     * @param string $end   Date de fin (YYYY-MM-DD)
     * @param callable $extractCallback Fonction qui retourne la valeur à accumuler pour un repas : fn($meal) => ...
     * @param array|string|null $keys Optionnel : clé(s) pour initialiser les résultats si tableau associatif
     *
     * @return array|int Tableau associatif ou entier selon $keys
     */
    private function calculateAveragePerDay(string $start, string $end, callable $extractCallback, array|string|null $keys = null)
    {
        // Conversion des dates en objets DateTime
        $dateStart = new \DateTime($start);
        $dateEnd = (new \DateTime($end))->modify('+1 day'); // Inclut le dernier jour

        // Récupération des repas sur la période
        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        if (empty($meals)) {
            // Retourne 0 ou null selon si c'est un tableau ou une valeur simple
            return is_array($keys) ? array_fill_keys($keys, 0) : 0;
        }

        // Initialisation des totaux
        $totals = [];
        if (is_array($keys)) {
            foreach ($keys as $key) {
                $totals[$key] = 0;
            }
        } else {
            $totals = 0;
        }

        // Parcours des repas jour par jour
        foreach ($meals as $day => $dayMeals) {
            foreach ($dayMeals as $meal) {
                $value = $extractCallback($meal);

                if (is_array($totals) && is_array($value)) {
                    // Si plusieurs clés, additionne chaque valeur
                    foreach ($value as $k => $v) {
                        $totals[$k] += $v ?? 0;
                    }
                } else {
                    $totals += $value ?? 0;
                }
            }
        }

        // Nombre de jours dans la période
        $countDays = (int) $dateStart->diff($dateEnd)->format("%a");

        // Moyenne quotidienne arrondie
        if (is_array($totals)) {
            foreach ($totals as $k => $v) {
                $totals[$k] = $countDays > 0 ? round($v / $countDays) : 0;
            }
        } else {
            $totals = $countDays > 0 ? round($totals / $countDays) : round($totals);
        }

        return $totals;
    }

    /**
     * Calcule l'énergie moyenne quotidienne consommée sur une période donnée.
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return int|null Énergie moyenne par jour, arrondie, ou null si aucun repas
     */
    public function averageDailyEnergyForAPeriod(string $start, string $end): ?int
    {
        return $this->calculateAveragePerDay(
            $start,
            $end,
            fn($meal) => $this->mealUtil->getEnergy($meal)
        );
    }

    /**
     * Calcule la quantité moyenne quotidienne de nutriments consommés sur une période.
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return array Quantités moyennes par jour pour chaque nutriment : 
     *               ['protein' => int, 'lipid' => int, 'carbohydrate' => int, 'sodium' => int]
     */
    public function averageDailyNutrientForAPeriod(string $start, string $end): array
    {
        return $this->calculateAveragePerDay(
            $start,
            $end,
            fn($meal) => $this->mealUtil->getNutrients($meal),
            NutrientHandler::NUTRIENTS
        );
    }

    /**
     * Calcule la quantité moyenne quotidienne consommée pour chaque groupe alimentaire parent (FGP)
     * sur une période donnée.
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return array Tableau associatif des quantités moyennes par jour pour chaque FGP, 
     *               avec l'alias du FGP comme clé et la quantité moyenne comme valeur.
     */
    public function averageDailyFgpForAPeriod(string $start, string $end): array
    {
        $foodGroupParents = $this->foodGroupParentRepository->findByIsPrincipal(1);
        $aliases = array_map(fn($fgp) => $fgp->getAlias(), $foodGroupParents);

        return $this->calculateAveragePerDay(
            $start,
            $end,
            fn($meal) => $this->mealUtil->getFoodGroupParents($meal),
            $aliases
        );
    }

    /**
     * Récupère le plat préféré de l'utilisateur sur une période donnée.
     * Le plat préféré est celui qui a été consommé en plus grande quantité totale (somme des portions).
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return Dish|null Retourne l'objet Dish le plus consommé ou null si aucun plat n'a été trouvé
     */
    public function getFavoriteDishPerPeriod(string $start, string $end): ?Dish
    {
        // Conversion des dates en objets DateTime
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);

        // Récupération des repas de l'utilisateur sur la période
        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        // Accumulateur pour stocker les quantités totales par plat
        $dishes = [];

        foreach ($meals as $date => $list) {
            foreach ($list as $meal) {
                foreach ($meal->getDishAndFoods() as $item) {
                    // On ne traite que les plats (type "Dish")
                    if ('Dish' === $item['type']) {
                        if (isset($dishes[$item['id']])) {
                            $dishes[$item['id']] += (int) $item['quantity'];
                        } else {
                            $dishes[$item['id']] = (int) $item['quantity'];
                        }
                    }
                }
            }
        }

        // Détermination du plat le plus consommé
        if (!empty($dishes)) {
            $maxValue = max($dishes);
            $id = array_keys($dishes, $maxValue)[0];
            $dish = $this->dishRepository->findOneById($id);
        }

        // Retourne le plat le plus consommé ou null si aucun plat
        return $dish ?? null;
    }

    /**
     * Récupère l'aliment préféré de l'utilisateur sur une période donnée.
     * L'aliment préféré est celui consommé en plus grande quantité totale (en grammes).
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return Food|null Retourne l'objet Food le plus consommé ou null si aucun aliment n'a été trouvé
     */
    public function getFavoriteFoodPerPeriod(string $start, string $end): ?Food
    {
        // Conversion des dates en objets DateTime
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);

        // Récupération des repas de l'utilisateur sur la période
        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        // Accumulateur pour stocker les quantités totales par aliment
        $foods = [];

        foreach ($meals as $date => $list) {
            foreach ($list as $meal) {
                foreach ($meal->getDishAndFoods() as $item) {
                    // On ne traite que les aliments (type "Food")
                    if ('Food' === $item['type']) {
                        // Conversion en grammes pour uniformiser les quantités
                        $quantityGr = $this->foodUtil->convertInGr($item['quantity'], $item['id'], $item['unitMeasureAlias']);

                        if (isset($foods[$item['id']])) {
                            $foods[$item['id']] += (int) $quantityGr;
                        } else {
                            $foods[$item['id']] = (int) $quantityGr;
                        }
                    }
                }
            }
        }

        // Détermination de l'aliment le plus consommé
        if (!empty($foods)) {
            $maxValue = max($foods);
            $id = array_keys($foods, $maxValue)[0];
            $food = $this->foodRepository->findOneById($id);
        }

        // Retourne l'aliment le plus consommé ou null si aucun aliment
        return $food ?? null;
    }

    /**
     * Récupère le repas le plus calorique consommé par l'utilisateur sur une période donnée.
     * Le repas le plus calorique est celui dont l'énergie totale (kcal) est la plus élevée.
     *
     * @param string $start Date de début (format YYYY-MM-DD)
     * @param string $end   Date de fin (format YYYY-MM-DD)
     *
     * @return Meal|null Retourne l'objet Meal le plus calorique ou null si aucun repas n'a été trouvé
     */
    public function getMostCaloricPerPeriod(string $start, string $end): ?Meal
    {
        // Conversion des dates en objets DateTime
        $dateStart = new \DateTime($start);
        $dateEnd = new \DateTime($end);

        // Récupération des repas de l'utilisateur sur la période
        $meals = $this->getMealsForAPeriod($dateStart, $dateEnd);

        // Initialisation du compteur pour l'énergie maximale et du repas associé
        $energyMax = 0;
        $mostCaloricMeal = null;

        // Parcours de tous les repas
        foreach ($meals as $dateStr => $list) {
            foreach ($list as $meal) {
                // Récupération de l'énergie totale du repas
                $energy = $this->mealUtil->getEnergy($meal);

                // Si l'énergie du repas est supérieure à la valeur max actuelle, on met à jour
                if ($energy > $energyMax) {
                    $energyMax = $energy;
                    $mostCaloricMeal = $meal;
                }
            }
        }

        // Retourne le repas le plus calorique ou null si aucun repas
        return $mostCaloricMeal;
    }

    /**
     * Récupère tous les repas de l'utilisateur sur une période donnée.
     * Chaque jour de la période est une clé du tableau retourné.
     *
     * @param \DateTime $dateStart Date de début de la période
     * @param \DateTime $dateEnd   Date de fin de la période
     * @param string|null $feature Optionnel, pour indiquer la donnée à récupérer (par défaut 'energy')
     *
     * @return array|null Tableau associatif des repas par date (YYYY-MM-DD) ou null si aucun repas
     */
    public function getMealsForAPeriod(\DateTime $dateStart, \DateTime $dateEnd): ?array
    {
        // Initialisation du tableau qui contiendra tous les repas
        $results = [];

        // Cas où la période contient plusieurs jours
        if ($dateStart != $dateEnd) {
            // On parcourt chaque jour de la période
            foreach (new \DatePeriod($dateStart, new \DateInterval('P1D'), $dateEnd, \DatePeriod::INCLUDE_END_DATE) as $dt) {
                $dateStr = $dt->format('Y-m-d'); // Formatage de la date en chaîne

                // Récupération des repas pour la date
                if (null !== $meals = $this->getMealByDate($dateStr)) {
                    $results[$dateStr] = $meals;
                }
            }
        } else {
            // Cas où la période ne contient qu'un seul jour
            $dateStr = $dateStart->format('Y-m-d');
            if (null !== $meals = $this->getMealByDate($dateStr)) {
                $results[$dateStr] = $meals;
            }
        }

        // Retourne le tableau des repas par date
        return $results;
    }

    /**
     * Récupère les repas de l'utilisateur pour une date précise.
     *
     * @param string $dateStr Date au format 'YYYY-MM-DD'
     *
     * @return array|null Tableau des repas pour cette date ou null si aucun repas trouvé
     */
    private function getMealByDate(string $dateStr): ?array
    {
        // Requête vers le repository des repas pour récupérer ceux correspondant à la date et à l'utilisateur courant
        $meals = $this->mealRepository->findBy([
            'eatedAt' => $dateStr,           // Filtre sur la date
            'user' => $this->security->getUser(), // Filtre sur l'utilisateur courant
        ]);

        // Si des repas sont trouvés, on les retourne
        if (!empty($meals)) {
            return $meals;
        }

        // Sinon, on retourne null
        return null;
    }
}
