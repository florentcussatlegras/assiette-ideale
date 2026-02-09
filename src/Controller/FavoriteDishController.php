<?php

namespace App\Controller;

use App\Entity\Dish;
use App\Repository\DishRepository;
use App\Repository\FoodGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/favorite-dishes', name: 'app_favorite_dishes')]
class FavoriteDishController extends AbstractController
{

    #[Route('/list', name: '_list')]
    public function list(Request $request, DishRepository $dishRepository, FoodGroupRepository $foodGroupRepository)
    {
        if ($request->query->get('ajax')) {
            return $this->render('dish/_dish_list.html.twig', [
                'dishes' => $this->getUser()->getFavoriteDishes(),
            ]);
        }

        return $this->render('dish/list.html.twig', [
            'dishes' => $this->getUser()->getFavoriteDishes(),
            'foodGroups' => $foodGroupRepository->findAll(),
        ]);
    }

    #[Route('/add', name: '_add')]
    public function add(Request $request, EntityManagerInterface $em, DishRepository $dishRepository)
    {

        if ($request->query->has('dish_id')) {

            if (null !== $dish = $dishRepository->findOneById((int)$request->query->get('dish_id'))) {

                $user = $this->getUser();
                $user->addFavoriteDishes($dish);
                $em->persist($user);
                $em->flush();

                // return new JsonResponse(['success' => 'Le plat a été rajouté aux favoris']);
                return $this->render('partials/alert/_alert.html.twig', [
                    'key' => 'notice',
                    'message' => sprintf('Le plat "%s" a été ajouté à vos favoris', $dish->getName()),
                ]);
            } else {

                return new JsonResponse(['error' => sprintf('Le plat id %d n\'existe pas!', (int)$request->query->get('dish_id'))]);
            }
        }

        return new JsonResponse(['error' => 'Veuillez indiquer un plat!']);
    }

    #[Route('/remove', name: '_remove')]
    public function remove(Request $request, EntityManagerInterface $em, DishRepository $dishRepository)
    {

        if ($request->query->has('dish_id')) {

            if (null !== $dish = $dishRepository->findOneById((int)$request->query->get('dish_id'))) {

                $user = $this->getUser();
                $user->removeFavoriteDishes($dish);
                $em->persist($user);
                $em->flush();

                if ($request->query->has('ajax') && $request->query->has('from_list')) {

                    return new JsonResponse([
                        'list' => $this->renderView('dish/_dish_list.html.twig', [
                            'dishes' => $this->getUser()->getFavoriteDishes(),
                        ]),
                        'alert' => $this->renderView('partials/alert/_alert.html.twig', [
                            'key' => 'notice',
                            'message' => sprintf('Le plat "%s" a été supprimé de vos favoris', $dish->getName()),
                        ])
                    ]);
                } else {
                    return $this->render('partials/alert/_alert.html.twig', [
                        'key' => 'notice',
                        'message' => sprintf('Le plat "%s" a été supprimé de vos favoris', $dish->getName()),
                    ]);
                }
            } else {

                return new JsonResponse(['error' => sprintf('Le plat id %d n\'existe pas!', (int)$request->query->get('dish_id'))]);
            }
        }

        return new JsonResponse(['error' => 'Veuillez indiquer un plat!']);
    }
}
