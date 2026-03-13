<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator associé à la contrainte IsStepRecipeUnique.
 *
 * Vérifie que chaque étape d'une recette possède un rang unique.
 * Si des doublons sont détectés, une violation est ajoutée pour chaque rang en double.
 */
class IsStepRecipeUniqueValidator extends ConstraintValidator
{
    /**
     * Valide que les étapes d'une recette sont uniques.
     *
     * @param iterable $value      Les étapes de la recette (Collection ou tableau d'objets)
     * @param Constraint $constraint L'instance de la contrainte IsStepRecipeUnique
     *
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        // Convertit la collection d'étapes en tableau
        $steps = $value->toArray();

        // Remplace chaque objet étape par son rang
        array_walk($steps, function (&$step) {
            $step = $step->getRankStep();
        });

        // Compte combien de fois chaque rang apparaît
        $steps = array_count_values($steps);

        // Filtre les rangs qui apparaissent plus d'une fois (doublons)
        $steps = array_filter($steps, function ($count) {
            return $count > 1;
        });

        // Si des doublons existent, ajouter une violation pour chaque rang en double
        if (!empty($steps)) {
            foreach (array_keys($steps) as $step) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ step }}', $step)
                    ->addViolation();
            }
        }
    }
}