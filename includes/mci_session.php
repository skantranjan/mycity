<?php

declare(strict_types=1);

/**
 * Start a secure-enough session for public-site demo auth (reviews, etc.).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

/** Safe path-only return URL (open redirect guard). */
function mci_safe_return_url(?string $explicit = null): string
{
    $r = $explicit ?? ($_GET['return'] ?? '/index.php');
    if (!is_string($r) || $r === '' || $r[0] !== '/') {
        return '/index.php';
    }
    if (strncmp($r, '//', 2) === 0) {
        return '/index.php';
    }
    return $r;
}
