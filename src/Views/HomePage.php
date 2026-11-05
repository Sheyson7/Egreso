<?php
declare(strict_types=1);

require_once __DIR__ . '/../reservations.php';

$pageTitle = 'Seberih | Fútbol, amigos y buenos momentos';
$user = $_SESSION['user'] ?? null;
$notice = take_flash();
$demoMode = booking_demo_mode();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <meta
        name="description"
        content="Reservá tu próximo partido en Seberih, complejo deportivo con dos canchas de fútbol en Sauce."
    >
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#inicio" aria-label="Seberih, inicio">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span>SEBERIH<span class="brand-dot">.</span></span>
        </a>
        <nav class="main-nav" aria-label="Navegación principal">
            <a href="#canchas">El complejo</a>
            <a href="#reservas">Reservas</a>
            <?php if ($user): ?><a href="/mis-reservas.php">Mis reservas</a><?php endif; ?>
            <a href="#ubicacion">Ubicación</a>
            <a href="/cuenta.php"><?= $user ? 'Hola, ' . e($user['name']) : 'Mi cuenta' ?></a>
        </nav>
        <a class="header-cta" href="#reservas">Reservar cancha <span aria-hidden="true">↗</span></a>
        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">
            <span></span>
            <span></span>
        </button>
    </header>

    <main>
        <section class="hero" id="inicio">
            <div class="hero-image" aria-hidden="true"></div>
            <div class="hero-shade" aria-hidden="true"></div>
            <div class="hero-content">
                <p class="eyebrow">
                    <span class="eyebrow-line"></span>
                    COMPLEJO DEPORTIVO · SAUCE
                </p>
                <h1>El partido<br>empieza <span>acá.</span></h1>
                <p class="hero-copy">Juntá al equipo. Elegí tu cancha.<br>Lo demás es disfrutar.</p>
                <a class="button button-primary" href="#reservas">
                    Encontrá tu horario <span aria-hidden="true">↓</span>
                </a>
                <div class="hero-note">
                    <span class="live-dot"></span>
                    DOS CANCHAS · UN SOLO LUGAR
                </div>
            </div>
            <div class="hero-side-label" aria-hidden="true">SEBERIH · FÚTBOL Y ENCUENTRO</div>
            <div class="hero-scroll" aria-hidden="true"><span></span> DESLIZÁ PARA EXPLORAR</div>
        </section>

        <section class="booking-section" id="reservas">
            <div class="section-heading">
                <p class="eyebrow eyebrow-dark">
                    <span class="eyebrow-line"></span>
                    TU PRÓXIMO PARTIDO
                </p>
                <h2>Reservá tu cancha<span class="accent-dot">.</span></h2>
                <p>Elegí la fecha, el horario y cuántas horas quieren jugar.</p>
            </div>
            <form class="booking-card" id="booking-form" data-logged-in="<?= $user ? 'true' : 'false' ?>" data-csrf="<?= e(csrf_token()) ?>" data-date-max="<?= e(booking_date_limit()->format('Y-m-d')) ?>" data-hourly-price="<?= booking_price() ?>" data-deposit-percent="<?= (int) config('BOOKING_DEPOSIT_PERCENT', '15') ?>">
                <?php if ($notice): ?>
                    <p class="booking-notice <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></p>
                <?php endif; ?>
                <?php if ($demoMode): ?>
                    <p class="booking-notice demo-mode" role="status">
                        Modo de prueba: la reserva se guardará en esta computadora sin cobrar ahora. En la versión real se deberá pagar la seña del 15% para confirmarla.
                    </p>
                <?php endif; ?>
                <div class="field-group field-select">
                    <label for="court">CANCHA</label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true">▦</span>
                        <select id="court" name="court" required>
                            <option value="1">Cancha 1</option>
                            <option value="2">Cancha 2</option>
                        </select>
                    </div>
                </div>
                <div class="field-group date-select-field">
                    <label>FECHA DEL PARTIDO</label>
                    <p class="selected-date-label" id="selected-date-label">Elegí un día</p>
                    <div class="date-quick-options" id="date-quick-options" role="group" aria-label="Próximos días"></div>
                    <button class="calendar-toggle" id="date-calendar-toggle" type="button" aria-expanded="false" aria-controls="date-calendar">
                        Ver otras fechas <span aria-hidden="true">⌄</span>
                    </button>
                    <div class="booking-calendar" id="date-calendar" hidden>
                        <div class="calendar-heading">
                            <button type="button" id="calendar-previous" aria-label="Mes anterior">‹</button>
                            <strong id="calendar-month" aria-live="polite"></strong>
                            <button type="button" id="calendar-next" aria-label="Mes siguiente">›</button>
                        </div>
                        <div class="calendar-weekdays" aria-hidden="true">
                            <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span>
                        </div>
                        <div class="calendar-days" id="calendar-days" role="group" aria-label="Días del mes"></div>
                    </div>
                    <input id="date" name="date" type="hidden">
                </div>
                <div class="field-group">
                    <label for="duration">DURACIÓN</label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true">◷</span>
                        <select id="duration" name="duration" required>
                            <option value="1">1 hora</option>
                            <option value="2">2 horas</option>
                            <option value="3">3 horas</option>
                            <option value="4">4 horas</option>
                            <option value="5">5 horas</option>
                            <option value="6">6 horas</option>
                            <option value="7">7 horas</option>
                            <option value="8">8 horas</option>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label for="time">HORARIO</label>
                    <div class="input-wrap">
                        <span class="input-icon" aria-hidden="true">◷</span>
                        <select id="time" name="time" required>
                            <option value="" disabled selected>Elegí un horario</option>
                        </select>
                    </div>
                </div>
                <button class="button button-dark" type="submit">
                    <?= $user ? ($demoMode ? 'Guardar reserva' : 'Pagar seña') : 'Iniciar sesión' ?> <span aria-hidden="true">→</span>
                </button>
                <div class="booking-summary" aria-live="polite">
                    <span>Valor por hora <strong id="booking-price">$ 1.200 UYU</strong></span>
                    <span>Total de la cancha <strong id="booking-total">$ 1.200 UYU</strong></span>
                    <span>Seña para confirmar (15%) <strong id="booking-deposit">$ 180 UYU</strong></span>
                </div>
                <p class="form-note" id="form-message" aria-live="polite"><?= $demoMode ? 'La seña indicada es solo informativa durante esta prueba; no se realizará un cobro.' : 'La seña confirma el turno; el saldo se paga en el complejo.' ?></p>
                <?php if ($user): ?>
                    <div class="booking-user">Reserva a nombre de <strong><?= e($user['name']) ?></strong></div>
                <?php else: ?>
                    <div class="booking-user">Para reservar necesitás <a href="/cuenta.php#iniciar-sesion">iniciar sesión</a> o <a href="/cuenta.php#registrarse">crear tu cuenta</a>.</div>
                <?php endif; ?>
            </form>
        </section>

        <section class="courts-section" id="canchas">
            <div class="courts-intro">
                <p class="eyebrow">
                    <span class="eyebrow-line"></span>
                    EL COMPLEJO
                </p>
                <h2>Dos canchas.<br><span>Tu mismo equipo.</span></h2>
                <p>Un espacio para jugar, competir y compartir. Elegí la cancha para el partido que están armando.</p>
                <a class="text-link" href="#reservas">
                    Elegir horario <span aria-hidden="true">↗</span>
                </a>
            </div>
            <article class="court-card court-one">
                <div class="court-number">01 <span>/ 02</span></div>
                <div class="court-card-bottom">
                    <p>CANCHA</p>
                    <h3>Cancha 1</h3>
                </div>
            </article>
            <article class="court-card court-two">
                <div class="court-number">02 <span>/ 02</span></div>
                <div class="court-card-bottom">
                    <p>CANCHA</p>
                    <h3>Cancha 2</h3>
                </div>
            </article>
        </section>

        <section class="values-strip">
            <div class="value-item">
                <span class="value-icon">01</span>
                <div>
                    <h3>Armá el equipo</h3>
                    <p>Un mensaje al grupo y ya está.</p>
                </div>
            </div>
            <div class="value-item">
                <span class="value-icon">02</span>
                <div>
                    <h3>Elegí tu horario</h3>
                    <p>Encontrá un momento para todos.</p>
                </div>
            </div>
            <div class="value-item">
                <span class="value-icon">03</span>
                <div>
                    <h3>Nos vemos en la cancha</h3>
                    <p>El tercer tiempo también cuenta.</p>
                </div>
            </div>
        </section>

        <section class="location-section" id="ubicacion">
            <div class="location-copy">
                <p class="eyebrow">
                    <span class="eyebrow-line"></span>
                    NOS ENCONTRAMOS EN SAUCE
                </p>
                <h2>El fútbol queda<br><span>más cerca.</span></h2>
                <p>Seberih, un punto de encuentro para disfrutar el fútbol con amigos.</p>
                <div class="location-detail">
                    <span class="pin-icon" aria-hidden="true">⌖</span>
                    <span>
                        <strong>Complejo Seberih</strong><br>
                        8WRQ+2F6, Sauce, Canelones<br>
                        <small class="location-coordinates">34°39′35.8″S · 56°03′40.9″W</small>
                    </span>
                </div>
                <a
                    class="button button-dark location-cta"
                    href="https://maps.app.goo.gl/6TYvRfkU8ybxck2D9?g_st=iw"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Cómo llegar <span aria-hidden="true">↗</span>
                </a>
            </div>
            <div class="location-visual" aria-label="Ilustración de ubicación del complejo">
                <div class="map-grid"></div>
                <div class="map-road road-one"></div>
                <div class="map-road road-two"></div>
                <div class="map-pin"><span>S</span></div>
                <span class="map-label">SEBERIH</span>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand footer-brand" href="#inicio">
            <span class="brand-mark">S</span>
            <span>SEBERIH<span class="brand-dot">.</span></span>
        </a>
        <p>El partido empieza acá.</p>
        <span class="footer-copy">© <?= date('Y') ?> Seberih · Sauce, Canelones</span>
        <?php if ($user): ?>
            <form action="/auth.php" method="post" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Cerrar sesión</button>
            </form>
        <?php endif; ?>
        <?php if ($demoMode): ?>
            <a class="admin-link" href="/admin.php">Gestión local</a>
        <?php endif; ?>
    </footer>
</body>
</html>
