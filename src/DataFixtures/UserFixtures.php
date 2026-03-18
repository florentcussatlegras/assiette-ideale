<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\NutrientHandler;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\NutrientRecommendationUser;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * UserFixtures.php
 *
 * Fixtures pour créer des utilisateurs de test et administrateurs.
 * - Crée un admin et un utilisateur standard.
 * - Initialise les quantités recommandées par type d'aliment.
 * - Associe les recommandations de nutriments via NutrientHandler.
 */
class UserFixtures extends BaseFixture implements FixtureGroupInterface
{ 
    /**
     * @param UserPasswordHasherInterface $passwordHasher Pour hasher les mots de passe
     * @param NutrientHandler $nutritionHandler Pour récupérer les recommandations nutritionnelles
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher, 
        private NutrientHandler $nutritionHandler
    ){}

    /**
     * Charge les utilisateurs en base
     *
     * @param ObjectManager $manager
     */
    protected function loadData(ObjectManager $manager)
    {
        // ======================
        // Quantités (factices) recommandées par type de groupe alimentaire
        // ======================
        $recommendedQuantities = [
            'FGP_VPO' => 200,    // Viandes, poissons, œufs
            'FGP_STARCHY' => 200, // Féculents
            'FGP_VEG' => 200,     // Légumes
            'FGP_FRUIT' => 200,   // Fruits
            'FGP_DAIRY' => 200,   // Produits laitiers
            'FGP_FAT' => 200,     // Matières grasses
            'FGP_SUGAR' => 200,   // Sucres
        ];

        // ======================
        // Création de l'administrateur
        // ======================
        $admin = new User();
        $admin->setUsername('florent_admin');
        $admin->setEmail('florent_admin@example.com');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, '1234')
        );
        $admin->eraseCredentials();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setFirstFillProfile(1); // indique que le profil initial est rempli
        $admin->setRecommendedQuantities($recommendedQuantities);
        $manager->persist($admin);

        // ======================
        // Création d'un utilisateur standard
        // ======================
        $user = new User();
        $user->setUsername('florent_user');
        $user->setEmail('florent_user@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, '1234')
        );
        $user->eraseCredentials();
        $user->setRecommendedQuantities($recommendedQuantities);
        $user->setIsVerified(true);

        // ======================
        // Associer les recommandations de nutriments
        // ======================
        $nutrientRecommendations = $this->nutritionHandler->getRecommendations();
        foreach ($nutrientRecommendations as $nutrientId => $quantity) {
            // On suppose que $nutrientRepository est accessible ici
            $nutrient = $nutrientRepository->findOneById($nutrientId);

            $nutrientRecommendationUser = new NutrientRecommendationUser();
            $nutrientRecommendationUser->setNutrient($nutrient);
            $nutrientRecommendationUser->setUser($user);
            $nutrientRecommendationUser->setRecommendedQuantity($quantity);

            $user->addNutrientRecommendation($nutrientRecommendationUser);

            $manager->persist($nutrientRecommendationUser);
        }

        $manager->persist($user);

        // Enregistrement en base
        $manager->flush();
    }

    /**
     * Groupes de fixtures pour un chargement ciblé
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return ['users', 'dev', 'test'];
    }
}