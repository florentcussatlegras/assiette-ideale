<?php

namespace App\Service;

use App\Entity\Dish;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\UploaderHelper;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DishPicture.php
 *
 * Service pour gérer l'upload et la validation des images d'un plat
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class DishPicture
{
    public function __construct(
        private UploaderHelper $uploaderHelper,
        private ValidatorInterface $validator,
        private SessionInterface $session
    ) {}

    /**
     * Upload une image du plat et la rattache à l'entité Dish
     *
     * @param Dish $dish Plat à mettre à jour
     * @param FormInterface $form Formulaire contenant le fichier
     */
    public function handlePicture(Dish $dish, FormInterface $form): void
    {
        $pictureFile = $form->get('pictureFile')->getData();

        if (!$pictureFile) {
            // Pas de fichier uploadé => rien à faire
            return;
        }

        // Valide le fichier image
        $pictureConstraint = new Assert\File([
            'maxSize' => '5M',
            'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'],
            'mimeTypesMessage' => 'Merci de choisir une image valide',
        ]);

        $errorsPic = $this->validator->validate($pictureFile, $pictureConstraint);

        if (isset($errorsPic[0])) {
            // Stocke l'erreur en session et stoppe le traitement
            $this->session->set('recipe_error_pic', $errorsPic[0]->getMessage());
            return;
        }

        // Upload le fichier et récupère le nom
        $pictureName = $this->uploaderHelper->upload($pictureFile, UploaderHelper::DISH);

        // Lie l'image à l'entité Dish
        $dish->setPicture($pictureName);
    }
}