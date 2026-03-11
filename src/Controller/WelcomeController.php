<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WelcomeController.php
 *
 * Gère l'affichage de la page de bienvenue et l'acceptation par l'utilisateur.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class WelcomeController extends AbstractController
{
    /**
     * Affiche la page de bienvenue si l'utilisateur ne l'a pas encore vue.
     *
     * @param EntityManagerInterface $em
     * 
     * @return Response
     */
    #[Route('/welcome', name: 'app_welcome', methods: ['GET'])]
    public function welcome(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté, redirige vers login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si l'utilisateur a déjà vu la page, on le renvoie vers la saisie du profil
        if ($user->getHasSeenWelcome()) {
            return $this->redirectToRoute('app_profile_edit');
        }

        // Sinon, on affiche la page de bienvenue
        return $this->render('user/welcome.html.twig');
    }

    /**
     * Marque que l'utilisateur a accepté/consulté la page de bienvenue.
     *
     * @param EntityManagerInterface $em
     * 
     * @return Response
     */
    #[Route('/welcome/accept', name: 'app_welcome_accept', methods: ['POST'])]
    public function acceptWelcome(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Si l'utilisateur n'est pas connecté, redirige vers login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Marque que la page a été vue
        $user->setHasSeenWelcome(true);
        $em->flush();

        // Redirige vers la première étape du profil
        return $this->redirectToRoute('app_profile_edit');
    }
}