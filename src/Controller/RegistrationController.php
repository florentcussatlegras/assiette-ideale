<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\Type\RegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * RegistrationController.php
 *
 * Gère le processus d'inscription utilisateur :
 *  - Création de compte
 *  - Envoi du code de confirmation
 *  - Vérification et validation de l'email
 *  - Renvoi d'email de confirmation si nécessaire
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
class RegistrationController extends AbstractController
{
    /**
     * Inscription d'un nouvel utilisateur.
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @param UserPasswordHasherInterface $passwordHasher
     * @param MailerInterface $mailer
     * @param LoggerInterface $logger
     *
     * @return Response
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'], defaults: ['no_header' => true])]
    public function register(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): Response {
        // Redirection si l'utilisateur est déjà connecté
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('app_dashboard_index');
        }

        // Création d'un nouvel utilisateur et formulaire
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash du mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Génération du code de confirmation à 6 chiffres
            $confirmationCode = random_int(100000, 999999);

            $user
                ->setConfirmationCode($passwordHasher->hashPassword($user, $confirmationCode))
                ->setConfirmationCodeExpiresAt(new \DateTime('+15 minutes'))
                ->setConfirmationAttempts(0);

            // Persistance en base
            $manager->persist($user);
            $manager->flush();

            // Stocke l'email en session pour l'étape suivante
            $request->getSession()->set('confirmation_email', $user->getEmail());

            try {
                // Envoi du mail de confirmation
                $mailer->send((new TemplatedEmail())
                    ->subject('Confirmation de votre adresse électronique')
                    ->htmlTemplate('emails/confirmation_email.html.twig')
                    ->from('contact@fc-nutrition.com')
                    ->to(new Address($user->getEmail()))
                    ->context([
                        'username' => $user->getUsername(),
                        'confirmationCode' => $confirmationCode,
                    ])
                );

                $this->addFlash('success', 'Un code de confirmation vous a été envoyé par email.');
            } catch (TransportExceptionInterface $e) {
                // Log technique
                $logger->error('Erreur envoi email', [
                    'exception' => $e->getMessage(),
                    'email' => $user->getEmail(),
                ]);

                // Message utilisateur
                $this->addFlash('warning',
                    'Votre compte a été créé, mais l’email de confirmation n’a pas pu être envoyé.'
                );
            }

            // Redirection vers la page de saisie du code
            return $this->redirectToRoute('app_confirmation_code');
        }

        // Affichage du formulaire (422 si soumis mais invalide)
        return $this->render('registration/register.html.twig', [
            'registerForm' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    /**
     * Vérifie l'email de l'utilisateur via le lien envoyé.
     *
     * @param Request $request
     * @param VerifyEmailHelperInterface $verifyEmailHelper
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $manager
     *
     * @return Response
     */
    #[Route('/verify', name: 'app_verify_email', methods: ['GET'])]
    public function verifyUserEmail(
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper,
        UserRepository $userRepository,
        EntityManagerInterface $manager
    ): Response {
        $user = $userRepository->find($request->query->get('id'));
        if (!$user) {
            throw $this->createNotFoundException();
        }

        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail()
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_register');
        }

        // Activation de l'utilisateur et validation de l'étape "profil param"
        $user->setIsVerified(true);
        $user->setValidStepProfiles(['parameter']);
        $manager->flush();

        $this->addFlash('success', 'Account Verified! You can now log in.');

        // Redirection vers la connexion
        $response = new RedirectResponse($this->generateUrl('app_login'));

        // Gestion du cookie pour suivre les emails inscrits récemment
        $emails = [];
        if ($request->cookies->has('already_register_last_7_days')) {
            $emails = unserialize($request->cookies->get('already_register_last_7_days'));
            $response->headers->clearCookie('already_register_last_7_days');
        }
        $emails[] = $user->getEmail();
        $cookie = Cookie::create('already_register_last_7_days')
            ->withValue(serialize($emails))
            ->withExpires(new \DateTime("+7 days"))
            ->withSecure(true);

        $response->headers->setCookie($cookie);

        return $response;
    }

    /**
     * Renvoi d'un email de vérification si nécessaire.
     *
     * @param User $user
     * @param Request $request
     * @param UserRepository $userRepository
     * @param VerifyEmailHelperInterface $verifyEmailHelper
     *
     * @return Response
     */
    #[Route('/verify/resend/{id}', name: 'app_verify_resend_email', methods: ['GET', 'POST'], requirements: ['id' => '\d+'], defaults: ['no_header' => true])]
    public function resendVerifyEmail(
        User $user,
        Request $request,
        VerifyEmailHelperInterface $verifyEmailHelper
    ): Response {
        if (!$user) {
            throw $this->createNotFoundException();
        }

        // Formulaire caché contenant juste l'ID pour valider la requête
        $form = $this->createFormBuilder()
            ->add('id', HiddenType::class)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $signatureComponents = $verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            $this->addFlash('success', 'Un nouvel email de confirmation de votre inscription vous a été envoyé.');

            return $this->redirectToRoute('app_logout', [
                'confirmSignature' => $signatureComponents->getSignedUrl()
            ]);
        }

        // Affichage du formulaire de renvoi
        return $this->render('registration/resend_verify_email.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}