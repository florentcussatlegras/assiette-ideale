<?php

namespace App\Service;

use App\Entity\Dish;
use App\Entity\DishFoodGroup;
use App\Entity\FoodGroup\FoodGroupParent;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FoodUtil;

/**
 * DishUtil.php
 * 
 * Service utilitaire pour manipuler les entités Dish (plats).
 *
 * Ce service centralise différentes opérations liées aux plats :
 *
 * - Calcul des quantités de groupes alimentaires pour un nombre de portions donné.
 * - Filtrage des plats selon des contraintes nutritionnelles.
 * - Exclusion des plats contenant des aliments interdits pour l'utilisateur.
 * - Recherche de plats avec différents critères (mot-clé, groupes alimentaires, lactose, gluten…).
 * - Outils utilitaires comme le tri alphabétique.
 *
 * L'objectif est de regrouper la logique métier liée aux plats afin
 * d'éviter de la disperser dans les contrôleurs.
 *
 * Ce service s'appuie notamment sur :
 * - DishRepository pour les requêtes personnalisées.
 * - FoodUtil pour déterminer si un aliment est interdit.
 *
 * @author Florent Cussatlegras
 * @package App\Service
 */
class DishUtil
{
    /**
     * Constructeur du service.
     *
     * Injection des dépendances nécessaires au fonctionnement du service :
     * - EntityManager pour accéder aux repositories Doctrine
     * - FoodUtil pour analyser les restrictions liées aux aliments
     * - DishRepository pour effectuer des recherches avancées
     *
     * @param EntityManagerInterface $manager Gestionnaire Doctrine
     * @param FoodUtil $foodUtil Service utilitaire pour la gestion des aliments
     * @param DishRepository $dishRepository Repository des plats
     */
    public function __construct(
        private EntityManagerInterface $manager,
        private FoodUtil $foodUtil,
        private DishRepository $dishRepository
    ) {}

    /**
     * Calcule les quantités de chaque groupe alimentaire parent pour un nombre de portions donné.
     *
     * Exemple :
     * Si un plat contient :
     * - 50g de légumes par portion
     * - 30g de protéines par portion
     *
     * Pour 3 portions la méthode retournera :
     * - légumes = 150g
     * - protéines = 90g
     *
     * Le calcul se fait en :
     * 1. parcourant tous les groupes alimentaires parents
     * 2. récupérant les quantités définies pour le plat
     * 3. multipliant par le nombre de portions demandées
     *
     * @param Dish $dish Plat analysé
     * @param int $nPortion Nombre de portions
     *
     * @return array<string,float> Tableau associatif :
     * [
     *     'legumes' => 120,
     *     'proteines' => 80,
     *     ...
     * ]
     */
    public function getFoodGroupParentQuantitiesForNPortion(Dish $dish, int $nPortion): array
    {
        $quantities = [];

        foreach ($this->manager->getRepository(FoodGroupParent::class)->findAll() as $fgp) {

            // Initialisation de la quantité pour ce groupe alimentaire
            $quantities[$fgp->getAlias()] = 0;

            // Récupération des relations DishFoodGroup pour ce plat et ce groupe
            foreach ($this->manager->getRepository(DishFoodGroup::class)->findByDishAndFoodGroupParent($dish, $fgp) as $dfg) {

                // Addition des quantités définies pour une portion
                $quantities[$fgp->getAlias()] += $dfg->getQuantityForOne();
            }

            // Multiplication par le nombre de portions demandées
            $quantities[$fgp->getAlias()] *= $nPortion;
        }

        return $quantities;
    }

