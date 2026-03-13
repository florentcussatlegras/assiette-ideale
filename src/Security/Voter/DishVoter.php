<?php

namespace App\Security\Voter;

use App\Entity\Dish;
use App\Entity\User;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Voter pour contrôler l'accès à l'édition des plats (Dish).
 * 
 * Vérifie si un utilisateur peut modifier un plat spécifique.
 */
class DishVoter extends Voter
{
    private Security $security;

    /**
     * Constructeur.
     *
     * @param Security $security Service Security pour vérifier les rôles
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Détermine si ce voter supporte l'attribut et le sujet donnés.
     *
     * @param string $attribute L'action à vérifier (ex: 'EDIT_DISH')
     * @param mixed  $subject   L'objet sur lequel l'action est effectuée
     * 
     * @return bool True si le voter peut gérer cet attribut et objet, false sinon
     */
    protected function supports($attribute, $subject): bool
    {
        // Ce voter gère uniquement l'attribut 'EDIT_DISH' et les objets Dish
        return in_array($attribute, ['EDIT_DISH']) && $subject instanceof Dish;
    }

    /**
     * Vérifie si l'utilisateur a le droit de réaliser l'action sur le sujet.
     *
     * @param string         $attribute L'action à vérifier
     * @param Dish           $subject   L'objet Dish sur lequel l'action est effectuée
     * @param TokenInterface $token     Token de sécurité contenant l'utilisateur
     * 
     * @return bool True si l'accès est autorisé, false sinon
     *
     * @throws \Exception Si le sujet n'est pas du type attendu
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        /** @var User|null $user */
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, refus immédiat
        if (!$user instanceof User) {
            return false;
        }

        // Les admins et éditeurs de plats ont tous les droits
        if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_EDITOR_DISH')) {
            return true;
        }

        // Vérification de type pour éviter les erreurs
        if (!$subject instanceof Dish) {
            throw new \Exception('L\'objet vérifié doit être un plat (Dish)');
        }

        // Vérifie les permissions spécifiques pour l'attribut
        switch ($attribute) {
            case 'EDIT_DISH':
                // Seul le propriétaire du plat peut le modifier
                return $subject->getUser() === $user;
        }

        // Par défaut, accès refusé
        return false;
    }
}