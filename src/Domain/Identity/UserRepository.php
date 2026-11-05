<?php
declare(strict_types=1);

namespace App\Domain\Identity;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function create(User $user): User;
}
