<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Member;
use App\Repository\UserRepository;
use App\Form\Type\RegistrationType;
use App\Form\Type\MemberType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;


class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'], defaults: ['no_header' => true])]
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer, LoggerInterface $logger)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            return $this->redirectToRoute('app_dashboard_index');

        
        // L'inscription se fait en 3 étapes
        // Paramètres/profile/besoins energétiques
        // On crée un User que l'on stocke d'abord en session
        // On l'enregistre ensuite à 'étape 2

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $confirmationCode = random_int(100000, 999999);

            $user
                ->setConfirmationCode($passwordHasher->hashPassword($user, $confirmationCode))
                ->setConfirmationCodeExpiresAt(new \DateTime('+15 minutes'))
                ->setConfirmationAttempts(0);

            $manager->persist($user);
            $manager->flush();

            $request->getSession()->set(
                'confirmation_email',
                $user->getEmail()
            );

            try {

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

                $this->addFlash(
                    'success',
                    'Un code de confirmation vous a été envoyé par email.'
                );

            } catch (TransportExceptionInterface $e) {

                // log technique
                $logger->error('Erreur envoi email', [
                    'exception' => $e->getMessage(),
                    'email' => $user->getEmail(),
                ]);

                // message utilisateur
                $this->addFlash('warning',
                    'Votre compte a été créé, mais l’email de confirmation n’a pas pu être envoyé.'
                );

            }

            // redirection vers la page de saisie du code
            return $this->redirectToRoute('app_confirmation_code');

        }

        return $this->render('registration/register.html.twig', [
            'registerForm' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    #[Route('/verify', name: 'app_verify_email', methods: ['GET'])]
    public function verifyUserEmail(Request $request, VerifyEmailHelperInterface $verifyEmailHelper, 
    UserRepository $userRepository, EntityManagerInterface $manager): Response
    {
        $user = $userRepository->find($request->query->get('id'));
      
        if (!$user) {
            throw $this->createNotFoundException();
        }
        try {
            $verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail(),
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());
            return $this->redirectToRoute('app_register');
        }

        $user->setIsVerified(true);
        $user->setValidStepProfiles(['parameter']);
        $manager->flush();

        $this->addFlash('success', 'Account Verified! You can now log in.');

        $response = new RedirectResponse($this->generateUrl('app_login'));
        
        // On crée un cookie qui stocke les emails à chaque inscription pour les utilisateurs 
        // s'inscrivant plusieurs fois en moins d'une semaine. Ce cookie est ensuite vérifier à chaque
        // réinscription dans /register et permettra l'envoi d'une notification à l'admin
        $emails = [];
        if($request->cookies->has('already_register_last_7_days'))
        {
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

    #[Route('/verify/resend/{id}', name: 'app_verify_resend_email', methods: ['GET', 'POST'], requirements: ['id' => '\d+'], defaults: ['no_header' => true])]
    public function resendVerifyEmail(User $user, Request $request, UserRepository $userRepository, VerifyEmailHelperInterface $verifyEmailHelper)
    {
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()->add('id', HiddenType::class)->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {   
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

        return $this->render('registration/resend_verify_email.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
