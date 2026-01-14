<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\NutrientHandler;
use App\DataFixtures\BaseFixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\NutrientRecommendationUser;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends BaseFixture implements FixtureGroupInterface
{ 
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher, 
        private NutrientHandler $nutritionHandler
    ){}

    protected function loadData(ObjectManager $manager)
    {
        $recommendedQuantitites = [
                'FGP_VPO' => 200,
                'FGP_STARCHY' => 200,
                'FGP_VEG' => 200,
                'FGP_FRUIT' => 200,
                'FGP_DAIRY' => 200,
                'FGP_FAT' => 200,
                'FGP_SUGAR' => 200,
        ];

        $admin = new User();
        $admin->setUsername('florent_admin');
        $admin->setEmail('florent_admin@example.com');
        $admin->setPassword(
            $this->passwordHasher->hashPassword(
                $admin, 
                '1234'
            )
        );
        $admin->eraseCredentials();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setFirstFillProfile(1);
        $admin->setRecommendedQuantities($recommendedQuantities);
        $manager->persist($admin);

        $user = new User();
        $user->setUsername('florent_user');
        $user->setEmail('florent_user@example.com');
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user, 
                '1234'
            )
        );
        $user->eraseCredentials();
        $user->setRecommendedQuantities($recommendedQuantities);
        $user->setIsVerified(true);

        $nutrientRecommendations = $this->nutrientHandler->getRecommendations();
        foreach($nutrientRecommendations as $nutrientId => $quantity) {
            $nutrient = $nutrientRepository->findOneById($nutrientId);
            $nutrientRecommendationUser = new NutrientRecommendationUser();
            $nutrientRecommendationUser->setNutrient($nutrient);
            $nutrientRecommendationUser->setUser($user);
            $nutrientRecommendationUser->setRecommendedQuantity($quantity);

            $user->addNutrientRecommendation($nutrientRecommendationUser);

            $manager->persist($nutrientRecommendationUser);
        }

        $manager->persist($user);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['users', 'dev', 'test'];
    }
}