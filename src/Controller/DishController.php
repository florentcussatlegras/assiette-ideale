<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\UnitTime;
use App\Service\FoodUtil;
use App\Service\DishUtil;
use App\Entity\StepRecipe;
use App\Form\Type\DishType;
use App\Entity\NutritionalTable;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Service\SessionFoodHandler;
use Algolia\SearchBundle\SearchService;
use App\Repository\FoodGroupParentRepository;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use App\Service\AlertFeature;
use App\Service\DishWorkflow;
use App\Service\NutrientHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * DishController.php
 * 
 * Contrôleur responsable de la gestion des plats (Dish).
 *
 * Permet notamment :
 * - la création et modification de plats
 * - la gestion des aliments associés
 * - la sauvegarde temporaire des données en session
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 */
#[Route(
    '/plats'
)]
class DishController extends AbstractController
{
    /**
     * Sauvegarde un plat dans la session utilisateur.
     *
     * Cette méthode reconstruit un objet Dish à partir des données envoyées
     * via la query string (formulaire sérialisé). Elle permet de conserver
     * les informations du plat lors de la navigation entre les pages
     * (ex: ajout d'aliments, suppression, modification).
     *
     * Fonctionnalités principales :
     * - Désérialisation des données du formulaire
     * - Hydratation de l'objet Dish
     * - Gestion des étapes de recette
     * - Gestion du tableau nutritionnel
     * - Ajout / suppression d'aliments dans la session
     * - Redirection vers les pages appropriées
     *
     * @param Request $request Requête HTTP contenant les données du plat sérialisées
     * @param EntityManagerInterface $manager Gestionnaire Doctrine pour accéder aux entités
     * @param SessionFoodHandler $sessionFoodHandler Service permettant de gérer les aliments stockés en session
     *
     * @return Response Redirection vers la page appropriée selon l'action effectuée
     */
    #[Route('/save-dish-in-session', name: 'app_save_dish_in_session', methods: ['GET', 'POST'], options: ['expose' => true])]
    public function saveDishInSession(
        Request $request,
        EntityManagerInterface $manager,
        SessionFoodHandler $sessionFoodHandler,
    ): Response {

        // Les données du formulaire du plat sont envoyées sous forme de query string sérialisée
        // (ex : dish[name]=...&dish[type]=...&dish[stepRecipes][0][description]=...).
        // On récupère cette chaîne afin de pouvoir reconstruire le tableau PHP correspondant.
        $dishSerialized = $request->query->get('dish_serialized');

        // La chaîne étant encodée pour l'URL, on la décode d'abord puis on utilise parse_str()
        // afin de transformer la query string en tableau PHP exploitable.
        parse_str(urldecode($dishSerialized), $dishDeserialized);

        /*
            Structure obtenue après désérialisation :

            $dishDeserialized = [
                "dish" => [
                    "id" => "",
                    "name" => "",
                    "lengthPersonForRecipe" => "",
                    "preparationTime" => "",
                    "preparationTimeUnitTime" => "15",
                    "cookingTime" => "",
                    "cookingTimeUnitTime" => "15",
                    "nutritionalTable" => [...],
                    "_token" => "...",
                    "quantityFood" => [...]
                ]
            ]
        */

        // On récupère uniquement les données du plat
        $arrayDish = $dishDeserialized['dish'];


        /*
        |--------------------------------------------------------------------------
        | Détermination du contexte : création ou modification d'un plat
        |--------------------------------------------------------------------------
        |
        | Si un id de plat est présent, on est dans le cas d'une modification.
        | Sinon, il s'agit de la création d'un nouveau plat.
        |
        */
        if (!empty($arrayDish['dish_id'])) {

            // Cas d'un plat existant

            // Détermination de l'URL vers laquelle rediriger après traitement
            if ($request->query->has('remove_pic')) {

                // L'utilisateur souhaite supprimer la photo du plat
                $url = $this->generateUrl('app_pic_dish_delete', ['id' => (int)$arrayDish['dish_id']]);
            } elseif ($request->query->has('delete_food')) {

                // L'utilisateur souhaite retirer un aliment du plat
                $url = $this->generateUrl('app_dish_food_delete', [
                    'foodgroup_alias' => $request->query->get('foodgroup_alias'),
                    'id_food' => $request->query->get('id_food'),
                    'id_dish' => (int)$arrayDish['dish_id']
                ]);
            } else {

                // Redirection standard vers la page d'édition du plat
                $url = $this->generateUrl('app_dish_edit', ['id' => (int)$arrayDish['dish_id']]);
            }

            // Récupération du plat existant depuis la base de données
            $dish = $manager->getRepository(Dish::class)->findOneById((int)$arrayDish['dish_id']);
        } else {

            // Cas d'une création de plat

            $dish = new Dish();

            if ($request->query->has('remove_pic')) {

                $url = $this->generateUrl('app_pic_dish_delete');
            } elseif ($request->query->has('delete_food')) {

                $url = $this->generateUrl('app_dish_food_delete', [
                    'foodgroup_alias' => $request->query->get('foodgroup_alias'),
                    'id_food' => $request->query->get('id_food'),
                ]);
            } else {

                // Redirection standard vers la page de création
                $url = $this->generateUrl('app_dish_new');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Hydratation de l'entité Dish avec les données du formulaire
        |--------------------------------------------------------------------------
        |
        | On reconstruit l'objet Dish à partir des valeurs envoyées par le formulaire.
        | Les conversions nécessaires sont appliquées (string -> int ou null).
        |
        */

        $dish->setName($arrayDish['name']);
        $dish->setType($arrayDish['type']);
        $dish->setLevel($arrayDish['level']);

        $dish->setLengthPersonForRecipe(
            !empty($arrayDish['lengthPersonForRecipe'])
                ? (int)$arrayDish['lengthPersonForRecipe']
                : null
        );

        $dish->setPreparationTime(
            !empty($arrayDish['preparationTime'])
                ? (int)$arrayDish['preparationTime']
                : null
        );

        /*
        |--------------------------------------------------------------------------
        | Gestion de l'unité du temps de préparation
        |--------------------------------------------------------------------------
        |
        | Si une unité est sélectionnée dans le formulaire, on récupère l'entité
        | correspondante en base de données.
        | Sinon on instancie une unité vide pour éviter les erreurs de type.
        |
        */
        if (!empty($arrayDish['preparationTimeUnitTime'])) {

            $preparationTimeUnitTime = $manager->getRepository(UnitTime::class)
                ->findOneById((int)$arrayDish['preparationTimeUnitTime']);

            $dish->setPreparationTimeUnitTime($preparationTimeUnitTime);
        } else {

            $dish->setPreparationTimeUnitTime(new UnitTime());
        }

        $dish->setCookingTime(
            !empty($arrayDish['cookingTime'])
                ? (int)$arrayDish['cookingTime']
                : null
        );

        /*
        |--------------------------------------------------------------------------
        | Gestion de l'unité du temps de cuisson
        |--------------------------------------------------------------------------
        */
        if (!empty($arrayDish['cookingTimeUnitTime'])) {

            $cookingTimeUnitTime = $manager->getRepository(UnitTime::class)
                ->findOneById((int)$arrayDish['cookingTimeUnitTime']);

            $dish->setCookingTimeUnitTime($cookingTimeUnitTime);
        } else {

            $dish->setCookingTimeUnitTime(new UnitTime());
        }

        /*
        |--------------------------------------------------------------------------
        | Reconstruction des étapes de la recette
        |--------------------------------------------------------------------------
        |
        | Les étapes sont envoyées sous forme de tableau.
        | On recrée une entité StepRecipe pour chaque étape non vide.
        |
        */
        if (isset($arrayDish['stepRecipes'])) {

            $rankStep = 1;

            foreach ($arrayDish['stepRecipes'] as $arrayStepRecipe) {

                // On ignore les étapes sans description
                if (empty($arrayStepRecipe['description'])) {
                    continue;
                }

                $stepRecipe = new StepRecipe();

                $rankStep++;

                $stepRecipe->setDescription($arrayStepRecipe['description']);

                // Association de l'étape au plat
                $dish->addStepRecipe($stepRecipe);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Reconstruction de la table nutritionnelle
        |--------------------------------------------------------------------------
        |
        | Si le plat est nouveau, on crée une nouvelle entité.
        | Sinon on récupère celle déjà associée au plat.
        |
        */
        if (isset($arrayDish['nutritionalTable'])) {

            $nutritionalTable = null === $dish->getId()
                ? new NutritionalTable()
                : $dish->getNutritionalTable();

            $nutritionalTable->setProtein(!empty($arrayDish['nutritionalTable']['protein']) ? (int)$arrayDish['nutritionalTable']['protein'] : null);
            $nutritionalTable->setLipid(!empty($arrayDish['nutritionalTable']['lipid']) ? (int)$arrayDish['nutritionalTable']['lipid'] : null);
            $nutritionalTable->setSaturatedFattyAcid(!empty($arrayDish['nutritionalTable']['saturatedFattyAcid']) ? (int)$arrayDish['nutritionalTable']['saturatedFattyAcid'] : null);
            $nutritionalTable->setCarbohydrate(!empty($arrayDish['nutritionalTable']['carbohydrate']) ? (int)$arrayDish['nutritionalTable']['carbohydrate'] : null);
            $nutritionalTable->setSugar(!empty($arrayDish['nutritionalTable']['sugar']) ? (int)$arrayDish['nutritionalTable']['sugar'] : null);
            $nutritionalTable->setSalt(!empty($arrayDish['nutritionalTable']['salt']) ? (int)$arrayDish['nutritionalTable']['salt'] : null);
            $nutritionalTable->setFiber(!empty($arrayDish['nutritionalTable']['fiber']) ? (int)$arrayDish['nutritionalTable']['fiber'] : null);
            $nutritionalTable->setEnergy(!empty($arrayDish['nutritionalTable']['energy']) ? (int)$arrayDish['nutritionalTable']['energy'] : null);
            $nutritionalTable->setNutriscore(!empty($arrayDish['nutritionalTable']['nutriscore']) ? (int)$arrayDish['nutritionalTable']['nutriscore'] : null);

            $dish->setNutritionalTable($nutritionalTable);
        }

        /*
        |--------------------------------------------------------------------------
        | Sauvegarde temporaire du plat en session
        |--------------------------------------------------------------------------
        |
        | Le plat n'est pas encore persisté en base.
        | On le stocke en session afin de conserver l'état du formulaire
        | lors des navigations intermédiaires (ex : ajout d'aliments).
        |
        */
        $request->getSession()->set('recipe_dish', $dish);

        /*
        |--------------------------------------------------------------------------
        | Suppression d'un aliment de la liste en session
        |--------------------------------------------------------------------------
        */
        if ($request->query->has('delete_food')) {

            $sessionFoodHandler->remove(
                $request->query->get('foodgroup_alias'),
                $request->query->get('id_food')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Redirection vers la liste des aliments
        |--------------------------------------------------------------------------
        |
        | L'utilisateur souhaite ajouter un aliment au plat.
        | On mémorise la route de retour pour revenir ensuite
        | vers la page du plat.
        |
        */
        if ($request->query->has('id_foodgroup_togo') && "undefined" !== $request->query->get('id_foodgroup_togo')) {

            $request->getSession()->set('route_referer', $url);

            return $this->redirectToRoute('app_food_list', [
                'fg' => $request->query->get('id_foodgroup_togo'),
                'unique_fg' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Ajout d'un nouvel aliment depuis le formulaire du plat
        |--------------------------------------------------------------------------
        */
        if ($request->query->has('new_food_serialized')) {

            $newFoodSerialized = $request->query->get('new_food_serialized');

            parse_str(urldecode($newFoodSerialized), $newFood);

            $sessionFoodHandler->add($newFood);
        }

        // Redirection spécifique après création du plat
        if ($request->query->has('from_form_new')) {
            return $this->redirectToRoute('app_dish_new');
        }

        // Redirection finale vers l'URL déterminée précédemment
        return $this->redirect($url);
    }

    /**
     * Affiche le formulaire de création de plat et gère l'ajout
     * 
     * @param Request $request Requête HTTP
     * @param DishWorkflowService $workflow Service pour gérer toute la logique métier du plat
     * @param FoodGroupRepository $foodGroupRepository Repository pour récupérer les groupes alimentaires
     * 
     * @return Response
     */
    #[Route('/nouveau', name: 'app_dish_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        FoodGroupRepository $foodGroupRepository,
        DishWorkflow $workflow,
    ): Response {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // Récupère un plat depuis la session si existant, sinon crée un nouveau
        $dish = $workflow->getDishFromSessionOrNew();

        // Crée le formulaire lié au plat
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request); // Hydrate le formulaire avec la requête

        // Délègue la logique métier (gestion des boutons, upload, session, persistance)
        $response = $workflow->handleForm($form, $request);

        // Si le workflow a retourné une redirection (ex: sauvegarde du plat), on renvoie la réponse
        if ($response instanceof Response) {
            return $response;
        }

        // Sinon, on affiche le formulaire
        return $this->render(
            'dish/form_layout.html.twig',
            [
                'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
                'dishForm' => $form->createView(),
            ]
        );
    }

    /**
     * Affiche le formulaire d'édition d'un plat et gère la mise à jour.
     * 
     * @param Dish $dish Plat à éditer (injecté automatiquement)
     * @param Request $request Requête HTTP
     * @param DishWorkflow $dishWorkflow Service pour gérer toute la logique métier du plat
     * @param FoodGroupRepository $foodGroupRepository Repository pour récupérer les groupes alimentaires
     * @param SessionFoodHandler $sessionFoodHandler Gestion des aliments stockés en session
     * 
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_dish_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Dish $dish,
        Request $request,
        FoodGroupRepository $foodGroupRepository,
        DishWorkflow $dishWorkflow,
        SessionFoodHandler $sessionFoodHandler
    ): Response {
        // Vérifie que l'utilisateur est authentifié
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // Récupère la session
        $session = $request->getSession();

        // Crée le formulaire DishType pour le plat à éditer
        $form = $this->createForm(DishType::class, $dish);

        // Traite les données de la requête HTTP
        $form->handleRequest($request);

        // Si aucun aliment n'est stocké en session, on initialise la session avec les aliments du plat existant
        if (!$session->has('recipe_foods') || empty($session->get('recipe_foods'))) {
            $sessionFoodHandler->addFromDishObject($dish);
        }

        // Si aucune image n'est stockée en session, on ajoute celle du plat existant
        if (!$session->has('recipe_picture')) {
            $session->set('recipe_picture', $dish->getPicture());
        }

        // Traite le formulaire via le service DishWorkflow
        $response = $dishWorkflow->handleForm($form, $request);

        // Si le service renvoie une réponse (redirection après sauvegarde ou ajout d'aliment), on retourne cette réponse
        if ($response instanceof Response) {
            return $response;
        }

        // Si on arrive ici, c'est que le formulaire n'a pas été soumis ou n'est pas valide
        // On rend le formulaire avec le plat et la liste des groupes d'aliments
        return $this->render('dish/form_layout.html.twig', [
            'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
            'dishForm' => $form->createView(),
            'dish' => $dish,
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? 422 : 200
        ));
    }

    /**
     * Supprime un plat de la base de données.
     *
     * @param Dish|null $dish L'entité Dish à supprimer, injectée automatiquement via ParamConverter
     * @param EntityManagerInterface $manager Gestionnaire d'entités Doctrine
     * @param Request $request Requête HTTP pour récupérer d'éventuels paramètres
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirection vers le dashboard après suppression
     */
    #[Route('/{id}/remove', name: 'app_dish_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(?Dish $dish, EntityManagerInterface $manager): Response
    {
        // Vérifie que le plat existe avant de tenter de le supprimer
        if (!$dish) {
            $this->addFlash('error', 'Plat introuvable.');
            return $this->redirectToRoute('app_dashboard_index');
        }

        // Supprime le plat de Doctrine
        $manager->remove($dish);
        $manager->flush();

        // Ajoute un message flash pour confirmer la suppression
        $this->addFlash('notice', 'Le plat a bien été supprimé');

        // Redirection vers la page d'accueil du dashboard
        return $this->redirectToRoute('app_dashboard_index');
    }

    /**
     * Affiche les quantités recommandées par portion pour un plat donné.
     * 
     * Calcule :
     * - Les quantités par groupe alimentaire principal pour N portions
     * - Les valeurs nutritionnelles principales (protéines, lipides, glucides, sodium, énergie)
     * 
     * @param Dish $dish Plat dont on calcule les quantités
     * @param int $portion Nombre de portions (par défaut 1)
     * @param AlertFeature $alertFeature Service pour extraire les données nutritionnelles
     * @param DishUtil $dishUtil Service pour calculer les quantités par portion
     * @param FoodGroupParentRepository $foodGroupParentRepository Repository pour récupérer les noms des groupes alimentaires
     * 
     * @return Response
     */
    #[Route('/dish-quantities-recommended-by-portion/{id}/{portion}', name: 'app_dish_quantities_recommended_by_portion', requirements: ['id' => '\d+', 'portion' => '\d+'], defaults: ['portion' => 1], methods: ['GET'])]
    public function dishQuantitiesRecommendedByPortion(Dish $dish, int $portion = 1, AlertFeature $alertFeature, DishUtil $dishUtil, FoodGroupParentRepository $foodGroupParentRepository)
    {
        // Récupère les quantités des groupes alimentaires principaux pour N portions
        $fgpQuantities = $dishUtil->getFoodGroupParentQuantitiesForNPortion($dish, $portion);

        // Récupère les alias des groupes alimentaires
        $aliases = array_keys($fgpQuantities);

        // Récupère les entités FoodGroupParent correspondantes
        $foodGroups = $foodGroupParentRepository->findBy(['alias' => $aliases]);

        // Indexe les groupes alimentaires par alias pour un accès rapide
        $indexed = [];
        foreach ($foodGroups as $fgp) {
            $indexed[$fgp->getAlias()] = $fgp->getName();
        }

        // Remplace les alias par les noms lisibles des groupes alimentaires
        $fgpQuantitiesWithFgpName = [];
        foreach ($fgpQuantities as $alias => $quantity) {
            $fgpQuantitiesWithFgpName[$indexed[$alias] ?? $alias] = $quantity;
        }

        // Calcule les valeurs nutritionnelles principales pour le plat et la portion donnée
        $results = [
            'fgpQuantitiesForNPortion' => $fgpQuantitiesWithFgpName,
            NutrientHandler::PROTEIN => $alertFeature->extractDataFromDishOrFoodSelected(NutrientHandler::PROTEIN, $dish, $portion),
            NutrientHandler::LIPID => $alertFeature->extractDataFromDishOrFoodSelected(NutrientHandler::LIPID, $dish, $portion),
            NutrientHandler::CARBOHYDRATE => $alertFeature->extractDataFromDishOrFoodSelected(NutrientHandler::CARBOHYDRATE, $dish, $portion),
            NutrientHandler::SODIUM => $alertFeature->extractDataFromDishOrFoodSelected(NutrientHandler::SODIUM, $dish, $portion),
            'energy' => $alertFeature->extractDataFromDishOrFoodSelected('energy', $dish, $portion),
        ];

        // Rend le template avec les résultats et les informations du plat
        return $this->render(
            'dish/partials/_quantities_recommended_by_portion.html.twig',
            [
                'results' => $results,
                'dish' => $dish,
                'portion' => $portion,
            ]
        );
    }

    /**
     * Affiche les détails d'un plat.
     *
     * Récupère l'utilisateur connecté pour identifier ses plats favoris et
     * les passer à la vue afin de marquer visuellement les favoris.
     *
     * @param Dish $dish Le plat à afficher
     * 
     * @return Response La page détaillée du plat
     */
    #[Route(
        '/{id}/show/{slug}',
        name: 'app_dish_show',
        requirements: ['id' => '\d+', 'slug' => '[a-zA-Z0-9\-]+'],
        methods: ['GET'],
        options: ['expose' => true]
    )]
    public function show(Dish $dish)
    {
        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Si un utilisateur est connecté, on récupère les IDs de ses plats favoris
        if ($user) {
            $favoriteDishesId = array_map(function ($dish) {
                return $dish->getId();
            }, $user->getFavoriteDishes()->toArray());
        }

        // Rend le template avec le plat et les favoris de l'utilisateur (si existants)
        return $this->render('dish/show.html.twig', [
            'dish' => $dish,
            'favoriteDishesId' => $favoriteDishesId ?? null,
        ]);
    }

    /**
     * Édite les informations d'un aliment dans un plat.
     *
     * Supporte l'édition via requête AJAX pour modifier la quantité et l'unité
     * d'un aliment directement depuis la page du plat.
     *
     * @param FoodRepository $foodRepository Repository pour récupérer l'entité Food
     * @param UnitMeasureRepository $unitMeasureRepository Repository pour les unités de mesure
     * @param SessionFoodHandler $sessionFoodHandler Service pour gérer les aliments en session
     * @param FoodUtil $foodUtil Utilitaire pour les calculs ou traitements sur les aliments
     * @param Request $request Requête HTTP
     * @param int $idFood ID de l'aliment à éditer
     * @param string|null $idDish ID du plat auquel appartient l'aliment (optionnel)
     * 
     * @return Response Le formulaire d'édition ou le rendu AJAX de la quantité mise à jour
     */
    #[Route(
        '/food/{idFood<\d+>}/edit/{idDish?}',
        name: 'app_dish_food_edit',
        requirements: ['idFood' => '\d+', 'idDish' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function editFood(
        FoodRepository $foodRepository,
        UnitMeasureRepository $unitMeasureRepository,
        SessionFoodHandler $sessionFoodHandler,
        Request $request,
        int $idFood,
        ?string $idDish
    ) {
        // Gestion d'une modification via AJAX
        if ($request->query->get('ajax')) {
            // Met à jour la quantité et l'unité dans la session
            $sessionFoodHandler->modifyQuantity($idFood, $request->query->all());

            // Retourne uniquement le bloc HTML partiel avec la nouvelle quantité
            return $this->render('dish/_quantity_food.html.twig', [
                'quantity' => $request->query->get('new_quantity'),
                'unitMeasure' => $request->query->get('new_unit_measure'),
                'idFood' => $idFood,
                'idDish' => $idDish,
            ]);
        }

        // Affiche le formulaire complet pour éditer l'aliment
        return $this->render('dish/_form_edit_food.html.twig', [
            'food' => $foodRepository->findOneById($idFood),
            'idDish' => $idDish,
            'unitMeasures' => $unitMeasureRepository->findAll(),
        ]);
    }

    /**
     * Supprime un aliment de la session pour le plat en cours de création ou d'édition.
     *
     * Cette méthode agit uniquement sur la session et ne supprime pas l'aliment de la base de données.
     * Selon si un plat existe déjà, elle redirige vers la page d'édition du plat ou vers la création d'un nouveau plat.
     *
     * @param Request $request Requête HTTP contenant la session
     * @param string $foodgroup_alias Alias du groupe alimentaire auquel appartient l'aliment
     * @param int $id_food ID de l'aliment à supprimer
     * @param int|null $id_dish ID du plat en cours (optionnel)
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirection vers la page de création ou d'édition du plat
     */
    #[Route(
        '/food/{foodgroup_alias}/{id_food<\d+>}/delete/{id_dish<\d+>}',
        name: 'app_dish_food_delete',
        requirements: ['id_food' => '\d+', 'id_dish' => '\d+'],
        methods: ['GET']
    )]
    public function deletefood(Request $request, $foodgroup_alias, $id_food, $id_dish = null)
    {
        // Récupère tous les aliments stockés en session pour le plat en cours
        $foods = $request->getSession()->get('recipe_foods', []);

        // Supprime l'aliment correspondant à l'alias et à l'ID
        unset($foods[$foodgroup_alias][$id_food]);

        // Met à jour la session avec la nouvelle liste d'aliments
        $request->getSession()->set('recipe_foods', $foods);

        // Si un plat existe (édition), redirige vers la page d'édition du plat
        if ($id_dish) {
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $id_dish
            ]);
        }

        // Sinon redirige vers la création d'un nouveau plat
        return $this->redirectToRoute('app_dish_new');
    }

    /**
     * Affiche la liste des plats avec filtres, pagination et support AJAX.
     * 
     * Gère les filtres par :
     * - Groupe alimentaire (fg)
     * - Mot-clé (q)
     * - Type de plat
     * - Options sans gluten / sans lactose
     * 
     * La méthode supporte aussi le chargement partiel via AJAX pour l'infinite scroll.
     *
     * @param Request $request Requête HTTP avec paramètres de filtrage et pagination
     * @param DishRepository $dishRepository Repository pour récupérer les plats
     * @param FoodGroupRepository $foodGroupRepository Repository pour récupérer les groupes alimentaires
     * 
     * @return Response
     */
    #[Route('/list', name: 'app_dish_list', methods: ['GET'])]
    public function list(Request $request, DishRepository $dishRepository, FoodGroupRepository $foodGroupRepository): Response
    {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var App\Entity\User|null $user */
        $user = $this->getUser();

        // Récupère le ou les groupes alimentaires sélectionnés depuis la query string
        $fg = !empty($request->query->get('fg')) ? $request->query->all()['fg'] : [];

        // Récupère les IDs des plats favoris de l'utilisateur
        $favoriteDishesId = array_map(function ($dish) {
            return $dish->getId();
        }, $user->getFavoriteDishes()->toArray());

        // Gestion du cas où aucun groupe alimentaire n'est sélectionné et requête AJAX
        if (empty($fg) && $request->query->get('ajax')) {
            return $this->render('dish/_dish_list.html.twig', [
                'dishes' => null,
                'lastResults' => true,
                'favoriteDishesId' => $favoriteDishesId,
            ]);
        }

        // Récupération des paramètres de filtrage et pagination
        $keyword = $request->query->get('q', null);
        $type = $request->query->get('type', "type.dish.all");
        $page = (int) $request->query->get('page', 0);
        $freeGluten = (bool) $request->query->get('freeGluten', false);
        $freeLactose = (bool) $request->query->get('freeLactose', false);

        $limit = 12; // Nombre de plats par page
        $lastResults = false;

        if ("none" !== $fg) {
            // Calcul de l'offset pour la pagination
            $offset = $page * $limit;

            // Recherche des plats selon les filtres et limites
            $dishes = $dishRepository->myFindByKeywordAndFGAndTypeAndLactoseAndGluten(
                $keyword,
                $fg,
                $freeLactose,
                $freeGluten,
                $type,
                $limit,
                $offset
            );

            // Vérifie si on a atteint la fin des résultats
            if (count($dishes) > $limit) {
                array_pop($dishes); // Supprime le plat en trop pour respecter la limite
                $lastResults = false;
            } else {
                $lastResults = true;
            }
        } else {
            // Aucun groupe sélectionné → pas de résultats
            $lastResults = true;
            $dishes = [];
        }

        // Requête AJAX → retourne uniquement le bloc de liste partielle
        if ($request->query->get('ajax')) {
            return $this->render('dish/_dish_list.html.twig', [
                'dishes' => $dishes,
                'lastResults' => $lastResults,
                'favoriteDishesId' => $favoriteDishesId,
            ]);
        }

        // Requête standard → retourne la page complète
        return $this->render('dish/list.html.twig', [
            'foodGroupsSelected' => $fg,
            'dishes' => $dishes,
            'foodGroups' => $foodGroupRepository->findAll(),
            'lastResults' => $lastResults,
            'favoriteDishesId' => $favoriteDishesId,
        ]);
    }

    /**
     * Affiche un aperçu de la liste des aliments pour un plat.
     * 
     * Permet de rechercher des aliments via un mot-clé (`q`) et d'afficher
     * les unités de mesure disponibles. Utilisé généralement dans un modal ou
     * un champ de recherche en live.
     * 
     * @param Request $request Requête HTTP contenant le paramètre 'q' pour la recherche
     * @param EntityManagerInterface $manager Gestionnaire Doctrine (non utilisé ici mais injecté)
     * @param FoodRepository $foodRepository Repository pour récupérer les aliments
     * @param SearchService $searchService Service pour la recherche avancée (injecté mais non utilisé ici)
     * @param UnitMeasureRepository $unitMeasureRepository Repository pour récupérer les unités de mesure
     * @param int|null $idDish ID optionnel du plat auquel les aliments seront associés
     * 
     * @return Response
     */
    #[Route('/food/list/preview/{idDish?}', name: 'app_dish_food_list_preview', requirements: ['idDish' => '\d+'], methods: ['GET'])]
    public function foodListPreview(
        Request $request,
        FoodRepository $foodRepository,
        UnitMeasureRepository $unitMeasureRepository,
        ?int $idDish
    ): Response {
        // Récupère la liste des aliments correspondant au mot-clé de recherche
        $foods = $foodRepository->myFindByKeyword($request->query->get('q'));

        // Rendu du template Twig avec les résultats et informations associées
        return $this->render('dish/_search_food_list_preview.html.twig', [
            'foods' => $foods,
            'query' => $request->query->get('q'),
            'unitMeasures' => $unitMeasureRepository->findAll(),
            'idDish' => $idDish,
        ]);
    }

    /**
     * Fournit une liste de plats au format JSON, paginée par limite et offset.
     *
     * @param DishRepository $dishRepository Repository pour récupérer les plats
     * @param SerializerInterface $serializer Service pour convertir les objets en JSON
     * @param int $limit Nombre maximum de plats à renvoyer (par défaut 10)
     * @param int $offset Décalage pour la pagination (par défaut 0)
     *
     * @return Response Réponse HTTP contenant les plats sérialisés en JSON
     */
    #[Route('/list_json/{limit}/{offset}', name: 'app_dish_list_json', requirements: ['limit' => '\d+', 'offset' => '\d+'], methods: ['GET'])]
    public function listJson(
        DishRepository $dishRepository,
        SerializerInterface $serializer,
        int $limit = 10,
        int $offset = 0
    ): Response {
        // Récupère les plats triés par nom, avec limite et offset pour pagination
        $dishes = $dishRepository->findBy([], ['name' => 'ASC'], $limit, $offset);

        // Sérialise les plats en JSON avec le groupe de normalisation "list_dish"
        $data = $serializer->serialize(
            $dishes,
            'json',
            [
                'groups' => 'list_dish', // Définition du groupe pour les champs exposés
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d' // Format des dates
            ]
        );

        // Création de la réponse JSON
        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Vide tous les aliments stockés en session pour le plat en cours.
     *
     * Cette méthode utilise le service SessionFoodHandler pour supprimer
     * toutes les données de la session liées aux aliments du plat.
     * 
     * Après suppression :
     * - si un ID de plat est fourni, redirige vers la page d'édition du plat ;
     * - sinon, redirige vers la création d'un nouveau plat.
     *
     * @param SessionFoodHandler $sessionFoodHandler Service de gestion des aliments en session
     * @param int|null $id Identifiant optionnel du plat (Dish)
     * 
     * @return RedirectResponse Redirection vers la page appropriée selon la présence de l'ID
     */
    #[Route('/session/clear/{id?}', name: 'app_dish_food_session_clear', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function clear(SessionFoodHandler $sessionFoodHandler, ?int $id)
    {
        // Supprime toutes les données d'aliments en session
        $sessionFoodHandler->removeAll();

        // Redirection selon la présence d'un ID de plat
        if ($id) {
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $id
            ]);
        }

        return $this->redirectToRoute('app_dish_new');
    }

    /**
     * Télécharge la recette d'un plat sous forme de fichier PDF.
     * 
     * Cette méthode génère un fichier PDF minimal avec le nom du plat et
     * force le téléchargement via les en-têtes HTTP.
     *
     * @param Dish|null $dish Le plat dont on veut télécharger la recette
     * @param string $dirFileRecipe Chemin vers le répertoire où stocker temporairement le PDF
     * 
     * @return Response Réponse HTTP contenant le fichier à télécharger
     */
    #[Route('/download/{id?}', name: 'app_dish_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function download(Dish $dish = null, $dirFileRecipe)
    {
        // Nom du fichier PDF basé sur l'ID et le slug du plat
        $filename = sprintf('recipe-%d-%s.pdf', $dish->getId(), $dish->getSlug());

        // Création du fichier PDF minimal
        $fp = fopen($dirFileRecipe . '/' . $filename, 'w');
        if ($fp) {
            fwrite($fp, $dish->getName()); // Contenu minimal : nom du plat
            fclose($fp);
        }

        // Préparation de l'en-tête pour forcer le téléchargement
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response = new Response();
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * Retourne le nombre total de plats (Dish) enregistrés en base.
     *
     * Cette méthode récupère tous les objets Dish depuis le repository
     * et retourne leur nombre sous forme de réponse HTTP brute.
     *
     * @param EntityManagerInterface $manager Gestionnaire d'entités pour accéder au repository Dish
     *
     * @return Response Réponse HTTP contenant le nombre total de plats
     */
    #[Route('/count', methods: ['GET'])]
    public function count(EntityManagerInterface $manager)
    {
        // Récupère tous les plats
        $allDishes = $manager->getRepository(Dish::class)->findAll();

        // Retourne le nombre total en réponse
        return new Response((string) count($allDishes));
    }

    #[Route('/show-error-picture', methods: ['GET'])]
    public function showErrorPicture(Request $request): Response
    {
        $message = $request->getSession()->get('recipe_error_pic');
        $request->getSession()->remove('recipe_error_pic');

        return new Response($message);
    }

    /**
     * Affiche le message d'erreur lié à la quantité d'un aliment sélectionné.
     *
     * Récupère le message stocké en session sous la clé 'food_error_quantity'
     * et le supprime immédiatement après pour éviter qu'il soit affiché plusieurs fois.
     *
     * @param Request $request Requête HTTP contenant la session
     *
     * @return Response Réponse HTTP contenant le message d'erreur (texte brut)
     */
    #[Route('/show-error-food-quantity', methods: ['GET'])]
    public function showErrorFoodQuantity(Request $request): Response
    {
        // Récupère le message d'erreur de la session
        $message = $request->getSession()->get('food_error_quantity');

        // Supprime le message de la session après lecture
        $request->getSession()->remove('food_error_quantity');

        // Retourne le message en réponse
        return new Response($message);
    }
}
