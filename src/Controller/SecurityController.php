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

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $userRepository)
    {
        $user = $this->getUser();
        $nonVerifiedUser = null;

        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        // Si l'utilisateur a tenté de se connecter
        if ($lastUsername) {

            $user = $userRepository->findOneBy(['email' => $lastUsername]);

            if ($user && !$user->getIsVerified()) {
                // On mémorise cet utilisateur pour le template
                $nonVerifiedUser = $user;

                // Supprime l'erreur d'authentification classique pour ne pas afficher "Identifiants invalides"
                $error = null;
            }
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'non_verified_user' => $nonVerifiedUser,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout() {}

    // -------------------------------------------------------------------------
    // GOOGLE LOGIN
    // -------------------------------------------------------------------------
    #[Route('/connect/google', name: 'connect_google', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('google')->redirect(['email']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function connectGoogleCheck(
        Request $request,
        EntityManagerInterface $em,
        ClientRegistry $clientRegistry,
        UserAuthenticatorInterface $userAuthenticator,
        SocialAuthenticator $authenticator
    ): Response {
        try {
            $client = $clientRegistry->getClient('google');
            $googleUser = $client->fetchUser();

            $email = $googleUser->getEmail();
            $googleId = $googleUser->getId();
            $name = $googleUser->getName() ?: 'google_user_' . uniqid();

            // Recherche par email
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Crée un nouvel utilisateur
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($name);
                $user->setGoogleId($googleId);
                $user->setPassword(''); // pas de mot de passe
                $user->setIsVerified(true);
                $em->persist($user);
            } else {
                $user->setGoogleId($googleId);
            }

            $em->flush();

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Google : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    // -------------------------------------------------------------------------
    // GITHUB LOGIN
    // -------------------------------------------------------------------------
    #[Route('/connect/github', name: 'connect_github', methods: ['GET'])]
    public function connectGithub(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('github')->redirect(['user:email']);
    }

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
            $githubUser = $client->fetchUser();

            $githubId = $githubUser->getId();
            $data = $githubUser->toArray();

            $email = $githubUser->getEmail() ?? ($data['login'] . '@github.local');
            $username = $data['login'] ?? ('github_user_' . uniqid());

            // Recherche par email (ou création)
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setGithubId($githubId);
                $user->setPassword('');
                $user->setIsVerified(true);
                $em->persist($user);
            } else {
                $user->setGithubId($githubId);
            }

            $em->flush();

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur GitHub : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
