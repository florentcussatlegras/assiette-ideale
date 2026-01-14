<?php

namespace App\Controller\meal;

use App\Repository\FoodGroupParentRepository;
use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\User;
use App\Entity\TypeMeal;
use App\Entity\MealModel;
use App\Service\MealUtil;
use App\Service\AlertFeature;
use App\Form\Type\ParameterType;
use App\Service\QuantityTreatment;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\FoodGroup\FoodGroupParent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\MealModelRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Florent Cussatlegras <florent.cussatlegras@gmail.com>
 */

#[Route('/mes-repas-type')]
class ModelController extends AbstractController
{
	#[Route('/add', name: 'model_meal_add', methods: ['POST'], options: ['expose' => true])]
	public function add(Request $request, EntityManagerInterface $manager, TokenStorageInterface $tokenStorageInterface)
	{
		$session = $request->getSession();

		$rankMeal = $request->request->get('rankMeal');
		$name = $request->request->get('name');

		$type = $session->get('_meal_day_' . $rankMeal)['type'];
		$dishAndFood = $session->get('_meal_day_' . $rankMeal)['dishAndFoods'];
		$typeMeal = $manager->getRepository(TypeMeal::class)->findOneByBackName($type);
		
		$mealModel = new MealModel($name, $typeMeal, $dishAndFood, $tokenStorageInterface->getToken()->getUser());

		$manager->persist($mealModel);
		$manager->flush();

		$this->addFlash('info', 'Votre repas a bien été sauvegardé');

		if($request->query->has('meal_list')){
			return $this->redirectToRoute('model_meal_list');
		}

		return $this->redirectToRoute('meal_day');
	}

	#[Route('/remove/{id?}', name: 'model_meal_remove')]
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

	#[Route('/new', name: 'model_meal_new')]
	public function new(Request $request, MealUtil $mealUtil)
	{
		$mealUtil->removeMealsSession();
		$request->getSession()->set('_meal_day_date', 'model');

		return $this->redirectToRoute('meal_day_add');
	}

	#[Route('/list', name: 'model_meal_list', options: ['expose' => true])]
	public function list(MealModelRepository $mealModelRepository)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		return $this->render("meals/model/list.html.twig", [
		    		    // "modelMeals" => $manager->getRepository(MealModel::class)->myFindByUserGroupByType()
		    		    "modelMeals" => $mealModelRepository->myFindByUser()
		    ]
        );
	}

	#[Route('/list-modal-meal-type', name: 'modal_meal_type_list', options: ['expose' => true])]
	public function listModalMealType(MealModelRepository $mealModelRepository)
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		return $this->render("meals/day/_list_model_meals.html.twig", [
		    		    "modelMeals" => $mealModelRepository->myFindByUser()
		    ]
        );
	}

	#[Route('/listfgp/{id}', name: 'model_meal_list_fgp', options: ['expose' => true])]
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
}