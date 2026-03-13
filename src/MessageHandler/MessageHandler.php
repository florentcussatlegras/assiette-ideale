<?php

namespace App\MessageHandler;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mime\Address;

/**
 * Gère l'envoi des messages soumis via le formulaire de contact.
 *
 * - Envoie un email à l'administrateur.
 * - Persiste le message en base de données.
 */
class MessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $manager;
    private MailerInterface $mailer;
    private string $adminEmail;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager  Pour persister les messages
     * @param MailerInterface        $mailer   Pour envoyer les emails
     * @param string                 $adminEmail Adresse email de réception
     */
    public function __construct(EntityManagerInterface $manager, MailerInterface $mailer, string $adminEmail)
    {
        $this->manager = $manager;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    /**
     * Exécute le traitement du message.
     *
     * @param Message $message Message envoyé via le formulaire de contact
     */
    public function __invoke(Message $message)
    {
        // Création de l'email avec NotificationEmail de Symfony
        $email = (new NotificationEmail())
            ->subject($message->getSubject())                       // Sujet de l'email
            ->htmlTemplate('emails/contact_email.html.twig')       // Template HTML
            ->from(new Address('contact@fc-nutrition.com', 'Formulaire Contact')) // Expéditeur
            ->to(new Address($this->adminEmail))                  // Destinataire (admin)
            ->text($message->getBody())                           // Version texte simple
            ->context([                                           // Contexte pour le template
                'message' => $message
            ]);

        // Envoi de l'email
        $this->mailer->send($email);

        // Sauvegarde du message en base
        $this->manager->persist($message);
        $this->manager->flush($message);
    }
}