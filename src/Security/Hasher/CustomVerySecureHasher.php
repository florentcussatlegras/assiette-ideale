<?php

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;

class CustomVerySecureHasher implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        dd('hash');
        if($this->isPasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, [
            'cost' => 11
        ]);

        return $hashedPassword;
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        if('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return true;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}