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

class EmailConfirmationCodeController extends AbstractController
{
    #[Route('/confirmation-code', name: 'app_confirmation_code', methods: ['GET', 'POST'], defaults: ['no_header' => true])]
    public function confirm(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {

        $email = $request->getSession()->get('confirmation_email');

        if (!$email) {
            return $this->redirectToRoute('app_login');
        }

        $user = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Déjà confirmé
        if ($user->getIsVerified()) {
            $this->addFlash('success', 'Votre compte est déjà confirmé');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(ConfirmationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $submittedCode = $form->get('code')->getData();

            // Code expiré
            if ($user->getConfirmationCodeExpiresAt() < new \DateTime()) {
                $this->addFlash('error', 'Le code a expiré');
                return $this->redirectToRoute('app_confirmation_code');
            }

            // Vérification
            if (!password_verify(
                    (string) $submittedCode,
                    $user->getConfirmationCode()
                )
            ) {
                $this->addFlash('error', 'Le code est incorrect');
                return $this->redirectToRoute('app_confirmation_code');
            }

            // ✅ Succès
            $user->setIsVerified(true);
            $user->setConfirmationCode(null);
            $user->setConfirmationCodeExpiresAt(null);

            $entityManager->flush();

            $this->addFlash('success', 'Votre compte est activé, vous pouvez vous identifier');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/confirmation_code.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/resend-confirmation', name: 'app_resend_confirmation', methods: ['POST'])]
    public function resendConfirmation(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $email = $request->request->get('email');
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user || $user->getIsVerified()) {
            return $this->redirectToRoute('app_login');
        }

        // Générer un nouveau code
        $confirmationCode = random_int(100000, 999999);
        $user->setConfirmationCode($passwordHasher->hashPassword($user, $confirmationCode))
            ->setConfirmationCodeExpiresAt(new \DateTime('+15 minutes'))
            ->setConfirmationAttempts(0);
        $em->flush();

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
