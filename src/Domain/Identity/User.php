<?php
declare(strict_types=1);

namespace App\Domain\Identity;

use DomainException;

final class User
{
    private function __construct(
        private ?int $id,
        private string $name,
        private string $email,
        private ?string $phone,
        private string $passwordHash,
    ) {}

    public static function register(string $name, string $email, ?string $phone, string $password, string $confirmation): self
    {
        $name = trim($name);
        $email = strtolower(trim($email));
        $phone = $phone !== null ? trim($phone) : null;
        $nameLength = iconv_strlen($name, 'UTF-8');

        if ($nameLength === false || $nameLength < 2 || $nameLength > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Revisá tu nombre y correo electrónico.');
        }
        if ($phone !== null && $phone === '') {
            $phone = null;
        }
        if ($phone !== null && strlen($phone) > 40) {
            throw new DomainException('El teléfono ingresado es demasiado largo.');
        }
        if (strlen($password) < 8 || !hash_equals($password, $confirmation)) {
            throw new DomainException('La contraseña debe tener 8 caracteres o más y ambas deben coincidir.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($passwordHash)) {
            throw new DomainException('No se pudo proteger la contraseña. Intentá nuevamente.');
        }

        return new self(null, $name, $email, $phone, $passwordHash);
    }

    public static function fromRecord(array $record): self
    {
        return new self(
            (int) $record['id'],
            (string) $record['full_name'],
            (string) $record['email'],
            isset($record['phone']) ? (string) $record['phone'] : null,
            (string) $record['password_hash'],
        );
    }

    public function withId(int $id): self
    {
        return new self($id, $this->name, $this->email, $this->phone, $this->passwordHash);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    public function toSession(): array
    {
        if ($this->id === null) {
            throw new DomainException('La cuenta todavía no fue guardada.');
        }
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email];
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function email(): string { return $this->email; }
    public function phone(): ?string { return $this->phone; }
    public function passwordHash(): string { return $this->passwordHash; }
}
