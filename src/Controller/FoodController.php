<?php

namespace App\Controller;

use App\Entity\Food;
use App\Form\Type\FoodType;
use App\Service\UploaderHelper;
use App\Entity\NutritionalTable;
use App\Repository\FoodRepository;
use App\Entity\FoodGroup\FoodGroup;
use App\Service\SessionFoodHandler;
use Symfony\UX\Chartjs\Model\Chart;
use App\Form\Type\QuantityFoodFormType;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\DietRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * FoodController
 * 
 * Contrôleur pour la gestion de la liste des aliments et leur affichage dans le système.
 * 
 * Responsabilités principales :
 * - Afficher la liste des aliments avec filtres :
 *      - Par groupe alimentaire (optionnel)
 *      - Par mot-clé (recherche)
 *      - Par restrictions alimentaires (sans gluten, sans lactose)
 *      - Pagination
 * - Gérer l'affichage AJAX pour des listes partielles
 * - Fournir les unités de mesure et les groupes alimentaires disponibles pour le template
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
#[Route('/aliments')]
class FoodController extends AbstractController
{
	/**
	 * Affiche la liste des aliments, avec filtrage par groupe, mot-clé et restrictions alimentaires.
	 * 
	 * @param Request $request Requête HTTP contenant les filtres (fg, q, page, freeGluten, freeLactose, unique_fg)
	 * @param FoodGroupRepository $foodGroupRepository Pour récupérer les groupes alimentaires
	 * @param FoodRepository $foodRepository Pour récupérer les aliments filtrés
	 * @param UnitMeasureRepository $unitMeasureRepository Pour récupérer les unités de mesure
	 * 
	 * @return Response Rendu HTML de la liste d’aliments ou rendu partiel pour AJAX
	 */
	#[Route('/{dish_id?}', name: 'app_food_list', methods: ['GET'], requirements: ['dish_id' => '\d+'])]
	#[Entity('dish', options: ['id' => 'dish_id'])]
	public function list(
		Request $request,
		FoodGroupRepository $foodGroupRepository,
		FoodRepository $foodRepository,
		UnitMeasureRepository $unitMeasureRepository,
	): Response {
		// Récupération du ou des groupes alimentaires sélectionnés depuis la requête GET
		// Si le paramètre 'fg' est vide, on initialise un tableau vide
		$fg = !empty($request->query->get('fg')) ? $request->query->all()['fg'] : [];

		// Cas particulier : si aucun groupe n'est sélectionné et qu'il s'agit d'une requête AJAX
		// On renvoie directement une vue partielle vide avec le flag 'lastResults' à true
		if (empty($fg) && $request->query->get('ajax')) {
			if ($request->query->get('ajax')) {
				return $this->render('food/_food_list.html.twig', [
					'foods' => null,         // Pas de résultats à afficher
					'lastResults' => true,   // Indique que la liste est complète (pas de page suivante)
				]);
			}
		}

		// Récupération des autres paramètres de filtrage de la requête
		$keyword     = !empty($request->query->get('q')) ? $request->query->get('q') : null;      // Mot-clé de recherche
		$page        = !empty($request->query->get('page')) ? $request->query->get('page') : 0;   // Numéro de page pour la pagination
		$freeGluten  = !empty($request->query->get('freeGluten')) ? $request->query->get('freeGluten') : false; // Filtre sans gluten
		$freeLactose = !empty($request->query->get('freeLactose')) ? $request->query->get('freeLactose') : false; // Filtre sans lactose

		$limit       = 12;      // Nombre maximum d'aliments par page
		$lastResults = false;   // Flag pour savoir si nous avons atteint la dernière page

		// Si un ou plusieurs groupes alimentaires sont sélectionnés
		if ("none" !== $fg) {
			$offset = $page * $limit;  // Calcul de l'offset pour la pagination

			// Recherche des aliments correspondant aux filtres (mot-clé, groupe, lactose/gluten)
			$foods = $foodRepository->myFindByKeywordAndFGAndLactoseAndGluten(
				$keyword,
				$fg,
				$freeLactose,
				$freeGluten,
				$limit,
				$offset
			);

			// Gestion de la pagination : on vérifie si on a plus que la limite pour savoir s'il reste des pages
			if (count($foods) > $limit) {
				$lastResults = false;   // Il reste encore des résultats
				array_pop($foods);      // On retire le 13e élément pour respecter la limite d'affichage
			} else {
				$lastResults = true;    // Plus de résultats après cette page
			}
		} else {
			// Aucun groupe sélectionné : pas de résultats et fin des résultats
			$lastResults = true;
			$foods = [];
		}

		// Si la requête est en AJAX, on renvoie uniquement la vue partielle des aliments
		if ($request->query->get('ajax')) {
			return $this->render('food/_food_list.html.twig', [
				'foods' => $foods,
				'lastResults' => $lastResults,
			]);
		}

		// Sinon, on renvoie la page complète avec toutes les données nécessaires pour le template
		return $this->render('food/list.html.twig', [
			'foodGroupsSelected' => $fg ?? null,  // Groupes sélectionnés pour le filtre
			'unique_fg'         => $request->query->get('unique_fg', false), // Flag pour afficher un seul groupe
			'foodGroupName'     => ($request->query->has('unique_fg') && $request->query->get('unique_fg') == true) ? $foodGroupRepository->findOneById($fg) : null, // Nom du groupe si unique
			'foods'             => $foods,       // Liste finale d'aliments à afficher
			'unitMeasures'      => $unitMeasureRepository->findAll(), // Liste des unités de mesure
			'foodGroups'        => $foodGroupRepository->findAll(),  // Liste complète des groupes alimentaires
			'lastResults'       => $lastResults, // Flag indiquant s'il reste des résultats pour la pagination
		]);
	}


