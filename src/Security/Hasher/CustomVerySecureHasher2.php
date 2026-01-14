<?php

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class CustomVerySecureHasher2 implements PasswordHasherInterface
{
    use CheckPasswordLengthTrait;

    public function hash(string $plainPassword): string
    {
        if($this->ispasswordTooLong($plainPassword)) {
            throw new InvalidPasswordException();
        }

        $hashPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 13]);

        return $hashPassword;
    }

    public function verify(string $hashPassword, string $plainPassword): bool
    {
        if('' === $plainPassword || $this->isPasswordTooLong($plainPassword)) {
            return false;
        }

        return password_verify($plainPassword, $hashPassword);
    }

    public function needsRehash(string $hashPassword): bool
    {
        return password_needs_rehash($hashPassword, PASSWORD_BCRYPT, ['cost' => 13]);
    }
}