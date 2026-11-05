<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relativePath = is_string($path) ? ltrim(rawurldecode($path), '/') : '';
$publicRoot = realpath(PROJECT_ROOT . '/public');
$requestedFile = realpath(PROJECT_ROOT . '/public/' . $relativePath);

if (
    $publicRoot !== false
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
    && str_starts_with($relativePath, 'assets/')
) {
    return false;
}

require PROJECT_ROOT . '/public/index.php';
