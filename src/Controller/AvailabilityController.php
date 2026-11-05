<?php
declare(strict_types=1);

namespace App\Controller;

use Throwable;

final class AvailabilityController
{
    public function handle(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            \json_response(['error' => 'Método no permitido.'], 405);
        }

        try {
            $court = (int) ($_GET['court'] ?? 1);
            $date = (string) ($_GET['date'] ?? '');
            $duration = (int) ($_GET['duration'] ?? 1);
            $excludeId = (string) ($_GET['exclude'] ?? '');
            if ($excludeId !== '') {
                $excludedReservation = \booking_demo_mode() && isset($_SESSION['user'])
                    ? \find_user_demo_reservation($excludeId, (int) $_SESSION['user']['id'], $_SESSION['user']['email'])
                    : null;
                if (!$excludedReservation || ($excludedReservation['status'] ?? '') !== 'demo') {
                    $excludeId = '';
                }
            }

            \json_response([
                'hours' => \available_hours($court, $date, $duration, $excludeId !== '' ? $excludeId : null),
                'price' => \booking_price(),
                'total' => \booking_price() * max(1, min(8, $duration)),
                'deposit' => \booking_deposit_for(max(1, min(8, $duration))),
                'loggedIn' => isset($_SESSION['user']),
                'demoMode' => \booking_demo_mode(),
            ]);
        } catch (Throwable $error) {
            error_log($error->__toString());
            \json_response(['error' => 'No se pudieron consultar los horarios. Verificá que la base de datos esté activa.'], 503);
        }
    }
}
