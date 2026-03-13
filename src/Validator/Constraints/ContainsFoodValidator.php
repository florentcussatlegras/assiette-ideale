<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Validator associé à la contrainte ContainsFood.
 *
 * Vérifie qu'un plat contient au moins un aliment
 * dans la session utilisateur.
 *
 * Si aucun aliment n'est présent dans la session
 * (clé "recipe_foods"), une violation est ajoutée.
 */
class ContainsFoodValidator extends ConstraintValidator
{
    /**
     * Session utilisateur.
     */
    private $session;

    /**
     * Constructeur.
     *
     * Permet de récupérer la session via le RequestStack.
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    /**
     * Méthode appelée lors de la validation.
     *
     * Vérifie si la session contient des aliments
     * pour la recette en cours.
     *
     * @param mixed $value Valeur du champ (non utilisée ici)
     * 
     * @param Constraint $constraint Contrainte appliquée
     */
    public function validate($value, Constraint $constraint): void
    {
        // Si aucun aliment n'est présent dans la session
        if (!$this->session->has('recipe_foods') || empty($this->session->get('recipe_foods'))) {

            // Ajoute une erreur de validation
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ min }}', $constraint->min)
                ->addViolation();
        }
    }
}