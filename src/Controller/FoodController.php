<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\Food;
use App\Form\Type\FoodType;
use App\Service\UploaderHelper;
use App\Entity\NutritionalTable;
use App\Service\QuantityHandler;
use App\Repository\FoodRepository;
use App\Entity\FoodGroup\FoodGroup;
use App\Service\SessionFoodHandler;
use Symfony\UX\Chartjs\Model\Chart;
use Algolia\SearchBundle\SearchService;
use App\Form\Type\QuantityFoodFormType;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use ACSEO\TypesenseBundle\Finder\TypesenseQuery;
use App\Repository\DietRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

#[Route('/aliments')]
class FoodController extends AbstractController
{
	#[Route('/{dish_id?}', name: 'app_food_list')]
	#[Entity('dish', options: ['id' => 'dish_id'])]
    public function list(SessionInterface $session, Request $request, FoodGroupRepository $foodGroupRepository, FoodRepository $foodRepository, 
						UnitMeasureRepository $unitMeasureRepository, QuantityHandler $quantityHandler, ?Dish $dish,
						EntityManagerInterface $em, SearchService $searchService
	)
    {
		// $foods = $searchService->search($em, Food::class, 'dev_foods_query_suggestions');
	
		$fg = !empty($request->query->get('fg')) ? $request->query->all()['fg'] : [];

		if(empty($fg) && $request->query->get('ajax')) {

            if($request->query->get('ajax')) {
                return $this->render('food/_food_list.html.twig', [
					'foods' => null,
					'lastResults' => true,
				]);
            }

		}

        $keyword = !empty($request->query->get('q')) ? $request->query->get('q') : null;
        $page = !empty($request->query->get('page')) ? $request->query->get('page') : 0;
		$freeGluten = !empty($request->query->get('freeGluten')) ? $request->query->get('freeGluten') : false;
		$freeLactose = !empty($request->query->get('freeLactose')) ? $request->query->get('freeLactose') : false;
		
		$limit = 12;
		$lastResults = false;
		
		if("none" !== $fg) {
			$allFoods = $foodRepository->myFindByKeywordAndFGAndLactoseAndGluten(
				$keyword,
				$fg,
				$freeLactose,
				$freeGluten
			);
			// exit();
			$offset = $page * $limit;
			$foods = array_slice($allFoods, $offset, $limit);
			if(count($foods) < 10) {
				$lastResults = true;
			}
			if(10 === count($foods)) {
				$lastFoods = array_pop($foods);
				$lastAllFoods = array_pop($allFoods);
				if($lastFoods->getId() == $lastAllFoods->getId()) {
					$lastResults = true;
				}
			}
		}else{
			$lastResults = true;
			$foods = [];
		}

		// $foodGroupsSelected = !$request->query->has('food_groups') ? $foodGroupRepository->myFindAllIds() : $request->query->get('food_groups');

		// $searchTerm = $request->query->get('q');
		// $foods = $foodRepository->search(
		// 	$foodGroupsSelected,
		// 	$searchTerm
		// );

		if($request->query->get('ajax')) {
			return $this->render('food/_food_list.html.twig', [
				'foods' => $foods,
				'lastResults' => $lastResults,
			]);
		}

        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

		// $foodSubFoodGroupList = $foodRepository->findBy(['foodGroup' => $foodGroup, 'isSubFoodGroup' => true]);

        // return $this->render('food/list.html.twig', [
		// 	'foodGroup' => $foodGroup,
        //     'foods' => $foodRepository->findBy(['foodGroup' => $foodGroup]),
		// 	'dish' => $dish,
		// 	'foodSubFoodGroupList' => $foodSubFoodGroupList,
		// 	'unitMeasures' => $quantityConverter->getUnitMeasureList()
		// ]);
		// dd($foodGroupRepository->findAll());

		// if($this->foodGroupAlias) {
        //     $foodGroup = $this->foodGroupRepository->findByAlias($this->foodGroupAlias);
        //     if(empty($this->query)) {
        //         return $this->foodRepository->findBy(['foodGroup' => $foodGroup]);
        //     }

        //     return $this->foodRepository->myFindByKeywordAndFg($this->query, $this->foodGroupAlias);
        // }

		return $this->render('food/list.html.twig', [
			'foodGroupsSelected' => $fg ?? null,
			'unique_fg' 		 => $request->query->get('unique_fg', false),
			'foodGroupName'      => ($request->query->has('unique_fg') && $request->query->get('unique_fg') == true) ? $foodGroupRepository->findOneById($fg) : null,
			'foods'              => $foods,
			'unitMeasures'       => $unitMeasureRepository->findAll(),
			'foodGroups'         => $foodGroupRepository->findAll(),
			'lastResults'        => $lastResults,
		]);
    }
	

