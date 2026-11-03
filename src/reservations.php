<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const BOOKING_HOURS = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

function booking_price(): int
{
    return max(1, (int) config('BOOKING_PRICE_PER_HOUR', '1200'));
}

function booking_deposit(): int
{
    return booking_deposit_for(1);
}

function booking_deposit_for(int $duration): int
{
    return (int) round(booking_price() * $duration * (int) config('BOOKING_DEPOSIT_PERCENT', '15') / 100);
}

function with_demo_reservations(callable $operation): mixed
{
    $directory = PROJECT_ROOT . '/storage';
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('No se pudo crear la carpeta local para las reservas.');
    }

    $lock = fopen($directory . '/.reservations.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('No se pudo bloquear el archivo de reservas.');
    }

    try {
        $path = $directory . '/reservations.json';
        $needsWrite = !is_file($path);
        if (!$needsWrite) {
            $json = file_get_contents($path);
            $reservations = is_string($json) ? json_decode($json, true) : null;
            if (!is_array($reservations)) {
                throw new RuntimeException('El archivo local de reservas no contiene datos válidos.');
            }
        } else {
            $legacyRows = db()->query(
                "SELECT r.id, r.court_id, r.starts_at, r.ends_at, r.total_amount, r.deposit_amount, r.created_at,
                        TIMESTAMPDIFF(HOUR, r.starts_at, r.ends_at) AS duration,
                        u.full_name, u.email
                 FROM reservations r JOIN users u ON u.id = r.user_id WHERE r.status = 'demo'
                 ORDER BY r.starts_at"
            )->fetchAll();
            $reservations = array_map(static fn (array $row): array => [
                'id' => $row['id'],
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

function all_demo_reservations(): array
{
    return with_demo_reservations(static fn (array $reservations): array => [false, $reservations]);
}

function user_demo_reservations(string $email): array
{
    return array_values(array_filter(
        all_demo_reservations(),
        static fn (array $reservation): bool => strcasecmp((string) ($reservation['email'] ?? ''), $email) === 0,
    ));
}

function find_user_demo_reservation(string $reservationId, string $email): ?array
{
    foreach (user_demo_reservations($email) as $reservation) {
        if (($reservation['id'] ?? '') === $reservationId) {
            return $reservation;
        }
    }
    return null;
}

function cancel_demo_reservation(string $reservationId, ?string $ownerEmail = null): bool
{
    return with_demo_reservations(static function (array &$reservations) use ($reservationId, $ownerEmail): array {
        foreach ($reservations as &$reservation) {
            $owned = $ownerEmail === null || strcasecmp((string) ($reservation['email'] ?? ''), $ownerEmail) === 0;
            if (($reservation['id'] ?? '') === $reservationId && ($reservation['status'] ?? '') === 'demo' && $owned) {
                if ($ownerEmail !== null && new DateTimeImmutable($reservation['starts_at']) <= new DateTimeImmutable()) {
                    throw new DomainException('Ya no se puede cancelar una reserva cuyo horario comenzó.');
                }
                $reservation['status'] = 'cancelled';
                $reservation['cancelled_at'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
                return [true, true];
            }
        }
        unset($reservation);
        return [false, false];
    });
}

function reschedule_demo_reservation(string $reservationId, string $ownerEmail, int $courtId, string $date, string $time, int $duration): bool
{
    $start = valid_booking_date($date, $time, $duration);
    if (!$start || $courtId < 1 || $courtId > 2 || !in_array($time, BOOKING_HOURS, true)) {
        throw new DomainException('Ese horario no es válido. Elegí un horario disponible.');
    }

    return with_demo_reservations(static function (array &$reservations) use ($reservationId, $ownerEmail, $courtId, $date, $duration, $start): array {
        $currentIndex = null;
        foreach ($reservations as $index => $reservation) {
            if (($reservation['id'] ?? '') === $reservationId
                && ($reservation['status'] ?? '') === 'demo'
                && strcasecmp((string) ($reservation['email'] ?? ''), $ownerEmail) === 0) {
                $currentIndex = $index;
                break;
            }
        }
        if ($currentIndex === null) {
            throw new DomainException('No encontramos una reserva activa asociada a tu cuenta.');
        }

        $current = $reservations[$currentIndex];
        if (new DateTimeImmutable($current['starts_at']) <= new DateTimeImmutable()) {
            throw new DomainException('Ya no se puede cambiar una reserva cuyo horario comenzó.');
        }

        $end = $start->modify("+{$duration} hours");
        foreach ($reservations as $other) {
            if (($other['status'] ?? '') !== 'demo'
                || ($other['id'] ?? '') === $reservationId
                || (int) ($other['court_id'] ?? 0) !== $courtId
                || substr((string) $other['starts_at'], 0, 10) !== $date) {
                continue;
            }
            if ($start->format('Y-m-d H:i:s') < $other['ends_at'] && $other['starts_at'] < $end->format('Y-m-d H:i:s')) {
                throw new DomainException('Ese nuevo horario acaba de ocuparse. Elegí otro disponible.');
            }
        }

        $reservations[$currentIndex]['court_id'] = $courtId;
        $reservations[$currentIndex]['starts_at'] = $start->format('Y-m-d H:i:s');
        $reservations[$currentIndex]['ends_at'] = $end->format('Y-m-d H:i:s');
        $reservations[$currentIndex]['duration'] = $duration;
        $reservations[$currentIndex]['total_amount'] = booking_price() * $duration;
        $reservations[$currentIndex]['deposit_amount'] = booking_deposit_for($duration);
        $reservations[$currentIndex]['updated_at'] = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
        return [true, true];
    });
}

function create_demo_reservation(array $user, int $courtId, string $date, string $time, int $duration, DateTimeImmutable $start): string
{
    $reservationId = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
    $end = $start->modify("+{$duration} hours");

    with_demo_reservations(static function (array &$reservations) use ($user, $courtId, $date, $duration, $start, $end, $reservationId): array {
        foreach ($reservations as $reservation) {
            if (($reservation['status'] ?? '') !== 'demo' || (int) ($reservation['court_id'] ?? 0) !== $courtId) {
                continue;
            }
            if (substr((string) $reservation['starts_at'], 0, 10) !== $date) {
                continue;
            }
            if ($start->format('Y-m-d H:i:s') < $reservation['ends_at'] && $reservation['starts_at'] < $end->format('Y-m-d H:i:s')) {
                throw new DomainException('Alguien acaba de tomar parte de ese horario. Elegí otro horario disponible.');
            }
        }

        $reservations[] = [
            'id' => $reservationId,
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'court_id' => $courtId,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
            'duration' => $duration,
            'total_amount' => booking_price() * $duration,
            'deposit_amount' => booking_deposit_for($duration),
            'currency' => 'UYU',
            'status' => 'demo',
            'created_at' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
        ];
        return [true, null];
    });

    return site_url('pago.php?estado=demo&reserva=' . rawurlencode($reservationId));
}

function booking_date_limit(): DateTimeImmutable
{
    $today = new DateTimeImmutable('today');
    $targetMonth = $today->modify('first day of this month')->modify('+3 months');
    return $targetMonth->setDate(
        (int) $targetMonth->format('Y'),
        (int) $targetMonth->format('m'),
        min((int) $today->format('j'), (int) $targetMonth->format('t')),
    );
}

function valid_booking_date(string $date, string $time, int $duration = 1): ?DateTimeImmutable
{
    if ($duration < 1 || $duration > 8) {
        return null;
    }
    $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$start || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $start->format('Y-m-d H:i') !== $date . ' ' . $time) {
        return null;
    }

    $today = new DateTimeImmutable('today');
    if ($start->setTime(0, 0) < $today || $start->setTime(0, 0) > booking_date_limit()) {
        return null;
    }

    for ($hour = 0; $hour < $duration; $hour++) {
        if (!in_array($start->modify("+{$hour} hours")->format('H:i'), BOOKING_HOURS, true)) {
            return null;
        }
    }

    $end = $start->modify("+{$duration} hours");
    $closingTime = $start->setTime(22, 0);
    if ($start <= new DateTimeImmutable('now') || $end > $closingTime) {
        return null;
    }
    return $start;
}

function available_hours(int $courtId, string $date, int $duration = 1, ?string $excludeReservationId = null): array
{
    if ($courtId < 1 || $courtId > 2 || $duration < 1 || $duration > 8 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return [];
    }

    if (booking_demo_mode()) {
        $busy = with_demo_reservations(static function (array $reservations) use ($courtId, $date, $excludeReservationId): array {
            $slots = [];
            foreach ($reservations as $reservation) {
                if (($reservation['status'] ?? '') !== 'demo'
                    || ($reservation['id'] ?? '') === $excludeReservationId
                    || (int) ($reservation['court_id'] ?? 0) !== $courtId
                    || substr((string) $reservation['starts_at'], 0, 10) !== $date) {
                    continue;
                }
                $slot = new DateTimeImmutable($reservation['starts_at']);
                while ($slot < new DateTimeImmutable($reservation['ends_at'])) {
                    $slots[] = $slot->format('H:i');
                    $slot = $slot->modify('+1 hour');
                }
            }
            return [false, $slots];
        });
    } else {
        $statement = db()->prepare(
            "SELECT DATE_FORMAT(s.slot_start, '%H:%i') AS slot_time
             FROM reservation_slots s
             JOIN reservations r ON r.id = s.reservation_id
             WHERE s.court_id = ? AND DATE(s.slot_start) = ?
               AND (r.status = 'confirmed' OR (r.status = 'pending_payment' AND r.expires_at > NOW()))"
        );
        $statement->execute([$courtId, $date]);
        $busy = array_column($statement->fetchAll(), 'slot_time');
    }

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');
    return array_values(array_filter(BOOKING_HOURS, static function (string $hour) use ($date, $today, $busy, $duration): bool {
        $start = valid_booking_date($date, $hour, $duration);
        if (!$start || ($date === $today && $start <= new DateTimeImmutable('now'))) {
            return false;
        }
        for ($offset = 0; $offset < $duration; $offset++) {
            if (in_array($start->modify("+{$offset} hours")->format('H:i'), $busy, true)) {
                return false;
            }
        }
        return true;
    }));
}

function release_reservation(string $reservationId, string $status): void
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare("UPDATE reservations SET status = ? WHERE id = ? AND status = 'pending_payment'");
        $statement->execute([$status, $reservationId]);
        if ($statement->rowCount() > 0) {
            $connection->prepare('DELETE FROM reservation_slots WHERE reservation_id = ?')->execute([$reservationId]);
        }
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollBack();
        throw $error;
    }
}

function mercado_pago_request(string $method, string $path, ?array $payload = null): array
{
    $token = config('MERCADOPAGO_ACCESS_TOKEN');
    if (!$token) {
        throw new RuntimeException('Mercado Pago aún no está configurado. Falta agregar las credenciales de la cuenta del complejo.');
    }

    $curl = curl_init('https://api.mercadopago.com' . $path);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Idempotency-Key: ' . bin2hex(random_bytes(20)),
        ],
    ]);
    if ($payload !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    $decoded = is_string($body) ? json_decode($body, true) : null;
    if ($error !== '' || $status < 200 || $status >= 300 || !is_array($decoded)) {
        throw new RuntimeException('No se pudo procesar el pago con Mercado Pago. Intentá nuevamente en unos minutos.');
    }
    return $decoded;
}

