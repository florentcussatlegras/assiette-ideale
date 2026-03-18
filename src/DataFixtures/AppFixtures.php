<?php

namespace App\DataFixtures;

use App\Entity\Hour;
use App\Entity\Gender;
use App\Entity\AgeRange;
use App\Entity\WorkingType;
use App\Entity\SportingTime;
use App\Entity\AgeRangeGender;
use App\Entity\PhysicalActivity;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * AppFixtures.php
 *
 * Fixture pour créer les entités de base liées aux profils utilisateurs :
 * - Genres (Gender)
 * - Types de travail (WorkingType)
 * - Temps de sport (SportingTime)
 * - Activité physique (PhysicalActivity)
 * - Horaires de travail (Hour)
 */
class AppFixtures extends BaseFixture implements FixtureGroupInterface
{
    /**
     * Charge les données de base pour les profils utilisateurs.
     *
     * @param ObjectManager $manager
     * @return void
     */
    protected function loadData(ObjectManager $manager): void
    {
        // ======================
        // GENRES
        // ======================

        /**
         * Genre masculin
         * Nom court et long : "Homme"
         * Alias : Gender::MALE
         */
        $male = new Gender();
        $male->setName('Homme');
        $male->setLongName('Homme');
        $male->setAlias(Gender::MALE);
        $manager->persist($male);

        /**
         * Genre féminin
         * Nom court et long : "Femme"
         * Alias : Gender::FEMALE
         */
        $female = new Gender();
        $female->setName('Femme');
        $female->setLongName('Femme');
        $female->setAlias(Gender::FEMALE);
        $manager->persist($female);

        /**
         * Genre autre / non-binaire / non spécifié
         * Nom court et long : "Autre"
         * Alias : Gender::OTHER
         */
        $other = new Gender();
        $other->setName('Autre');
        $other->setLongName('Autre');
        $other->setAlias(Gender::OTHER);
        $manager->persist($other);


        // ======================
        // TYPES DE TRAVAIL
        // ======================

        /**
         * Type de travail non physique (softWorking)
         * isHard = false
         * Description : l'utilisateur n'exerce pas un métier physique
         */
        $softWorking = new WorkingType();
        $softWorking->setIsHard(false);
        $softWorking->setDescription('Vous n\'exercez pas un métier physique');

        /**
         * Type de travail physique (hardWorking)
         * isHard = true
         * Description : l'utilisateur exerce un métier physique
         */
        $hardWorking = new WorkingType();
        $hardWorking->setIsHard(true);
        $hardWorking->setDescription('Vous exercez un métier physique');


        // ======================
        // TEMPS DE SPORT
        // ======================

        /**
         * SportingTime : pas de sport
         * duration = NO_SPORT
         * Description : l'utilisateur ne pratique aucun sport
         */
        $noSport = new SportingTime();
        $noSport->setDuration('NO_SPORT');
        $noSport->setDescription('Vous ne faites pas de sport');

        /**
         * SportingTime : moins de 5h de sport par semaine
         * duration = LESS_5_H
         * Description : l'utilisateur pratique moins de 5h de sport par semaine
         */
        $less5 = new SportingTime();
        $less5->setDuration('LESS_5_H');
        $less5->setDescription('Vous pratiquez moins de 5h de sport par semaine');

        /**
         * SportingTime : plus de 5h de sport par semaine
         * duration = MORE_5_H
         * Description : l'utilisateur pratique plus de 5h de sport par semaine
         */
        $more5 = new SportingTime();
        $more5->setDuration('MORE_5_H');
        $more5->setDescription('Vous pratiquez plus de 5h de sport par semaine');

        // ======================
        // ACTIVITÉ PHYSIQUE
        // ======================

        /**
         * Activité physique pour personne ayant un travail non physique (softWorking)
         * et ne faisant pas de sport (noSport)
         * Valeur d'activité = 1 (basique)
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking); // Travail non physique
        $physicalActivity->setSportingTime($noSport);    // Pas de sport
        $physicalActivity->setValue(1);                  // Facteur d'activité
        $manager->persist($physicalActivity);

        /**
         * Activité physique pour personne ayant un travail non physique (softWorking)
         * et pratiquant moins de 5h de sport par semaine (less5)
         * Valeur d'activité légèrement supérieure à la base
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking);
        $physicalActivity->setSportingTime($less5);      // Moins de 5h de sport/semaine
        $physicalActivity->setValue(1.1);               // Facteur d'activité augmenté
        $manager->persist($physicalActivity);

        /**
         * Activité physique pour personne ayant un travail non physique (softWorking)
         * et pratiquant plus de 5h de sport par semaine (more5)
         * Valeur d'activité encore plus élevée
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($softWorking);
        $physicalActivity->setSportingTime($more5);     // Plus de 5h de sport/semaine
        $physicalActivity->setValue(1.2);               // Facteur d'activité augmenté
        $manager->persist($physicalActivity);

        /**
         * Activité physique pour personne ayant un travail physique (hardWorking)
         * et ne faisant pas de sport (noSport)
         * Valeur légèrement plus élevée que softWorking + noSport
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking); // Travail physique
        $physicalActivity->setSportingTime($noSport);    // Pas de sport
        $physicalActivity->setValue(1.1);               // Facteur d'activité
        $manager->persist($physicalActivity);

        /**
         * Activité physique pour personne ayant un travail physique (hardWorking)
         * et pratiquant moins de 5h de sport par semaine (less5)
         * Facteur d'activité plus élevé
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking);
        $physicalActivity->setSportingTime($less5);
        $physicalActivity->setValue(1.2);
        $manager->persist($physicalActivity);

        /**
         * Activité physique pour personne ayant un travail physique (hardWorking)
         * et pratiquant plus de 5h de sport par semaine (more5)
         * Facteur d'activité maximal dans ce modèle
         */
        $physicalActivity = new PhysicalActivity();
        $physicalActivity->setWorkingType($hardWorking);
        $physicalActivity->setSportingTime($more5);
        $physicalActivity->setValue(1.3);
        $manager->persist($physicalActivity);


        // ======================
        // HEURES DE TRAVAIL
        // ======================

        /**
         * Horaires normaux
         * Exemple : 9h-18h
         * Alias utilisé : NORMAL_H
         */
        $hour = new Hour();
        $hour->setTitle('%s normaux');
        $hour->setAlias('NORMAL_H');
        $hour->setDetails('(9h-18h...)');
        $manager->persist($hour);

        /**
         * Horaires décalés
         * Exemple : 2*8h / 3*8h / 5*8h
         * Alias utilisé : STAGGERED_H
         */
        $hour = new Hour();
        $hour->setTitle('%s décalés');
        $hour->setDetails('(2*8/3*8/5*8)');
        $hour->setAlias('STAGGERED_H');
        $manager->persist($hour);

        /**
         * Horaires mi-temps
         * Exemple : 9h-12h, 14h-18h
         * Alias utilisé : HALF_TIME_H
         */
        $hour = new Hour();
        $hour->setTitle('%s mi-temps');
        $hour->setDetails('(9h-12h, 14-18h...)');
        $hour->setAlias('HALF_TIME_H');
        $manager->persist($hour);

        /**
         * Aucun horaire
         * Exemple : personne n’a pas d’horaire défini
         * Alias utilisé : NO_H
         */
        $hour = new Hour();
        $hour->setTitle('Aucun %s');
        $hour->setAlias('NO_H');
        $manager->persist($hour);

        $manager->flush();
    }

    /**
     * Retourne les groupes de fixtures pour ce fichier.
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['apps', 'dev', 'test'];
    }
}
