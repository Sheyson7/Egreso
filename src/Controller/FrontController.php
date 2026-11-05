<?php
declare(strict_types=1);

namespace App\Controller;

use RuntimeException;

final class FrontController
{
    private const ROUTES = [
        '/' => 'src/Views/HomePage.php',
        '/index.php' => 'src/Views/HomePage.php',
        '/cuenta.php' => 'src/Views/AccountPage.php',
        '/auth.php' => 'public/auth.php',
        '/reservas.php' => 'public/reservas.php',
        '/disponibilidad.php' => 'public/disponibilidad.php',
        '/mis-reservas.php' => 'public/mis-reservas.php',
        '/admin.php' => 'public/admin.php',
        '/pago.php' => 'public/pago.php',
        '/webhook.php' => 'public/webhook.php',
    ];

    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $target = self::ROUTES[is_string($path) ? $path : '/'] ?? null;
        if ($target === null) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No encontrado.';
            return;
        }

        $file = \PROJECT_ROOT . '/' . $target;
        if (!is_file($file)) {
            throw new RuntimeException('No se encontró el controlador de la ruta solicitada.');
        }
        require $file;
    }
}
