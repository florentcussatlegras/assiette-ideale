<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig pour les plats (Dish).
 * 
 * Fournit des fonctions utiles pour l'affichage dans les templates,
 * comme mettre en évidence un mot-clé dans un nom de plat ou aliment.
 */
class DishExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $em Manager pour accéder à la base de données si besoin
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Déclare les fonctions Twig disponibles via cette extension.
     *
     * @return array Liste des fonctions Twig
     */
    public function getFunctions(): array
    {
        return [
            // Fonction Twig 'showKeyword' pour mettre en valeur un mot-clé dans une chaîne
            new TwigFunction('showKeyword', [$this, 'setShowKeyword']),
        ];
    }

    /**
     * Met en évidence un mot-clé dans une chaîne.
     *
     * Si le mot-clé est trouvé dans le nom, il sera entouré
     * d'un span avec la classe CSS 'text-light-blue'.
     *
     * @param string $name    La chaîne dans laquelle chercher le mot-clé
     * @param string $keyword Le mot-clé à mettre en évidence
     * 
     * @return string Chaîne HTML avec le mot-clé surligné
     */
    public function setShowKeyword(string $name, string $keyword): string
    {
        // Cherche la position du mot-clé dans la chaîne, insensible à la casse
        if (false !== $start = stripos($name, trim($keyword))) {
            // Récupère le texte exact correspondant au mot-clé
            $search = substr($name, $start, strlen($keyword));

            // Remplace le mot-clé par la version HTML surlignée
            return str_replace($search, '<span class="text-light-blue">' . $search . '</span>', $name);
        }

        // Si le mot-clé n'est pas trouvé, retourne le nom original
        return $name;
    }
}