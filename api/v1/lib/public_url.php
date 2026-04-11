<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Canonical site origin for absolute links in emails (no trailing slash).
 */
function api_public_site_base_url(): string
{
    $fromEnv = api_env_optional('MCI_PUBLIC_SITE_URL');
    if ($fromEnv !== null && $fromEnv !== '') {
        return rtrim($fromEnv, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host;
}
