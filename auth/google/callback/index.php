<?php

declare(strict_types=1);

/**
 * Google OAuth 2.0 callback handler.
 *
 * Flow:
 *  1. User arrives here after Google redirects back with ?code=&state=
 *  2. Verify CSRF state token
 *  3. Exchange code for access token via Google's token endpoint
 *  4. Fetch user profile from Google's userinfo endpoint
 *  5. find-or-create MCI user, set session + cookie, redirect to dashboard
 */

require_once __DIR__ . '/../../../includes/mci_session.php';
require_once __DIR__ . '/../../../api/v1/lib/oauth_social.php';

// ── Session must be active for state verification ─────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
}

$returnUrl = mci_safe_return_url((string)($_SESSION['mci_oauth_return'] ?? '/subscriber/dashboard/'));
unset($_SESSION['mci_oauth_return']);

// ── Error from Google ─────────────────────────────────────────────────────────
if (!empty($_GET['error'])) {
    header('Location: /login/?oauth_error=' . rawurlencode((string)$_GET['error']));
    exit;
}

// ── Validate state (CSRF guard) ───────────────────────────────────────────────
$returnedState = (string)($_GET['state'] ?? '');
if (!mci_oauth_verify_state($returnedState, 'google')) {
    header('Location: /login/?oauth_error=invalid_state');
    exit;
}

// ── Exchange code for access token ────────────────────────────────────────────
$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    header('Location: /login/?oauth_error=missing_code');
    exit;
}

$clientId     = (string)(getenv('MCI_GOOGLE_CLIENT_ID')     ?: ($_ENV['MCI_GOOGLE_CLIENT_ID']     ?? ''));
$clientSecret = (string)(getenv('MCI_GOOGLE_CLIENT_SECRET') ?: ($_ENV['MCI_GOOGLE_CLIENT_SECRET'] ?? ''));
$scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirectUri  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com') . '/auth/google/callback/';

if ($clientId === '' || $clientSecret === '') {
    header('Location: /login/?oauth_error=provider_not_configured');
    exit;
}

$tokenData = mci_oauth_http_post('https://oauth2.googleapis.com/token', [
    'code'          => $code,
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri'  => $redirectUri,
    'grant_type'    => 'authorization_code',
]);

if (!is_array($tokenData) || empty($tokenData['access_token'])) {
    header('Location: /login/?oauth_error=token_exchange_failed');
    exit;
}

$accessToken = (string)$tokenData['access_token'];

// ── Fetch user profile ────────────────────────────────────────────────────────
$profile = mci_oauth_http_get('https://www.googleapis.com/oauth2/v3/userinfo', $accessToken);

if (!is_array($profile) || empty($profile['sub'])) {
    header('Location: /login/?oauth_error=profile_fetch_failed');
    exit;
}

$providerUserId = (string)($profile['sub']     ?? '');
$providerEmail  = (string)($profile['email']   ?? '');
$displayName    = (string)($profile['name']    ?? '');
$profileImage   = (string)($profile['picture'] ?? '');

// ── find-or-create user + issue session ──────────────────────────────────────
$result = mci_oauth_find_or_create_user(
    'google',
    $providerUserId,
    $providerEmail,
    $displayName,
    $profileImage
);

if (empty($result['ok'])) {
    $err = rawurlencode((string)($result['error'] ?? 'login_failed'));
    header('Location: /login/?oauth_error=' . $err);
    exit;
}

mci_oauth_finish_login($result, $returnUrl);
