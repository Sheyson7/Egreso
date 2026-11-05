<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use RuntimeException;

final class JsonDemoReservationRepository
{
    public function __construct(private string $directory) {}

    public function transact(callable $operation): mixed
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo crear la carpeta local para las reservas.');
        }

        $lock = fopen($this->directory . '/.reservations.lock', 'c+');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('No se pudo bloquear el archivo de reservas.');
        }

        try {
            $path = $this->directory . '/reservations.json';
            $needsWrite = !is_file($path);
            if (!$needsWrite) {
                $json = file_get_contents($path);
                $reservations = is_string($json) ? json_decode($json, true) : null;
                if (!is_array($reservations)) {
                    throw new RuntimeException('El archivo local de reservas no contiene datos válidos.');
                }
                $needsOwnerMigration = false;
                foreach ($reservations as $reservation) {
                    if (!isset($reservation['user_id']) || (int) $reservation['user_id'] < 1) {
                        $needsOwnerMigration = true;
                        break;
                    }
                }
                if ($needsOwnerMigration) {
                    $accountIds = [];
                    foreach (PdoConnectionFactory::connection()->query('SELECT id, email FROM users')->fetchAll() as $account) {
                        $accountIds[strtolower($account['email'])] = (int) $account['id'];
                    }
                    foreach ($reservations as &$reservation) {
                        if (!isset($reservation['user_id']) || (int) $reservation['user_id'] < 1) {
                            $reservation['user_id'] = $accountIds[strtolower((string) ($reservation['email'] ?? ''))] ?? 0;
                        }
                    }
                    unset($reservation);
                    $needsWrite = true;
                }
            } else {
                $legacyRows = PdoConnectionFactory::connection()->query(
                    "SELECT r.id, r.user_id, r.court_id, r.starts_at, r.ends_at, r.total_amount, r.deposit_amount, r.created_at,
                            TIMESTAMPDIFF(HOUR, r.starts_at, r.ends_at) AS duration,
                            u.full_name, u.email
                     FROM reservations r JOIN users u ON u.id = r.user_id WHERE r.status = 'demo'
                     ORDER BY r.starts_at"
                )->fetchAll();
                $reservations = array_map(static fn (array $row): array => [
                    'id' => $row['id'],
                    'user_id' => (int) $row['user_id'],
                    'name' => $row['full_name'],
                    'email' => $row['email'],
                    'court_id' => (int) $row['court_id'],
                    'starts_at' => $row['starts_at'],
                    'ends_at' => $row['ends_at'],
                    'duration' => (int) $row['duration'],
                    'total_amount' => (int) $row['total_amount'],
                    'deposit_amount' => (int) $row['deposit_amount'],
                    'status' => 'demo',
                    'created_at' => $row['created_at'],
                ], $legacyRows);
            }

            [$changed, $result] = $operation($reservations);
            if ($changed || $needsWrite) {
                $encoded = json_encode($reservations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (!is_string($encoded)) {
                    throw new RuntimeException('No se pudieron preparar las reservas para guardarlas.');
                }
                $temporaryPath = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
                if (file_put_contents($temporaryPath, $encoded . PHP_EOL, LOCK_EX) === false) {
                    throw new RuntimeException('No se pudo escribir el archivo local de reservas.');
                }
                if (!rename($temporaryPath, $path)) {
                    if (!copy($temporaryPath, $path)) {
                        @unlink($temporaryPath);
                        throw new RuntimeException('No se pudo actualizar el archivo local de reservas.');
                    }
                    @unlink($temporaryPath);
                }
            }

            return $result;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