	/**
	 * Récupère et affiche la liste des groupes alimentaires.
	 * 
	 * Cette méthode sert à générer un template partiel contenant tous les groupes alimentaires
	 * disponibles dans la base de données, triés selon le champ 'order'. Elle peut également
	 * indiquer quel groupe est actuellement sélectionné pour l'interface utilisateur.
	 * 
	 * @param EntityManagerInterface $manager Service Doctrine pour accéder aux entités
	 * @param Request $request Requête HTTP entrante (non utilisée directement ici, mais disponible pour extensions)
	 * @param int|null $foodGroupSelected ID optionnel du groupe alimentaire sélectionné
	 * 
	 * @return Response Rendu du template partiel _foodgroup_list.html.twig avec :
	 *                  - 'foodGroups' : liste complète des groupes alimentaires
	 *                  - 'foodGroupSelected' : ID du groupe sélectionné ou null
	 */
	#[Route('/foodgroup/list/{foodGroupSelected?}', name: 'app_food_foodgroup_list', methods: ['GET'], requirements: ['foodGroupSelected' => '\d+'])]
	public function getFoodGroupList(EntityManagerInterface $manager, Request $request, ?int $foodGroupSelected): Response
	{
		// On récupère tous les groupes alimentaires depuis la base de données
		// Le tri se fait sur le champ 'order' si disponible
		$foodGroups = $manager->getRepository(FoodGroup::class)->findAll([], ['sort' => 'order']);

		// On passe à la vue le groupe éventuellement sélectionné (peut être null si aucun)
		// Utile pour pré-sélectionner un groupe dans l'interface ou pour filtrer
		return $this->render('/food/_foodgroup_list.html.twig', [
			'foodGroups' => $foodGroups,
			'foodGroupSelected' => $foodGroupSelected,
		]);
	}

