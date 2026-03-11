<?php

namespace App\Controller\meal;

use App\Service\WeekAlertFeature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * SearchController.php
 *
 * Contrôleur pour la recherche des menus de la semaine.
 *
 * Permet de créer un formulaire de recherche de menus par date
 * et de rediriger vers le menu de la semaine correspondante.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale
 *
 * @package App\Controller\meal
 */
class SearchController extends AbstractController
{
    /**
     * Recherche un menu hebdomadaire à partir d'une date saisie.
     *
     * - Affiche un formulaire de saisie de date
     * - Valide la date entrée par l'utilisateur
     * - Redirige vers la route 'menu_week_menu' avec le premier jour de la semaine correspondant
     *
     * @param Request $request Objet de requête HTTP
     * @param WeekAlertFeature $weekAlertFeature Service pour calculer le premier jour de la semaine
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/meal/search', name: 'app_meal_search', methods: ['GET', 'POST'])]
    public function searchWeekMenu(Request $request, WeekAlertFeature $weekAlertFeature)
    {
        // Création du formulaire de recherche avec un champ 'week' pour la date
        $searchWeekMenuForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_meal_search'))
            ->add('week', TextType::class, [
                'attr' => [
                    // Attributs HTML pour le champ : classe CSS, placeholder, styles focus
                    'class' => 'datepicker-input block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-900 text-sm focus:ring-sky-200 focus:border-sky-500 transition',
                    'placeholder' => 'jj/mm/aaaa'
                ],
            ])
            ->getForm();

        // Traitement de la soumission du formulaire
        $searchWeekMenuForm->handleRequest($request);

        // Vérifie si le formulaire est soumis et valide
        if ($searchWeekMenuForm->isSubmitted() && $searchWeekMenuForm->isValid()) {

            // Récupère la date saisie par l'utilisateur
            $dateStr = $searchWeekMenuForm->get('week')->getData();

            // Transforme la date du format 'jj/mm/aaaa' en objet DateTime
            $date = \DateTime::createFromFormat('d/m/Y', $dateStr);

            // Vérifie que la date est valide
            if (!$date) {
                // Ajoute un message flash en cas de date invalide et redirige vers le formulaire
                $this->addFlash('error', 'Date invalide');
                return $this->redirectToRoute('app_meal_search');
            }

            // Redirige vers le menu de la semaine en calculant le premier jour de la semaine
            return $this->redirectToRoute('menu_week_menu', [
                'startingDate' => $weekAlertFeature->getStartingDayOfWeek($date->format('Y-m-d'))
            ]);
        }

        // Affiche le formulaire si pas encore soumis ou invalide
        return $this->render('meals/week/_search_week_menu.html.twig', [
            'searchWeekMenuForm' => $searchWeekMenuForm->createView()
        ]);
    }
}