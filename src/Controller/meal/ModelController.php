<?php

namespace App\Controller\meal;

use App\Repository\FoodGroupParentRepository;
use App\Entity\TypeMeal;
use App\Entity\MealModel;
use App\Service\MealUtil;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\Type\MealFilterType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MealModelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ModelController.php
 *
 * Contrôleur pour gérer les "repas type" (modèles de repas) de l'utilisateur.
 *
 * Ce contrôleur permet de :
 *  - Ajouter, modifier ou supprimer un repas type
 *  - Créer un nouveau repas type à partir de la session
 *  - Lister les repas type avec filtres et tri
 *  - Vérifier la présence de gluten ou lactose dans un repas type
 *  - Obtenir la liste des groupes alimentaires associés à un repas type
 *  - Mettre à jour l'énergie de tous les repas type en un seul coup
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\meal
 */
#[Route('/mes-repas-type')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class ModelController extends AbstractController
{
	/**
	 * Ajoute ou modifie un repas type pour l'utilisateur courant.
	 * Si un idMealModel est fourni, l'ancien modèle est supprimé après l'ajout.
	 *
	 * @param Request $request Requête HTTP pour récupérer rankMeal, name et autres données
	 * @param EntityManagerInterface $manager Gestionnaire d'entités pour persister/supprimer
	 * @param TokenStorageInterface $tokenStorageInterface Récupération de l'utilisateur courant
	 * @param MealUtil $mealUtil Service pour calculer l'énergie du repas
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer des modèles existants
	 * @param int|null $idMealModel Optionnel, id d'un modèle existant à remplacer
	 * 
	 * @return Response Redirection vers la liste ou la page du jour
	 */
	#[Route('/add/{idMealModel?}', name: 'model_meal_add', methods: ['POST'], requirements: ['idMealModel' => '\d+'], options: ['expose' => true])]
	public function add(
		Request $request,
		EntityManagerInterface $manager,
		TokenStorageInterface $tokenStorageInterface,
		MealUtil $mealUtil,
		MealModelRepository $mealModelRepository,
		?int $idMealModel
	): Response {
		// Récupération de la session utilisateur
		$session = $request->getSession();

		// Récupération des données envoyées par le formulaire
		$rankMeal = $request->request->get('rankMeal');
		$name = $request->request->get('name');

		// Récupération du type et de la liste plat/aliments du repas depuis la session
		$type = $session->get('_meal_day_' . $rankMeal)['type'];
		$dishAndFood = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'];

		// Récupération de l'entité TypeMeal correspondante
		$typeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($type);

		// Création du nouveau modèle de repas
		$mealModel = new MealModel($name, $typeMeal, $dishAndFood, $tokenStorageInterface->getToken()->getUser());

		// Calcul de l'énergie totale du modèle
		$mealModel->setEnergy($mealUtil->getEnergy($mealModel));

		// Persistance de la nouvelle entité
		$manager->persist($mealModel);

		// Si un ancien modèle doit être remplacé
		if ($idMealModel) {
			$oldMealModelToRemove = $mealModelRepository->findOneById($idMealModel);
			$manager->remove($oldMealModelToRemove);
		}

		// Exécution des opérations de base de données
		$manager->flush();

		// Message flash de confirmation
		$this->addFlash('info', 'Votre repas a bien été sauvegardé');

		// Redirection si paramètre meal_list présent
		if ($request->query->has('meal_list')) {
			return $this->redirectToRoute('model_meal_list');
		}

		// Redirection par défaut vers la page du jour
		return $this->redirectToRoute('meal_day');
	}

	/**
	 * Supprime un modèle de repas type existant.
	 * Vérifie le token CSRF pour sécuriser la suppression.
	 *
	 * @param EntityManagerInterface $manager Gestionnaire d'entités
	 * @param Request $request Requête HTTP pour récupérer le token et paramètre ajax
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer les modèles
	 * @param MealModel|null $mealModel Modèle à supprimer
	 * 
	 * @return Response Rendu Twig ou redirection
	 */
	#[Route('/remove/{id?}', name: 'model_meal_remove', methods: ['GET'], requirements: ['id' => '\d+'])]
	public function remove(
		EntityManagerInterface $manager,
		Request $request,
		MealModelRepository $mealModelRepository,
		?MealModel $mealModel
	): Response {
		// Récupération du token CSRF depuis la requête
		$submittedToken = $request->query->get('_token');

		// Vérification de la validité du token
		if (!$this->isCsrfTokenValid('delete_meal_' . $mealModel->getId(), $submittedToken)) {
			return $this->json(['error' => 'Token CSRF invalide'], 400);
		}

		// Suppression de l'entité et flush
		$manager->remove($mealModel);
		$manager->flush();

		// Si requête AJAX, renvoie le partial avec la liste mise à jour
		if ($request->query->get('ajax')) {
			return $this->render('meals/model/_list.html.twig', [
				"modelMeals" => $mealModelRepository->myFindByUser(),
			]);
		}

		// Message flash de confirmation
		$this->addFlash('info', 'Le repas a bien été supprimé');

		// Redirection vers la liste des modèles
		return $this->redirectToRoute('model_meal_list');
	}

	/**
	 * Initialise un nouveau repas type en supprimant les repas existants dans la session.
	 * Redirection vers la page de création du repas.
	 *
	 * @param Request $request Requête HTTP pour récupérer la session
	 * @param MealUtil $mealUtil Service pour manipuler les repas
	 * 
	 * @return Response Redirection vers la page d'ajout
	 */
	#[Route('/new', name: 'model_meal_new', methods: ['GET'])]
	public function new(Request $request, MealUtil $mealUtil): Response
	{
		// Nettoyage des repas en session
		$mealUtil->removeMealsSession();
		$request->getSession()->set('_meal_day_date', 'model');

		// Redirection vers la page d'ajout de repas
		return $this->redirectToRoute('meal_day_add');
	}

	/**
	 * Prépare la modification d'un repas type existant.
	 * Réinitialise la session et redirige vers la page d'ajout en fournissant l'id du modèle.
	 *
	 * @param Request $request Requête HTTP
	 * @param MealUtil $mealUtil Service pour manipuler les repas
	 * @param int|null $idMealModel Id du modèle à modifier
	 * 
	 * @return Response Redirection vers la page d'ajout
	 */
	#[Route('/edit/{idMealModel}', name: 'model_meal_edit', methods: ['GET'], requirements: ['idMealModel' => '\d+'])]
	public function edit(Request $request, MealUtil $mealUtil, ?int $idMealModel): Response
	{
		// Nettoyage de la session pour repartir à zéro
		$mealUtil->removeMealsSession();
		$request->getSession()->set('_meal_day_date', 'model');

		// Redirection vers l'ajout avec l'id du modèle existant
		return $this->redirectToRoute('meal_day_add', [
			'idMealModel' => $idMealModel,
		]);
	}

	/**
	 * Liste tous les repas type de l'utilisateur, avec filtres et tri optionnels.
	 * Gère également l'affichage via AJAX.
	 *
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer les modèles
	 * @param Request $request Requête HTTP contenant les filtres GET
	 * 
	 * @return Response Vue Twig ou partial AJAX
	 */
	#[Route('/list', name: 'model_meal_list', methods: ['GET'], options: ['expose' => true])]
	public function list(MealModelRepository $mealModelRepository, Request $request): Response
	{
		// Vérification de l'authentification
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		// Récupération des filtres depuis la requête GET
		$filters = [
			'minCalories' => $request->query->getInt('minCalories'),
			'maxCalories' => $request->query->getInt('maxCalories'),
			'search'      => $request->query->get('search'),
			'types'       => $request->query->all('types'),
			'sort'        => $request->query->get('sort', 'asc'),
		];

		// Recherche des repas type filtrés
		$modelMeals = $mealModelRepository->findFilteredByUser($filters);

		// Si requête AJAX, renvoie le partial avec les résultats filtrés
		if ($request->isXmlHttpRequest()) {
			return $this->render('meals/model/_list.html.twig', [
				'modelMeals' => $modelMeals,
			]);
		}

		// Sinon, récupération de tous les modèles de l'utilisateur
		$modelMeals = $mealModelRepository->myFindByUser();

		// Rendu de la page complète
		return $this->render("meals/model/list.html.twig", [
			'modelMeals' => $modelMeals,
			'filterCaloriesForm' => $this->createForm(MealFilterType::class)->createView(),
		]);
	}

	/**
	 * Vérifie si un repas type contient du gluten et/ou du lactose.
	 *
	 * @param int $idModelMeal Id du repas type à analyser
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer le modèle
	 * @param MealUtil $mealUtil Service pour analyser le repas
	 * 
	 * @return Response Partial Twig avec les résultats
	 */
	#[Route('/contains-gluten-lactose/{idModelMeal}', name: 'model_meal_have_gluten_lactose', methods: ['GET'])]
	public function containsGlutenAndLactose(
		int $idModelMeal,
		MealModelRepository $mealModelRepository,
		MealUtil $mealUtil
	): Response {

		$mealModel = $mealModelRepository->findOneById($idModelMeal);
		// Récupère l'entité MealModel correspondant à l'ID fourni. 
		// $mealModel contient toutes les informations sur le repas type, y compris les plats et aliments.

		return $this->render('meals/partials/_have_gluten_lactose.html.twig', [
			'results' => $mealUtil->checkGlutenAndLactose($mealModel)
			// Passe le repas type au service MealUtil pour déterminer la présence de gluten et lactose.
			// Le résultat est un tableau ou objet contenant ces informations, utilisé par le partial Twig.
		]);
	}

	/**
	 * Récupère tous les repas types de l'utilisateur et les affiche dans un modal.
	 *
	 * - Utilise le repository pour obtenir uniquement les repas types appartenant à l'utilisateur courant
	 * - Prépare le formulaire de filtrage par calories
	 * - Rend le partial Twig qui affichera la liste dans un modal
	 *
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer les modèles
	 * 
	 * @return Response Vue Twig partielle pour modal
	 */
	#[Route('/list-model-meal-modal', name: 'model_meal_list_modal', methods: ['GET'], options: ['expose' => true])]
	public function listModelMealModal(MealModelRepository $mealModelRepository)
	{
		$modelMeals = $mealModelRepository->myFindByUser();
		// Récupère tous les repas types appartenant à l'utilisateur connecté.
		// myFindByUser() est une méthode custom du repository qui filtre par utilisateur courant.

		return $this->render("meals/model/_wrapper_list.html.twig", [
			'modelMeals' => $modelMeals,
			// Passe la liste des repas types récupérés à Twig pour affichage
			'filterCaloriesForm' => $this->createForm(MealFilterType::class)->createView(),
			// Crée un formulaire pour filtrer les repas par calories et le passe à Twig
			'listModal' => true,
			// Indique à Twig que la liste sera affichée dans un modal (affichage spécifique)
		]);
	}

	/**
	 * Affiche la liste des groupes alimentaires principaux (FGP) pour un repas type donné.
	 *
	 * - Récupère la liste des groupes alimentaires présents dans le repas
	 * - Récupère tous les groupes alimentaires principaux pour affichage
	 * - Passe également la taille pour l'affichage des pastilles/étiquettes
	 *
	 * @param Request $request Requête HTTP
	 * @param MealUtil $mealUtil Service pour analyser les repas
	 * @param FoodGroupParentRepository $foodGroupParentRepository Répository des groupes alimentaires
	 * @param EntityManagerInterface $manager Gestionnaire d'entités
	 * @param MealModel $meal Modèle de repas
	 * @param int $sizeTabletColorFgp Taille pour l'affichage des couleurs
	 * 
	 * @return Response Vue Twig partielle affichant la liste des groupes alimentaires
	 */
	#[Route('/listfgp/{id}', name: 'model_meal_list_fgp', methods: ['GET'], requirements: ['id' => '\d+'], options: ['expose' => true])]
	public function getListFgp(
		MealUtil $mealUtil,
		FoodGroupParentRepository $foodGroupParentRepository,
		MealModel $meal,
		int $sizeTabletColorFgp = 5
	): Response {
		return $this->render(
			"meals/partials/_list_fgp.html.twig",
			[
				// Liste des groupes alimentaires présents dans le repas (via MealUtil)
				"listFgp" => $mealUtil->getListfgp($meal->getDishAndFoods()),

				// Tous les groupes alimentaires principaux pour affichage et comparaison
				"foodGroupParents" => $foodGroupParentRepository->findByIsPrincipal(1),

				// Taille des pastilles ou badges à afficher dans le template
				"size" => $sizeTabletColorFgp
			]
		);
	}

	/**
	 * Met à jour l'énergie de tous les repas type en une seule fois.
	 *
	 * @param MealModelRepository $mealModelRepository Répository pour récupérer tous les modèles
	 * @param MealUtil $mealUtil Service pour calculer l'énergie
	 * @param EntityManagerInterface $entityManager Gestionnaire d'entités
	 * 
	 * @return Response Confirmation du succès
	 */
	#[Route('/update-energy', name: 'model_meal_update_energy_one_shot', methods: ['GET'])]
	public function updateEnergyOneShot(
		MealModelRepository $mealModelRepository,
		MealUtil $mealUtil,
		EntityManagerInterface $entityManager
	): Response {
		$meals = $mealModelRepository->findAll();

		// Recalcul de l'énergie pour chaque repas type
		foreach ($meals as $meal) {
			$meal->setEnergy($mealUtil->getEnergy($meal));
		}

		$entityManager->flush();

		return new Response('Energy repas type modifiée');
	}
}
