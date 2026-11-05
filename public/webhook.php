<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/reservations.php';

(new \App\Controller\WebhookController())->handle();
