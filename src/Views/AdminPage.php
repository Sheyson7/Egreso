<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <title>Gestión local | Seberih</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/account.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="account-page">
    <header class="account-header">
        <a class="brand" href="/" aria-label="Seberih, inicio">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span>SEBERIH<span class="brand-dot">.</span></span>
        </a>
        <a class="account-back" href="/">Volver al sitio <span aria-hidden="true">↗</span></a>
    </header>
    <main class="reservation-admin">
        <p class="eyebrow"><span class="eyebrow-line"></span>SOLO EN ESTA COMPUTADORA</p>
        <h1>Gestión de <span>reservas.</span></h1>
        <p class="admin-intro">Las reservas de prueba se leen desde <code>storage/reservations.json</code>. Cancelar una reserva libera ese horario.</p>
        <?php if ($notice): ?>
            <p class="admin-message <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></p>
        <?php endif; ?>
        <?php if ($pageError): ?>
            <p class="admin-message error" role="alert"><?= e($pageError) ?></p>
        <?php elseif ($reservations === []): ?>
            <section class="admin-empty">
                <h2>Todavía no hay reservas guardadas.</h2>
                <p>Cuando alguien complete una reserva de prueba, aparecerá aquí.</p>
            </section>
        <?php else: ?>
            <div class="admin-reservations">
                <?php foreach ($reservations as $reservation): ?>
                    <?php $isActive = ($reservation['status'] ?? '') === 'demo'; ?>
                    <article class="admin-reservation <?= $isActive ? '' : 'is-cancelled' ?>">
                        <div class="admin-reservation-heading">
                            <div>
                                <p>CANCHA <?= (int) $reservation['court_id'] ?> · <?= e((new DateTimeImmutable($reservation['starts_at']))->format('d/m/Y')) ?></p>
                                <h2><?= e($reservation['name']) ?></h2>
                            </div>
                            <span class="admin-status <?= $isActive ? 'is-active' : 'is-cancelled' ?>"><?= $isActive ? 'Prueba activa' : 'Cancelada' ?></span>
                        </div>
                        <div class="admin-reservation-details">
                            <span>Horario <strong><?= e((new DateTimeImmutable($reservation['starts_at']))->format('H:i')) ?>–<?= e((new DateTimeImmutable($reservation['ends_at']))->format('H:i')) ?></strong></span>
                            <span>Duración <strong><?= (int) $reservation['duration'] ?> h</strong></span>
                            <span>Valor <strong>$ <?= number_format((int) $reservation['total_amount'], 0, ',', '.') ?> UYU</strong></span>
                            <span>Seña real (15%) <strong>$ <?= number_format((int) $reservation['deposit_amount'], 0, ',', '.') ?> UYU</strong></span>
                        </div>
                        <p class="admin-contact"><a href="mailto:<?= e($reservation['email']) ?>"><?= e($reservation['email']) ?></a></p>
                        <?php if ($isActive): ?>
                            <form method="post" class="admin-cancel-form" onsubmit="return confirm('¿Cancelar esta reserva de prueba y liberar el horario?')">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="reservation_id" value="<?= e($reservation['id']) ?>">
                                <button type="submit">Cancelar y liberar horario</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
