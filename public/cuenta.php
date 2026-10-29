<?php
declare(strict_types=1);

$pageTitle = 'Mi cuenta | Seberih';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d2018">
    <meta
        name="description"
        content="Accedé a tu cuenta de Seberih o crea una nueva."
    >
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/account.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <script src="/assets/js/account.js" defer></script>
</head>
<body class="account-page">
    <header class="account-header">
        <a class="brand" href="/" aria-label="Seberih, inicio">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span>SEBERIH<span class="brand-dot">.</span></span>
        </a>
        <a class="account-back" href="/">
            Volver al complejo <span aria-hidden="true">↗</span>
        </a>
    </header>

    <main class="account-main">
        <div class="account-intro">
            <p class="eyebrow">
                <span class="eyebrow-line"></span>
                TU LUGAR EN EL EQUIPO
            </p>
            <h1>Nos vemos<br><span>en la cancha.</span></h1>
            <p>Ingresá a tu cuenta o creá una para tener todo listo para tu próximo partido.</p>
        </div>

        <div class="account-cards">
            <section class="account-card" id="iniciar-sesion" aria-labelledby="login-title">
                <div class="account-card-heading">
                    <span class="account-card-index">01</span>
                    <div>
                        <p>YA TENÉS CUENTA</p>
                        <h2 id="login-title">Iniciar sesión</h2>
                    </div>
                </div>
                <form class="account-form" id="login-form" novalidate>
                    <div class="account-field">
                        <label for="login-email">Correo electrónico</label>
                        <input
                            id="login-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="nombre@correo.com"
                            required
                        >
                    </div>
                    <div class="account-field">
                        <label for="login-password">Contraseña</label>
                        <input
                            id="login-password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Tu contraseña"
                            required
                        >
                    </div>
                    <button class="button button-dark account-submit" type="submit">
                        Ingresar <span aria-hidden="true">→</span>
                    </button>
                    <p class="account-feedback" id="login-feedback" aria-live="polite">
                        Interfaz preliminar: el inicio de sesión aún no está conectado.
                    </p>
                </form>
            </section>

            <section class="account-card account-card-register" id="registrarse" aria-labelledby="register-title">
                <div class="account-card-heading">
                    <span class="account-card-index">02</span>
                    <div>
                        <p>SUMATE A SEBERIH</p>
                        <h2 id="register-title">Crear cuenta</h2>
                    </div>
                </div>
                <form class="account-form" id="register-form" novalidate>
                    <div class="account-field">
                        <label for="register-name">Nombre y apellido</label>
                        <input
                            id="register-name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            placeholder="Tu nombre"
                            required
                        >
                    </div>
                    <div class="account-field">
                        <label for="register-email">Correo electrónico</label>
                        <input
                            id="register-email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            placeholder="nombre@correo.com"
                            required
                        >
                    </div>
                    <div class="account-field">
                        <label for="register-phone">Teléfono <span>(opcional)</span></label>
                        <input id="register-phone" name="phone" type="tel" autocomplete="tel" placeholder="09X XXX XXX">
                    </div>
                    <div class="account-password-row">
                        <div class="account-field">
                            <label for="register-password">Contraseña</label>
                            <input
                                id="register-password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                minlength="8"
                                placeholder="Mínimo 8 caracteres"
                                required
                            >
                        </div>
                        <div class="account-field">
                            <label for="register-password-confirm">Repetir contraseña</label>
                            <input
                                id="register-password-confirm"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                minlength="8"
                                placeholder="Repetí la contraseña"
                                required
                            >
                        </div>
                    </div>
                    <button class="button button-primary account-submit" type="submit">
                        Crear mi cuenta <span aria-hidden="true">→</span>
                    </button>
                    <p class="account-feedback" id="register-feedback" aria-live="polite">
                        Interfaz preliminar: el registro aún no guarda tus datos.
                    </p>
                </form>
            </section>
        </div>
    </main>

    <footer class="account-footer">
        <span>SEBERIH · SAUCE, CANELONES</span>
        <a href="/">Volver al inicio</a>
    </footer>
</body>
</html>