	#[Route('/foodgroup/list/{foodGroupSelected?}', name: 'app_food_foodgroup_list')]
	public function getFoodGroupList(EntityManagerInterface $manager, Request $request, ?int $foodGroupSelected)
	{
		return $this->render('/food/_foodgroup_list.html.twig', [
			'foodGroups' => $manager->getRepository(FoodGroup::class)->findAll([], ['sort' => 'order']),
			'foodGroupSelected' => $foodGroupSelected
		]);
	}

	#[Route('/show/{slug}', name: 'app_food_show')]
	public function show(Food $food, ChartBuilderInterface $chartBuilder, SerializerInterface $serializer, TranslatorInterface $translator)
	{
		$chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

		$nutritionalTable = $food->getNutritionalTable();

		$datasChart = $serializer->normalize($nutritionalTable, null, ['groups' => 'group_chart']);

		$nutrientLabels = array_keys($datasChart);

		$nutrientsTypeColors = NutritionalTable::NUTRIENTS_TYPE_COLORS;

		$colors = array_map(
			function($nutritionLabel) use($nutrientsTypeColors){ 
				return $nutrientsTypeColors[$nutritionLabel]; 
			}
		, $nutrientLabels);

		$chart->setData([
            'labels' => array_map(function($nutrientLabel) use($translator) { return ucFirst($translator->trans($nutrientLabel, domain: 'nutrient')); }, $nutrientLabels),
            'datasets' => [
                [
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'data' => array_map(
						function($nutritionLabel) use($datasChart){ 
							return $datasChart[$nutritionLabel]; 
						}
					, $nutrientLabels),
                ],
            ],
			'weight' => 250,
        ]);

		$values = array_map(fn($label) => $datasChart[$label], $nutrientLabels);
		$allZero = count(array_filter($values)) === 0; // true si toutes les valeurs sont 0

		return $this->render('food/show.html.twig', [
			'food' => $food,
			'chart' => $chart,
			'chartAllZero' => $allZero,
		]);
	}

	#[Route('/add/{slug?}', name: 'app_food_add')]
	public function add(?string $slug, Request $request, EntityManagerInterface $manager, UploaderHelper $uploaderHelper)
	{
		// $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

		if($slug) {
			if (null === $food = $manager->getRepository(Food::class)->findOneBySlug($slug)) {
				throw $this->createNotFoundException('The food does not exist');
			}
		}else{
			$food = new Food();
		}
		$form = $this->createForm(FoodType::class, $food);

		$form->handleRequest($request);

		if($form->isSubmitted() && $form->isvalid()) {

			if(null !== $pictureFile = $form->get('pictureFile')->getData()) {
				$pictureName = $uploaderHelper->upload($pictureFile, UploaderHelper::FOOD);
				$food->setPicture($pictureName);
			}

			$manager->persist($food);
			$manager->flush();

			!$food->getId() ? $this->addFlash('notice', 'L\'aliment a été ajouté') : $this->addFlash('notice', 'L\'aliment a été modifié');

			return $this->redirectToRoute('app_food_show', [
				'slug' => $food->getSlug()
			]);
		}

		return $this->render('food/add.html.twig', [
			'form' => $form->createView()
		]);
	}

	#[Route('/search', name: 'app_food_search', priority: 2)]
	public function search(SearchService $searchService, EntityManagerInterface $manager): Response
	{
		return $this->render('food/search.html.twig');
	}

