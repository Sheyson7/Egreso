<?php
declare(strict_types=1);

namespace App\Controller;

use Throwable;

final class AdminController
{
    public function handle(): void
    {
        if (!\booking_demo_mode()) {
            http_response_code(404);
            exit('No encontrado.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            \require_csrf();
            $reservationId = (string) ($_POST['reservation_id'] ?? '');
            if (!preg_match('/^[a-f0-9-]{36}$/', $reservationId)) {
                \flash('error', 'La reserva seleccionada no es válida.');
            } elseif (\cancel_demo_reservation($reservationId)) {
                \flash('success', 'Reserva cancelada y horario liberado.');
            } else {
                \flash('error', 'No se encontró una reserva activa con ese identificador.');
            }
            header('Location: /admin.php');
            exit;
        }

        $notice = \take_flash();
        try {
            $reservations = \all_demo_reservations();
            usort($reservations, static fn (array $first, array $second): int => strcmp($first['starts_at'], $second['starts_at']));
            $pageError = null;
        } catch (Throwable $error) {
            error_log($error->__toString());
            $reservations = [];
            $pageError = 'No se pudo leer el archivo local. Revisá que no se haya editado a mano con un formato inválido.';
        }

        require \PROJECT_ROOT . '/src/Views/AdminPage.php';
    }
}
