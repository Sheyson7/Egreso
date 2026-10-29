<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/reservations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Método no permitido.'], 405);
}

try {
    $court = (int) ($_GET['court'] ?? 1);
    $date = (string) ($_GET['date'] ?? '');
    json_response([
        'hours' => available_hours($court, $date),
        'price' => booking_price(),
        'deposit' => booking_deposit(),
        'loggedIn' => isset($_SESSION['user']),
    ]);
} catch (Throwable $error) {
    error_log($error->__toString());
    json_response(['error' => 'No se pudieron consultar los horarios. Verificá que la base de datos esté activa.'], 503);
}