	/**
	 * Affiche la page détaillée d'un aliment et génère son graphique nutritionnel.
	 * 
	 * @param string $slug Slug de l'aliment à afficher
	 * @param FoodRepository $foodRepository Repository pour accéder aux aliments
	 * @param ChartBuilderInterface $chartBuilder Service pour construire les graphiques
	 * @param SerializerInterface $serializer Service pour normaliser les données nutritionnelles
	 * @param TranslatorInterface $translator Service pour traduire les labels des nutriments
	 * 
	 * @return Response Rendu de la page détaillée de l'aliment avec son graphique nutritionnel
	 * 
	 * @throws NotFoundHttpException Si aucun aliment ne correspond au slug fourni
	 */
	#[Route('/show/{slug}', name: 'app_food_show', methods: ['GET'], requirements: ['slug' => '[a-zA-Z0-9\-]+'])]
	public function show(
		string $slug,
		FoodRepository $foodRepository,
		ChartBuilderInterface $chartBuilder,
		SerializerInterface $serializer,
		TranslatorInterface $translator
	): Response {

		// On récupère l'aliment correspondant au slug fourni
		$food = $foodRepository->findOneBy(['slug' => $slug]);

		// Si l'aliment n'existe pas, on renvoie une exception 404
		if (!$food) {
			throw $this->createNotFoundException('Aliment introuvable.');
		}

		// Création d'un graphique de type "doughnut" (camembert circulaire)
		$chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

		// Récupération du tableau nutritionnel de l'aliment
		$nutritionalTable = $food->getNutritionalTable();

		// Normalisation des données pour le graphique (format exploitable par Chart.js)
		$datasChart = $serializer->normalize($nutritionalTable, null, ['groups' => 'group_chart']);

		// Récupération des noms de nutriments pour les labels du graphique
		$nutrientLabels = array_keys($datasChart);

		// Tableau des couleurs pour chaque type de nutriment
		$nutrientsTypeColors = NutritionalTable::NUTRIENTS_TYPE_COLORS;

		// Attribution des couleurs correspondantes à chaque nutriment
		$colors = array_map(
			function ($nutritionLabel) use ($nutrientsTypeColors) {
				return $nutrientsTypeColors[$nutritionLabel];
			},
			$nutrientLabels
		);

		// Calcul de la somme totale des nutriments pour calculer les pourcentages
		$total = array_sum($datasChart);

		// Calcul des pourcentages de chaque nutriment par rapport au total
		$percentages = array_map(function ($label) use ($datasChart, $total) {
			return $total > 0 ? round(($datasChart[$label] / $total) * 100, 1) : 0;
		}, $nutrientLabels);

		// Configuration des données du graphique Chart.js
		$chart->setData([
			'labels' => array_map(
				function ($nutrientLabel, $percentage) use ($translator) {
					// Traduction du nom du nutriment et ajout du pourcentage
					return ucfirst($translator->trans($nutrientLabel, domain: 'nutrient')) . ': ' . $percentage . '%';
				},
				$nutrientLabels,
				$percentages
			),
			'datasets' => [
				[
					'backgroundColor' => $colors, // Couleur des sections
					'borderColor' => $colors,     // Couleur des bordures
					'data' => array_map(
						function ($nutritionLabel) use ($datasChart) {
							return $datasChart[$nutritionLabel]; // Valeurs brutes pour le graphique
						},
						$nutrientLabels
					),
				],
			],
			'weight' => 250, // Épaisseur du graphique (option Chart.js)
		]);

		// Options supplémentaires du graphique
		$chart->setOptions([
			'plugins' => [
				'legend' => [
					'labels' => [
						'usePointStyle' => true, // Affichage des points sous forme de cercle
						'pointStyle' => 'circle',
					]
				]
			]
		]);

		// Vérification si toutes les valeurs nutritionnelles sont nulles
		$values = array_map(fn($label) => $datasChart[$label], $nutrientLabels);
		$allZero = count(array_filter($values)) === 0; // true si toutes les valeurs sont 0

		// Rendu du template avec l'aliment, le graphique et l'indicateur "tout zéro"
		return $this->render('food/show.html.twig', [
			'food' => $food,
			'chart' => $chart,
			'chartAllZero' => $allZero,
		]);
	}

