<?php

namespace App\Controller;

use App\Service\EnergyHandler;
use App\Service\NutrientHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NutrientRepository;
use App\Repository\NutrientRecommendationUserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * RecommendationController
 *
 * Contrôleur principal pour gérer :
 *  - l'affichage des nutriments et recommandations
 *  - le calcul de l'énergie quotidienne
 *  - le calcul des recommandations nutritionnelles
 *
 * Auteur : Florent Cussatlegras <florent.cussatlegras@gmail.com>
 * Date : 2026-03-09
 * Projet : Assiette idéale 
 */
#[Route('/recommendation', name: 'app_recommendation_')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class RecommendationController extends AbstractController
{
    /**
     * Page principale des recommandations : liste tous les nutriments.
     *
     * @param NutrientRepository $nutrientRepository
     *
     * @return Response
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(NutrientRepository $nutrientRepository): Response
    {
        return $this->render('recommendations/index.html.twig', [
            'nutrients' => $nutrientRepository->findAll(),
        ]);
    }

    /**
     * Affiche le bloc d'énergie du profil utilisateur.
     *
     * @param EnergyHandler $energyHandler
     *
     * @return Response
     */
    #[Route('/energy', name: 'energy_index', methods: ['GET'])]
    public function energy(EnergyHandler $energyHandler): Response
    {
        return $this->render('profile/partials/_energy.html.twig', [
            'missingElements' => $energyHandler->profileMissingForEnergy(),
        ]);
    }

    /**
     * Calcule l'énergie quotidienne de l'utilisateur.
     *
     * @param EnergyHandler $energyHandler
     * @param EntityManagerInterface $manager
     *
     * @return Response
     */
    #[Route('/energy/calculate', name: 'energy_estimate', methods: ['POST'])]
    public function calculateEnergy(EnergyHandler $energyHandler, EntityManagerInterface $manager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Active le calcul automatique de l'énergie pour l'utilisateur
        $user->setAutomaticCalculateEnergy(true);

        // Calcule l'énergie quotidienne via le service EnergyHandler
        $energyValue = $energyHandler->evaluateEnergy($user);

        // Enregistre la valeur calculée dans l'entité User
        $user->setEnergy($energyValue);

        // Persiste les modifications dans la base de données
        $manager->flush();

        // Message flash pour informer l'utilisateur
        $this->addFlash('success', 'Votre énergie quotidienne a bien été mise à jour.');

        // Redirige vers la page de profil
        return $this->redirectToRoute('app_profile_index');
    }

    /**
     * Calcule les recommandations nutritionnelles pour l'utilisateur.
     *
     * @param NutrientHandler $nutrientHandler
     * @param EntityManagerInterface $manager
     * @param NutrientRecommendationUserRepository $nutrientRecommendationUserRepository
     *
     * @return Response
     */
    #[Route('/nutrient/calculate', name: 'nutrient_estimate', methods: ['POST'])]
    public function calculateNutrientRecommendations(
        NutrientHandler $nutrientHandler,
        EntityManagerInterface $manager,
    ): Response {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Suppression des anciennes recommandations
        $user->getNutrientRecommendations()->forAll(function ($key, $entity) use ($manager, $user) {
            $user->removeNutrientRecommendation($entity);
            $manager->remove($entity);
            return true;
        });

        // Ajout des nouvelles recommandations
        foreach ($nutrientHandler->getRecommendations() as $nutrientRecommendation) {
            $user->addNutrientRecommendation($nutrientRecommendation);
        }

        // Persiste les modifications dans la base de données
        $manager->flush();

        // Message flash pour informer l'utilisateur
        $this->addFlash('success', 'Vos recommandations de nutrition ont bien été mises à jour.');

        return $this->redirectToRoute('app_profile_index');
    }
}
