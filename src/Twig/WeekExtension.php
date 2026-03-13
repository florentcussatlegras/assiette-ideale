<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Extension Twig permettant de traduire et formater des dates
 * pour l'affichage dans les templates Twig.
 *
 * Fournit notamment :
 *  - la traduction des jours anglais → français
 *  - la traduction des mois anglais → français
 *  - un format de date complet en français
 */
class WeekExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $em EntityManager (non utilisé ici mais disponible si besoin)
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Déclare les filtres Twig disponibles.
     *
     * Exemple d'utilisation dans Twig :
     * {{ 'Monday'|dayTrans }}
     * {{ 'January'|monthTrans }}
     * {{ '2024-05-12'|detailedDate }}
     *
     * @return array Liste des filtres Twig
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('dayTrans', [$this, 'getDayTranslate']),
            new TwigFilter('monthTrans', [$this, 'getMonthTranslate']),
            new TwigFilter('detailedDate', [$this, 'getDetailedDate']),
        ];
    }

    /**
     * Traduit un jour de la semaine anglais en français.
     *
     * @param string $dayEn Jour en anglais (ex: Monday)
	 * 
     * @return string Jour traduit en français
     */
    public function getDayTranslate(string $dayEn): string
    {
        $days = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];

        return $days[$dayEn] ?? $dayEn;
    }

    /**
     * Traduit un mois anglais en français.
     *
     * @param string $monthEn Mois en anglais (ex: January)
	 * 
     * @return string Mois traduit en français
     */
    public function getMonthTranslate(string $monthEn): string
    {
        $months = [
            'January' => 'Janvier',
            'February' => 'Février',
            'March' => 'Mars',
            'April' => 'Avril',
            'May' => 'Mai',
            'June' => 'Juin',
            'July' => 'Juillet',
            'August' => 'Août',
            'September' => 'Septembre',
            'October' => 'Octobre',
            'November' => 'Novembre',
            'December' => 'Décembre'
        ];

        return $months[$monthEn] ?? $monthEn;
    }

    /**
     * Formate une date en format détaillé français.
     *
     * Exemple :
     * "2024-05-12" → "Dimanche 12 Mai 2024"
     *
     * @param string $dateStr Date sous forme de chaîne
	 * 
     * @return string Date formatée en français
     */
    public function getDetailedDate(string $dateStr): string
    {
        $date = new \DateTime($dateStr);

        // Récupère le jour de la semaine et le traduit
        $day = $this->getDayTranslate($date->format('l'));

        // Jour du mois
        $number = $date->format('d');

        // Mois traduit
        $month = $this->getMonthTranslate($date->format('F'));

        // Année
        $year = $date->format('Y');

        return $day . ' ' . $number . ' ' . $month . ' ' . $year;
    }
}