<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Entity\Food;
use App\Entity\UnitTime;
use App\Service\FoodUtil;
use App\Entity\StepRecipe;
use Pagerfanta\Pagerfanta;
use App\Form\Type\DishType;
use App\Service\UploaderHelper;
use App\Entity\NutritionalTable;
use App\Service\DishFoodHandler;
use App\Service\TypeDishHandler;
use App\Repository\DishRepository;
use App\Repository\FoodRepository;
use App\Service\SessionFoodHandler;
use Algolia\SearchBundle\SearchService;
use App\Form\Type\QuantityFoodFormType;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UnitMeasureRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Serializer\Normalizer\DishDenormalizer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;


#[Route(
    '/plats'
)]
class DishController extends AbstractController
{
    // protected $searchService;

    // public function __construct(
    //     private SearchService $searchService)
    // {
    //     $this->searchService = $searchService;
    // }

    // #[Route('/food/add', name: 'app_dish_food_add', methods: ['POST'])]
    // public function addFood(Request $request, SessionFoodHandler $sessionFoodHandler)
    // {
    //     dd($request->request->all());
    //     $sessionFoodHandler->addFromSearchBox($request->request->all()['foods']);
    // }

    #[Route('/save-dish-in-session', name: 'app_save_dish_in_session', options: ['expose' => true])]
    public function saveDishInSession(
            Request $request, 
            EntityManagerInterface $manager,
            SessionFoodHandler $sessionFoodHandler,
            SerializerInterface $serializer
        )
    {
        // Les données du plat sont stockées en session pour en conservant l'affichage
        $dishSerialized = $request->query->get('dish_serialized');

        parse_str(urldecode($dishSerialized), $dishDeserialized);
        // dd($dishDeserialized);
        /*
            $dishDeserialized :
            "dish" => array:10 [▼
                "id" => ""
                "name" => ""
                "lengthPersonForRecipe" => ""
                "preparationTime" => ""
                "preparationTimeUnitTime" => "15"
                "cookingTime" => ""
                "cookingTimeUnitTime" => "15"
                "nutritionalTable" => array:9 [▶]
                "_token" => "cdcb5.hJe1i9OdVHGx0XkBFkTcxMr4P8sNIbdPvWstHHbvJ48._fjC2bmuZjfk4RA3Zy2ItIO_XfthFu4Z5yofLDCpdPfFpdTosawBCYeGEg"
                "quantityFood" => array:3 [▶]
        */
        $arrayDish = $dishDeserialized['dish'];

        // #[Route('/food/{foodgroup_alias}/{id_food<\d+>}/delete/{id_dish<\d+>}', name: 'app_dish_food_delete')]

        if(!empty($arrayDish['dish_id'])) {
            if($request->query->has('remove_pic')) {
                $url = $this->generateUrl('app_pic_dish_delete', ['id' => (int)$arrayDish['dish_id']]);
            }elseif($request->query->has('delete_food')){
                $url = $this->generateUrl('app_dish_food_delete', [
                    'foodgroup_alias' => $request->query->get('foodgroup_alias'),
                    'id_food' => $request->query->get('id_food'),
                    'id_dish' => (int)$arrayDish['dish_id']
                ]);
            }else{
                $url = $this->generateUrl('app_dish_edit', ['id' => (int)$arrayDish['dish_id']]);
            }
            $dish = $manager->getRepository(Dish::class)->findOneById((int)$arrayDish['dish_id']);
        }else{
            $dish = new Dish();
            if($request->query->has('remove_pic')) {
                $url = $this->generateUrl('app_pic_dish_delete');
            }elseif($request->query->has('delete_food')){
                $url = $this->generateUrl('app_dish_food_delete', [
                    'foodgroup_alias' => $request->query->get('foodgroup_alias'),
                    'id_food' => $request->query->get('id_food'),
                ]);
            }else{
                $url = $this->generateUrl('app_dish_new');
            }
        }

        $dish->setName($arrayDish['name']);
        $dish->setType($arrayDish['type']);
        $dish->setLevel($arrayDish['level']);
        $dish->setLengthPersonForRecipe(!empty($arrayDish['lengthPersonForRecipe']) ? (int)$arrayDish['lengthPersonForRecipe'] : null);
        $dish->setPreparationTime(!empty($arrayDish['preparationTime']) ? (int)$arrayDish['preparationTime'] : null);
        if(!empty($arrayDish['preparationTimeUnitTime'])) {
            $preparationTimeUnitTime = $manager->getRepository(UnitTime::class)
                                            ->findOneById((int)$arrayDish['preparationTimeUnitTime']);
            $dish->setPreparationTimeUnitTime($preparationTimeUnitTime);
        }else{
            $dish->setPreparationTimeUnitTime(new UnitTime());
        }
        $dish->setCookingTime(!empty($arrayDish['cookingTime']) ? (int)$arrayDish['cookingTime'] : null);    
        if(!empty($arrayDish['cookingTimeUnitTime'])) {
            $cookingTimeUnitTime = $manager->getRepository(UnitTime::class)
                                            ->findOneById((int)$arrayDish['cookingTimeUnitTime']);
            $dish->setCookingTimeUnitTime($cookingTimeUnitTime);
        }else{
            $dish->setCookingTimeUnitTime(new UnitTime());
        }

        if(isset($arrayDish['stepRecipes'])) {
            $rankStep = 1;
            foreach($arrayDish['stepRecipes'] as $arrayStepRecipe) {
                $stepRecipe = new StepRecipe();
                if(empty($arrayStepRecipe['description'])) {
                    continue;
                }
                $rankStep++;
                $stepRecipe->setDescription($arrayStepRecipe['description']);
                $dish->addStepRecipe($stepRecipe);
            }
        }

        if(isset($arrayDish['nutritionalTable'])) {
            $nutritionalTable = null === $dish->getId() ? new NutritionalTable() : $dish->getNutritionalTable();
            $nutritionalTable->setProtein(!empty($arrayDish['nutritionalTable']['protein']) ? (int)$arrayDish['nutritionalTable']['protein']: null);
            $nutritionalTable->setLipid(!empty($arrayDish['nutritionalTable']['lipid']) ? (int)$arrayDish['nutritionalTable']['lipid']: null);
            $nutritionalTable->setSaturatedFattyAcid(!empty($arrayDish['nutritionalTable']['saturatedFattyAcid']) ? (int)$arrayDish['nutritionalTable']['saturatedFattyAcid']: null);
            $nutritionalTable->setCarbohydrate(!empty($arrayDish['nutritionalTable']['carbohydrate']) ? (int)$arrayDish['nutritionalTable']['carbohydrate']: null);
            $nutritionalTable->setSugar(!empty($arrayDish['nutritionalTable']['sugar']) ? (int)$arrayDish['nutritionalTable']['sugar']: null);
            $nutritionalTable->setSalt(!empty($arrayDish['nutritionalTable']['salt']) ? (int)$arrayDish['nutritionalTable']['salt']: null);
            $nutritionalTable->setFiber(!empty($arrayDish['nutritionalTable']['fiber']) ? (int)$arrayDish['nutritionalTable']['fiber']: null);
            $nutritionalTable->setEnergy(!empty($arrayDish['nutritionalTable']['energy']) ? (int)$arrayDish['nutritionalTable']['energy']: null);
            $nutritionalTable->setNutriscore(!empty($arrayDish['nutritionalTable']['nutriscore']) ? (int)$arrayDish['nutritionalTable']['nutriscore']: null);
            $dish->setNutritionalTable($nutritionalTable);
        }

        // On stocke le plat en session
        $request->getSession()->set('recipe_dish', $dish);

        // On souhaite supprimer un aliment de la liste
        if($request->query->has('delete_food')) {
            $sessionFoodHandler->remove($request->query->get('foodgroup_alias'), $request->query->get('id_food'));
        }

        // On souhaite aller vers la page de la liste des aliments
        if($request->query->has('id_foodgroup_togo') && "undefined" !== $request->query->get('id_foodgroup_togo')) {
            $request->getSession()->set(
                'route_referer',
                $url
            );
            // $data = [
            //     'food_groups' => [$request->query->get('id_foodgroup_togo')]
            // ];
            // dd(http_build_query($data));

            return $this->redirectToRoute('app_food_list', [
                'fg' => $request->query->get('id_foodgroup_togo'),
                'unique_fg' => true,
            ]);
        }

        // Ou on vient de soumettre les formulaires d'ajout/modif d'un aliment depuis la page du plat
        if($request->query->has('new_food_serialized')) {

            $newFoodSerialized = $request->query->get('new_food_serialized');
            parse_str(urldecode($newFoodSerialized), $newFood);

            $sessionFoodHandler->add($newFood);
        }

        if($request->query->has('from_form_new')) {
            return $this->redirectToRoute('app_dish_new');
        }
        
        return $this->redirect($url);
    }

