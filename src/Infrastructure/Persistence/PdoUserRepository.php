<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Identity\EmailAlreadyRegistered;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use PDO;
use PDOException;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $connection) {}

    public function findByEmail(string $email): ?User
    {
        $statement = $this->connection->prepare(
            'SELECT id, full_name, email, phone, password_hash FROM users WHERE email = ? LIMIT 1'
        );
        $statement->execute([$email]);
        $record = $statement->fetch();
        return $record ? User::fromRecord($record) : null;
    }

    public function create(User $user): User
    {
        $statement = $this->connection->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)'
        );
        try {
            $statement->execute([$user->name(), $user->email(), $user->phone(), $user->passwordHash()]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new EmailAlreadyRegistered(previous: $error);
            }
            throw $error;
        }

        return $user->withId((int) $this->connection->lastInsertId());
    }
}
