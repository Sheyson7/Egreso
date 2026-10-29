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
    $redirect = start_reservation(
        $_SESSION['user'],
        (int) ($_POST['court'] ?? 0),
        trim((string) ($_POST['date'] ?? '')),
        trim((string) ($_POST['time'] ?? '')),
    );
    json_response(['redirect' => $redirect]);
} catch (DomainException $error) {
    json_response(['error' => $error->getMessage()], 409);
} catch (Throwable $error) {
    error_log($error->__toString());
    json_response(['error' => $error instanceof RuntimeException ? $error->getMessage() : 'No pudimos iniciar la reserva. Intentá nuevamente.'], 503);
}
