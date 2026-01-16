<?php

namespace App\Controller;

use App\Repository\DishRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminUtilityController extends AbstractController
{
    #[Route('/admin/utility/dishs', name: 'admin_utility_dishs', methods: ['GET'])]
    public function getDishsApi(DishRepository $dishRepository, Request $request)
    {
        return $this->json([
            'dishs' => $dishRepository->myFindByKeywordAndFGP($request->query->get('query'))
        ]);
    }
}