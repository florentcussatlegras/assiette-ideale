<?php

namespace App\Service;

use App\Util\Util;
use App\Entity\Food;
use App\Entity\Dish;
use App\Entity\UnitMeasure;
use App\Repository\FoodRepository;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FoodUtil.php
 *
 * Service utilitaire pour manipuler les aliments et plats.
 *
 * Fournit :
 * - Conversion des quantités en grammes selon l'unité ou le poids moyen
 * - Gestion des aliments interdits pour l'utilisateur
 * - Recherche d'aliments par groupe, parent de groupe, mots-clés, en excluant les aliments interdits
 * - Calcul des nutriments (protéines, lipides, glucides, sodium) pour un aliment ou un plat
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class FoodUtil
{
	public function __construct(
		private EntityManagerInterface $manager,
		private Security $security,
		private FoodRepository $foodRepository,
		private DishRepository $dishRepository,
		private UnitMeasureRepository $unitMeasureRepository
	) {}

	/**
	 * Convertit une quantité donnée en grammes selon l'unité et l'aliment.
	 *
	 * @param float $quantity
	 * @param int|Food $food
	 * @param int|string|UnitMeasure $unitMeasureObjectOrIdOrAlias
	 * 
	 * @return float
	 *
	 * @throws NotFoundHttpException si l'aliment ou l'unité n'existe pas
	 */
	public function convertInGr(float $quantity, int|Food $food, int|string|UnitMeasure $unitMeasureObjectOrIdOrAlias): float
	{
		// Si $food n'est pas déjà un objet Food, on le récupère par ID depuis le repository
		if (!$food instanceof Food) {
			if (null === $food = $this->foodRepository->findOneById($food)) {
				throw new NotFoundHttpException(sprintf("Aucun aliment ne possède l'identifiant %s", $food));
			}
		}

		// Si $unitMeasureObjectOrIdOrAlias n'est pas un objet UnitMeasure, on tente de le récupérer par alias ou ID
		if (!$unitMeasureObjectOrIdOrAlias instanceof UnitMeasure) {
			if (null === $unitMeasure = $this->unitMeasureRepository->findOneByAlias($unitMeasureObjectOrIdOrAlias)) {
				if (null === $unitMeasure = $this->unitMeasureRepository->findOneById($unitMeasureObjectOrIdOrAlias)) {
					throw new NotFoundHttpException(sprintf(
						"Aucune unité de mesure ne possède d'alias ou d'identifiant %s",
						$unitMeasureObjectOrIdOrAlias
					));
				}
			}
		} else {
			$unitMeasure = $unitMeasureObjectOrIdOrAlias;
		}

		// Si c'est une unité "discrète" (ex: un œuf), on utilise le poids moyen
		if ($unitMeasure->isIsUnit()) {
			return $food->getMedianWeight() * $quantity;
		}

		// Sinon on convertit la quantité en grammes selon le ratio de l'unité
		return $quantity * $unitMeasure->getGramRatio();
	}

	/**
	 * Vérifie si un aliment est interdit pour l'utilisateur.
	 *
	 * @param Food $food
	 * 
	 * @return bool
	 */
	public function isForbidden($food): bool
	{
		return !empty($this->getForbiddenReasons($food));
	}

	/**
	 * Vérifie si un aliment est interdit pour l'utilisateur et renvoi un tableau avec nom/type restriction/source restriction.
	 *
	 * @param Food $food
	 * 
	 * @return array
	 */
	public function getForbiddenReasons($food): array
	{
		$user = $this->security->getUser();

		$reasons = [];

		// 1️⃣ forbiddenFoods utilisateur
		foreach ($user->getForbiddenFoods() as $forbiddenFood) {

			if (
				$food->getId() == (int) $forbiddenFood->getId()
				||
				(null !== $food->getSubFoodGroup() && $food->getSubFoodGroup()->getId() == (int) $forbiddenFood->getId())
			) {

				$reasons[] = [
					'food' => $food->getName(),
					'type' => 'user_forbidden_food',
					'source' => $forbiddenFood->getName()
				];
			}
		}

		// 2️⃣ diets
		foreach ($user->getDiets() as $diet) {

			if ($food->getForbiddenDiets()->contains($diet)) {

				$reasons[] = [
					'food' => $food->getName(),
					'type' => 'diet',
					'source' => $diet->getShortName()
				];
			}

			if ($food->getFoodGroup()->getForbiddenDiets()->contains($diet)) {

				$reasons[] = [
					'food' => $food->getName(),
					'type' => 'diet_group',
					'source' => $diet->getShortName()
				];
			}
		}

		return $reasons;
	}

	/**
	 * Recherche des aliments correspondant à un code de groupe (FG),
	 * en excluant les aliments interdits pour l'utilisateur.
	 *
	 * @param string $fgCode Code du groupe d'aliments
	 * @param array $forbiddenFoods Liste d'aliments à exclure (optionnel)
	 * @return array Liste d'objets Food valides pour l'utilisateur
	 */
	public function myFindByFgCodeExcludeForbidden($fgCode, $forbiddenFoods = [])
	{
		$results = [];

		// Recherche dans la base selon le code de groupe et les aliments interdits
		foreach (
			$this->manager->getRepository(Food::class)
				->myFindByFgCodeExcludeForbidden($fgCode, $forbiddenFoods) as $food
		) {
			// Vérifie que l'aliment n'est pas interdit pour l'utilisateur
			if (!$this->isForbidden($food)) {
				$results[] = $food;
			}
		}

		return $results;
	}

	/**
	 * Recherche des aliments correspondant à un code de groupe parent (FGP),
	 * en excluant les aliments interdits pour l'utilisateur.
	 *
	 * @param string $fgpCode Code du groupe parent d'aliments
	 * @param array $forbiddenFoods Liste d'aliments à exclure (optionnel)
	 * @return array Liste d'objets Food valides pour l'utilisateur
	 */
	public function myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods = [])
	{
		$results = [];

		// Recherche dans la base selon le code de groupe parent et les aliments interdits
		foreach (
			$this->manager->getRepository(Food::class)
				->myFindByFgpCodeExcludeForbidden($fgpCode, $forbiddenFoods) as $food
		) {
			// Vérifie que l'aliment n'est pas interdit pour l'utilisateur
			if (!$this->isForbidden($food)) {
				$results[] = $food;
			}
		}

		return $results;
	}

	/**
	 * Recherche des aliments correspondant à un mot-clé et des groupes parents (FGP),
	 * en appliquant des critères de sélection et en excluant les aliments interdits.
	 *
	 * @param string|null $keyword Mot-clé à rechercher
	 * @param array $fgplist Liste de codes de groupes parents (FGP)
	 * @param string $typeSelectFgp 'or' ou 'and' pour combiner les FGP
	 * @param string $sortAlpha Tri alphabétique 'ASC' ou 'DESC'
	 * @param array $forbiddenFoods Liste d'aliments à exclure
	 * @param int $offset Décalage pour pagination
	 * @param int $limit Nombre maximum de résultats
	 * @return array Liste d'objets Food valides pour l'utilisateur
	 */
	public function myFindByKeywordAndFGPExcludeForbidden(
		$keyword = null,
		$fgplist = [],
		$typeSelectFgp = 'or',
		$sortAlpha = 'ASC',
		$forbiddenFoods = [],
		$offset = 0,
		$limit = 8
	) {
		$results = [];

		// Recherche dans la base selon le mot-clé, les FGP et les options de tri/pagination
		foreach (
			$this->manager->getRepository(Food::class)
				->myFindByKeywordAndFGPExcludeForbidden($keyword, $fgplist, $typeSelectFgp, $sortAlpha, $forbiddenFoods, $offset, $limit) as $food
		) {
			// Vérifie que l'aliment n'est pas interdit pour l'utilisateur
			if (!$this->isForbidden($food)) {
				$results[] = $food;
			}
		}

		return $results;
	}

	/**
	 * Recherche des aliments correspondant à des mots-clés et groupes,
	 * en appliquant éventuellement des restrictions lactose et gluten,
	 * et en excluant les aliments interdits pour l'utilisateur.
	 *
	 * @param string|null $keyword Mot-clé à rechercher
	 * @param array $fglist Liste de codes de groupes alimentaires
	 * @param bool $freeLactose Filtrer les aliments sans lactose si true
	 * @param bool $freeGluten Filtrer les aliments sans gluten si true
	 * 
	 * @return array Liste d'objets Food valides pour l'utilisateur
	 */
	public function myFindByKeywordAndFGAndLactoseAndGlutenExcludeForbidden($keyword, $fglist, $freeLactose, $freeGluten)
	{
		$results = [];

		// Recherche dans la base selon les critères (mots-clés, groupe, lactose/gluten)
		foreach ($this->foodRepository->myFindByKeywordAndFGAndLactoseAndGluten($keyword, $fglist, $freeLactose, $freeGluten) as $food) {

			// Vérifie si l'aliment n'est pas interdit pour l'utilisateur
			if (!$this->isForbidden($food)) {
				$results[] = $food;
			}
		}

		return $results;
	}

	/**
	 * Retourne les nutriments pour un aliment ou un plat sélectionné.
	 *
	 * @param int|Food|Dish $dishOrFood
	 * @param string $type 'Food' ou 'Dish'
	 * @param float $quantity
	 * @param int|string|UnitMeasure|null $unitMeasureObjectOrIdOrAlias
	 * 
	 * @return array|null ['protein','lipid','carbohydrate','sodium'] ou null si type inconnu
	 */
	public function getNutrientsForDishOrFoodSelected(int|Food|Dish $dishOrFood, $type, float $quantity, int|string|UnitMeasure|null $unitMeasureObjectOrIdOrAlias = null): ?array
	{
		switch ($type) {
			case 'Food':
				// Si ce n'est pas déjà un objet Food, on le récupère par ID
				if (!$dishOrFood instanceof Food) {
					if (null === $dishOrFood = $this->foodRepository->findOneById($dishOrFood)) {
						throw new NotFoundHttpException('Cet aliment n\'existe pas');
					}
				}

				// Convertit la quantité fournie dans l'unité spécifiée en grammes
				$quantityInGr = $this->convertInGr($quantity, $dishOrFood, $unitMeasureObjectOrIdOrAlias);

				// Retourne les valeurs nutritionnelles proportionnelles à la quantité
				return [
					'protein' => ($quantityInGr * $dishOrFood->getNutritionalTable()->getProtein()) / 100,
					'lipid' => ($quantityInGr * $dishOrFood->getNutritionalTable()->getLipid()) / 100,
					'carbohydrate' => ($quantityInGr * $dishOrFood->getNutritionalTable()->getCarbohydrate()) / 100,
					'sodium' => ($quantityInGr * $dishOrFood->getNutritionalTable()->getSalt()) / 100,
				];

			case 'Dish':
				// Si ce n'est pas déjà un objet Dish, on le récupère par ID
				if (!$dishOrFood instanceof Dish) {
					if (null === $dishOrFood = $this->dishRepository->findOneById($dishOrFood)) {
						throw new NotFoundHttpException('Ce plat n\'existe pas');
					}
				}

				// Initialise le tableau des nutriments
				$results = ['protein' => 0, 'lipid' => 0, 'carbohydrate' => 0, 'sodium' => 0];

				// Parcourt tous les aliments du plat
				foreach ($dishOrFood->getDishFoods()->toArray() as $dishFood) {
					// Quantité effective de chaque aliment selon la portion choisie
					$quantiteG = ($dishFood->getQuantityG() * $quantity) / $dishOrFood->getLengthPersonForRecipe();

					// Ajoute la contribution de chaque aliment aux nutriments totaux du plat
					$results['protein'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getProtein()) / 100;
					$results['lipid'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getLipid()) / 100;
					$results['carbohydrate'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getCarbohydrate()) / 100;
					$results['sodium'] += ($quantiteG * $dishFood->getFood()->getNutritionalTable()->getSalt()) / 100;
				}

				return $results;

			default:
				// Retourne null si le type n'est pas reconnu
				return null;
		}
	}
}
