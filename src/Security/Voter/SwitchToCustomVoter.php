<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SwitchToCustomVoter extends Voter
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return (in_array($attribute, ['OLDER_THAN_42']) 
            && $subject instanceof UserInterface);

    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if(!$user instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }

        if($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return true;
        }

        return ($user->getAge() > 42);
    }
}