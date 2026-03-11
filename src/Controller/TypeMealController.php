<?php

namespace App\Controller;

use App\Repository\TypeMealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * TypeMealController.php
 *
 * Gère l'affichage des types de repas disponibles.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class TypeMealController extends AbstractController
{
    /**
     * Liste tous les types de repas pour les afficher dans le choix utilisateur.
     *
     * @param TypeMealRepository $typeMealRepository
     * 
     * @return Response
     */
    #[Route('/type-meal', name: 'app_type_meal_choices', methods: ['GET'])]
    public function listChoices(TypeMealRepository $typeMealRepository): Response
    {
        // Récupère tous les types de repas depuis la base de données
        $typeMeals = $typeMealRepository->findAll();

        // Passe les données au template Twig pour affichage
        return $this->render('type_meal/choices.html.twig', [
            'typeMeals' => $typeMeals,
        ]);
    }
}