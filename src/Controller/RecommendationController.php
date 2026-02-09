<?php

namespace App\Controller;

use App\Service\EnergyHandler;
use App\Service\NutrientHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NutrientRepository;
use App\Repository\NutrientRecommendationUserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/recommendation', name: 'app_recommendation_')]
class RecommendationController extends AbstractController
{
    // INDEX
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NutrientRepository $nutrientRepository)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('recommendations/index.html.twig', [
            'nutrients' => $nutrientRepository->findAll(),
        ]);
    }

    // GROUPES ALIMENTAIRES

    // new route for 'app_recommended_quantity_edit':
    #[Route('/foodgroup/edit', name: 'foodgroup_edit', methods: ['GET'])]
    public function edit(EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();

        // $recommendedQuantities = $user->getRecommendedQuantities();
        $recommendedQuantities = [
            'FGP_VPO' => 200,
            'FGP_STARCHY' => 200,
            'FGP_VEG' => 200,
            'FGP_FRUIT' => 200,
            'FGP_DAIRY' => 200,
            'FGP_FAT' => 200,
            'FGP_SUGAR' => 200,
            'FGP_CONDIMENT' => 200
        ];
        // $recommendedQuantities['FGP_CONDIMENT'] = 200;

        $user->setRecommendedQuantities($recommendedQuantities);

        $manager->persist($user);
        $manager->flush();

        return new Response('Quantités mises à jour');
    }

    // ENERGY
    #[Route('/energy', name: 'energy_index', methods: ['GET'])]
    public function energy(EnergyHandler $energyHandler)
    {
        return $this->render('profile/partials/_energy.html.twig', [
            'missingElements' => $energyHandler->profileMissingForEnergy(),
        ]);
    }

    #[Route('/energy/calculate', name: 'energy_estimate', methods: ['POST'])]
    public function calculateEnergy(EnergyHandler $energyHandler, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        
        $user->setAutomaticCalculateEnergy(true);
        $energyHandler->evaluateEnergy($user);
        $user->setEnergy($energyHandler->evaluateEnergy($user));
        
        $manager->flush();
        
        $this->addFlash('success', 'Votre energie quotidienne a bien été mise à jour2.');

        return $this->redirectToRoute('app_profile_index');
    }

    // NUTRIENT
    #[Route('/nutrient/calculate', name: 'nutrient_estimate', methods: ['POST'])]
    public function calculateNutrientRecommendations(
            NutrientHandler $nutrientHandler, 
            EntityManagerInterface $manager, 
            NutrientRecommendationUserRepository $nutrientRecommendationUserRepository
    )
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        
        // $nutrientRecommendations = $this->nutrientHandler->getRecommendations();

        $user->getNutrientRecommendations()->forAll(function($key, $entity) use ($manager, $user) {
            $user->removeNutrientRecommendation($entity);
            $manager->remove($entity);

            return true;
        });

        foreach($nutrientHandler->getRecommendations() as $nutrientRecommendation) {
            $user->addNutrientRecommendation($nutrientRecommendation);
        }
        
        $manager->flush();
        
        $this->addFlash('success', 'Vos recommendations de nutrition ont bien été mise à jour.');

        return $this->redirectToRoute('app_profile_index');
    }
}