    /**
     * Filtre une liste de plats selon la quantité d'un groupe alimentaire donné.
     *
     * Exemple :
     * - récupérer les plats contenant entre 50g et 150g de légumes.
     *
     * @param Dish[] $dishes Liste de plats à analyser
     * @param string $fgpAlias Alias du groupe alimentaire parent
     * @param float $qtyMin Quantité minimale
     * @param float $qtyMax Quantité maximale
     *
     * @return Dish[] Liste filtrée de plats respectant la contrainte
     */
    public function getByQuantityFoodGroupParent(array $dishes, string $fgpAlias, float $qtyMin, float $qtyMax): array
    {
        $results = [];

        foreach ($dishes as $dish) {

            // Récupération de la quantité pour 1 portion
            $quantity = $this->getFoodGroupParentQuantitiesForNPortion($dish, 1)[$fgpAlias];

            // Vérification si la quantité se trouve dans l'intervalle
            if ($quantity >= $qtyMin && $quantity <= $qtyMax) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Détermine les raisons pour lesquelles un plat est interdit.
     *
     * La méthode analyse chaque aliment composant le plat et récupère
     * les raisons d'interdiction via le service FoodUtil.
     *
     * Exemple de résultat :
     * [
     *     [
     *         'food' => 'Jambon cru',
     *         'type' => 'diet_group',
     *         'source' => 'Ovo-végétarisme'
     *     ],
     *     [
     *         'food' => 'Ananas',
     *         'type' => 'user_forbidden_food',
     *         'source' => 'Ananas'
     *     ]
     * ]
     *
     * @param Dish $dish Plat analysé
     *
     * @return array<int,array<string,string>> Liste des raisons d'interdiction
     */
    public function getForbiddenReasons($dish): array
    {
        $reasons = [];

        foreach ($dish->getDishFoods() as $dishFood) {

            $food = $dishFood->getFood();

            // Récupération des raisons d'interdiction pour cet aliment
            $foodReasons = $this->foodUtil->getForbiddenReasons($food);

            foreach ($foodReasons as $reason) {

                $reasons[] = [
                    'food' => $food->getName(),
                    'type' => $reason['type'],
                    'source' => $reason['source']
                ];
            }
        }

        return $reasons;
    }

    /**
     * Recherche des plats par mot-clé et groupe alimentaire parent
     * tout en excluant les plats contenant des aliments interdits.
     *
     * @param string $keyword Mot-clé de recherche
     * @param string $fgp Alias du groupe alimentaire
     * @param int $offset Décalage pagination
     * @param int $limit Nombre maximal de résultats
     *
     * @return Dish[] Liste des plats autorisés
     */
    public function myFindByKeywordAndFGPExcludeFordidden(string $keyword, string $fgp, int $offset, int $limit): array
    {
        $results = [];

        foreach ($this->manager->getRepository(Dish::class)->myFindByKeywordAndFGP($keyword, $fgp, $offset, $limit) as $dish) {

            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Recherche avancée de plats avec plusieurs contraintes :
     * - mot-clé
     * - groupes alimentaires
     * - lactose
     * - gluten
     *
     * Les plats contenant des aliments interdits sont exclus du résultat.
     *
     * @param string $keyword Mot-clé de recherche
     * @param array $fglist Liste des groupes alimentaires
     * @param bool $freeLactose Filtrer les plats sans lactose
     * @param bool $freeGluten Filtrer les plats sans gluten
     *
     * @return Dish[] Liste filtrée de plats
     */
    public function myFindByKeywordAndFGAndTypeAndLactoseAndGlutenExcludeForbidden(
        string $keyword,
        array $fglist,
        bool $freeLactose,
        bool $freeGluten
    ): array
    {
        $results = [];

        foreach ($this->dishRepository->myFindByKeywordAndFGAndTypeAndLactoseAndGluten($keyword, $fglist, $freeLactose, $freeGluten) as $dish) {

            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Retourne tous les plats disponibles en excluant
     * ceux contenant des aliments interdits.
     *
     * @return Dish[]
     */
    public function myFindAllExcludeForbidden(): array
    {
        $results = [];

        foreach ($this->manager->getRepository(Dish::class)->findAll() as $dish) {

            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Recherche des plats selon un groupe alimentaire parent
     * et une plage de quantités.
     *
     * Les plats contenant des aliments interdits sont exclus.
     *
     * @param string $fgpAlias Alias du groupe alimentaire
     * @param float $qtyMin Quantité minimale
     * @param float $qtyMax Quantité maximale
     *
     * @return Dish[]
     */
    public function myFindByGroupAndQuantityRangeExcludeForbidden(string $fgpAlias, float $qtyMin, float $qtyMax): array
    {
        $results = [];

        foreach ($this->manager->getRepository(Dish::class)->myFindByGroupAndQuantityRange($fgpAlias, $qtyMin, $qtyMax) as $dish) {

            if (!$this->isForbidden($dish)) {
                $results[] = $dish;
            }
        }

        return $results;
    }

    /**
     * Comparateur permettant de trier des objets Dish par nom.
     *
     * Utilisable avec la fonction PHP `usort()`.
     *
     * Exemple :
     * usort($dishes, [$dishUtil, 'orderObjectByName']);
     *
     * @param Dish $a
     * @param Dish $b
     *
     * @return int
     */
    public function orderObjectByName($a, $b): int
    {
        return $a->getName() <=> $b->getName();
    }
}