<?php

namespace App\Controller\meal;

use App\Service\WeekAlertFeature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SearchController extends AbstractController
{
    #[Route('/meal/search', name: 'app_meal_search', methods: ['GET', 'POST'])]
    public function searchWeekMenu(Request $request, WeekAlertFeature $weekAlertFeature)
    {
        $searchWeekMenuForm = $this->createFormBuilder()
                                    ->setAction($this->generateUrl('app_meal_search'))
                                    ->add('week', TextType::class, [
                                        'attr' => [
                                            'class' => 'datepicker-input block w-full rounded-lg border border-gray-300 py-2 px-3 text-gray-900 text-sm focus:ring-sky-200 focus:border-sky-500 transition',
                                            'placeholder' => 'jj/mm/aaaa'
                                        ],
                                    ])
                                    ->getForm();
                                    
        $searchWeekMenuForm->handleRequest($request);
                             
        if($searchWeekMenuForm->isSubmitted() && $searchWeekMenuForm->isValid()) {
            $dateStr = $searchWeekMenuForm->get('week')->getData();

            $date = \DateTime::createFromFormat('d/m/Y', $dateStr);

            if (!$date) {
                $this->addFlash('error', 'Date invalide');
                return $this->redirectToRoute('app_meal_search');
            }

            return $this->redirectToRoute('menu_week_menu', [
                'startingDate' => $weekAlertFeature->getStartingDayOfWeek($date->format('Y-m-d'))
            ]);
        }

        return $this->render('meals/week/_search_week_menu.html.twig', [
            'searchWeekMenuForm' => $searchWeekMenuForm->createView()
        ]);
    }
}