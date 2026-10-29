<?php
declare(strict_types=1);

$pageTitle = 'Seberih | Fútbol, amigos y buenos momentos';
$currentDate = (new DateTimeImmutable())->format('Y-m-d');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <meta name="description" content="Reservá tu próximo partido en Seberih, complejo deportivo con dos canchas de fútbol en Sauce.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
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
            <a href="#ubicacion">Ubicación</a>
            <a href="/cuenta.php">Mi cuenta</a>
        </nav>
        <a class="header-cta" href="#reservas">Reservar cancha <span aria-hidden="true">↗</span></a>
        <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false"><span></span><span></span></button>
    </header>

    <main>
        <section class="hero" id="inicio">
            <div class="hero-image" aria-hidden="true"></div>
            <div class="hero-shade" aria-hidden="true"></div>
            <div class="hero-content">
                <p class="eyebrow"><span class="eyebrow-line"></span> COMPLEJO DEPORTIVO · SAUCE</p>
                <h1>El partido<br>empieza <span>acá.</span></h1>
                <p class="hero-copy">Juntá al equipo. Elegí tu cancha.<br>Lo demás es disfrutar.</p>
                <a class="button button-primary" href="#reservas">Encontrá tu horario <span aria-hidden="true">↓</span></a>
                <div class="hero-note"><span class="live-dot"></span> DOS CANCHAS · UN SOLO LUGAR</div>
            </div>
            <div class="hero-side-label" aria-hidden="true">SEBERIH · FÚTBOL Y ENCUENTRO</div>
            <div class="hero-scroll" aria-hidden="true"><span></span> DESLIZÁ PARA EXPLORAR</div>
        </section>

        <section class="booking-section" id="reservas">
            <div class="section-heading">
                <p class="eyebrow eyebrow-dark"><span class="eyebrow-line"></span> TU PRÓXIMO PARTIDO</p>
                <h2>Reservá tu cancha<span class="accent-dot">.</span></h2>
                <p>Elegí el día y el horario que mejor le quede al equipo.</p>
            </div>
            <form class="booking-card" id="booking-form">
                <div class="field-group field-select">
                    <label for="court">CANCHA</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">▦</span>
                        <select id="court" name="court" required>
                            <option value="Cancha 1">Cancha 1</option>
                            <option value="Cancha 2">Cancha 2</option>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label for="date">FECHA</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">▣</span>
                        <input id="date" name="date" type="date" min="<?= htmlspecialchars($currentDate, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="field-group">
                    <label for="time">HORARIO</label>
                    <div class="input-wrap"><span class="input-icon" aria-hidden="true">◷</span>
                        <select id="time" name="time" required>
                            <option value="" disabled selected>Elegí un horario</option>
                            <option>09:00</option><option>10:00</option><option>11:00</option><option>12:00</option>
                            <option>14:00</option><option>15:00</option><option>16:00</option><option>17:00</option>
                            <option>18:00</option><option>19:00</option><option>20:00</option><option>21:00</option>
                        </select>
                    </div>
                </div>
                <button class="button button-dark" type="submit">Consultar <span aria-hidden="true">→</span></button>
                <p class="form-note" id="form-message" aria-live="polite">Prototipo: la consulta no se envía ni guarda todavía.</p>
            </form>
        </section>

        <section class="courts-section" id="canchas">
            <div class="courts-intro">
                <p class="eyebrow"><span class="eyebrow-line"></span> EL COMPLEJO</p>
                <h2>Dos canchas.<br><span>Tu mismo equipo.</span></h2>
                <p>Un espacio para jugar, competir y compartir. Elegí la cancha para el partido que están armando.</p>
                <a class="text-link" href="#reservas">Elegir horario <span aria-hidden="true">↗</span></a>
            </div>
            <article class="court-card court-one">
                <div class="court-number">01 <span>/ 02</span></div>
                <div class="court-card-bottom"><p>CANCHA</p><h3>Cancha 1</h3></div>
            </article>
            <article class="court-card court-two">
                <div class="court-number">02 <span>/ 02</span></div>
                <div class="court-card-bottom"><p>CANCHA</p><h3>Cancha 2</h3></div>
            </article>
        </section>

        <section class="values-strip">
            <div class="value-item"><span class="value-icon">01</span><div><h3>Armá el equipo</h3><p>Un mensaje al grupo y ya está.</p></div></div>
            <div class="value-item"><span class="value-icon">02</span><div><h3>Elegí tu horario</h3><p>Encontrá un momento para todos.</p></div></div>
            <div class="value-item"><span class="value-icon">03</span><div><h3>Nos vemos en la cancha</h3><p>El tercer tiempo también cuenta.</p></div></div>
        </section>

        <section class="location-section" id="ubicacion">
            <div class="location-copy">
                <p class="eyebrow"><span class="eyebrow-line"></span> NOS ENCONTRAMOS EN SAUCE</p>
                <h2>El fútbol queda<br><span>más cerca.</span></h2>
                <p>Seberih, un punto de encuentro para disfrutar el fútbol con amigos.</p>
                <div class="location-detail"><span class="pin-icon" aria-hidden="true">⌖</span><span><strong>Complejo Seberih</strong><br>8WRQ+2F6, Sauce, Canelones<br><small class="location-coordinates">34°39′35.8″S · 56°03′40.9″W</small></span></div>
                <a class="button button-dark location-cta" href="https://maps.app.goo.gl/6TYvRfkU8ybxck2D9?g_st=iw" target="_blank" rel="noopener noreferrer">Cómo llegar <span aria-hidden="true">↗</span></a>
            </div>
            <div class="location-visual" aria-label="Ilustración de ubicación del complejo"><div class="map-grid"></div><div class="map-road road-one"></div><div class="map-road road-two"></div><div class="map-pin"><span>S</span></div><span class="map-label">SEBERIH</span></div>
        </section>
    </main>

    <footer class="site-footer">
        <a class="brand footer-brand" href="#inicio"><span class="brand-mark">S</span><span>SEBERIH<span class="brand-dot">.</span></span></a>
        <p>El partido empieza acá.</p>
        <span class="footer-copy">© <?= date('Y') ?> Seberih · Sauce, Canelones</span>
    </footer>
</body>
</html>
