<?php

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;

class CustomVerySecureHasher4 implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        if($this->isPasswordTooLong($plainPassword))
        {
            throw new InvalidPasswordException();
        }

        return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 15]);
    }

    public function verify(string $hashPassword, string $plainPassword): bool
    {
        if('' === $plainPassword || $this->isPasswordTooLong($plainPassword))
            return false;

        return password_verify($plainPassword, $hashPassword);
    }

    public function needsRehash($hashPassword): bool
    {
        return password_needs_rehash($hashPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}