	/**
	 * Ajout ou modification d'un aliment.
	 * 
	 * @param string|null $slug Le slug de l'aliment à modifier (optionnel)
	 * @param Request $request La requête HTTP contenant les données du formulaire
	 * @param EntityManagerInterface $manager Pour gérer la persistance des entités
	 * @param UploaderHelper $uploaderHelper Service pour gérer l'upload des images
	 * 
	 * @return Response Retourne la vue du formulaire ou une redirection après soumission
	 */
	#[Route('/add/{slug?}', name: 'app_food_add', methods: ['GET', 'POST'], requirements: ['slug' => '[a-zA-Z0-9\-]+'])]
	public function add(?string $slug, Request $request, EntityManagerInterface $manager, UploaderHelper $uploaderHelper): Response
	{
		// Vérification si on est en modification (slug fourni)
		if ($slug) {
			// Récupération de l'aliment existant
			if (null === $food = $manager->getRepository(Food::class)->findOneBySlug($slug)) {
				// Si le slug n'existe pas, on renvoie une exception 404
				throw $this->createNotFoundException('The food does not exist');
			}
		} else {
			// Création d'un nouvel aliment si pas de slug
			$food = new Food();
		}

		// Création du formulaire lié à l'objet Food
		$form = $this->createForm(FoodType::class, $food);

		// Traitement de la requête
		$form->handleRequest($request);

		// Vérification que le formulaire a été soumis et est valide
		if ($form->isSubmitted() && $form->isValid()) {

			// Gestion du fichier image si fourni
			if (null !== $pictureFile = $form->get('pictureFile')->getData()) {
				// Upload du fichier et récupération du nom final
				$pictureName = $uploaderHelper->upload($pictureFile, UploaderHelper::FOOD);
				$food->setPicture($pictureName);
			}

			// Persistance de l'aliment en base
			$manager->persist($food);
			$manager->flush();

			// Message flash selon si ajout ou modification
			!$food->getId()
				? $this->addFlash('notice', 'L\'aliment a été ajouté')
				: $this->addFlash('notice', 'L\'aliment a été modifié');

			// Redirection vers la page de détail de l'aliment
			return $this->redirectToRoute('app_food_show', [
				'slug' => $food->getSlug()
			]);
		}

		// Affichage du formulaire si pas soumis ou invalide
		return $this->render('food/add.html.twig', [
			'form' => $form->createView()
		]);
	}

	/**
	 * Affiche la page de recherche des aliments.
	 * 
	 * @return Response Rendu de la vue 'food/search.html.twig'
	 */
	#[Route('/search', name: 'app_food_search', methods: ['GET'], priority: 2)]
	public function search(): Response
	{
		return $this->render('food/search.html.twig');
	}

	/**
	 * Génère et rend le formulaire pour saisir la quantité d'un aliment dans une recette.
	 *
	 * @param RouterInterface $router Service pour générer l'URL de soumission du formulaire
	 * 
	 * @return Response Rendu du formulaire prêt à être inclus dans une vue
	 */
	public function renderQuantityForm(RouterInterface $router): Response
	{
		// Création d'un formulaire pour saisir la quantité d'un aliment.
		$quantityFoodForm = $this->createForm(QuantityFoodFormType::class, null, [
			'action' => $router->generate('food_add_dish_session')
		]);

		// Rendu du template partiel '_form_quantity_food.html.twig' avec le formulaire..
		return $this->render('dish/_form_quantity_food.html.twig', [
			'quantityFoodForm' => $quantityFoodForm->createView()
		]);
	}

	/**
	 * Ajoute un aliment sélectionné à la session du plat en cours de création.
	 *
	 * @param FoodRepository $foodRepository Pour récupérer les entités Food depuis la base
	 * @param SessionFoodHandler $sessionFoodHandler Service gérant les aliments en session
	 * @param Request $request Requête HTTP contenant les données du formulaire
	 *
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirige vers la création d'un nouveau plat
	 */
	#[Route('/add-dish-sesssion', name: 'app_food_add_dish_session', methods: ['POST'])]
	public function addDishSession(FoodRepository $foodRepository, SessionFoodHandler $sessionFoodHandler, Request $request): Response
	{
		// Récupération des données du formulaire envoyé via POST
		$quantitiesFood = $request->request->all()['quantity_food_form'];

		// Extraction du token CSRF pour validation côté serveur
		$token = $quantitiesFood['_token'];

		// Récupération de l'identifiant de l'aliment sélectionné
		$foodId = $quantitiesFood['foodId'];

		// Recherche de l'entité Food correspondante dans la base
		$food = $foodRepository->findOneBy(['id' => (int)$foodId]);

		// Récupération du code du groupe alimentaire de l'aliment
		$foodGroupCode = $food->getFoodGroup()->getAlias();

		// Quantité saisie par l'utilisateur et unité de mesure sélectionnée
		$quantity = $quantitiesFood['quantity'];
		$unitMeasureId = $quantitiesFood['unitMeasure'];

		// Préparation du tableau de données à envoyer au gestionnaire de session
		$requestData = [
			'_token' => $token,
			'food_group_code' => $foodGroupCode,
			'foods' => [
				$foodId => [
					'quantity' => $quantity,
					'unit_measure' => $unitMeasureId
				]
			]
		];

		// Ajout de l'aliment et de sa quantité à la session via le service SessionFoodHandler
		$sessionFoodHandler->addFromFoodList($requestData);

		// Redirection vers la création d'un nouveau plat
		return $this->redirectToRoute('app_dish_new');
	}

