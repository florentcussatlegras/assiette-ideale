<?php

namespace App\Security\Voter;

use App\Entity\Dish;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

class DishVoter extends Voter
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function supports($attribute, $subject): bool
    {
        return (in_array($attribute, ['EDIT_DISH']) && $subject instanceof Dish);
    }

    public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if(!$user instanceof User) {
            return false;
        }

        if($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_EDITOR_DISH')) {
            return true;
        }

        if(!$subject instanceof Dish) {
            throw new \Exception('L\'objet vérifié doit être un plat');
        }

        switch ($attribute) {
            case 'EDIT_DISH':
                return $subject->getUser() === $user;
        }

        return false;
    }
}