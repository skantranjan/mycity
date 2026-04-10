<?php

declare(strict_types=1);

/**
 * CORS: no wildcard. Reflect Origin only when allowed (same Host or MCI_ALLOWED_API_ORIGINS).
 * Also sets baseline security headers for JSON API responses.
 */
function api_v1_send_cors_and_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? trim((string) $_SERVER['HTTP_ORIGIN']) : '';
    $host   = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string) $_SERVER['HTTP_HOST'])) : '';

    $allowedOrigin = null;
    $rawList       = getenv('MCI_ALLOWED_API_ORIGINS');
    if (is_string($rawList) && trim($rawList) !== '') {
        $allowed = array_filter(array_map('trim', explode(',', $rawList)));
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            $allowedOrigin = $origin;
        }
    } elseif ($origin !== '' && $host !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $hostOnly   = strtolower(explode(':', $host, 2)[0]);
        if (is_string($originHost) && strtolower($originHost) === $hostOnly) {
            $allowedOrigin = $origin;
        }
    }

    if ($allowedOrigin !== null) {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Vary: Origin');
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-MCI-Image-Upload-Token');
}
