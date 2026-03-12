<?php

namespace App\Service;

use App\Entity\Alert\LevelAlert;
use App\Service\AlertFeature;

/**
 * WeekAlertFeature.php
 * 
 * Service pour la gestion des dates et semaines liées aux alertes hebdomadaires.
 *
 * Objectif :
 *  - Fournir des méthodes pour calculer le lundi et vendredi d'une semaine donnée.
 *  - Générer des tableaux de jours utilisables pour les menus hebdomadaires ou alertes.
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class WeekAlertFeature
{
    /**
     * Retourne les jours d'une semaine (lundi à dimanche) avec leurs noms et dates.
     *
     * @param string|null $startingDate Date de départ (format Y-m-d) ou null pour semaine actuelle
     * @return array Tableau de jours ['l' => NomJour, 'Y-m-d' => Date]
     */
    public function get_lundi_vendredi_from_week($startingDate = null): array
    {
        $date = $startingDate === null ? new \Datetime() : new \DateTime($startingDate);
        $week = $date->format('W');
        $year = $date->format('Y');

        $firstDayInYear = date("N", mktime(0, 0, 0, 1, 1, $year));

        $shift = ($firstDayInYear < 5)
            ? -($firstDayInYear - 1) * 86400
            : (8 - $firstDayInYear) * 86400;

        $weekInSeconds = $week > 1 ? ($week - 1) * 604800 : 0;

        $jours = [];
        for ($i = 1; $i <= 7; $i++) {
            $timestamp = mktime(0, 0, 0, 1, $i, $year) + $weekInSeconds + $shift;
            $jours[] = [
                'l' => date('l', $timestamp),
                'Y-m-d' => date('Y-m-d', $timestamp)
            ];
        }

        return $jours;
    }

    /**
     * Retourne un tableau clé => valeur [YYYY-MM-DD => NomJour] pour la semaine d'une date donnée.
     *
     * @param string $startingDate
     * @return array
     */
    public function getDaysForWeekMenu(string $startingDate): array
    {
        $days = [];
        foreach ($this->get_lundi_vendredi_from_week($startingDate) as $dateOfDay) {
            $days[$dateOfDay['Y-m-d']] = $dateOfDay['l'];
        }

        return $days;
    }

    /**
     * Retourne le lundi correspondant à la semaine d'une date donnée.
     *
     * @param string $date
     * @return string YYYY-MM-DD
     */
    public function getStartingDayOfWeek(string $date): string
    {
        return date("Y-m-d", strtotime('monday this week', strtotime($date)));
    }
}