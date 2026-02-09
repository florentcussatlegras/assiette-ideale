<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    #[Route('/welcome', name: 'app_welcome', methods: ['GET'])]
    public function welcome(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // S’il l’a déjà vue → on le renvoie vers la saisie d’infos
        if ($user->getHasSeenWelcome()) {
            return $this->redirectToRoute('app_profile_edit');
        }

        // Sinon, on lui affiche la page
        return $this->render('user/welcome.html.twig');
    }

    #[Route('/welcome/accept', name: 'app_welcome_accept', methods: ['POST'])]
    public function acceptWelcome(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Marque qu’il a vu la page
        $user->setHasSeenWelcome(true);
        $em->flush();

        // Redirige vers la première étape du profil
        return $this->redirectToRoute('app_profile_edit');
    }
}
