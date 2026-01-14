<?php

namespace App\Service;

use App\Repository\UnitMeasureRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuantityHandler
{
    public function __construct(
        private UnitMeasureRepository $unitMeasureRepository
    ){}

    public function convert($quantity, $foodMedianWeight, $aliasUnitMeasure)
    {
        $unitMeasure = $this->unitMeasureRepository->findOneByAlias($aliasUnitMeasure);

        if(null === $unitMeasure) {
            throw new NotFoundHttpException(sprintf("Aucune unité de mesure ne comporte l'alias %s", $aliasUnitMeasure));
        }

        if($unitMeasure->isUnit()) {
            return $foodMedianWeight * $quantity;
        }

        return $quantity * $unitMeasure->getGramRatio();
    }
}