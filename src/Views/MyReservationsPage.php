<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <title>Mis reservas | Seberih</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/account.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <script src="/assets/js/mis-reservas.js" defer></script>
</head>
<body class="account-page">
    <header class="account-header">
        <a class="brand" href="/" aria-label="Seberih, inicio">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span>SEBERIH<span class="brand-dot">.</span></span>
        </a>
        <a class="account-back" href="/">Volver al complejo <span aria-hidden="true">↗</span></a>
    </header>
    <main class="reservation-admin account-reservations-page">
        <p class="eyebrow"><span class="eyebrow-line"></span>HOLA, <?= e($user['name']) ?></p>
        <h1>Mis <span>reservas.</span></h1>
        <p class="admin-intro">
            <?php if ($demoMode): ?>
                Podés revisar, cambiar o cancelar tus reservas de prueba hasta que comience el partido. No se realiza ningún cobro en esta versión.
            <?php else: ?>
                Acá podés consultar el estado de tus reservas. Para cambios o cancelaciones de reservas con seña, comunicate con el complejo.
            <?php endif; ?>
        </p>
        <?php if ($notice): ?>
            <p class="admin-message <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></p>
        <?php endif; ?>
        <?php if ($pageError): ?>
            <p class="admin-message error" role="alert"><?= e($pageError) ?></p>
        <?php elseif ($reservations === []): ?>
            <section class="admin-empty">
                <h2>Todavía no tenés reservas.</h2>
                <p>Elegí una fecha y un horario para organizar tu próximo partido.</p>
                <a class="button button-primary" href="/#reservas">Buscar un horario <span aria-hidden="true">→</span></a>
            </section>
        <?php else: ?>
            <div class="admin-reservations user-reservations">
                <?php foreach ($reservations as $reservation): ?>
                    <?php
                    $isDemoActive = $demoMode
                        && ($reservation['status'] ?? '') === 'demo'
                        && new DateTimeImmutable($reservation['starts_at']) > new DateTimeImmutable();
                    $isCancelled = ($reservation['status'] ?? '') === 'cancelled';
                    $statusLabel = $isCancelled ? 'Cancelada' : ($isDemoActive ? 'Prueba guardada' : (($reservation['status'] ?? '') === 'confirmed' ? 'Confirmada' : (($reservation['status'] ?? '') === 'pending_payment' ? 'Pendiente de pago' : (($reservation['status'] ?? '') === 'demo' ? 'Finalizada' : ucfirst((string) $reservation['status'])))));
                    $startsAt = new DateTimeImmutable($reservation['starts_at']);
                    $dateValue = $startsAt->format('Y-m-d');
                    $duration = (int) ($reservation['duration'] ?? ((new DateTimeImmutable($reservation['ends_at']))->getTimestamp() - $startsAt->getTimestamp()) / 3600);
                    ?>
                    <article class="admin-reservation user-reservation <?= $isCancelled ? 'is-cancelled' : '' ?>" data-reservation-id="<?= e((string) $reservation['id']) ?>">
                        <div class="admin-reservation-heading">
                            <div>
                                <p class="reservation-schedule">CANCHA <?= (int) $reservation['court_id'] ?> · <?= e($startsAt->format('d/m/Y')) ?> · <?= e($startsAt->format('H:i')) ?>–<?= e((new DateTimeImmutable($reservation['ends_at']))->format('H:i')) ?></p>
                                <h2><?= e($user['name']) ?></h2>
                            </div>
                            <span class="admin-status <?= $isDemoActive ? 'is-active' : ($isCancelled ? 'is-cancelled' : '') ?>"><?= e($statusLabel) ?></span>
                        </div>
                        <div class="admin-reservation-details">
                            <span>Duración <strong class="reservation-duration-value"><?= $duration ?> h</strong></span>
                            <span>Total <strong>$ <?= number_format((int) $reservation['total_amount'], 0, ',', '.') ?> UYU</strong></span>
                            <span>Seña (15%) <strong>$ <?= number_format((int) $reservation['deposit_amount'], 0, ',', '.') ?> UYU</strong></span>
                        </div>
                        <?php if ($isDemoActive): ?>
                            <p class="reservation-action-message" aria-live="polite"></p>
                            <form class="reservation-reschedule-form" data-csrf="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="reschedule">
                                <input type="hidden" name="reservation_id" value="<?= e((string) $reservation['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <div class="reservation-edit-fields">
                                    <label>Cancha
                                        <select name="court" required>
                                            <option value="1" <?= (int) $reservation['court_id'] === 1 ? 'selected' : '' ?>>Cancha 1</option>
                                            <option value="2" <?= (int) $reservation['court_id'] === 2 ? 'selected' : '' ?>>Cancha 2</option>
                                        </select>
                                    </label>
                                    <label>Fecha
                                        <select name="date" required>
                                            <?php foreach ($dateOptions as $dateKey => $dateLabel): ?>
                                                <option value="<?= e($dateKey) ?>" <?= $dateKey === $dateValue ? 'selected' : '' ?>><?= e($dateLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label>Duración
                                        <select name="duration" required>
                                            <?php for ($hours = 1; $hours <= 8; $hours++): ?>
                                                <option value="<?= $hours ?>" <?= $hours === $duration ? 'selected' : '' ?>><?= $hours ?> <?= $hours === 1 ? 'hora' : 'horas' ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </label>
                                    <label>Horario
                                        <select name="time" required data-current-time="<?= e($startsAt->format('H:i')) ?>">
                                            <option value="" disabled selected>Buscando disponibilidad…</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="reservation-edit-footer">
                                    <span>Total <strong class="reservation-total">$ <?= number_format((int) $reservation['total_amount'], 0, ',', '.') ?> UYU</strong> · Seña informativa (15%) <strong class="reservation-deposit">$ <?= number_format((int) $reservation['deposit_amount'], 0, ',', '.') ?> UYU</strong></span>
                                    <button class="button button-dark" type="submit">Guardar cambios</button>
                                </div>
                            </form>
                            <form class="reservation-cancel-form" data-csrf="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="reservation_id" value="<?= e((string) $reservation['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <button type="submit">Cancelar reserva y liberar horario</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
