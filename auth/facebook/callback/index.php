<?php

declare(strict_types=1);

/**
 * Facebook OAuth 2.0 callback handler.
 *
 * Flow:
 *  1. User arrives here after Facebook redirects back with ?code=&state=
 *  2. Verify CSRF state token
 *  3. Exchange code for access token via Facebook's token endpoint
 *  4. Fetch user profile from Graph API
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

// ── Error from Facebook ───────────────────────────────────────────────────────
if (!empty($_GET['error'])) {
    header('Location: /login/?oauth_error=' . rawurlencode((string)($_GET['error_description'] ?? $_GET['error'])));
    exit;
}

// ── Validate state (CSRF guard) ───────────────────────────────────────────────
$returnedState = (string)($_GET['state'] ?? '');
if (!mci_oauth_verify_state($returnedState, 'facebook')) {
    header('Location: /login/?oauth_error=invalid_state');
    exit;
}

// ── Exchange code for access token ────────────────────────────────────────────
$code = (string)($_GET['code'] ?? '');
if ($code === '') {
    header('Location: /login/?oauth_error=missing_code');
    exit;
}

$appId        = (string)(getenv('MCI_FACEBOOK_APP_ID')     ?: ($_ENV['MCI_FACEBOOK_APP_ID']     ?? ''));
$appSecret    = (string)(getenv('MCI_FACEBOOK_APP_SECRET') ?: ($_ENV['MCI_FACEBOOK_APP_SECRET'] ?? ''));
$scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirectUri  = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com') . '/auth/facebook/callback/';

if ($appId === '' || $appSecret === '') {
    header('Location: /login/?oauth_error=provider_not_configured');
    exit;
}

$tokenData = mci_oauth_http_post(
    'https://graph.facebook.com/v20.0/oauth/access_token',
    [
        'client_id'     => $appId,
        'client_secret' => $appSecret,
        'redirect_uri'  => $redirectUri,
        'code'          => $code,
    ]
);

if (!is_array($tokenData) || empty($tokenData['access_token'])) {
    header('Location: /login/?oauth_error=token_exchange_failed');
    exit;
}

$accessToken = (string)$tokenData['access_token'];

// ── Fetch user profile ────────────────────────────────────────────────────────
// Request id, name, email, picture from Graph API
$profileUrl = 'https://graph.facebook.com/v20.0/me?fields=id,name,email,picture.width(200)'
    . '&access_token=' . rawurlencode($accessToken);

$profile = mci_oauth_http_get($profileUrl, $accessToken);

if (!is_array($profile) || empty($profile['id'])) {
    header('Location: /login/?oauth_error=profile_fetch_failed');
    exit;
}

$providerUserId = (string)($profile['id']    ?? '');
$providerEmail  = (string)($profile['email'] ?? '');
$displayName    = (string)($profile['name']  ?? '');
$profileImage   = (string)($profile['picture']['data']['url'] ?? '');

// Facebook may not return email if user denied permission or uses phone login
if ($providerEmail === '') {
    header('Location: /login/?oauth_error=email_not_provided');
    exit;
}

// ── find-or-create user + issue session ──────────────────────────────────────
$result = mci_oauth_find_or_create_user(
    'facebook',
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
