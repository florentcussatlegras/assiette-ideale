<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
// use Florent\QuantityConverterBundle\Model\Provider\AbstractUnitMeasureProvider;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * CustomUnitMeasureProvider.php
 *
 * Service permettant de fournir la liste des unités de mesure utilisées
 * dans l'application. Les unités sont récupérées depuis un fichier JSON
 * puis désérialisées en objets grâce au Serializer Symfony.
 *
 * Ce service peut servir de provider personnalisé pour gérer les conversions
 * d’unités (grammes, millilitres, pièces, etc.).
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
// class CustomUnitMeasureProvider extends AbstractUnitMeasureProvider
class CustomUnitMeasureProvider
{
    /**
     * Injection des dépendances via le constructeur
     */
    public function __construct(
        private ManagerRegistry $registry,     // Accès au gestionnaire Doctrine et aux repositories
        private SerializerInterface $serializer, // Permet de désérialiser le JSON en objets PHP
        private string $classOrAlias           // Classe cible utilisée pour la désérialisation
    ) {}

    /**
     * Retourne la liste des unités de mesure.
     *
     * Les données sont lues depuis le fichier JSON public contenant
     * la définition des unités, puis converties en objets via le serializer.
     *
     * @return array Liste des unités de mesure
     */
    public function getList(): array
    {
        // Lecture du fichier JSON contenant les unités de mesure
        $unitMeasuresJson = file_get_contents(__DIR__.'/../../public/json/unit-measures.json');

        // Désérialisation du JSON en tableau d'objets
        return $this->serializer->deserialize(
            $unitMeasuresJson,
            $this->getClass().'[]',
            'json'
        );
    }
}