function start_reservation(array $user, int $courtId, string $date, string $time, int $duration): string
{
    $demoMode = booking_demo_mode();
    $siteUrl = config('APP_URL', 'http://localhost:8000');
    if (!$demoMode && (!config('MERCADOPAGO_ACCESS_TOKEN') || !config('MERCADOPAGO_WEBHOOK_SECRET') || !str_starts_with($siteUrl, 'https://'))) {
        throw new RuntimeException('Las reservas con pago todavía no están habilitadas: el complejo debe configurar Mercado Pago y publicar el sitio con HTTPS.');
    }

    $start = valid_booking_date($date, $time, $duration);
    if (!$start || $courtId < 1 || $courtId > 2 || !in_array($time, BOOKING_HOURS, true)) {
        throw new DomainException('Ese horario no es válido. Elegí otro horario disponible.');
    }

    if ($demoMode) {
        return create_demo_reservation($user, $courtId, $date, $time, $duration, $start);
    }

    $reservationId = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
    $expiresAt = new DateTimeImmutable('+15 minutes');
    $connection = db();
    $connection->beginTransaction();
    try {
        $connection->exec("UPDATE reservations SET status = 'expired' WHERE status = 'pending_payment' AND expires_at <= NOW()");
        $connection->exec("DELETE s FROM reservation_slots s JOIN reservations r ON r.id = s.reservation_id WHERE r.status = 'expired'");
        $insert = $connection->prepare(
            "INSERT INTO reservations (id, user_id, court_id, starts_at, ends_at, total_amount, deposit_amount, status, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([
            $reservationId,
            $user['id'],
            $courtId,
            $start->format('Y-m-d H:i:s'),
            $start->modify("+{$duration} hours")->format('Y-m-d H:i:s'),
            booking_price() * $duration,
            booking_deposit_for($duration),
            'pending_payment',
            $expiresAt->format('Y-m-d H:i:s'),
        ]);
        $slotInsert = $connection->prepare('INSERT INTO reservation_slots (court_id, slot_start, reservation_id) VALUES (?, ?, ?)');
        for ($offset = 0; $offset < $duration; $offset++) {
            $slotInsert->execute([$courtId, $start->modify("+{$offset} hours")->format('Y-m-d H:i:s'), $reservationId]);
        }
        $connection->commit();
    } catch (PDOException $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        if ($error->getCode() === '23000') {
            throw new DomainException('Alguien acaba de tomar ese horario. Elegí otro disponible.');
        }
        throw $error;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }

    try {
        $preference = mercado_pago_request('POST', '/checkout/preferences', [
            'items' => [[
                'id' => $reservationId,
                'title' => 'Seña de reserva · Cancha ' . $courtId . ' · ' . $duration . ' h',
                'quantity' => 1,
                'currency_id' => 'UYU',
                'unit_price' => booking_deposit_for($duration),
            ]],
            'external_reference' => $reservationId,
            'metadata' => ['reservation_id' => $reservationId],
            'expires' => true,
            'expiration_date_from' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'expiration_date_to' => $expiresAt->format(DateTimeInterface::ATOM),
            'back_urls' => [
                'success' => site_url('pago.php?estado=retorno&reserva=' . rawurlencode($reservationId)),
                'pending' => site_url('pago.php?estado=pendiente'),
                'failure' => site_url('pago.php?estado=rechazado'),
            ],
            'auto_return' => 'approved',
            'notification_url' => site_url('webhook.php'),
        ]);
        if (empty($preference['id']) || empty($preference['init_point'])) {
            throw new RuntimeException('Mercado Pago no devolvió un enlace de pago válido.');
        }
        $connection->prepare('UPDATE reservations SET payment_preference_id = ? WHERE id = ?')
            ->execute([$preference['id'], $reservationId]);
        return (string) $preference['init_point'];
    } catch (Throwable $error) {
        release_reservation($reservationId, 'payment_failed');
        throw $error;
    }
}

