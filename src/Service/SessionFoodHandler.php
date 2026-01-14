<?php

namespace App\Service;

use App\Entity\Dish;
use App\Service\FoodUtil;
use App\Service\UploaderHelper;
use App\Repository\FoodRepository;
use App\Repository\DishFoodRepository;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\RequestStack;
use Florent\QuantityConverterBundle\QuantityConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use  Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

class SessionFoodHandler
{
    // private $dishFoodRepository;
    // private $foodRepository;
    // private $quantityConverter;
    // private $csrfTokenManager;
    
    public function __construct(
            private RequestStack $requestStack, 
            private DishFoodRepository $dishFoodRepository,
            private FoodRepository $foodRepository, 
            private CsrfTokenManagerInterface $csrfTokenManager, 
            private UploaderHelper $uploaderHelper,
            private FoodUtil $foodUtil,
            private UrlGeneratorInterface $urlGenerator
    )
    {
        // $this->session = $requestStack->getSession();
        // $this->dishFoodRepository = $dishFoodRepository;
        // $this->foodRepository = $foodRepository;
        // $this->quantityConverter = $quantityConverter;
        // $this->csrfTokenManager = $csrfTokenManager;
        // $this->uploaderHelper = $uploaderHelper;
    }

    // Envoi les aliments de la request envoyée par le formulaires/liste d'aliment dans la session
    // public function addFromFoodList(array $request)
    // {
    //     if(
    //         !$this->csrfTokenManager->isTokenValid(new CsrfToken('select_foods', $request['_token']))
    //     )
    //     {
    //         throw new InvalidCsrfTokenException('Le token soumis est invalide.');
    //     }

    //     $this->add($request['foods'], $request['food_group_code']);

    //     return true;
    // }

    // Extrait les aliments d'un plat en mode edit pour les envoyer dans la session
    public function addFromDishObject(Dish $dish)
    {       
        $dishFoods = $this->dishFoodRepository->findByDishAndGroupByFoodGroup($dish);

        $dishFoods = array_filter($dishFoods, function($values){
            return !empty($values);
        });

        array_walk(
            $dishFoods,
            function(&$dishFoodRows, $foodGroupCode){
                foreach($dishFoodRows as $key => $dishFoodRow){
                    $food = $dishFoodRow->getFood();
                    $foodRow = [
                        'quantity' => $dishFoodRow->getQuantityReal(),
                        'unit_measure' => $dishFoodRow->getUnitMeasure()->getAlias(),
                        'food' => $food,
                        'quantity_g' => $dishFoodRow->getQuantityG(),
                    ];
                    unset($dishFoodRows[$key]);
                    $dishFoodRows[$food->getId()] = $foodRow;
                }
            }
        );

        $this->requestStack->getSession()->set('recipe_foods', $dishFoods);

        return true;

    }

    public function addFromSearchbox(array $quantityFood)
    {
        $food = $this->foodRepository->findOneBy(['id' => (int)$quantityFood['foodId']]);
        
        $foods[$quantityFood['foodId']] = [
                    'quantity' => $quantityFood['quantity'],
                'unit_measure' => $quantityFood['unitMeasure']->getAlias()
        ];

        $this->add($foods, $food->getFoodGroup()->getAlias());

        return true;
    }

