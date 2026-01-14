<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Message;
use App\Form\Type\TaskType;
use App\Form\Type\MessageType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;

#[Route('/contact')]
class ContactController extends AbstractController
{
    #[Route('/task', name: 'app_contact_task')]
    public function new(Request $request): Response
    {
        $task = new Task();

        $form = $this->createForm(TaskType::class, $task);

        return $this->render('task/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $manager, MessageBusInterface $bus): Response
    {
        $message = new Message();
        $formContact = $this->createForm(MessageType::class, $message);

        $formContact->handleRequest($request);

        if($formContact->isSubmitted() && $formContact->isValid()) 
        {
            $bus->dispatch($message);

            $this->addFlash('success', 'Votre message a bien été envoyé');

            return $this->redirectToroute('app_contact');
        }

        return $this->renderForm('contact/index.html.twig', [
            'formContact' => $formContact
        ]);
    }
}