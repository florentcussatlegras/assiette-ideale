<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * UserController.php
 *
 * Fournit des endpoints liés à l'utilisateur connecté.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class UserController extends AbstractController
{
    /**
     * Retourne les informations de l'utilisateur connecté au format JSON.
     *
     * @return JsonResponse
     */
    #[Route('api/me', name: 'app_user_api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function apiMe(): JsonResponse
    {
        // Récupère l'utilisateur courant
        $user = $this->getUser();

        // Retourne l'utilisateur en JSON avec le groupe de sérialisation 'user:read'
        return $this->json($user, 200, [], [
            'groups' => ['user:read']
        ]);
    }
}