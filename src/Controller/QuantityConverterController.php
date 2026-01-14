<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Florent\QuantityConverterBundle\QuantityConverter;

#[Route('/quantity-converter2', name:'app_quantity_converter_')]
class QuantityConverterController extends AbstractController
{
    #[Route('/', name:'index')]
    public function index(QuantityConverter $qtyConverter)
    {
        return $this->render('quantity-converter/index.html.twig');
    }

    #[Route('/unit-measure/list', name:'unit_measure_list')]
    public function unitMeasureList(QuantityConverter $quantityConverter)
    {
        return $this->render('quantity-converter/unit-measure-list.html.twig', [
            'unit_measure_list' => $quantityConverter->getUnitMeasureList(),
        ]);
    }
}