    #[Route('/nouveau', name: 'app_dish_new', methods: ['GET', 'POST'])]
    public function new(
            EntityManagerInterface $manager, 
            Request $request,
            FoodGroupRepository $foodGroupRepository,
            DishFoodHandler $dishFoodHandler,
            SessionFoodHandler $sessionFoodHandler,
            UploaderHelper $uploaderHelper,
            ValidatorInterface $validator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        // dump($session->clear());
        // dump($session->all());

        $dish = new Dish();
        $form = $this->createForm(DishType::class, $dish);

        $session = $request->getSession();
        
        $form->handleRequest($request);
        
        // if($request->isMethod('POST'))
        // {
            
            if ($form->isSubmitted() && $form->isValid()) {
                   
                //L'utilisateur supprime une photo
                // if (null !== $picRankForDelete = $form->get('picRankForDelete')->getData()) {
                //     $session->set('recipe_dish', $dish);
                //     return $this->redirectToRoute('app_pic_dish_delete', [
                //         'rank' => $picRankForDelete
                //     ]);
                // }

                // On a cliqué sur le bouton de validation du formulaire
                if ($form->get('saveAndAdd')->isClicked()) {

                        // On ajoute au plat les images
                    // sélectionnées dans la session qui ont déja été uploadées
                    if ($session->get('recipe_picture')) {
                        $dish->setPicture($session->get('recipe_picture'));
                    }
    
                    // On ajoute les images sélectionnées dans le input file
                    if (null !== $pictureFile = $form->get('pictureFile')->getData()) { 

                        // $pictureConstraint = new Assert\File([
                        //         'maxSize' => '5M',
                        //         'mimeTypes' => ['jpeg', 'jpg', 'gif'],
                        //         'mimeTypesMessage' => 'Merci de choisir une image valide',
                        // ]);

                        $pictureConstraint = new Assert\File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'],
                            'mimeTypesMessage' => 'Merci de choisir une image valide',
                        ]);

                        $errorsPic = $validator->validate(
                            $pictureFile,
                            $pictureConstraint
                        );

                        if(isset($errorsPic[0])) {

                            $session->set('recipe_error_pic', $errorsPic[0]->getMessage());
                            
                            $request->getSession()->set('recipe_dish', $dish);

                            return $this->redirectToRoute('app_dish_new');
                        }

                        $pictureName = $uploaderHelper->upload($pictureFile, UploaderHelper::DISH);
                        $dish->setPicture($pictureName);

                    }
    
                    // On crée les élements DishFood, DishFoodGroup, DishFoodGroupParent liés au plat Dish
                    $dish = $dishFoodHandler->createDishFoodElement($dish);
                    //dd($dish);
                    // On vide la session
                    $session->clear();
    
                    $manager->persist($dish);
                    $manager->flush();
    
                    $this->addFlash('success', 'Le plat a bien été ajouté.');
                        
                    return $this->redirectToRoute('app_dish_show', [
                            'id' => $dish->getId(),
                            'slug' => $dish->getSlug()
                        ]);

                // } elseif ($form->get('saveQuantityFood')->isClicked()) {

                //     // Ici l'utilisateur a saisi un aliment sur le champs de recherche et a saisi et validé
                //     // la quantité et l'unité de mesure qu'il souhaite ajouté à la recette,
                //     // depuis les champs de la fenêtre modale

                //     $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

                //     // L'objet dish est (re)stocké en session
                //     $session->set('recipe_dish', $dish);

              
                //     // On récupère les données quantité et unité de mesure saisies
                //     // Champ quantityFood => Formulaire imbriqué de type QuantityFoodFormType
                //     $sessionFoodHandler->addFromSearchBox($form->get('quantityFood')->getData());

                //     return $this->redirectToRoute('app_dish_new');

                } elseif ($form->getClickedButton()->getConfig()->hasOption('food_group')) {
                   
                    // Si le bouton saisi est de type DishFoodGroupSubmitType :
                    // On stocke les données du formulaire en session
                    // afin de les récupérer en retour de saisie des aliments
                    // On redirige vers la liste des aliments du groupe concerné

                    $sessionFoodHandler->savePicturesInSession($form->get('pictureFile')->getData());
                    
                    // L'objet dish est (re)stocké en session
                    $session->set('recipe_dish', $dish);
                    
                    $session->set(
                        'route_referer',
                        $this->generateUrl($request->attributes->get('_route'))
                    );

                    return $this->redirectToRoute('app_food_list', [
                            'slug' => $form->getClickedButton()->getConfig()
                                            ->getOption('food_group')->getSlug(),

                    ]);
                }

            }elseif($form->isSubmitted() && !$form->isValid()) {

                // Si l'utilisateur a sélectionné des images, on les stocke en session
                $sessionFoodHandler->savePicturesInSession($form->get('pictureFile')->getData());

            }elseif(!$form->isSubmitted() && $request->isMethod('POST')){

                // On vient d'ajouter un aliment depuis la page de liste des aliments
                // On stocke les aliments et leur quantité sélectionnée en session
                if(true !== $response = $sessionFoodHandler->add($request->request->all())) {
                    // si !true => aucun aliment n'a été sélectionné
                    // on redirige vers la page des aliments avec un message d'erreur
                    $session->set('food_error_quantity', 'Merci d\'indiquer une quantité');

                    return $response;
                }

                // return $this->render('dish/form_layout.html.twig', [
                //     'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
                //     'dishForm' => $form->createView(),
                // ], new Response(null, 422));
                return $this->redirectToRoute('app_dish_new');
            }
        // }

        return $this->render('dish/form_layout.html.twig', [
            'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
            'dishForm' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/{id}/edit', name: 'app_dish_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
            Dish $dish, 
            EntityManagerInterface $manager, 
            Request $request,
            FoodGroupRepository $foodGroupRepository, 
            DishFoodHandler $dishFoodHandler,
            SessionFoodHandler $sessionFoodHandler,
            UploaderHelper $uploaderHelper,
            ValidatorInterface $validator
    ): Response
    {
        // $this->denyAccessUnlessGranted('EDIT_DISH', $dish);
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        
        //dump($session->all());
        $session = $request->getSession();
        // dd($request->request->all());
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);
        // dd($dish);
        
        // Si aucun aliment en session, on stocke les aliments du plat en session

        if(!$session->has('recipe_foods') || empty($session->get('recipe_foods'))) {
        // if(!$session->has('recipe_foods')) {
            $sessionFoodHandler->addFromDishObject($dish);
        }

        if(!$session->has('recipe_picture')) {
            $session->set('recipe_picture', $dish->getPicture());
        }

        // if($request->isMethod('POST'))
        // {
            if($form->isSubmitted() && $form->isValid()) {

               //L'utilisateur supprime une photo
            //    if(null !== $picRankForDelete = $form->get('picRankForDelete')->getData()) {
            //         $session->set('recipe_dish', $dish);
            //         return $this->redirectToRoute('app_pic_dish_delete', [
            //             'rank' => $picRankForDelete,
            //             "dish_id" => $dish->getId()
            //         ]);
            //     }
                
                if($form->get('saveAndAdd')->isClicked()) {

                    // if($form->isValid()) {
                        // dd($dish->getPathPicture());
                        // $manager->remove($dish->getPicturePath());
                        // $manager->flush();

                        // On ajoute les images sélectionnées dans la session
                        // qui ont déja été uploadées
                        // if($session->has('recipe_picture')) {
                        //     $dish->setPicture($session->get('recipe_picture'));
                        // }

                        // On ajoute (ou remplace l'image) avec une éventuelle nouvelle image
                        // dd($form->get('pictureFile'));
                        if (null !== $pictureFile = $form->get('pictureFile')->getData()) { 

                            $pictureConstraint = new Assert\File([
                                    'maxSize' => '5M',
                                    'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'],
                                    'mimeTypesMessage' => 'Merci de choisir une image valide',
                            ]);

                            $errorsPic = $validator->validate(
                                $pictureFile,
                                [$pictureConstraint]
                            );

                            if(isset($errorsPic[0])) {
                                $session->set('recipe_error_pic', $errorsPic[0]->getMessage());

                                $request->getSession()->set('recipe_dish', $dish);
                                
                                return $this->redirectToRoute('app_dish_edit', [
                                    'id' => $dish->getId(),
                                ]);
                            }

                            if(null !== $dish->getPicture()) {
                                unlink($this->getParameter('uploads_base_dir').'/'.UploaderHelper::DISH.'/'.$dish->getPicture());
                            }
                            $pictureName = $uploaderHelper->upload($pictureFile, UploaderHelper::DISH);
                            $dish->setPicture($pictureName);
                        }

                        // On supprime les anciens élements DishFood, DishFoodGroup, DishFoodGroupParent 
                        // liés au plat Dish
                        $dishFoodHandler->removeDishFoodElement($dish);
                
                        $manager->flush();
                        // On recrée les nouveaux élements DishFood, DishFoodGroup, DishFoodGroupParent liés au plat Dish
                        $dish = $dishFoodHandler->createDishFoodElement($dish);
                        $session->clear();

                        $manager->persist($dish);
                        $manager->flush();

                        //dd($dish->getDishFoods()->toArray());

                        $this->addFlash('success', 'Le plat a bien été modifié.');

                        return $this->redirectToRoute('app_dish_show', [
                            'id' => $dish->getId(),
                            'slug' => $dish->getSlug()
                        ]);

                // }elseif ($form->getClickedButton()->getConfig()->hasOption('food_group')){

                //     $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

                // }

                // }elseif($form->get('saveQuantityFood')->isClicked()) {

                //     // Ici l'utilisateur a saisi un aliment sur le champs de recherche et a saisi et validé 
                //     // la quantité et l'unité de mesure qu'il souhaite ajouté à la recette,
                //     // depuis les champs de la fenêtre modale

                //     // Si l'utilisateur a sélectionné des images, on les stocke en session
                //     $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());
                //     // L'objet dish est (re)stocké en session
                //     $session->set('recipe_dish', $dish);
              
                //     // On récupère les données quantité et unité de mesure saisies
                //     // Champ quantityFood => Formulaire imbriqué de type QuantityFoodFormType

                //     $sessionFoodHandler->addFromSearchBox($form->get('quantityFood')->getData());

                //     return $this->redirectToRoute('app_dish_edit', [
                //             'id'=> $dish->getId()
                //     ]);

                }elseif($form->getClickedButton()->getConfig()->hasOption('food_group')){

                    // Si l'utilisateur a sélectionné des images, on les stocke en session
                    $sessionFoodHandler->savePicturesInSession($form->get('pictureFile')->getData());

                    // L'objet dish est stocké en session
                    $session->set('recipe_dish', $dish);

                    $session->set('route_referer', 
                        $this->generateUrl($request->attributes->get('_route'), ['id' => $dish->getId()])
                    );

                    return $this->redirectToRoute('app_food_list', [
                                        'slug' => $form->getClickedButton()->getConfig()
                                                        ->getOption('food_group')->getSlug(),
                    ]);

                }

            }elseif(!$form->isSubmitted() && $request->isMethod('POST')){

                // B
                // On stocke les aliments et leur quantité sélectionnée en session
                // dd($request->request->all());
                $sessionFoodHandler->add($request->request->all());
            }  
        //}

        return $this->render('dish/form_layout.html.twig', [
            'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
            'dishForm' => $form->createView(),
            'dish' => $dish,
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/{id}/remove', name: 'app_dish_remove')]
    public function remove(?Dish $dish, EntityManagerInterface $manager, Request $request)
    {
        $manager->remove($dish);
        $manager->flush();

        $this->addFlash('notice', 'Le plat a bien été supprimé');

        return $this->redirectToRoute('app_dashboard_index');
    }

    // #[Route('/{id}/edit', name: 'app_dish_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    // public function edit(Dish $dish, 
    //             EntityManagerInterface $manager, 
    //             Request $request, 
    //             SessionInterface $session,
    //             FoodGroupRepository $foodGroupRepository, 
    //             DishFoodHandler $dishFoodHandler,
    //             SessionFoodHandler $sessionFoodHandler,
    //             UploaderHelper $uploaderHelper
    //             ): Response
    // {
    //     // $this->denyAccessUnlessGranted('EDIT_DISH', $dish);
        
    //     //dump($session->all());
        
    //     $form = $this->createForm(DishType::class, $dish);
    //     $form->handleRequest($request);
        
    //     // Si aucun aliment en session, on stocke les aliments du plat en session

    //     if(!$session->has('recipe_foods') || empty($session->get('recipe_foods'))) {
    //         $sessionFoodHandler->addFromDishObject($dish);
    //     }

    //     if(!$session->has('recipe_pictures')) {
    //         $session->set('recipe_pictures', $dish->getPictures()->toArray());
    //     }

    //     if($request->isMethod('POST'))
    //     {
    //         if($form->isSubmitted()) {

    //            //L'utilisateur supprime une photo
    //            if(null !== $picRankForDelete = $form->get('picRankForDelete')->getData()) {
    //                 $session->set('recipe_dish', $dish);
    //                 return $this->redirectToRoute('app_pic_dish_delete', [
    //                     'rank' => $picRankForDelete,
    //                     "dish_id" => $dish->getId()
    //                 ]);
    //             }
                
    //             if($form->get('saveAndAdd')->isClicked()) {

    //                 if($form->isValid()) {

    //                     foreach($dish->getPictures() as $picture) {
    //                         $manager->remove($picture);
    //                     }
    //                     $manager->flush();

    //                     // On ajoute les images sélectionnées dans la session
    //                     // qui ont déja été uploadées
    //                     if($session->get('recipe_pictures')) {
    //                         foreach($session->get('recipe_pictures') as $picture) {
    //                             $dish->addPicture($picture);
    //                         }
    //                     }

    //                     // On ajoute les images sélectionnées dans le input file
    //                     if($picturesFile = $form->get('picturesFile')->getData()) {
    //                         foreach($picturesFile as $pictureFile) {
    //                             $picture = $uploaderHelper->uploadDishPictures($pictureFile);
    //                             $dish->addPicture($picture);
    //                         }
    //                     }

    //                     // On supprime les anciens élements DishFood, DishFoodGroup, DishFoodGroupParent 
    //                     // liés au plat Dish
    //                     $dishFoodHandler->removeDishFoodElement($dish);
                
    //                     $manager->flush();
    //                     // On recrée les nouveaux élements DishFood, DishFoodGroup, DishFoodGroupParent liés au plat Dish
    //                     $dish = $dishFoodHandler->createDishFoodElement($dish);
    //                     //dd($dish);
    //                     $session->clear();

    //                     //$manager->persist($dish);
    //                     $manager->flush();

    //                     //dd($dish->getDishFoods()->toArray());

    //                     $this->addFlash('success', 'Le plat a bien été modifié.');

    //                     return $this->redirectToRoute('app_dish_show', [
    //                         'id' => $dish->getId(),
    //                         'slug' => $dish->getSlug()
    //                     ]);

    //                 }else{

    //                     $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

    //                 }

    //             }elseif($form->get('saveQuantityFood')->isClicked()) {

    //                 // Ici l'utilisateur a saisi un aliment sur le champs de recherche et a saisi et validé 
    //                 // la quantité et l'unité de mesure qu'il souhaite ajouté à la recette,
    //                 // depuis les champs de la fenêtre modale

    //                 // Si l'utilisateur a sélectionné des images, on les stocke en session
    //                 $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());
    //                 // L'objet dish est (re)stocké en session
    //                 $session->set('recipe_dish', $dish);
              
    //                 // On récupère les données quantité et unité de mesure saisies
    //                 // Champ quantityFood => Formulaire imbriqué de type QuantityFoodFormType

    //                 $sessionFoodHandler->addFromSearchBox($form->get('quantityFood')->getData());

    //                 return $this->redirectToRoute('app_dish_edit', [
    //                         'id'=> $dish->getId()
    //                 ]);

    //             }elseif($form->getClickedButton()->getConfig()->hasOption('food_group')){

    //                 // Si l'utilisateur a sélectionné des images, on les stocke en session
    //                 $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

    //                 // L'objet dish est stocké en session
    //                 $session->set('recipe_dish', $dish);

    //                 $session->set('route_referer', 
    //                     $this->generateUrl($request->attributes->get('_route'), ['id' => $dish->getId()])
    //                 );

    //                 return $this->redirectToRoute('app_food_list', [
    //                                     'slug' => $form->getClickedButton()->getConfig()
    //                                                     ->getOption('food_group')->getSlug(),
    //                 ]);

    //             }

    //         }else{

    //             // B
    //             // On stocke les aliments et leur quantité sélectionnée en session
    //             $sessionFoodHandler->addFromFoodList($request->request->all());
    //         }  
    //     }

    //     return $this->render('dish/edit.html.twig', [
    //         'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
    //         'dishForm' => $form->createView(),
    //         'dish' => $dish,
    //     ]);
    // }

    #[Route('/{id}/show/{slug}', name: 'app_dish_show', options: ['expose'=> true])]
    public function show(Request $request, Dish $dish)
    {
        // $dishDate = $dish->getUpdatedAt();
        
        // $response = new Response();
        // $response->setLastModified($dishDate);
        // $response->setPublic();
      
        // if($response->isNotModified($request)) {
        //     return $response;
        // }

        // return $this->render('dish/show.html.twig', [
        //     'dish' => $dish,
        // ], $response);

        return $this->render('dish/show.html.twig', [
            'dish' => $dish,
        ]);
    }

    #[Route('/food/{idFood<\d+>}/edit/{idDish?}', name: 'app_dish_food_edit', methods: ['GET', 'POST'])]
    public function editFood(FoodRepository $foodRepository, UnitMeasureRepository $unitMeasureRepository, SessionFoodHandler $sessionFoodHandler, FoodUtil $foodUtil, Request $request, int $idFood, ?string $idDish)
    {
        // $form = $this->createForm(QuantityFoodFormType::class, null, [
        //     'action' => $this->generateUrl('app_dish_food_edit', ['idFood' => (int)$idFood, 'idDish' => (int)$idDish]),
        //     'idFood' => $idFood
        // ]);
        
        // $form->handleRequest($request);

        // if($form->isSubmitted() && $form->isValid()) {
        //     $sessionFoodHandler->addFromSearchBox($form->getData());
        //     if(!$idDish) {
        //         return $this->redirectToRoute('app_dish_new');
        //     }else{
        //         return $this->redirectToRoute('app_dish_edit', ['id' => $idDish]);
        //     }
        // }

        if($request->query->get('ajax')) {
            $sessionFoodHandler->modifyQuantity($idFood, $request->query->all());

            return $this->render('dish/_quantity_food.html.twig', [
                // 'form' => $form->createView(),
                // 'idFood' => $idFood,
                'quantity' => $request->query->get('new_quantity'),
                'unitMeasure' => $request->query->get('new_unit_measure'),
                'idFood' => $idFood,
                'idDish' => $idDish,
            ]);
        }

        // if($request->isMethod('POST')) {
        //     $sessionFoodHandler->add($request->request->all());
        //     if(!$idDish) {
        //         return $this->redirectToRoute('app_dish_new');
        //     }else{
        //         return $this->redirectToRoute('app_dish_edit', ['id' => $idDish]);
        //     }
        // }



        // return $this->render('dish/_modal_edit_quantity_food.html.twig', [
        //             // 'form' => $form->createView(),
        //             // 'idFood' => $idFood,
        //             'food' => $foodRepository->findOneById($idFood),
        //             'idDish' => $idDish,
        //             'unitMeasures' => $unitMeasureRepository->findAll(),
        // ]);
   
        return $this->render('dish/_form_edit_food.html.twig', [
            // 'form' => $form->createView(),
            // 'idFood' => $idFood,
            'food' => $foodRepository->findOneById($idFood),
            'idDish' => $idDish,
            'unitMeasures' => $unitMeasureRepository->findAll(),
        ]);
    }

    #[Route('/food/{foodgroup_alias}/{id_food<\d+>}/delete/{id_dish<\d+>}', name: 'app_dish_food_delete')]
    public function deletefood(Request $request, $foodgroup_alias, $id_food, $id_dish = null)
    {
        $foods = $request->getSession()->get('recipe_foods');
        unset($foods[$foodgroup_alias][$id_food]);
        // if(empty($foods[$foodgroup_alias]))
        //     unset($foods[$foodgroup_alias]);
        $request->getSession()->set('recipe_foods', $foods);

        if($id_dish)
        {
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $id_dish
            ]);
        }

        return $this->redirectToRoute('app_dish_new'); 
    }

    #[Route('/list', name: 'app_dish_list')]
    public function list(Request $request, DishRepository $dishRepository, FoodGroupRepository $foodGroupRepository)
    {
        $fg = !empty($request->query->get('fg')) ? $request->query->all()['fg'] : [];

        $favoriteDishesId = array_map(function($dish) {
            return $dish->getId();
        }, $this->getUser()->getFavoriteDishes()->toArray());

		if(empty($fg) && $request->query->get('ajax')) {

            if($request->query->get('ajax')) {
                return $this->render('dish/_dish_list.html.twig', [
                        'dishes' => null,
                   'lastResults' => true,
              'favoriteDishesId' => $favoriteDishesId,
                ]);
            }

		}

        $keyword = !empty($request->query->get('q')) ? $request->query->get('q') : null;
        $type = !empty($request->query->get('type')) ? $request->query->get('type') : "type.dish.all";
        $page = !empty($request->query->get('page')) ? $request->query->get('page') : 0;
        $freeGluten = !empty($request->query->get('freeGluten')) ? $request->query->get('freeGluten') : false;
		$freeLactose = !empty($request->query->get('freeLactose')) ? $request->query->get('freeLactose') : false;

        $limit = 12;

        $lastResults = false;

		if("none" !== $fg) {
            $allDishes = $dishRepository->myFindByKeywordAndFGAndTypeAndLactoseAndGluten(
                $keyword,
                $fg,
                $freeLactose,
                $freeGluten,
                $type
            );
			$offset = $page * $limit;
			$dishes = array_slice($allDishes, $offset, $limit);
			if(count($dishes) < 10) {
				$lastResults = true;
			}
			if(10 === count($dishes)) {
				$lastDishes = array_pop($dishes);
				$lastAllDishes = array_pop($allDishes);
				if($lastDishes->getId() == $lastAllDishes->getId()) {
					$lastResults = true;
				}
			}
		}else{
            $lastResults = true;
			$dishes = [];
		}

        if($request->query->get('ajax')) {
			return $this->render('dish/_dish_list.html.twig', [
                    'dishes' => $dishes,
               'lastResults' => $lastResults,
          'favoriteDishesId' => $favoriteDishesId,
			]);
		}

        return $this->render('dish/list.html.twig', [
        'foodGroupsSelected' => $fg,
                    'dishes' => $dishes,
                'foodGroups' => $foodGroupRepository->findAll(),
               'lastResults' => $lastResults,
          'favoriteDishesId' => $favoriteDishesId,
        ]);
    }

    #[Route('/food/list/preview/{idDish?}', name: 'app_dish_food_list_preview')]
    public function foodListPreview(Request $request, EntityManagerInterface $manager, FoodRepository $foodRepository, SearchService $searchService, UnitMeasureRepository $unitMeasureRepository, ?int $idDish)
    {
        return $this->render('dish/_search_food_list_preview.html.twig', [
            'foods' => $foodRepository->myFindByKeyword($request->query->get('q')),
            'query' => $request->query->get('q'),
            'unitMeasures' => $unitMeasureRepository->findAll(),
            'idDish' => $idDish,
        ]);
    }

    #[Route('/list_json/{limit}/{offset}', name: 'app_dish_list_json')]
    public function listJson(DishRepository $dishRepository, SerializerInterface $serializer, int  $limit = 10, int $offset = 0)
    {
        $dishes = $dishRepository->findBy([], ['name' => 'ASC'], $limit, $offset);

        $data = $serializer->serialize($dishes, 'json', [
                'groups' => 'list_dish',
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'
            ]
        );
        
        $response = new Response($data);
        $response->headers->set('Content-type', 'application/json');

        return new Response($data);
    }

    #[Route('/session/clear/{id<\d+>?}', name: 'app_dish_food_session_clear')]
    public function clear(SessionFoodHandler $sessionFoodHandler, ?int $id)
    {
        $sessionFoodHandler->removeAll();

        if($id){
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $id
            ]);
        }

        return $this->redirectToRoute('app_dish_new');
    }

    #[Route('/download/{id<\d+>?}', name: 'app_dish_download')]
    public function download(Dish $dish = null, $dirFileRecipe)
    {
        $filename = sprintf('recipe-%d-%s.pdf', $dish->getId(), $dish->getSlug());

        $fp = fopen($dirFileRecipe. '/' . $filename, 'w');

        if($fp) {
            fwrite($fp, $dish->getName());
        }

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response = new Response();
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/count')]
    public function count(EntityManagerInterface $manager)
    {
        return new Response(count($manager->getRepository(Dish::class)->findAll()));
    }

    #[Route('/show-error-picture')]
    public function showErrorPicture(Request $request): Response
    {
        $message = $request->getSession()->get('recipe_error_pic');
        $request->getSession()->remove('recipe_error_pic');

        return new Response($message);
    }

    #[Route('/show-error-food-quantity')]
    public function showErrorFoodQuantity(Request $request): Response
    {
        $message = $request->getSession()->get('food_error_quantity');
        $request->getSession()->remove('food_error_quantity');

        return new Response($message);
    }

    // #[Route('/update-gluten-lactose')]
    // public function updateGlutenLactose(DishRepository $dishRepo, EntityManagerInterface $em)
    // {
    //     foreach($dishRepo->findAll() as $dish) {
    //         $dish->setHaveLactose(false);
    //         $dish->setHaveGluten(false);
            
    //         foreach($dish->getDishFoods() as $dishFood) {
    //             if($dishFood->getFood()->isHaveLactose()) {
    //                 $dish->sethaveLactose(true);
    //             }
    //             if($dishFood->getFood()->isHaveGluten()) {
    //                 $dish->sethaveGluten(true);
    //             }
    //         }

    //         $em->persist($dish);
    //     }

    //     $em->flush();

    //     return new Response('OK');
    // }
}
