<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminUtilityController extends AbstractController
{
    /**
     * @Route("/admin/utility/dishs", methods={"GET"}, name="admin_utility_dishs")
     */
    public function getDishsApi(DishRepository $dishRepository, Request $request)
    {
        return $this->json([
            'dishs' => $dishRepository->myFindByKeywordAndFGP($request->query->get('query'))
        ]);
    }
}