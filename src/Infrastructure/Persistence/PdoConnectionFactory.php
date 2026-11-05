<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

final class PdoConnectionFactory
{
    private static ?PDO $connection = null;
    private static bool $schemaInitialized = false;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = \config('DB_HOST', '127.0.0.1');
        $port = \config('DB_PORT', '3307');
        $database = \config('DB_NAME', 'seberih');
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        self::$connection = new PDO($dsn, \config('DB_USER', 'seberih_app'), \config('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$connection->exec("SET time_zone = '" . (new \DateTimeImmutable())->format('P') . "'");
        self::initializeSchema(self::$connection);

        return self::$connection;
    }

    private static function initializeSchema(PDO $connection): void
    {
        if (self::$schemaInitialized) {
            return;
        }

        $schema = file_get_contents(\PROJECT_ROOT . '/database/schema.sql');
        if ($schema === false) {
            throw new \RuntimeException('No se encontró el esquema de la base de datos.');
        }
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            $connection->exec($statement);
        }

        $statusType = $connection->query(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reservations' AND COLUMN_NAME = 'status'"
        )->fetchColumn();
        if (is_string($statusType) && !str_contains($statusType, "'demo'")) {
            $connection->exec("ALTER TABLE reservations MODIFY status ENUM('pending_payment', 'confirmed', 'demo', 'payment_failed', 'expired', 'cancelled') NOT NULL");
        }

        self::$schemaInitialized = true;
    }
}
