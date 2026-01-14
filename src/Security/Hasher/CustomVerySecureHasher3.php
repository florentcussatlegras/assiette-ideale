<?php

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class CustomVerySecureHasher3 implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        if($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        return $hashedPassword;
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return password_verify($plainPassword, $hashedPassword);
    }
    
    public function needsRehash(string $hashedPassword): bool
    {
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT, ['cost' => 13]);
    }
}