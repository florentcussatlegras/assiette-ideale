<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Contrainte personnalisée permettant de vérifier
 * que la valeur d'énergie saisie par l'utilisateur
 * est comprise dans une plage valide.
 *
 * Cette validation prend en compte l'unité utilisée
 * (kcal ou kJ) et applique les limites correspondantes.
 */
#[\Attribute]
class IsEnergyValid extends Constraint
{
    /**
     * Unité de mesure de l'énergie (kcal ou kJ).
     *
     * @var string|null
     */
    public ?string $unitMeasure = null;

    /**
     * Valeur minimale autorisée en kilocalories.
     */
    public int $minKcal = 300;

    /**
     * Valeur maximale autorisée en kilocalories.
     */
    public int $maxKcal = 12000;

    /**
     * Valeur minimale autorisée en kilojoules.
     */
    public int $minKj = 1000;

    /**
     * Valeur maximale autorisée en kilojoules.
     */
    public int $maxKj = 30000;

    /**
     * Message d'erreur retourné lorsque la valeur
     * est en dehors de la plage autorisée.
     *
     * Variables disponibles :
     *  - {{ min }}
     *  - {{ max }}
     *  - {{ unit }}
     */
    public string $messageRange = "L'énergie doit être comprise entre {{ min }} et {{ max }} {{ unit }}";
}