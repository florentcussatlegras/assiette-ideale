<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * DefaultController.php
 * 
 * Contrôleur responsable de la page d'accueil.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 */
class DefaultController extends AbstractController
{
    /**
     * Page d'accueil.
     *
     * - Crée un cookie pour stocker les dates et heures de connexion du dernier mois.
     * - Affiche un formulaire simple pour sélectionner une couleur.
     *
     * @param Request $request
     * 
     * @return Response
     */
    #[Route('/', name: 'app_homepage', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {   
        $response = new Response();

        // Initialisation du tableau de connexions
        $connectionTimes = [];
        if ($request->cookies->has('connection_times')) {
            // Récupère les connexions précédentes depuis le cookie
            $connectionTimes = unserialize($request->cookies->get('connection_times'));
            // Supprime l'ancien cookie pour le recréer
            $response->headers->clearCookie('connection_times');
        }

        // Ajoute la date/heure de connexion actuelle
        $connectionTimes[] = new \DateTime();

        // Crée un nouveau cookie pour stocker les connexions
        $cookieConnection = Cookie::create('connection_times')
            ->withValue(serialize($connectionTimes))       // sérialisation pour stockage
            ->withExpires(new \DateTime("+1 month"))       // expiration dans un mois
            ->withSecure(true);                            // cookie sécurisé HTTPS

        $response->headers->setCookie($cookieConnection);

        // Création d'un formulaire simple avec un champ couleur
        $form = $this->createFormBuilder()
                     ->add('color', ColorType::class)
                     ->getForm();

        // Rend le template avec le formulaire
        $response->setContent($this->renderView('homepage/index.html.twig', [
            'form' => $form->createView()
        ]));

        return $response;
    }

    /**
     * Affiche toutes les connexions stockées dans le cookie.
     *
     * @param Request $request
     */
    #[Route(
        '/connections',
        name: 'app_connections',
        methods: ['GET'],
        condition: "context.getMethod() in ['GET']"
    )]
    public function connections(Request $request)
    {
        $cookieConnections = [];
        if ($request->cookies->has('connection_times')) {
            // Récupère et désérialise les connexions
            $cookieConnections = unserialize($request->cookies->get('connection_times'));
            foreach ($cookieConnections as $connection) {
                // Affiche chaque date/heure formatée
                dump($connection->format('d/m/Y à H\hi'));
            }
        }
        exit; // Fin du script après dump (debug)
    }

    /**
     * Supprime le cookie "already_register_last_7_days".
     *
     * @return Response
     */
    #[Route('/clear-cookie', name:'app_clear_cookie', methods: ['GET', 'HEAD'])]
    public function clearCookie()
    {
        $response = new Response();
        // Supprime le cookie spécifique
        $response->headers->clearCookie('already_register_last_7_days');

        return $response;
    }

    /**
     * Marque le profil de l'utilisateur comme complété pour la première fois.
     *
     * @param EntityManagerInterface $manager
     * 
     * @return RedirectResponse
     */
    #[Route('/profilefill', name: 'app_first_profile_fill_true', methods: ['GET', 'POST'])]
    public function firstProfileFillTrue(EntityManagerInterface $manager)
    {
        $user = $this->getUser();

        // Définit le flag "firstProfileFill" à true
        $user->setFirstProfileFill(true);
        $manager->persist($user);
        $manager->flush();

        // Redirection vers la page d'accueil
        return new RedirectResponse($this->generateUrl('app_homepage'));
    }
}