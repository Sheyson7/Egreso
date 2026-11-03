<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/reservations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Método no permitido.'], 405);
}
require_csrf();

if (!isset($_SESSION['user'])) {
    json_response(['error' => 'Iniciá sesión con tu nombre para reservar.'], 401);
}

try {
    $action = (string) ($_POST['action'] ?? 'create');
    if ($action === 'cancel' || $action === 'reschedule') {
        if (!booking_demo_mode()) {
            json_response(['error' => 'Las reservas con pago se gestionan directamente con el complejo por ahora.'], 409);
        }
        $reservationId = trim((string) ($_POST['reservation_id'] ?? ''));
        if (!preg_match('/^[a-f0-9-]{36}$/', $reservationId)) {
            throw new DomainException('La reserva seleccionada no es válida.');
        }

        if ($action === 'cancel') {
            if (!cancel_demo_reservation($reservationId, (int) $_SESSION['user']['id'], $_SESSION['user']['email'])) {
                throw new DomainException('No encontramos una reserva activa asociada a tu cuenta.');
            }
            json_response(['message' => 'Reserva cancelada. El horario quedó libre.']);
        }

        reschedule_demo_reservation(
            $reservationId,
            (int) $_SESSION['user']['id'],
            $_SESSION['user']['email'],
            (int) ($_POST['court'] ?? 0),
            trim((string) ($_POST['date'] ?? '')),
            trim((string) ($_POST['time'] ?? '')),
            (int) ($_POST['duration'] ?? 1),
        );
        json_response(['message' => 'Cambios guardados en tu reserva.']);
    }

    $redirect = start_reservation(
        $_SESSION['user'],
        (int) ($_POST['court'] ?? 0),
        trim((string) ($_POST['date'] ?? '')),
        trim((string) ($_POST['time'] ?? '')),
        (int) ($_POST['duration'] ?? 1),
    );
    json_response(['redirect' => $redirect]);
} catch (DomainException $error) {
    json_response(['error' => $error->getMessage()], 409);
} catch (Throwable $error) {
    error_log($error->__toString());
    json_response(['error' => $error instanceof RuntimeException ? $error->getMessage() : 'No pudimos iniciar la reserva. Intentá nuevamente.'], 503);
}
