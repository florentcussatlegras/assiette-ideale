<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\UnitMeasureToAliasTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UnitMeasureController extends AbstractController
{
    #[Route('/unitmeasure/show', name:'app_unitmeasure_show')]
    public function show(Request $request, UnitMeasureToAliasTransformer $transformer)
    {
        $builder = $this->createFormBuilder()
                        ->add('alias', TextType::class, [
                            'attr' => [
                                'placeholder' => 'g, kg, l ...'
                            ]
                    ]);
        
        $builder->get('alias')->addModelTransformer($transformer);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isvalid())
        {
            dd($form->get('alias')->getData());
        }

        return $this->render('unit_measure/show.html.twig', [
            'form' => $form->createView()
        ]);
    }
}