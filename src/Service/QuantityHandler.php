<?php

namespace App\Service;

use App\Repository\UnitMeasureRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * QuantityHandler.php
 * 
 * Service pour la gestion et la conversion des quantités d'aliments.
 *
 * Fonctionnalités principales :
 *  - Convertir une quantité donnée d'un aliment en grammes, en tenant compte de son unité de mesure.
 *
 * Fonctionnement :
 *  - Utilise la médiane de poids de l’aliment pour les unités "unitaires" (ex : 1 pomme = 150g).
 *  - Utilise le ratio en grammes pour les unités standardisées (ex : cuillère, tasse, etc.).
 * 
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-10
 * Projet : Assiette idéale
 */
class QuantityHandler
{
    public function __construct(
        private UnitMeasureRepository $unitMeasureRepository
    ){}

    /**
     * Convertit une quantité d’aliment selon son unité en grammes.
     *
     * @param float|int $quantity Quantité à convertir
     * @param float $foodMedianWeight Poids moyen de l’aliment (pour les unités "unitaires")
     * @param string $aliasUnitMeasure Alias de l’unité de mesure
     * 
     * @return float Quantité convertie en grammes
     *
     * @throws NotFoundHttpException si l’unité n’existe pas
     */
    public function convert($quantity, $foodMedianWeight, $aliasUnitMeasure): float
    {
        $unitMeasure = $this->unitMeasureRepository->findOneByAlias($aliasUnitMeasure);

        if (null === $unitMeasure) {
            throw new NotFoundHttpException(sprintf(
                "Aucune unité de mesure ne comporte l'alias %s",
                $aliasUnitMeasure
            ));
        }

        // Unité "unité" : on utilise le poids moyen de l’aliment
        if ($unitMeasure->isUnit()) {
            return $foodMedianWeight * $quantity;
        }

        // Autres unités : conversion via le ratio en grammes
        return $quantity * $unitMeasure->getGramRatio();
    }
}