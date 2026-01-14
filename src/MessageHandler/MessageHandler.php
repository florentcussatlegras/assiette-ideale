<?php

namespace App\MessageHandler;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mime\Address;

class MessageHandler implements MessageHandlerInterface
{
    private $manager;
    private $mailer;
    private $adminEmail;

    public function __construct(EntityManagerInterface $manager, MailerInterface $mailer, $adminEmail)
    {
        $this->manager = $manager;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
    }

    public function __invoke(Message $message)
    {
        $email = (new NotificationEmail)
                    ->subject($message->getSubject())
                    ->htmlTemplate('emails/contact_email.html.twig')
                    ->from(new Address('contact@fc-nutrition.com', 'Formulaire Contact'))
                    ->to(new Address($this->adminEmail))
                    ->text($message->getBody())
                    ->context([
                        'message' => $message
                    ]);

        $this->mailer->send($email);

        $this->manager->persist($message);
        $this->manager->flush($message);
    }
}