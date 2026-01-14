<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Form\Type\DishType;
use App\Service\UploaderHelper;
use App\Service\DishFoodHandler;
use App\Repository\DishRepository;
use App\Service\SessionFoodHandler;
use Algolia\SearchBundle\SearchService;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


/**
 * @Route("/plat2")
 */
class DishController extends AbstractController
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @Route("/nouveau", name="app_dish_new", methods={"GET", "POST"})
     */
    public function new(EntityManagerInterface $manager, 
                Request $request, SessionInterface $session, 
                FoodGroupRepository $foodGroupRepository,
                DishFoodHandler $dishFoodHandler,
                SessionFoodHandler $sessionFoodHandler,
                UploaderHelper $uploaderHelper): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        // dump($session->clear());
        //dump($session->all());

        $dish = new Dish();
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);
        
        if($request->isMethod('POST'))
        {
          
            if($form->isSubmitted()) {
              
                //L'utilisateur supprime une photo
                if(null !== $picRankForDelete = $form->get('picRankForDelete')->getData()) {
                    $session->set('recipe_dish', $dish);
                    return $this->redirectToRoute('app_pic_dish_delete', [
                        'rank' => $picRankForDelete
                    ]);
                }

                // On a cliqué sur le bouton de validation du formulaire
                if($form->get('saveAndAdd')->isClicked()) {
                    
                    if($form->isValid()) {

                        // On ajoute au plat les images 
                        // sélectionnées dans la session qui ont déja été uploadées
                        if($session->get('recipe_pictures')) {
                            foreach($session->get('recipe_pictures') as $picture) {
                                $dish->addPicture($picture);
                            }
                        }
    
                        // On ajoute les images sélectionnées dans le input file
                        if($picturesFile = $form->get('picturesFile')->getData()) {
                            foreach($picturesFile as $pictureFile) {
                                $picture = $uploaderHelper->uploadDishPictures($pictureFile);
                                $dish->addPicture($picture);
                            }
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

                    }else{

                        $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

                    }

                }elseif($form->get('saveQuantityFood')->isClicked()) {

                    dd('save quantity food');
                    // Ici l'utilisateur a saisi un aliment sur le champs de recherche et a saisi et validé 
                    // la quantité et l'unité de mesure qu'il souhaite ajouté à la recette,
                    // depuis les champs de la fenêtre modale
   
                    // Si l'utilisateur a sélectionné des images, on les stocke en session
                    $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());
                    // L'objet dish est (re)stocké en session
                    $session->set('recipe_dish', $dish);
              
                    // On récupère les données quantité et unité de mesure saisies
                    // Champ quantityFood => Formulaire imbriqué de type QuantityFoodFormType
                    $sessionFoodHandler->addFromSearchBox($form->get('quantityFood')->getData());

                    return $this->redirectToRoute('app_dish_new');

                }elseif($form->getClickedButton()->getConfig()->hasOption('food_group')) {

                    // Si le bouton saisi est de type DishFoodGroupSubmitType : 
                    // On stocke les données du formulaire en session 
                    // afin de les récupérer en retour de saisie des aliments
                    // On redirige vers la liste des aliments du groupe concerné
                    
                    // Si l'utilisateur a sélectionné des images, on les stocke en session
                    $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

                    // L'objet dish est (re)stocké en session
                    $session->set('recipe_dish', $dish);
                    
                    $session->set('route_referer', 
                        $this->generateUrl($request->attributes->get('_route'))
                    );

                    return $this->redirectToRoute('app_food_list', [
                            'slug' => $form->getClickedButton()->getConfig()->getOption('food_group')
                                                                                            ->getSlug(),

                    ]);

                }

            }else{

                // B
                // On stocke les aliments et leur quantité sélectionnée en session
                $sessionFoodHandler->addFromFoodList($request->request->all());
            }  
        }

        return $this->render('dish/new.html.twig', [
            'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
            'dishForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", requirements={"id" : "\d+"}, name="app_dish_edit", methods={"GET", "POST"})
     */
    public function edit(Dish $dish, 
                EntityManagerInterface $manager, 
                Request $request, 
                SessionInterface $session,
                FoodGroupRepository $foodGroupRepository, 
                DishFoodHandler $dishFoodHandler,
                SessionFoodHandler $sessionFoodHandler,
                UploaderHelper $uploaderHelper
                ): Response
    {
        $this->denyAccessUnlessGranted('EDIT_DISH', $dish);
        
        //dump($session->all());
        
        $form = $this->createForm(DishFormType::class, $dish);
        $form->handleRequest($request);
        
        // Si aucun aliment en session, on stocke les aliments du plat en session
        if(!$session->has('recipe_foods')) {
            $sessionFoodHandler->addFromDishObject($dish);
        }

        if(!$session->has('recipe_pictures')) {
            $session->set('recipe_pictures', $dish->getPictures()->toArray());
        }

        if($request->isMethod('POST'))
        {
            if($form->isSubmitted()) {

               //L'utilisateur supprime une photo
               if(null !== $picRankForDelete = $form->get('picRankForDelete')->getData()) {
                    $session->set('recipe_dish', $dish);
                    return $this->redirectToRoute('app_pic_dish_delete', [
                        'rank' => $picRankForDelete,
                        "dish_id" => $dish->getId()
                    ]);
                }
                
                if($form->get('saveAndAdd')->isClicked()) {

                    if($form->isValid()) {

                        foreach($dish->getPictures() as $picture) {
                            $manager->remove($picture);
                        }
                        $manager->flush();

                        // On ajoute les images sélectionnées dans la session
                        // qui ont déja été uploadées
                        if($session->get('recipe_pictures')) {
                            foreach($session->get('recipe_pictures') as $picture) {
                                $dish->addPicture($picture);
                            }
                        }

                        // On ajoute les images sélectionnées dans le input file
                        if($picturesFile = $form->get('picturesFile')->getData()) {
                            foreach($picturesFile as $pictureFile) {
                                $picture = $uploaderHelper->uploadDishPictures($pictureFile);
                                $dish->addPicture($picture);
                            }
                        }

                        // On supprime les anciens élements DishFood, DishFoodGroup, DishFoodGroupParent 
                        // liés au plat Dish
                        $dishFoodHandler->removeDishFoodElement($dish);
                
                        $manager->flush();
                        // On recrée les nouveaux élements DishFood, DishFoodGroup, DishFoodGroupParent liés au plat Dish
                        $dish = $dishFoodHandler->createDishFoodElement($dish);

                        $session->clear();

                        //$manager->persist($dish);
                        $manager->flush();

                        //dd($dish->getDishFoods()->toArray());

                        $this->addFlash('success', 'Le plat a bien été modifié.');

                        return $this->redirectToRoute('app_dish_show', [
                            'id' => $dish->getId(),
                            'slug' => $dish->getSlug()
                        ]);

                    }else{

                        $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

                    }

                }elseif($form->get('saveQuantityFood')->isClicked()) {

                    // Ici l'utilisateur a saisi un aliment sur le champs de recherche et a saisi et validé 
                    // la quantité et l'unité de mesure qu'il souhaite ajouté à la recette,
                    // depuis les champs de la fenêtre modale

                    // Si l'utilisateur a sélectionné des images, on les stocke en session
                    $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());
                    // L'objet dish est (re)stocké en session
                    $session->set('recipe_dish', $dish);
              
                    // On récupère les données quantité et unité de mesure saisies
                    // Champ quantityFood => Formulaire imbriqué de type QuantityFoodFormType
   
                    $sessionFoodHandler->addFromSearchBox($form->get('quantityFood')->getData());

                    return $this->redirectToRoute('app_dish_edit', [
                            'id'=> $dish->getId()
                    ]);

                }elseif($form->getClickedButton()->getConfig()->hasOption('food_group')){

                    // Si l'utilisateur a sélectionné des images, on les stocke en session
                    $sessionFoodHandler->savePicturesInSession($form->get('picturesFile')->getData());

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

            }else{

                // B
                // On stocke les aliments et leur quantité sélectionnée en session
                $sessionFoodHandler->addFromFoodList($request->request->all());
            }  
        }

        return $this->render('dish/edit.html.twig', [
            'foodGroups' => $foodGroupRepository->findAll([], ['ranking' => 'ASC']),
            'dishForm' => $form->createView(),
            'dish' => $dish,
        ]);
    }

    /**
     * @Route("/{id}/show/{slug}", name="app_dish_show")
     */
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

    /**
     * @Route("food/{foodgroup_alias<\D+>}/{id_food<\d+>}/delete/{id_dish<\d+>}", name="app_dish_food_delete")
     */
    public function deletefood(RequestStack $requestStack, $foodgroup_alias, $id_food, $id_dish = null)
    {
        $foods = $requestStack->getSession()->get('recipe_foods');
        unset($foods[$foodgroup_alias][$id_food]);
        if(empty($foods[$foodgroup_alias]))
            unset($foods[$foodgroup_alias]);
        $requestStack->getSession()->set('recipe_foods', $foods);

        if($id_dish)
        {
            return $this->redirectToRoute('app_dish_edit', [
                'id' => $id_dish
            ]);
        }

        return $this->redirectToRoute('app_dish_new'); 
    }

    /**
     * @Route("/list", name="app_dish_list")
     */
    public function list(DishRepository $dishRepository)
    {
        return $this->render('dish/list.html.twig', [
            'dishs' => $dishRepository->findAll(),
        ]);
    }

    #[Route('/session/clear/{id<\d+>?}', name: 'app_dish_food_session_clear')]
    public function clear(SessionFoodHandler $sessionFoodHandler, ?int $id)
    {
        $sessionFoodHandler->remove();

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
}
