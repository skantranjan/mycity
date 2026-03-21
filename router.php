<?php

declare(strict_types=1);

/**
 * Router for PHP built-in dev server (Apache .htaccess is NOT applied).
 *
 * From project root (folder that contains api/, cp/, index.php):
 *   php -S localhost:8080 router.php
 *
 * Then open http://localhost:8080/ — /api/v1/* is handled by api/v1/index.php.
 */
$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!is_string($uri)) {
    return false;
}

if (str_starts_with($uri, '/api/v1')) {
    require __DIR__ . '/api/v1/index.php';

    return true;
}

return false;
