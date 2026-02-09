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


#[Route('/mes-repas-type')]
class ModelController extends AbstractController
{
	#[Route('/add/{idMealModel?}', name: 'model_meal_add', methods: ['POST'], requirements: ['idMealModel' => '\d+'], options: ['expose' => true])]
	public function add(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorageInterface, MealUtil $mealUtil, MealModelRepository $mealModelRepository, ?int $idMealModel)
	{
		$session = $request->getSession();

		$rankMeal = $request->request->get('rankMeal');
		$name = $request->request->get('name');

		$type = $session->get('_meal_day_' . $rankMeal)['type'];
		$dishAndFood = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'];
		$typeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($type);
		

		$mealModel = new MealModel($name, $typeMeal, $dishAndFood, $tokenStorageInterface->getToken()->getUser());
		$mealModel->setEnergy($mealUtil->getEnergy($mealModel));

		$manager->persist($mealModel);

		if($idMealModel) {
			$oldMealModelToRemove = $mealModelRepository->findOneById($idMealModel);
			$manager->remove($oldMealModelToRemove);
		}

		$manager->flush();

		$this->addFlash('info', 'Votre repas a bien été sauvegardé');

		if($request->query->has('meal_list')){
			return $this->redirectToRoute('model_meal_list');
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/remove/{id?}', name: 'model_meal_remove', methods: ['GET'], requirements: ['id' => '\d+'])]
	public function remove(EntityManagerInterface $manager, Request $request, MealModelRepository $mealModelRepository, ?MealModel $mealModel)
	{
		$submittedToken = $request->query->get('_token');

		if (!$this->isCsrfTokenValid('delete_meal_' . $mealModel->getId(), $submittedToken)) {
			return $this->json(['error' => 'Token CSRF invalide'], 400);
		}

		$manager->remove($mealModel);
		$manager->flush();

		if($request->query->get('ajax')) {
			return $this->render('meals/model/_list.html.twig', [
				"modelMeals" => $mealModelRepository->myFindByUser(),
			]);
		}

		$this->addFlash('info', 'Le repas a bien été supprimé');

		return $this->redirectToRoute('model_meal_list');
	}

	#[Route('/new', name: 'model_meal_new', methods: ['GET'])]
	public function new(Request $request, MealUtil $mealUtil)
	{
		$mealUtil->removeMealsSession();
		$request->getSession()->set('_meal_day_date', 'model');

		return $this->redirectToRoute('meal_day_add');
	}

	#[Route('/edit/{idMealModel}', name: 'model_meal_edit', methods: ['GET'], requirements: ['idMealModel' => '\d+'])]
	public function edit(Request $request, MealUtil $mealUtil, ?int $idMealModel)
	{
		$mealUtil->removeMealsSession();
		$request->getSession()->set('_meal_day_date', 'model');

		return $this->redirectToRoute('meal_day_add', [
			'idMealModel' => $idMealModel,
		]);
	}

	#[Route('/list', name: 'model_meal_list', methods: ['GET'], options: ['expose' => true])]
	public function list(MealModelRepository $mealModelRepository, Request $request)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		$filters = [
			'minCalories' => $request->query->getInt('minCalories'),
			'maxCalories' => $request->query->getInt('maxCalories'),
			'search'      => $request->query->get('search'),
			'types'       => $request->query->all('types'),
			'sort'        => $request->query->get('sort', 'asc'),
		];

		$modelMeals = $mealModelRepository->findFilteredByUser($filters);

		// appel AJAX
		if ($request->isXmlHttpRequest()) {
			return $this->render('meals/model/_list.html.twig', [
				'modelMeals' => $modelMeals,
			]);
		}

		$modelMeals = $mealModelRepository->myFindByUser();

		return $this->render("meals/model/list.html.twig", [
				'modelMeals' => $modelMeals,
				'filterCaloriesForm' => $this->createForm(MealFilterType::class)->createView(),
		    ]
        );
	}

	#[Route('/list-model-meal-modal', name: 'model_meal_list_modal', methods: ['GET'], options: ['expose' => true])]
	public function listModelMealModal(MealModelRepository $mealModelRepository)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		$modelMeals = $mealModelRepository->myFindByUser();

		return $this->render("meals/model/_wrapper_list.html.twig", [
				'modelMeals' => $modelMeals,
				'filterCaloriesForm' => $this->createForm(MealFilterType::class)->createView(),
				'listModal' => true,
		    ]
        );
	}

	#[Route('/listfgp/{id}', name: 'model_meal_list_fgp', methods: ['GET'], requirements: ['id' => '\d+'], options: ['expose' => true])]
	public function getListFgp(Request $request, MealUtil $mealUtil, FoodGroupParentRepository $foodGroupParentRepository, 
				EntityManagerInterface $manager, MealModel $meal, int $sizeTabletColorFgp = 5)
	{
		return $this->render("meals/partials/_list_fgp.html.twig", 
			[
			    "listFgp" => $mealUtil->getListfgp($meal->getDishAndFoods()),
				"foodGroupParents" => $foodGroupParentRepository->findByIsPrincipal(1),
				"size" => $sizeTabletColorFgp
			]
		);
	}

	#[Route('/update-energy', name: 'model_meal_update_energy_one_shot', methods: ['GET'])]
	public function updateEnergyOneShot(MealModelRepository $mealModelRepository, MealUtil $mealUtil, EntityManagerInterface $entityManager)
	{
		$meals = $mealModelRepository->findAll();

		foreach ($meals as $meal) {
			$meal->setEnergy($mealUtil->getEnergy($meal));
		}

		$entityManager->flush();

		return new Response('Energy repas type modifiée');
	}
}