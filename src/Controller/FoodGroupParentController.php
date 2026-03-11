<?php

namespace App\Controller;

use App\Repository\FoodGroupParentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * FoodGroupParentController.php
 *
 * Gère l'affichage des groupes alimentaires parents.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
#[Route('/food-groups')]
class FoodGroupParentController extends AbstractController
{
    /**
     * Liste tous les groupes alimentaires parents.
     *
     * @param FoodGroupParentRepository $repository
     * 
     * @return Response
     */
    #[Route('', name: 'app_food_groups_index', methods: ['GET'])]
    public function index(FoodGroupParentRepository $repository): Response
    {
        // Récupère tous les groupes alimentaires depuis le repository
        $groups = $repository->findAll();

        // Passe les groupes au template Twig pour affichage
        return $this->render('food_group_parents/index.html.twig', [
            'groups' => $groups
        ]);
    }

    /**
     * Affiche un groupe alimentaire parent spécifique selon le slug.
     *
     * @param string $slug
     * @param FoodGroupParentRepository $repository
     * 
     * @return Response
     */
    #[Route('/{slug}', name: 'app_food_groups_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, FoodGroupParentRepository $repository): Response
    {
        // Recherche le groupe par son slug
        $group = $repository->findOneBy([
            'slug' => $slug
        ]);

        // Si aucun groupe trouvé, renvoie une erreur 404
        if (!$group) {
            throw $this->createNotFoundException();
        }

        // Passe le groupe au template Twig pour affichage
        return $this->render('food_group_parents/show.html.twig', [
            'group' => $group
        ]);
    }
}