<?php

namespace App\Controller;

use App\Repository\TypeMealRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TypeMealController extends AbstractController
{
    #[Route('/type-meal', name: 'app_type_meal_choices', methods:['GET'])]
    public function listChoices(TypeMealRepository $typeMealRepository): Response
    {
        return $this->render('type_meal/choices.html.twig', [
            'typeMeals' => $typeMealRepository->findAll(),
        ]);
    }
}