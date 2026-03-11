<?php

namespace App\Service;

use App\Entity\Dish;
use App\Service\DishPicture;
use App\Service\DishFoodHandler;
use App\Service\SessionFoodHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service central pour gérer la logique métier des plats
 * 
 * - Gestion de la session
 * - Gestion de l'upload d'images
 * - Gestion des DishFood
 * - Redirections selon le bouton cliqué
 */
class DishWorkflow
{
    public function __construct(
        private SessionInterface $session,
        private DishPicture $pictureService,
        private DishFoodHandler $dishFoodHandler,
        private SessionFoodHandler $sessionFoodHandler,
        private EntityManagerInterface $manager
    ) {}

    /**
     * Récupère un plat depuis la session ou crée un nouveau
     */
    public function getDishFromSessionOrNew(): Dish
    {
        return $this->session->get('recipe_dish', new Dish());
    }

    /**
     * Traite le formulaire du plat
     *
     * @param FormInterface $form Formulaire DishType
     * @param Request $request Requête HTTP
     * @return Response|null Redirection si action terminée, sinon null pour afficher le formulaire
     */
    public function handleForm(FormInterface $form, Request $request): ?Response
    {
        // Formulaire non soumis => on ne fait rien
        if (!$form->isSubmitted()) {
            return null;
        }

        $dish = $form->getData();

        // Formulaire soumis mais invalide
        if (!$form->isValid()) {
            // On stocke les images uploadées en session pour les récupérer après
            $this->sessionFoodHandler->savePicturesInSession(
                $form->get('pictureFile')->getData()
            );
            return null;
        }

        // Bouton "saveAndAdd" cliqué
        if ($form->get('saveAndAdd')->isClicked()) {
            return $this->saveDish($dish, $form);
        }

        // Bouton de type DishFoodGroup cliqué
        if ($form->getClickedButton()->getConfig()->hasOption('food_group')) {
            return $this->redirectToFoodList($form, $request);
        }

        return null;
    }

    /**
     * Sauvegarde le plat dans la base, gère les images et DishFood
     */
    private function saveDish(Dish $dish, FormInterface $form): Response
    {
        // Gère l'image uploadée
        $this->pictureService->handlePicture($dish, $form);

        // Crée les éléments DishFood liés au plat
        $dish = $this->dishFoodHandler->createDishFoodElement($dish);

        // Vide la session
        $this->session->clear();

        // Persiste le plat
        $this->manager->persist($dish);
        $this->manager->flush();

        // Redirection vers la page du plat
        return new RedirectResponse('/dish/'.$dish->getId());
    }

    /**
     * Redirige vers la liste des aliments pour ajouter/modifier des aliments
     */
    private function redirectToFoodList(FormInterface $form, Request $request): Response
    {
        // Sauvegarde les images en session
        $this->sessionFoodHandler->savePicturesInSession(
            $form->get('pictureFile')->getData()
        );

        // Stocke l'objet dish en session
        $this->session->set('recipe_dish', $form->getData());

        // Redirection vers la liste des aliments
        return new RedirectResponse('/foods');
    }
}