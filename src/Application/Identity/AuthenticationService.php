<?php
declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\EmailAlreadyRegistered;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use DomainException;

final class AuthenticationService
{
    public function __construct(private UserRepository $users) {}

    public function register(string $name, string $email, ?string $phone, string $password, string $confirmation): User
    {
        try {
            return $this->users->create(User::register($name, $email, $phone, $password, $confirmation));
        } catch (EmailAlreadyRegistered $error) {
            throw new DomainException('Ese correo ya tiene una cuenta. Iniciá sesión.', 0, $error);
        }
    }

    public function authenticate(string $email, string $password): User
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);
        if (!$user || !$user->verifyPassword($password)) {
            throw new DomainException('El correo o la contraseña no coinciden.');
        }
        return $user;
    }
}
