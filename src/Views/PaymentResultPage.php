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
        <?php if (isset($_SESSION['user'])): ?>
            <a href="/mis-reservas.php">Ver mis reservas</a>
        <?php endif; ?>
    </main>
</body>
</html>
