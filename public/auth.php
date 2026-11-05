<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$authentication = ($_POST['action'] ?? '') === 'logout'
    ? null
    : new \App\Application\Identity\AuthenticationService(
        new \App\Infrastructure\Persistence\PdoUserRepository(db()),
    );
(new \App\Controller\AuthController($authentication))->handle();
