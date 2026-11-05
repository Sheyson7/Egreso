<?php
declare(strict_types=1);

namespace App\Controller;

use Throwable;

final class MyReservationsController
{
    public function handle(): void
    {
        if (!isset($_SESSION['user'])) {
            \flash('error', 'Iniciá sesión para consultar tus reservas.');
            header('Location: /cuenta.php#iniciar-sesion');
            exit;
        }

        $user = $_SESSION['user'];
        $notice = \take_flash();
        $pageError = null;
        try {
            if (\booking_demo_mode()) {
                $reservations = \user_demo_reservations((int) $user['id'], $user['email']);
            } else {
                $query = \db()->prepare(
                    'SELECT id, court_id, starts_at, ends_at, total_amount, deposit_amount, currency, status
                     FROM reservations WHERE user_id = ? ORDER BY starts_at DESC'
                );
                $query->execute([$user['id']]);
                $reservations = $query->fetchAll();
            }
            usort($reservations, static fn (array $first, array $second): int => strcmp($first['starts_at'], $second['starts_at']));
        } catch (Throwable $error) {
            error_log($error->__toString());
            $reservations = [];
            $pageError = 'No pudimos cargar tus reservas. Revisá que el almacenamiento local esté disponible.';
        }

        $monthNames = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
        $dateOptions = [];
        $day = new \DateTimeImmutable('today');
        $lastDate = \booking_date_limit();
        while ($day <= $lastDate) {
            $dateOptions[$day->format('Y-m-d')] = $day->format('j') . ' de ' . $monthNames[(int) $day->format('n') - 1];
            $day = $day->modify('+1 day');
        }
        $demoMode = \booking_demo_mode();

        require \PROJECT_ROOT . '/src/Views/MyReservationsPage.php';
    }
}
