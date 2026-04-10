<?php

declare(strict_types=1);

/**
 * Facebook OAuth initiation — builds the authorization URL and redirects the
 * user to Facebook's consent screen.
 *
 * Preserves the ?return= URL so we can send the user back after login.
 */

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/oauth_social.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

// Preserve the post-login return URL
$return = mci_safe_return_url((string)($_GET['return'] ?? ''));
if ($return !== '/' && $return !== '') {
    $_SESSION['mci_oauth_return'] = $return;
} else {
    $_SESSION['mci_oauth_return'] = '/subscriber/dashboard/';
}

$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirectUri = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com') . '/auth/facebook/callback/';

try {
    $url = mci_oauth_facebook_auth_url($redirectUri);
} catch (RuntimeException $e) {
    header('Location: /login/?oauth_error=provider_not_configured');
    exit;
}

header('Location: ' . $url);
exit;
