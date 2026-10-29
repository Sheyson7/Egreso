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

function available_hours(int $courtId, string $date, int $duration = 1): array
{
    if ($courtId < 1 || $courtId > 2 || $duration < 1 || $duration > 8 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return [];
    }

    $statement = db()->prepare(
        "SELECT DATE_FORMAT(s.slot_start, '%H:%i') AS slot_time
         FROM reservation_slots s
         JOIN reservations r ON r.id = s.reservation_id
         WHERE s.court_id = ? AND DATE(s.slot_start) = ?
           AND (r.status = 'confirmed' OR (r.status = 'pending_payment' AND r.expires_at > NOW()))"
    );
    $statement->execute([$courtId, $date]);
    $busy = array_column($statement->fetchAll(), 'slot_time');

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
    $siteUrl = config('APP_URL', 'http://localhost:8000');
    if (!config('MERCADOPAGO_ACCESS_TOKEN') || !config('MERCADOPAGO_WEBHOOK_SECRET') || !str_starts_with($siteUrl, 'https://')) {
        throw new RuntimeException('Las reservas con pago todavía no están habilitadas: el complejo debe configurar Mercado Pago y publicar el sitio con HTTPS.');
    }

    $start = valid_booking_date($date, $time, $duration);
    if (!$start || $courtId < 1 || $courtId > 2 || !in_array($time, BOOKING_HOURS, true)) {
        throw new DomainException('Ese horario no es válido. Elegí otro horario disponible.');
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
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_payment', ?)"
        );
        $insert->execute([
            $reservationId,
            $user['id'],
            $courtId,
            $start->format('Y-m-d H:i:s'),
            $start->modify("+{$duration} hours")->format('Y-m-d H:i:s'),
            booking_price() * $duration,
            booking_deposit_for($duration),
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
