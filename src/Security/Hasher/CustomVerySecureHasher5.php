<?php

namespace App\Security\Hasher;

use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomVerySecureHasher5 implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function hash(string $plainPassword): string
    {
        if($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 11]);

        return $hashedPassword;
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {   
        if('' === $plainPassword || $this->isPasswordTooLong($plainPassword))
            return false;


        if($this->needsRehash($hashedPassword)) {
            return false;
        }

        return password_verify($plainPassword, $hashedPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT, ['cost' => 11]);
    }
}