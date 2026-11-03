<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/reservations.php';

$state = (string) ($_GET['estado'] ?? 'retorno');
$reservation = null;
$reservationId = (string) ($_GET['reserva'] ?? '');
$paymentId = (string) ($_GET['payment_id'] ?? '');
if ($paymentId !== '' && preg_match('/^\d+$/', $paymentId) && isset($_SESSION['user'])) {
    try {
        verify_and_apply_payment($paymentId);
    } catch (Throwable $error) {
        error_log($error->__toString());
    }
}
if (preg_match('/^[a-f0-9-]{36}$/', $reservationId) && isset($_SESSION['user'])) {
    if (booking_demo_mode()) {
        foreach (all_demo_reservations() as $savedReservation) {
            if ($savedReservation['id'] === $reservationId && $savedReservation['email'] === $_SESSION['user']['email']) {
                $reservation = $savedReservation;
                break;
            }
        }
    } else {
        $query = db()->prepare('SELECT court_id, starts_at, ends_at, total_amount, deposit_amount, status FROM reservations WHERE id = ? AND user_id = ?');
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
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <title>Estado del pago | Seberih</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/account.css">
</head>
<body class="account-page">
    <main class="account-main payment-result">
        <p class="eyebrow eyebrow-dark">SEBERIH · RESERVAS</p>
        <h1><?= $reservation && $reservation['status'] === 'demo' ? 'Reserva de <span>prueba guardada.</span>' : 'Estado de tu <span>seña.</span>' ?></h1>
        <p><?= e($message) ?></p>
        <?php if ($reservation): ?>
            <p>Cancha <?= (int) $reservation['court_id'] ?> · <?= e((new DateTimeImmutable($reservation['starts_at']))->format('d/m/Y')) ?> · <?= e((new DateTimeImmutable($reservation['starts_at']))->format('H:i')) ?>–<?= e((new DateTimeImmutable($reservation['ends_at']))->format('H:i')) ?></p>
            <p>Total: $ <?= number_format((int) $reservation['total_amount'], 0, ',', '.') ?> UYU · Seña real (15%): $ <?= number_format((int) $reservation['deposit_amount'], 0, ',', '.') ?> UYU</p>
        <?php endif; ?>
        <a class="button button-primary" href="/#reservas">Volver a las reservas <span aria-hidden="true">→</span></a>
    </main>
</body>
</html>
