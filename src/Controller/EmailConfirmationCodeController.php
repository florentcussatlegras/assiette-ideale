<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ConfirmationCodeType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * EmailConfirmationCodeController.php
 * 
 * Contrôleur gérant la saisie et la validation des codes de confirmation 
 * pour l'activation des comptes utilisateurs.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 * 
 */
class EmailConfirmationCodeController extends AbstractController
{
    /**
     * Affiche le formulaire de saisie du code de confirmation et valide l'utilisateur.
     *
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $entityManager Pour récupérer et mettre à jour l'utilisateur
     *
     * @return Response
     */
    #[Route('/confirmation-code', name: 'app_confirmation_code', methods: ['GET', 'POST'], defaults: ['no_header' => true])]
    public function confirm(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        // Récupère l'email stocké en session lors de l'inscription
        $email = $request->getSession()->get('confirmation_email');
        if (!$email) {
            return $this->redirectToRoute('app_login');
        }

        // Récupère l'utilisateur correspondant à l'email
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Si le compte est déjà vérifié, redirection
        if ($user->getIsVerified()) {
            $this->addFlash('success', 'Votre compte est déjà confirmé');
            return $this->redirectToRoute('app_home');
        }

        // Création du formulaire de code de confirmation
        $form = $this->createForm(ConfirmationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedCode = $form->get('code')->getData();

            // Vérifie si le code a expiré
            if ($user->getConfirmationCodeExpiresAt() < new \DateTime()) {
                $this->addFlash('error', 'Le code a expiré');
                return $this->redirectToRoute('app_confirmation_code');
            }

            // Vérifie si le code correspond
            if (!password_verify((string) $submittedCode, $user->getConfirmationCode())) {
                $this->addFlash('error', 'Le code est incorrect');
                return $this->redirectToRoute('app_confirmation_code');
            }

            // Succès : active l'utilisateur et supprime le code
            $user->setIsVerified(true)
                ->setConfirmationCode(null)
                ->setConfirmationCodeExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre compte est activé, vous pouvez vous identifier');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/confirmation_code.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Génère un nouveau code de confirmation et l'envoie par e-mail à l'utilisateur.
     *
     * @param Request $request Requête HTTP
     * @param UserRepository $userRepository Pour récupérer l'utilisateur
     * @param EntityManagerInterface $em Pour sauvegarder le code en base
     * @param MailerInterface $mailer Pour envoyer l'e-mail
     * @param UserPasswordHasherInterface $passwordHasher Pour hasher le code
     *
     * @return Response Redirection vers le formulaire de confirmation
     */
    #[Route('/resend-confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resendConfirmation(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $email = $request->request->get('email');
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || $user->getIsVerified()) {
            return $this->redirectToRoute('app_login');
        }

        // Génération et hash du nouveau code de confirmation
        $confirmationCode = random_int(100000, 999999);
        $user->setConfirmationCode($passwordHasher->hashPassword($user, $confirmationCode))
            ->setConfirmationCodeExpiresAt(new \DateTime('+15 minutes'))
            ->setConfirmationAttempts(0);

        $em->flush();

        // Envoi de l'e-mail de confirmation
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

        $this->addFlash('success', 'Un nouveau code de confirmation vous a été envoyé par e-mail.');

        return $this->redirectToRoute('app_confirmation_code');
    }
}
