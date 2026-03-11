<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

/**
 * SecurityController.php
 *
 * Gère :
 *  - Connexion et déconnexion des utilisateurs
 *  - Authentification via OAuth2 (Google, GitHub)
 *  - Création automatique d’utilisateurs OAuth si nécessaire
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class SecurityController extends AbstractController
{
    /**
     * Formulaire de login classique.
     *
     * @param AuthenticationUtils $authenticationUtils Pour récupérer le dernier nom d'utilisateur et l'erreur
     * @param UserRepository $userRepository Pour vérifier si l'utilisateur existe et est vérifié
     * 
     * @return Response
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'], defaults: ['no_header' => true])]
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository): Response
    {
        // Récupère l'utilisateur courant si déjà connecté
        /** @var User $user */
        $user = $this->getUser();
        $nonVerifiedUser = null;

        // Récupère le dernier username saisi et l'erreur éventuelle
        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        // Si un utilisateur a tenté de se connecter
        if ($lastUsername) {
            // On récupère l'utilisateur par email
            $user = $userRepository->findOneBy(['email' => $lastUsername]);

            // Si l'utilisateur existe mais n'a pas vérifié son email
            if ($user && !$user->getIsVerified()) {
                $nonVerifiedUser = $user; // On mémorise l'utilisateur pour le template
                $error = null; // Supprime l'erreur classique pour ne pas afficher "Identifiants invalides"
            }
        }

        // Affiche le formulaire de connexion avec les informations nécessaires
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'non_verified_user' => $nonVerifiedUser,
        ]);
    }

    /**
     * Déconnexion.
     * Symfony gère la déconnexion automatiquement, la méthode reste vide.
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void {}

    // -------------------------------------------------------------------------
    // GOOGLE LOGIN
    // -------------------------------------------------------------------------
    /**
     * Redirection vers Google pour l'authentification OAuth2.
     */
    #[Route('/connect/google', name: 'connect_google', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry)
    {
        // Redirige vers Google avec la permission d'obtenir l'email
        return $clientRegistry->getClient('google')->redirect(['email']);
    }

    /**
     * Callback Google OAuth2 après l'autorisation.
     * - Récupère l'utilisateur Google
     * - Crée un utilisateur si inexistant
     * - Authentifie l'utilisateur
     */
    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function connectGoogleCheck(
        Request $request,
        EntityManagerInterface $em,
        ClientRegistry $clientRegistry,
        UserAuthenticatorInterface $userAuthenticator,
        SocialAuthenticator $authenticator
    ): Response {
        try {
            // Récupère le client Google
            $client = $clientRegistry->getClient('google');

            // Récupère les informations de l'utilisateur connecté sur Google
            $googleUser = $client->fetchUser();

            $email = $googleUser->getEmail(); // Email de l'utilisateur
            $googleId = $googleUser->getId(); // ID unique Google
            $name = $googleUser->getName() ?: 'google_user_' . uniqid(); // Nom ou fallback

            // Recherche l'utilisateur existant dans la base
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Création d'un nouvel utilisateur Google
                $user = new User();
                $user->setEmail($email)
                     ->setUsername($name)
                     ->setGoogleId($googleId)
                     ->setPassword('') // Pas de mot de passe
                     ->setIsVerified(true); // Google étant fiable, on le marque vérifié
                $em->persist($user);
            } else {
                // Met à jour l'ID Google si l'utilisateur existait déjà
                $user->setGoogleId($googleId);
            }

            $em->flush(); // Persiste en base

            // Authentifie l'utilisateur avec Symfony Security
            return $userAuthenticator->authenticateUser($user, $authenticator, $request);

        } catch (\Exception $e) {
            // En cas d'erreur, ajoute un message flash et redirige vers login
            $this->addFlash('error', 'Erreur Google : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    // -------------------------------------------------------------------------
    // GITHUB LOGIN
    // -------------------------------------------------------------------------
    /**
     * Redirection vers GitHub pour l'authentification OAuth2.
     */
    #[Route('/connect/github', name: 'connect_github', methods: ['GET'])]
    public function connectGithub(ClientRegistry $clientRegistry)
    {
        // Redirige vers GitHub avec la permission email
        return $clientRegistry->getClient('github')->redirect(['user:email']);
    }

    /**
     * Callback GitHub OAuth2 après autorisation.
     * - Crée ou récupère l'utilisateur GitHub
     * - Authentifie l'utilisateur
     */
    #[Route('/connect/github/check', name: 'connect_github_check')]
    public function connectGithubCheck(
        Request $request,
        ClientRegistry $clientRegistry,
        UserAuthenticatorInterface $userAuthenticator,
        EntityManagerInterface $em,
        SocialAuthenticator $authenticator
    ): Response {
        try {
            $client = $clientRegistry->getClient('github');
            $githubUser = $client->fetchUser(); // Récupère les infos GitHub
            $githubId = $githubUser->getId();
            $data = $githubUser->toArray();

            // Email et username (fallback si non fourni)
            $email = $githubUser->getEmail() ?? ($data['login'] . '@github.local');
            $username = $data['login'] ?? ('github_user_' . uniqid());

            // Recherche l'utilisateur existant
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Création d'un nouvel utilisateur
                $user = new User();
                $user->setEmail($email)
                     ->setUsername($username)
                     ->setGithubId($githubId)
                     ->setPassword('')
                     ->setIsVerified(true);
                $em->persist($user);
            } else {
                // Met à jour l'ID GitHub si l'utilisateur existait déjà
                $user->setGithubId($githubId);
            }

            $em->flush(); // Persistance

            // Authentifie l'utilisateur
            return $userAuthenticator->authenticateUser($user, $authenticator, $request);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur GitHub : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}