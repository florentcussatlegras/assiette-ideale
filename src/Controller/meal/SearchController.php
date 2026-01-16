<?php

namespace App\Controller\meal;

use App\Service\WeekAlertFeature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    #[Route('/meal/search', name: 'app_meal_search')]
    public function searchWeekMenu(Request $request, WeekAlertFeature $weekAlertFeature)
    {
        $searchWeekMenuForm = $this->createFormBuilder()
                                    ->setAction($this->generateUrl('app_meal_search'))
                                    ->add('week', DateType::class, [
                                        'attr' => [
                                            'class' => 'border border-gray-300 text-gray-900 text-sm focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5 datepicker-input rounded-lg',
                                        ],
                                        'widget' => 'single_text'
                                    ])
                                    ->getForm();
                                    
        $searchWeekMenuForm->handleRequest($request);
                             
        if($searchWeekMenuForm->isSubmitted() && $searchWeekMenuForm->isValid()) {
            return $this->redirectToRoute('menu_week_menu', [
                'startingDate' => $weekAlertFeature->getStartingDayOfWeek($searchWeekMenuForm->get('week')->getData()->format('Y-m-d'))
            ]);
        }

        return $this->render('meals/week/_search_week_menu.html.twig', [
            'searchWeekMenuForm' => $searchWeekMenuForm->createView()
        ]);
    }
}