<?php

namespace App\Controller;

use App\Repository\FoodGroupParentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/food-groups')]
class FoodGroupParentController extends AbstractController
{
    #[Route('', name: 'app_food_groups_index', methods: ['GET'])]
    public function index(FoodGroupParentRepository $repository)
    {
        $groups = $repository->findAll();

        return $this->render('food_group_parents/index.html.twig', [
            'groups' => $groups
        ]);
    }

    #[Route('/{slug}', name: 'app_food_groups_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, FoodGroupParentRepository $repository)
    {
        $group = $repository->findOneBy([
            'slug' => $slug
        ]);

        if (!$group) {
            throw $this->createNotFoundException();
        }

        return $this->render('food_group_parents/show.html.twig', [
            'group' => $group
        ]);
    }
}