function verify_and_apply_payment(string $paymentId): void
{
    $payment = mercado_pago_request('GET', '/v1/payments/' . rawurlencode($paymentId));
    $reservationId = (string) ($payment['external_reference'] ?? '');
    if (!preg_match('/^[a-f0-9-]{36}$/', $reservationId)) {
        return;
    }

    $connection = db();
    $connection->beginTransaction();
    try {
        $query = $connection->prepare('SELECT * FROM reservations WHERE id = ? FOR UPDATE');
        $query->execute([$reservationId]);
        $reservation = $query->fetch();
        if (!$reservation || $reservation['currency'] !== 'UYU') {
            $connection->commit();
            return;
        }

        $validPayment = (float) ($payment['transaction_amount'] ?? 0) === (float) $reservation['deposit_amount']
            && ($payment['currency_id'] ?? '') === 'UYU'
            && (string) ($payment['external_reference'] ?? '') === $reservationId;
        if (!$validPayment) {
            $connection->commit();
            return;
        }

        if (($payment['status'] ?? '') === 'approved' && $reservation['status'] === 'pending_payment') {
            $connection->prepare("UPDATE reservations SET status = 'confirmed', payment_id = ?, expires_at = NULL WHERE id = ?")
                ->execute([$paymentId, $reservationId]);
        } elseif (in_array($payment['status'] ?? '', ['rejected', 'cancelled'], true) && $reservation['status'] === 'pending_payment') {
            $connection->prepare("UPDATE reservations SET status = 'payment_failed', payment_id = ? WHERE id = ?")
                ->execute([$paymentId, $reservationId]);
            $connection->prepare('DELETE FROM reservation_slots WHERE reservation_id = ?')->execute([$reservationId]);
        }
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