	public function renderQuantityForm(RouterInterface $router)
	{
		$quantityFoodForm = $this->createForm(QuantityFoodFormType::class, null, [
			'action' => $router->generate('food_add_dish_session')
		]);

		return $this->render('dish/_form_quantity_food.html.twig', [
				'quantityFoodForm' => $quantityFoodForm->createView()
		]);
	}

	/**
	 * @Route("/add-dish-sesssion", name="app_food_add_dish_session")
	 */
	public function addDishSession(FoodRepository $foodRepository, SessionFoodHandler $sessionFoodHandler, Request $request)
	{
		$quantitiesFood = $request->request->all()['quantity_food_form'];
		$token = $quantitiesFood['_token'];
		$foodId = $quantitiesFood['foodId'];
		$food = $foodRepository->findOneBy(['id' => (int)$foodId]);
		$foodGroupCode = $food->getFoodGroup()->getAlias();
		$quantity = $quantitiesFood['quantity'];
		$unitMeasureId = $quantitiesFood['unitMeasure'];

		$request = [
			'_token' => $token,
			'food_group_code' => $foodGroupCode,
			'foods' => [
				$foodId => [
					'quantity' => $quantity,
					'unit_measure' => $unitMeasureId
				]
			]
		];

		$sessionFoodHandler->addFromFoodList($request);

		return $this->redirectToRoute('app_dish_new');
	}

	// /**
	//  * @Route("/search", name="search_food", defaults={"_format"="json"}, methods={"GET"})
	//  */
	// public function search(Request $request, FoodRepository $foodRepository)
	// {
	//     $q = $request->query->get('term');
	//     $results = $foodRepository->myFindByKeyword($q);

	//     return $this->render("food/search.json.twig", ['foods' => $results]);
	// }

	// /**
    //  * @Route("/create-nutrition")
    //  */
	// public function createNutrition(EntityManagerInterface $manager)
	// {
	// 	$foods = $manager->getRepository(Food::class)->findAll();

	// 	foreach($foods as $food) {
	// 		$nutritionalTable = new NutritionalTable();
	// 		$nutritionalTable->setEnergy($food->getEnergy());
	// 		$nutritionalTable->setCarbohydrate($food->getCarbohydrate());
	// 		$nutritionalTable->setLipid($food->getLipid());
	// 		$nutritionalTable->setProtein($food->getProtein());
	// 		$nutritionalTable->setNutriscore('A');

	// 		$manager->persist($nutritionalTable);

	// 		$food->setNutritionalTable($nutritionalTable);
	// 	}

	// 	$manager->flush();
		
	// 	return new Response('Aliment modifié');
	// }

	// private $foodFinder;

	// public function __construct($foodFinder)
	// {
	// 	$this->foodFinder = $foodFinder;
	// }

	/**
	 * @Route("/remove-sfg", name="app_food_remove-sfg")
	 */
	public function removeSfg(EntityManagerInterface $em)
	{
		foreach($em->getRepository(Food::class)->findAll() as $food)
		{
			$food->setIsSubFoodGroup(false);
			$food->setSubFoodGroup(null);
		}

		$em->flush();

		return new Response('Food modified');
	}

	/**
	 * @Route("/gluten/update", name="app_food_set_gluten")
	 */
	public function setDietGluten(FoodRepository $foodRepository, DietRepository $dietRepository, EntityManagerInterface $em)
	{
		$dietNoGluten = $dietRepository->findOneById(39);

		foreach($foodRepository->findAll() as $food) {
			if($food->getHaveGluten()) {
				$food->addForbiddenDiet($dietNoGluten);
				$em->persist($food);
			}
		}

		$em->flush();

		return new Response('diet gluten ok');
	}

	/**
	 * @Route("/lactose/update", name="app_food_set_lactose")
	 */
	public function setDietLactose(FoodRepository $foodRepository, DietRepository $dietRepository, EntityManagerInterface $em)
	{
		$dietNoLactose = $dietRepository->findOneById(40);

		foreach($foodRepository->findAll() as $food) {
			if($food->getHaveLactose()) {
				$food->addForbiddenDiet($dietNoLactose);
				$em->persist($food);
			}
		}

		$em->flush();

		return new Response('diet lactose ok');
	}

}