<?php
declare(strict_types=1);

namespace App\Controller;

use Throwable;

final class PaymentController
{
    public function show(): void
    {
        $state = (string) ($_GET['estado'] ?? 'retorno');
        $reservation = null;
        $reservationId = (string) ($_GET['reserva'] ?? '');
        $paymentId = (string) ($_GET['payment_id'] ?? '');
        if ($paymentId !== '' && preg_match('/^\d+$/', $paymentId) && isset($_SESSION['user'])) {
            try {
                \verify_and_apply_payment($paymentId);
            } catch (Throwable $error) {
                error_log($error->__toString());
            }
        }
        if (preg_match('/^[a-f0-9-]{36}$/', $reservationId) && isset($_SESSION['user'])) {
            if (\booking_demo_mode()) {
                foreach (\all_demo_reservations() as $savedReservation) {
                    if ($savedReservation['id'] === $reservationId
                        && (int) ($savedReservation['user_id'] ?? 0) === (int) $_SESSION['user']['id']
                        && $savedReservation['email'] === $_SESSION['user']['email']) {
                        $reservation = $savedReservation;
                        break;
                    }
                }
            } else {
                $query = \db()->prepare('SELECT court_id, starts_at, ends_at, total_amount, deposit_amount, status FROM reservations WHERE id = ? AND user_id = ?');
                $query->execute([$reservationId, $_SESSION['user']['id']]);
                $reservation = $query->fetch() ?: null;
            }
        }

        $message = $reservation && $reservation['status'] === 'demo'
            ? 'Guardamos esta reserva de prueba en la computadora. No se realizó ningún cobro. En la versión real se deberá pagar la seña del 15% para confirmarla: $ ' . number_format((int) $reservation['deposit_amount'], 0, ',', '.') . ' UYU.'
            : ($reservation && $reservation['status'] === 'confirmed'
            ? 'Seña aprobada: tu cancha quedó reservada a tu nombre.'
            : ($reservation && $reservation['status'] === 'cancelled'
                ? 'Esta reserva de prueba fue cancelada y el horario quedó libre.'
            : ($reservation && $reservation['status'] === 'payment_failed'
                ? 'El pago no fue aprobado. Si el horario sigue libre, podés volver a reservar.'
                : ($reservation && $reservation['status'] === 'expired'
                    ? 'El tiempo para pagar venció y el horario se liberó. Volvé a consultar la disponibilidad antes de intentarlo otra vez.'
                    : match ($state) {
                        'pendiente' => 'El pago todavía está pendiente. La reserva se confirmará cuando Mercado Pago lo apruebe.',
                        'rechazado' => 'El pago no fue aprobado. Si el horario sigue libre, podés volver a reservar.',
                        default => 'Gracias. Estamos verificando el pago con Mercado Pago; la reserva queda confirmada después de esa comprobación.',
                    }))));

        require \PROJECT_ROOT . '/src/Views/PaymentResultPage.php';
    }
}
