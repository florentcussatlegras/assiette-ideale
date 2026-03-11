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

/**
 * FavoriteDishController.php
 * 
 * Contrôleur responsable de la gestion des plats favoris d'un utilisateur.
 * 
 * Fonctionnalités principales :
 *  - Afficher la liste des plats favoris de l'utilisateur.
 *  - Ajouter un plat aux favoris.
 *  - Supprimer un plat des favoris.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
#[Route('/favorite-dishes', name: 'app_favorite_dishes')]
class FavoriteDishController extends AbstractController
{
    /**
     * Affiche la liste des plats favoris de l'utilisateur.
     * 
     * @param Request $request Requête HTTP, utilisée pour détecter si la requête est AJAX
     * @param DishRepository $dishRepository Non utilisé ici mais disponible pour d'éventuelles extensions
     * @param FoodGroupRepository $foodGroupRepository Permet de récupérer tous les groupes alimentaires pour affichage
     * 
     * @return Response Rend le template Twig approprié selon le contexte (AJAX ou page complète)
     */
    #[Route('/list', name: '_list')]
    public function list(Request $request, DishRepository $dishRepository, FoodGroupRepository $foodGroupRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($request->query->get('ajax')) {
            return $this->render('dish/_dish_list.html.twig', [
                'dishes' => $user->getFavoriteDishes(),
            ]);
        }

        return $this->render('dish/list.html.twig', [
            'dishes' => $user->getFavoriteDishes(),
            'foodGroups' => $foodGroupRepository->findAll(),
        ]);
    }

    /**
     * Ajoute un plat aux favoris de l'utilisateur connecté.
     * 
     * @param Request $request Requête HTTP contenant éventuellement `dish_id`
     * @param EntityManagerInterface $em Pour persister les modifications de l'utilisateur
     * @param DishRepository $dishRepository Pour récupérer le plat correspondant à l'ID fourni
     * 
     * @return Response|JsonResponse Retourne un template Twig d'alerte ou un JSON selon le contexte
     */
    #[Route('/add', name: '_add')]
    public function add(Request $request, EntityManagerInterface $em, DishRepository $dishRepository)
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifie si la requête contient le paramètre 'dish_id'
        if ($request->query->has('dish_id')) {

            // Tente de récupérer le plat correspondant à l'ID fourni
            $dish = $dishRepository->findOneById((int)$request->query->get('dish_id'));

            if ($dish) {

                // Ajoute le plat à la liste des favoris de l'utilisateur
                $user->addFavoriteDishes($dish);

                // Persiste les changements dans l'entité User
                $em->persist($user);

                // Sauvegarde les modifications en base de données
                $em->flush();

                // Retourne un rendu Twig d'alerte pour informer l'utilisateur que le plat a été ajouté
                return $this->render('partials/alert/_alert.html.twig', [
                    'key' => 'notice',
                    'message' => sprintf('Le plat "%s" a été ajouté à vos favoris', $dish->getName()),
                ]);
            } else {
                // Si aucun plat correspondant n'a été trouvé, retourne un JSON d'erreur
                return new JsonResponse([
                    'error' => sprintf('Le plat id %d n\'existe pas!', (int)$request->query->get('dish_id'))
                ]);
            }
        }

        // Si aucun 'dish_id' n'est fourni dans la requête, retourne un JSON d'erreur
        return new JsonResponse(['error' => 'Veuillez indiquer un plat!']);
    }

    /**
     * Supprime un plat des favoris de l'utilisateur.
     * 
     * @param Request $request Requête HTTP contenant éventuellement 'dish_id' et paramètres AJAX
     * @param EntityManagerInterface $em Pour persister les modifications de l'utilisateur
     * @param DishRepository $dishRepository Pour récupérer le plat correspondant
     * 
     * @return JsonResponse|Response JSON en cas d'AJAX ou rendu Twig pour l'alerte
     */
    #[Route('/remove', name: '_remove')]
    public function remove(Request $request, EntityManagerInterface $em, DishRepository $dishRepository)
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifie si la requête contient le paramètre 'dish_id'
        if ($request->query->has('dish_id')) {

            // Tente de récupérer le plat correspondant à l'ID fourni
            $dish = $dishRepository->findOneById((int)$request->query->get('dish_id'));
            if (null !== $dish) {

                // Supprime le plat des favoris de l'utilisateur
                $user->removeFavoriteDishes($dish);

                // Persiste les modifications sur l'utilisateur
                $em->persist($user);

                // Sauvegarde les changements en base
                $em->flush();

                // Si la requête est en AJAX et qu'elle provient d'une liste, on renvoie JSON avec mise à jour du DOM
                if ($request->query->has('ajax') && $request->query->has('from_list')) {

                    return new JsonResponse([
                        // Mise à jour de la liste des favoris
                        'list' => $this->renderView('dish/_dish_list.html.twig', [
                            'dishes' => $user->getFavoriteDishes(),
                        ]),
                        // Message d'alerte pour informer l'utilisateur
                        'alert' => $this->renderView('partials/alert/_alert.html.twig', [
                            'key' => 'notice',
                            'message' => sprintf('Le plat "%s" a été supprimé de vos favoris', $dish->getName()),
                        ])
                    ]);
                } else {
                    // Si ce n'est pas une requête AJAX, on renvoie simplement le rendu de l'alerte
                    return $this->render('partials/alert/_alert.html.twig', [
                        'key' => 'notice',
                        'message' => sprintf('Le plat "%s" a été supprimé de vos favoris', $dish->getName()),
                    ]);
                }
            } else {
                // Si aucun plat correspondant n'a été trouvé, retourne un JSON d'erreur
                return new JsonResponse([
                    'error' => sprintf('Le plat id %d n\'existe pas!', (int)$request->query->get('dish_id'))
                ]);
            }
        }

        // Si aucun 'dish_id' n'est fourni dans la requête, retourne un JSON d'erreur
        return new JsonResponse(['error' => 'Veuillez indiquer un plat!']);
    }
}
