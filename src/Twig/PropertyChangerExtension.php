<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

/**
 * Extension Twig permettant de récupérer dynamiquement
 * une propriété d'une entité à partir d'une autre propriété.
 *
 * Exemple d'utilisation dans Twig :
 *
 * {{ value|change('App\\Entity\\Food', 'alias', 'name') }}
 *
 * Cela va :
 * 1. Chercher un objet Food où alias = value
 * 2. Retourner la propriété name de cet objet
 */
class PropertyChangerExtension extends AbstractExtension
{
    private EntityManagerInterface $manager;

    /**
     * Constructeur.
     *
     * @param EntityManagerInterface $manager EntityManager pour accéder aux repositories
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Déclare les filtres Twig disponibles dans cette extension.
     *
     * @return array Liste des filtres Twig
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('change', [$this, 'getOtherProperty']),
        ];
    }

    /**
     * Récupère une propriété d'un objet à partir d'une autre propriété.
     *
     * Fonctionnement :
     * - Recherche une entité via le repository
     * - Vérifie l'existence du getter correspondant
     * - Retourne la valeur de la propriété demandée
     *
     * @param mixed  $value             Valeur utilisée pour rechercher l'objet
     * @param string $class             Classe de l'entité à interroger
     * @param string $startingProperty  Propriété utilisée pour la recherche (ex: alias)
     * @param string $finalProperty     Propriété à retourner (ex: name)
     *
     * @return mixed|null Valeur de la propriété finale ou null si aucun objet trouvé
     */
    public function getOtherProperty($value, string $class, string $startingProperty, string $finalProperty)
    {
        // Recherche l'objet correspondant à la valeur donnée
        $object = $this->manager
            ->getRepository($class)
            ->findOneBy([$startingProperty => $value]);

        if ($object) {

            // Construit dynamiquement le nom du getter
            // ex : name → getName
            $getter = sprintf('get%s', ucfirst($finalProperty));

            // Vérifie que la méthode existe via Reflection
            $reflectionClass = new \ReflectionClass($class);
            $reflectionClass->getMethod($getter);

            // Appelle dynamiquement la méthode getter
            return call_user_func([$object, $getter], []);
        }

        // Retourne null si aucun objet trouvé
        return null;
    }
}