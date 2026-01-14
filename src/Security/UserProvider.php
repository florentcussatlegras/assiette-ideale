<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private $manager;
    
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        dd('la');
        return $this->entityManager->createQuery(
            'SELECT u
            FROM App\Entity\User u
            WHERE u.username = :query
            OR u.email = :query'
        )
        ->setParameter('query', $identifier)
        ->getOneOrNullResult();
    }

    public function supportsClass($class): bool
    {
        return UserInterface::class === $class || is_subclass_of(UserInterface::class, $class);
    }

    public function refreshUser(UserInterface $user)
    {
        if(!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
    }
}