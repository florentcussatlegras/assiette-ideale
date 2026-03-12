<?php

namespace App\Serializer\Normalizer;

use App\Entity\Dish;
use App\Entity\NutritionalTable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Denormalizer pour l'entité Dish.
 *
 * Objectif :
 *  - Transformer un tableau de données en instance de Dish.
 *  - Gérer correctement les types (int) pour certaines propriétés numériques.
 *  - Traiter la sous-entité NutritionalTable et la convertir en objet.
 *  - Permettre la mise à jour d’un Dish existant ou la création d’un nouveau.
 *
 * Fonctionnement :
 *  - Vérifie et cast les valeurs numériques pour éviter les erreurs de type.
 *  - Crée ou récupère la table nutritionnelle associée.
 *  - Utilise l’ObjectNormalizer de Symfony pour remplir l’objet Dish.
 *  - Supporte la désérialisation partielle via OBJECT_TO_POPULATE pour les mises à jour.
 *
 * Points clés :
 *  - Compatible avec l’interface DenormalizerInterface.
 *  - Conçu pour travailler avec Doctrine EntityManager pour récupérer des entités existantes.
 *  - Prépare les données pour un flux API ou formulaire avant persistance.
 */
class DishDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private ObjectNormalizer $normalizer,
        private EntityManagerInterface $manager
    ) {}

    /**
     * Transforme un tableau de données en instance de Dish.
     *
     * @param array $data Les données à désérialiser
     * @param string $type Le type de l’objet cible (Dish)
     * @param string|null $format Format optionnel (ex: 'json')
     * @param array $context Contexte optionnel pour le normalizer
     *
     * @return Dish
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): Dish
    {
        $data["id"] = !empty($data["lengthPersonForRecipe"]) ? (int)$data["id"] : null;
        $data["lengthPersonForRecipe"] = !empty($data["lengthPersonForRecipe"]) ? (int)$data["lengthPersonForRecipe"] : null;
        $data["preparationTime"] = !empty($data["preparationTime"]) ? (int)$data["preparationTime"] : null;
        $data["preparationTimeUnitTime"] = !empty($data["preparationTimeUnitTime"]) ? (int)$data["preparationTimeUnitTime"] : null;
        $data["cookingTime"] = !empty($data["cookingTime"]) ? (int)$data["cookingTime"] : null;
        $data["cookingTimeUnitTime"] = !empty($data["cookingTimeUnitTime"]) ? (int)$data["cookingTimeUnitTime"] : null;

        if (isset($data["nutritionalTable"])) {
            $nutritionalTable = $data["nutritionalTable"];
            foreach (["protein","lipid","saturatedFattyAcid","carbohydrate","sugar","salt","fiber","energy"] as $key) {
                $nutritionalTable[$key] = !empty($nutritionalTable[$key]) ? (int)$nutritionalTable[$key] : null;
            }

            $nutritionalTableObject = $this->normalizer->denormalize(
                $nutritionalTable,
                NutritionalTable::class,
                'class'
            );
        } else {
            $nutritionalTableObject = new NutritionalTable();
        }

        $data["nutritionalTable"] = $nutritionalTableObject;

        if (!empty($data['dish']['id'])) {
            $dish = $this->manager->getRepository(Dish::class)->findOneById((int)$data['dish']['id']);
        } else {
            $dish = new Dish();
        }

        return $this->normalizer->denormalize(
            $data,
            Dish::class,
            'class',
            [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
                AbstractNormalizer::OBJECT_TO_POPULATE => $dish,
            ]
        );
    }

    /**
     * Vérifie si ce denormalizer supporte le type de données fourni.
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_array($data);
    }
}