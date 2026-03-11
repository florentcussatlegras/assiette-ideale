<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\ResetPasswordFormType;
use App\Form\Type\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * ResetPasswordController.php
 *
 * Gère toutes les opérations liées à la réinitialisation des mots de passe :
 *  - demande de réinitialisation
 *  - envoi d'email avec token sécurisé
 *  - validation et reset du mot de passe
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private ResetPasswordHelperInterface $resetPasswordHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper, EntityManagerInterface $entityManager)
    {
        $this->resetPasswordHelper = $resetPasswordHelper; // Service pour gérer les tokens de reset
        $this->entityManager = $entityManager; // Manager Doctrine pour persister les modifications
    }

    /**
     * Affiche et traite le formulaire de demande de réinitialisation du mot de passe.
     *
     * @param Request $request
     * @param MailerInterface $mailer
     * 
     * @return Response
     */
    #[Route('', name: 'app_forgot_password_request', defaults: ['no_header' => true])]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        // Création du formulaire de demande d'email pour le reset
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request); // Traitement de la requête POST si soumise

        if ($form->isSubmitted() && $form->isValid()) {
            // Si formulaire valide, traite l’envoi de l’email de réinitialisation
            return $this->processSendingPasswordResetEmail(
                $form->get('email')->getData(),
                $mailer
            );
        }

        // Sinon, affichage du formulaire vide ou avec erreurs
        return $this->render('security/request_password.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * Page de confirmation après la demande de réinitialisation.
     *
     * @return Response
     */
    #[Route('/check-email', name: 'app_check_email', defaults: ['no_header' => true])]
    public function checkEmail(): Response
    {
        // Récupère le token stocké en session si l'utilisateur a fait une demande
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            // Si aucun token, génère un token fictif pour ne pas révéler l'existence d'un utilisateur
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        // Affiche le template de confirmation avec le token (ou fictif)
        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    /**
     * Réinitialisation du mot de passe avec le token reçu par email.
     *
     * @param Request $request
     * @param string|null $token
     * @param UserPasswordHasherInterface $passwordHasher
     * 
     * @return Response
     */
    #[Route('/reset/{token?}', name: 'app_reset_password')]
    public function reset(Request $request, ?string $token = null, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Si le token est présent dans l'URL, on le stocke en session et on redirige vers la même route pour nettoyer l'URL
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        // Récupération du token depuis la session
        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token); 
            // Vérifie que le token est valide et récupère l'utilisateur associé
        } catch (ResetPasswordExceptionInterface $e) {
            // Token invalide ou expiré → on ajoute un message flash et redirige vers la demande de reset
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem validating your reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // Création du formulaire pour saisir le nouveau mot de passe
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request); // Traite la soumission du formulaire

        if ($form->isSubmitted() && $form->isValid()) {
            // Supprime la demande de reset pour éviter toute réutilisation du token
            $this->resetPasswordHelper->removeResetRequest($token);

            // Hash et enregistrement du nouveau mot de passe
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            $this->entityManager->flush(); // Persiste le changement en base

            $this->cleanSessionAfterReset(); // Nettoie le token en session

            // Redirige vers la page de login après succès
            return $this->redirectToRoute('app_login');
        }

        // Affiche le formulaire de reset avec les erreurs éventuelles
        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * Traite l’envoi de l’email de réinitialisation de mot de passe.
     *
     * @param string $emailFormData
     * @param MailerInterface $mailer
     * 
     * @return RedirectResponse
     */
    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $emailFormData,
        ]);

        // Ne pas révéler si l’utilisateur existe ou non → sécurité
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            // Génère un token de réinitialisation unique et sécurisé pour l'utilisateur
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // En cas d'erreur (ex: utilisateur supprimé entre temps), redirige vers check-email
            return $this->redirectToRoute('app_check_email');
        }

        // Prépare l'email avec le token et le template Twig
        $email = (new TemplatedEmail())
            ->from(new Address('contact@fc-nutrition.com', 'contact fc-nutrition'))
            ->to($user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email); // Envoi réel de l'email

        // Stocke le token dans la session pour pouvoir l'utiliser sur check-email
        $this->setTokenObjectInSession($resetToken);

        // Redirige vers la page de confirmation
        return $this->redirectToRoute('app_check_email');
    }
}