	/**
	 * Réinitialise tous les aliments pour qu'ils ne soient plus associés à un sous-groupe.
	 *
	 * Les modifications sont ensuite persistées en base via le EntityManager.
	 *
	 * @param EntityManagerInterface $em Pour accéder aux entités Food et effectuer les modifications
	 *
	 * @return \Symfony\Component\HttpFoundation\Response Retourne une réponse simple confirmant la modification
	 */
	#[Route('/remove-sfg', name: 'app_food_remove-sfg', methods: ['GET'])]
	public function removeSfg(EntityManagerInterface $em): Response
	{
		foreach ($em->getRepository(Food::class)->findAll() as $food) {
			$food->setIsSubFoodGroup(false);
			$food->setSubFoodGroup(null);
		}

		$em->flush();

		return new Response('Food modified');
	}

	/**
	 * Met à jour les aliments pour le régime sans gluten.
	 *
	 * @param FoodRepository $foodRepository Pour récupérer tous les aliments
	 * @param DietRepository $dietRepository Pour récupérer le régime sans gluten
	 * @param EntityManagerInterface $em Pour persister les modifications
	 *
	 * @return Response
	 */
	#[Route('/gluten/update', name: 'app_food_set_gluten', methods: ['GET'])]
	public function setDietGluten(FoodRepository $foodRepository, DietRepository $dietRepository, EntityManagerInterface $em): Response
	{
		// Récupère le régime "sans gluten" identifié par l'ID 39
		$dietNoGluten = $dietRepository->findOneById(39);

		// Parcourt tous les aliments
		foreach ($foodRepository->findAll() as $food) {
			// Si l'aliment contient du gluten
			if ($food->getHaveGluten()) {
				// Ajoute le régime sans gluten comme régime interdit pour cet aliment
				$food->addForbiddenDiet($dietNoGluten);
				// Marque l'entité pour persistance
				$em->persist($food);
			}
		}

		// Enregistre toutes les modifications en base de données
		$em->flush();

		// Retourne une réponse simple confirmant que l'opération est terminée
		return new Response('diet gluten ok');
	}

	/**
	 * Met à jour les aliments pour le régime sans lactose.
	 *
	 * @param FoodRepository $foodRepository Pour récupérer tous les aliments
	 * @param DietRepository $dietRepository Pour récupérer le régime sans lactose
	 * @param EntityManagerInterface $em Pour persister les modifications
	 *
	 * @return Response
	 */
	#[Route('/lactose/update', name: 'app_food_set_lactose', methods: ['GET'])]
	public function setDietLactose(FoodRepository $foodRepository, DietRepository $dietRepository, EntityManagerInterface $em): Response
	{
		// Récupère le régime "sans lactose" identifié par l'ID 40
		$dietNoLactose = $dietRepository->findOneById(40);

		// Parcourt tous les aliments de la base
		foreach ($foodRepository->findAll() as $food) {
			// Si l'aliment contient du lactose
			if ($food->getHaveLactose()) {
				// Ajoute le régime sans lactose comme régime interdit pour cet aliment
				$food->addForbiddenDiet($dietNoLactose);
				// Marque l'entité pour persistance
				$em->persist($food);
			}
		}

		// Sauvegarde toutes les modifications dans la base de données
		$em->flush();

		// Retourne une réponse simple pour confirmer que l'opération est terminée
		return new Response('diet lactose ok');
	}
}
