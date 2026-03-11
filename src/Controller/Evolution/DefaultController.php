<?php

namespace App\Controller\Evolution;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * DefaultController.php
 *
 * Controller principal pour la section "Évolution" de l'utilisateur.
 * Permet de visualiser les évolutions sur différentes périodes.
 *
 * Routes principales :
 * - /evolution -> Page principale de sélection de période
 *
 * Toutes les routes nécessitent que l'utilisateur soit authentifié.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\Evolution
 */
#[Route('/evolution', name: 'app_evolution_')]
class DefaultController extends AbstractController
{
    /**
     * Page principale de la section évolution.
     *
     * Permet de sélectionner une période pour afficher les statistiques
     * de l'utilisateur. Les paramètres GET "start" et "end" peuvent être fournis.
     * Si absent, start = -1 an, end = hier.
     *
     * @param Request $request Requête HTTP contenant éventuellement start/end
     * 
     * @return Response Vue de la page principale avec les dates formatées
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // Récupère les dates start et end via la fonction utilitaire
        [$start, $end] = $this->getStartEndDates($request);

        // Retourne la vue avec les dates formatées pour le front
        return $this->render('evolution/index.html.twig', [
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Récupère et formate les dates de début et de fin depuis la requête.
     *
     * Si une date n'est pas fournie, start = -1 an, end = hier.
     *
     * @param Request $request Requête HTTP contenant éventuellement start/end
     * 
     * @return array [string $start, string $end] Dates formatées en Y-m-d
     */
    private function getStartEndDates(Request $request): array
    {
        // Date de début : récupérée depuis GET ou -1 an par défaut
        $start = $request->query->has('start')
            ? \DateTime::createFromFormat('Y-m-d', $request->query->get('start'))
            : new \DateTime('-1 year');

        // Date de fin : récupérée depuis GET ou hier par défaut
        $end = $request->query->has('end')
            ? \DateTime::createFromFormat('Y-m-d', $request->query->get('end'))
            : new \DateTime('-1 day');

        // Retourne les dates formatées en string Y-m-d
        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }
}