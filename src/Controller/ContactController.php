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

/**
 * ContactController.php
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
#[Route('/contact')]
class ContactController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de contact.
     *
     * Fonctionnement :
     * 1. Création de l'entité Message
     * 2. Création du formulaire MessageType
     * 3. Traitement de la requête (handleRequest)
     * 4. Si le formulaire est valide :
     *    - envoi du message via le MessageBus (Symfony Messenger)
     *    - ajout d'un message flash
     *    - redirection vers la page contact
     *
     * @param Request $request Requête HTTP
     * @param EntityManagerInterface $manager Gestionnaire Doctrine (non utilisé ici mais injectable si besoin)
     * @param MessageBusInterface $bus Bus de messages utilisé pour envoyer le message de manière asynchrone
     *
     * @return Response
     */
    #[Route('/', name: 'app_contact')]
    public function index(Request $request, EntityManagerInterface $manager, MessageBusInterface $bus): Response
    {
        // Création d'une nouvelle entité Message
        $message = new Message();

        // Création du formulaire de contact lié à l'entité Message
        $formContact = $this->createForm(MessageType::class, $message);

        // Analyse de la requête HTTP pour hydrater le formulaire
        $formContact->handleRequest($request);

        // Vérifie si le formulaire a été soumis et s'il est valide
        if($formContact->isSubmitted() && $formContact->isValid()) 
        {
            // Envoi du message dans le bus Symfony Messenger
            // Permet un traitement asynchrone (ex: envoi d'email)
            $bus->dispatch($message);

            // Ajout d'un message flash affiché à l'utilisateur
            $this->addFlash('success', 'Votre message a bien été envoyé');

            // Redirection pour éviter la double soumission du formulaire
            return $this->redirectToRoute('app_contact');
        }

        // Affiche le formulaire de contact
        return $this->renderForm('contact/index.html.twig', [
            'formContact' => $formContact
        ]);
    }
}