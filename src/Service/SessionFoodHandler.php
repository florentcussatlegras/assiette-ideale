<?php

namespace App\Service;

use App\Entity\Dish;
use App\Service\FoodUtil;
use App\Service\UploaderHelper;
use App\Repository\FoodRepository;
use App\Repository\DishFoodRepository;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

/**
 * SessionFoodHandler.php
 * 
 * Service gérant la manipulation des aliments et plats dans la session utilisateur.
 * 
 * Fonctionnalités principales :
 *  - d'ajouter des aliments/plat en session
 *  - de modifier ou supprimer des aliments
 *  - de sauvegarder des images associées
 *  - de gérer la quantité en grammes pour chaque aliment
 * 
 * La session est utilisée pour préparer les formulaires de création/édition de plats.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class SessionFoodHandler
{
    public function __construct(
        private RequestStack $requestStack, 
        private DishFoodRepository $dishFoodRepository,
        private FoodRepository $foodRepository, 
        private CsrfTokenManagerInterface $csrfTokenManager, 
        private UploaderHelper $uploaderHelper,
        private FoodUtil $foodUtil,
        private UrlGeneratorInterface $urlGenerator
    )
    {}

    /**
     * Extrait les aliments d'un plat (Dish) et les met en session
     * afin de pré-remplir le formulaire d'édition.
     *
     * @param Dish $dish Le plat à traiter
     * 
     * @return bool Toujours true
     */
    public function addFromDishObject(Dish $dish): bool
    {       
        // Récupère tous les aliments du plat groupés par FoodGroup
        $dishFoods = $this->dishFoodRepository->findByDishAndGroupByFoodGroup($dish);

        // Supprime les groupes vides
        $dishFoods = array_filter($dishFoods, fn($values) => !empty($values));

        // Reformate les données pour mettre chaque aliment avec son ID comme clé
        array_walk(
            $dishFoods,
            function(&$dishFoodRows, $foodGroupCode){
                foreach($dishFoodRows as $key => $dishFoodRow){
                    $food = $dishFoodRow->getFood();
                    $foodRow = [
                        'quantity' => $dishFoodRow->getQuantityReal(), // quantité réelle saisie
                        'unit_measure' => $dishFoodRow->getUnitMeasure()->getAlias(), // unité de mesure
                        'food' => $food, // objet Food complet pour affichage
                        'quantity_g' => $dishFoodRow->getQuantityG(), // quantité convertie en grammes
                    ];
                    unset($dishFoodRows[$key]);
                    $dishFoodRows[$food->getId()] = $foodRow;
                }
            }
        );

        // Sauvegarde dans la session
        $this->requestStack->getSession()->set('recipe_foods', $dishFoods);

        return true;
    }

    /**
     * Ajoute un aliment sélectionné depuis la recherche dans la session
     *
     * @param array $quantityFood Tableau contenant l'ID, la quantité et l'unité
     * 
     * @return bool Toujours true
     */
    public function addFromSearchbox(array $quantityFood): bool
    {
        // Récupère l'objet Food depuis l'ID
        $food = $this->foodRepository->findOneBy(['id' => (int)$quantityFood['foodId']]);
        
        // Prépare le tableau des aliments à ajouter
        $foods[$quantityFood['foodId']] = [
            'quantity' => $quantityFood['quantity'],
            'unit_measure' => $quantityFood['unitMeasure']->getAlias()
        ];

        // Ajoute l'aliment dans la session
        $this->add($foods);

        return true;
    }

    /**
     * Ajoute un ou plusieurs aliments dans la session
     * - Vérifie le CSRF token
     * - Filtre les aliments avec quantité vide
     * - Calcule la quantité en grammes
     *
     * @param array|null $datas Données issues du formulaire contenant '_token' et 'foods'
     * 
     * @return bool|RedirectResponse True si succès ou redirection si erreur
     * 
     * @throws InvalidCsrfTokenException Si le CSRF token est invalide
     */
    public function add(?array $datas)
    {
        $session = $this->requestStack->getSession();

        // Vérifie la validité du CSRF token
        if(!$this->csrfTokenManager->isTokenValid(new CsrfToken('select_foods', $datas['_token'])))
        {
            throw new InvalidCsrfTokenException('Le token soumis est invalide.');
        }

        // Récupère les aliments du formulaire
        if(isset($datas['foods'])) {
            $foods = $datas['foods'];
            // Supprime les aliments avec quantité vide
            $foods = array_filter($foods, fn($values) => !empty($values['quantity']));
        }

        // Si aucun aliment n'est présent, renvoie sur la liste avec message d'erreur
        if(empty($foods)) {
            $session->set('food_error_quantity', 'Merci d\'indiquer une quantité');
            $params = isset($datas['fg']) ? ['fg' => $datas['fg']] : [];
            $url = $this->urlGenerator->generate('app_food_list', $params);
            return new RedirectResponse($url);
        }

        // Parcours chaque aliment pour compléter les informations nécessaires
        array_walk($foods, function(&$quantitiesInfos, $foodId){
            $food = $this->foodRepository->findOneBy(['id' => $foodId]);
            $quantitiesInfos['food'] = $food; // objet Food complet
            $quantitiesInfos['foodgroup_alias'] = $food->getFoodGroup()->getAlias(); // alias du groupe
            $quantitiesInfos['quantity_g'] = $this->foodUtil->convertInGr(
                $quantitiesInfos['quantity'], 
                $foodId,
                $quantitiesInfos['unit_measure']
            ); // quantité convertie en grammes
        });

        // Récupère les aliments déjà en session
        $sessionFoods = $session->get('recipe_foods', []);

        // Ajoute les nouveaux aliments au tableau existant
        if(!empty($foods)){
            foreach($foods as $foodId => $foodQuantities) {
                $sessionFoods[$foodQuantities['foodgroup_alias']][$foodId] = $foodQuantities;
            }
        }

        // Sauvegarde ou supprime la session si vide
        !empty($sessionFoods) ? $session->set('recipe_foods', $sessionFoods) : $session->remove('recipe_foods');

        return true;
    }

    /**
     * Sauvegarde une image d'aliment/plat dans la session
     *
     * @param UploadedFile|null $pictureFile
     * 
     * @return bool True si l'image a été sauvegardée
     */
    public function savePicturesInSession(?UploadedFile $pictureFile): bool
    {
        $session = $this->requestStack->getSession();

        if($pictureFile) {
            // Upload du fichier et sauvegarde du nom en session
            $picture = $this->uploaderHelper->upload($pictureFile, UploaderHelper::DISH);
            $session->set('recipe_picture', $picture);
            return true;
        }

        return false;
    }

    /**
     * Modifie la quantité d'un aliment déjà présent en session
     *
     * @param int $idFood ID de l'aliment
     * @param array|null $datas Données contenant 'new_quantity', 'new_unit_measure' et '_token'
     * 
     * @return void
     * 
     * @throws InvalidCsrfTokenException Si le CSRF token est invalide
     */
    public function modifyQuantity(int $idFood, ?array $datas): void
    {
        $session = $this->requestStack->getSession();

        if(!$this->csrfTokenManager->isTokenValid(new CsrfToken('modify_quantity_food', $datas['_token'])))
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
        $quantityInfos['quantity_g'] = $datas['new_unit_measure'] !== 'g'
            ? $this->foodUtil->convertInGr($datas['new_quantity'], $food->getId(), $datas['new_unit_measure'])
            : $datas['new_quantity'];

        $recipeFoods[$foodGroupAlias][$food->getId()] = $quantityInfos;
        $session->set('recipe_foods', $recipeFoods);
    }

    /**
     * Supprime un aliment spécifique de la session
     *
     * @param string $foodGroupAlias Alias du groupe d'aliment
     * @param int $idFood ID de l'aliment
     * 
     * @return bool Toujours true
     */
    public function remove(string $foodGroupAlias, int $idFood): bool
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

    /**
     * Supprime toutes les données liées à la recette en session
     *
     * @return bool Toujours true
     */
    public function removeAll(): bool
    {
        $session = $this->requestStack->getSession();

        if($session->has('recipe_dish')) $session->remove('recipe_dish');
        if($session->has('recipe_foods')) $session->remove('recipe_foods');
        if($session->has('recipe_picture')) $session->remove('recipe_picture');
        if($session->has('recipe_error_pic')) $session->remove('recipe_error_pic');

        return true;
    }
}