    public function add(?array $datas) 
    {
        $session = $this->requestStack->getSession();

        if(
            !$this->csrfTokenManager->isTokenValid(new CsrfToken('select_foods', $datas['_token']))
        )
        {
            throw new InvalidCsrfTokenException('Le token soumis est invalide.');
        }

        // $foodGroupSlug = $request['food_group_slug'];
        
        if(isset($datas['foods'])) {
            $foods = $datas['foods'];
            // On supprime les aliments aux quantités vides
            $foods = array_filter($foods, function ($values) {
                return !empty($values['quantity']);
            });
        }

        if(empty($foods)) {

            // $session->getFlashBag()->add('error', 'Merci d\'indiquer une quantité d\'aliments');
            $session->set('food_error_quantity', 'Merci d\'indiquer une quantité');

            // $url = $this->urlGenerator->generate('app_food_list', [
            //     'slug' => $foodGroupSlug
            // ]);
            $params = isset($datas['fg']) ? ['fg' => $datas['fg']] : [];

            $url = $this->urlGenerator->generate('app_food_list', $params);

            return new RedirectResponse($url);
        }

        // On ajoute 
        // - un champs contenant l'objet Food complet dans le tableau ajouté ensuite
        //   en session pour accéder au nom et photo des aliments en sortie 
        //   dans la liste des aliments du formulaire des plats
        // - un champs contenant la quantité sélectionné en grammes

        array_walk(

            $foods,

            function(&$quantitiesInfos, $foodId){

                $food = $this->foodRepository->findOneBy(['id' => $foodId]);
                $quantitiesInfos['food'] = $food;
                $quantitiesInfos['foodgroup_alias'] = $food->getFoodGroup()->getAlias();
                $quantitiesInfos['quantity_g'] = 
                    $this->foodUtil->convertInGr(
                        $quantitiesInfos['quantity'], 
                        $foodId,
                        $quantitiesInfos['unit_measure']
                    );
            }
            
        );
        
        $sessionFoods = $session->get('recipe_foods', []);
        
        if(!empty($foods)){
            foreach($foods as $foodId => $foodQuantities) {
                $sessionFoods[$foodQuantities['foodgroup_alias']][$foodId] = $foodQuantities;
            }
        }
        // elseif(array_key_exists($foodGroupAlias, $sessionFoods)) {
        //     unset($sessionFoods[$foodGroupAlias]);
        // }

        !empty($sessionFoods) ? $session->set('recipe_foods', $sessionFoods) : $session->remove('recipe_foods');

        return true;
    }

    public function savePicturesInSession(?UploadedFile $pictureFile)
    {
        $session = $this->requestStack->getSession();

        if($pictureFile) {
            // $picturesSession = $session->get('recipe_pictures', []);
            // foreach($pictureFiles as $pictureFile) {
            //     $picture = $this->uploaderHelper->uploadDishPictures($pictureFile);
            //     $picturesSession[] = $picture;
            // }
            $picture = $this->uploaderHelper->upload($pictureFile, UploaderHelper::DISH);
            $session->set('recipe_picture', $picture);

            return true;
        }

        return false;
    }

    public function modifyQuantity($idFood, ?array $datas)
    {
        $session = $this->requestStack->getSession();

        if(
            !$this->csrfTokenManager->isTokenValid(new CsrfToken('modify_quantity_food', $datas['_token']))
        )
        {
            throw new InvalidCsrfTokenException('Le token soumis est invalide.');
        }

        $food = $this->foodRepository->findOneById($idFood);
        $foodGroupAlias = $food->getFoodGroup()->getAlias();
        $recipeFoods = $session->get('recipe_foods');

        $quantityInfos['food'] = $food;
        $quantityInfos['foodgroup_alias'] = $foodGroupAlias;
        $quantityInfos['quantity'] = $datas['new_quantity'];
        $quantityInfos['unit_measure'] = $datas['new_unit_measure'];
        if('g' !== $datas['new_unit_measure']) {
            $quantityInfos['quantity_g'] = $this->foodUtil->convertInGr($datas['new_quantity'], $food->getId(), $datas['new_unit_measure']);
        }else{
            $quantityInfos['quantity_g'] = $datas['new_quantity'];
        }

        $recipeFoods[$foodGroupAlias][$food->getId()] = $quantityInfos;
        $session->set('recipe_foods', $recipeFoods);
    }

    public function remove(string $foodGroupAlias, int $idFood)
    {
        $session = $this->requestStack->getSession();

        $listFoods = $session->get('recipe_foods');
        unset($listFoods[$foodGroupAlias][$idFood]);
        if(empty($listFoods[$foodGroupAlias])) {
            unset($listFoods[$foodGroupAlias]);
        }
        $session->set('recipe_foods', $listFoods);

        return true;
    }

    public function removeAll()
    {
        $session = $this->requestStack->getSession();

        if($session->has('recipe_dish'))
            $session->remove('recipe_dish');

        if($session->has('recipe_foods'))
            $session->remove('recipe_foods');
        
        if($session->has('recipe_picture'))
            $session->remove('recipe_picture');

        if($session->has('recipe_error_pic'))
            $session->remove('recipe_error_pic');

        return true;
    }
}