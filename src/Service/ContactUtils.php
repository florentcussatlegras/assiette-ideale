<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;

/**
 * ContactUtils.php
 *
 * Service utilitaire pour gérer les sujets du formulaire de contact.
 * Il permet de récupérer la liste des sujets depuis un fichier JSON
 * situé dans un répertoire configuré.
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class ContactUtils
{
    /**
     * Injection des dépendances via le constructeur
     */
    public function __construct(
        private Finder $finder,            // Composant Symfony permettant de rechercher des fichiers
        private string $dirJson,           // Répertoire contenant les fichiers JSON
        private string $jsonSubjectContact // Nom du fichier JSON contenant les sujets de contact
    ) {}

    /**
     * Récupère la liste des sujets du formulaire de contact.
     *
     * La méthode recherche le fichier JSON correspondant dans le répertoire
     * configuré, puis retourne son contenu décodé sous forme de tableau.
     *
     * @return array Liste des sujets disponibles pour le formulaire de contact
     */
    public function getListSubject(): array
    {
        // Recherche du fichier JSON contenant les sujets de contact
        $objects = iterator_to_array(
            $this->finder
                ->in($this->dirJson)
                ->files()
                ->name($this->jsonSubjectContact)
        );

        // Lecture et décodage du contenu JSON
        return json_decode(current($objects)->getContents(), true);
    }
}