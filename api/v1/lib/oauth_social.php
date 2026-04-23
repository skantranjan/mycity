<?php

declare(strict_types=1);

/**
 * Shared OAuth social-login helpers.
 *
 * Handles:
 *  - State token generation & verification (CSRF protection for OAuth flow)
 *  - cURL-based HTTP POST/GET to provider endpoints
 *  - find-or-create user from provider identity
 *  - Session + JWT cookie setup identical to email/password login
 *
 * Required env vars (per provider):
 *  MCI_GOOGLE_CLIENT_ID, MCI_GOOGLE_CLIENT_SECRET
 *  MCI_FACEBOOK_APP_ID,  MCI_FACEBOOK_APP_SECRET
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/ip.php';
require_once __DIR__ . '/mci_mailer.php';
require_once __DIR__ . '/subscription_service.php';

// ── State token (CSRF guard for OAuth) ───────────────────────────────────────

/**
 * Generate a random state token, store it in session, return it.
 * Call before redirecting the user to the provider.
 */
function mci_oauth_make_state(string $provider): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }
    $state = bin2hex(random_bytes(16));
    $_SESSION['mci_oauth_state']         = $state;
    $_SESSION['mci_oauth_state_provider'] = $provider;
    $_SESSION['mci_oauth_state_time']    = time();
    return $state;
}

/**
 * Verify the state token returned by the provider matches what we stored.
 * Returns true if valid (within 10 min), false otherwise.
 */
function mci_oauth_verify_state(string $returnedState, string $provider): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }
    $stored   = (string)($_SESSION['mci_oauth_state']          ?? '');
    $storedPr = (string)($_SESSION['mci_oauth_state_provider'] ?? '');
    $storedAt = (int)($_SESSION['mci_oauth_state_time']        ?? 0);

    unset(
        $_SESSION['mci_oauth_state'],
        $_SESSION['mci_oauth_state_provider'],
        $_SESSION['mci_oauth_state_time']
    );

    if ($stored === '' || $returnedState !== $stored) { return false; }
    if ($storedPr !== $provider)                       { return false; }
    if (time() - $storedAt > 600)                      { return false; } // 10-min window
    return true;
}

// ── HTTP helper ───────────────────────────────────────────────────────────────

/**
 * cURL POST — returns decoded JSON array or null on failure.
 * @param array<string,string> $fields
 * @return array<mixed>|null
 */
function mci_oauth_http_post(string $url, array $fields): ?array
{
    $ch = curl_init($url);
    if ($ch === false) { return null; }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '') { return null; }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * cURL GET with Bearer token — returns decoded JSON array or null on failure.
 * @return array<mixed>|null
 */
function mci_oauth_http_get(string $url, string $bearerToken): ?array
{
    $ch = curl_init($url);
    if ($ch === false) { return null; }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Authorization: Bearer ' . $bearerToken,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (!is_string($body) || $body === '') { return null; }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

// ── Find-or-create user ───────────────────────────────────────────────────────

/**
 * Given a verified OAuth identity, find or create the mci_users record and
 * return the same result shape as api_direct_subscriber_login().
 *
 * @return array{ok:true, user: array{id:string, email:string, role:string}, token:string, exp:int}|array{ok:false, error:string, status:int}
 */
function mci_oauth_find_or_create_user(
    string $provider,
    string $providerUserId,
    string $providerEmail,
    string $displayName,
    string $profileImageUrl
): array {
    if ($providerUserId === '') {
        return ['ok' => false, 'error' => 'oauth_missing_user_id', 'status' => 400];
    }
    if ($providerEmail === '' || !filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'oauth_missing_email', 'status' => 400];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $ip   = api_client_ip();
    $role = 'subscriber';

    // ── 1. Is this provider account already linked? ───────────────────────────
    $stmt = $pdo->prepare(
        'SELECT u.id, u.email, r.short_name AS role
           FROM mci_user_auth_providers p
           JOIN mci_users u ON u.id = p.user_id
           JOIN mci_roles r ON r.id = u.role_id
          WHERE p.provider = ? AND p.provider_user_id = ?
            AND u.deleted_at IS NULL
          LIMIT 1'
    );
    $stmt->execute([$provider, $providerUserId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $userId = (string)$existing['id'];
        $role   = (string)($existing['role'] ?? 'subscriber');

        // Refresh last login + update provider token info
        $pdo->prepare('UPDATE mci_users SET last_login_at = NOW(6), is_logged_in = 1, last_update_ip = ? WHERE id = ?')
            ->execute([$ip, $userId]);
        $pdo->prepare(
            'UPDATE mci_user_auth_providers
                SET provider_email = ?, provider_profile_image = ?, updated_at = NOW(6)
              WHERE provider = ? AND provider_user_id = ?'
        )->execute([$providerEmail, $profileImageUrl !== '' ? $profileImageUrl : null, $provider, $providerUserId]);

        return _mci_oauth_issue_token($userId, $providerEmail, $role);
    }

    // ── 2. Email already registered (different account)? Link the provider. ──
    $stmt = $pdo->prepare(
        'SELECT u.id, r.short_name AS role
           FROM mci_users u
           JOIN mci_roles r ON r.id = u.role_id
          WHERE u.email = ? AND u.deleted_at IS NULL
          LIMIT 1'
    );
    $stmt->execute([mb_strtolower(trim($providerEmail))]);
    $byEmail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($byEmail) {
        $userId = (string)$byEmail['id'];
        $role   = (string)($byEmail['role'] ?? 'subscriber');

        // Link this provider to the existing account (ignore duplicate if already linked)
        try {
            $pdo->prepare(
                'INSERT IGNORE INTO mci_user_auth_providers
                   (id, user_id, provider, provider_user_id, provider_email, provider_profile_image)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                api_uuid_v4(), $userId, $provider, $providerUserId,
                $providerEmail,
                $profileImageUrl !== '' ? $profileImageUrl : null,
            ]);
        } catch (Throwable $ignored) {}

        $pdo->prepare('UPDATE mci_users SET last_login_at = NOW(6), is_logged_in = 1, last_update_ip = ? WHERE id = ?')
            ->execute([$ip, $userId]);

        return _mci_oauth_issue_token($userId, $providerEmail, $role);
    }

    // ── 3. Brand-new user — create account + link provider ───────────────────
    $userId      = api_uuid_v4();
    $providerId  = api_uuid_v4();
    $cleanEmail  = mb_strtolower(trim($providerEmail));
    $cleanName   = $displayName !== '' ? $displayName : trim(explode('@', $cleanEmail, 2)[0]);

    try {
        $pdo->beginTransaction();

        $pdo->prepare(
            'INSERT INTO mci_users
               (id, email, password_hash, role_id, display_name,
                terms_accepted_at, privacy_policy_accepted_at,
                email_verified_at,
                registration_ip, last_update_ip,
                last_login_at, is_logged_in)
             VALUES
               (?, ?, NULL, (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1), ?,
                NOW(6), NOW(6),
                NOW(6),
                ?, ?,
                NOW(6), 1)'
        )->execute([$userId, $cleanEmail, $role, $cleanName !== '' ? $cleanName : null, $ip, $ip]);

        $pdo->prepare(
            'INSERT INTO mci_user_auth_providers
               (id, user_id, provider, provider_user_id, provider_email, provider_profile_image)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $providerId, $userId, $provider, $providerUserId,
            $providerEmail,
            $profileImageUrl !== '' ? $profileImageUrl : null,
        ]);

        $sub = mci_subscription_assign_default_to_user($pdo, $userId, 'registration_oauth_' . $provider);
        if (!$sub['ok']) {
            throw new RuntimeException((string) ($sub['error'] ?? 'subscription_assign_failed'));
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'error' => 'register_failed', 'status' => 500];
    }

    mci_mail_send_welcome($cleanEmail, $cleanName !== '' ? $cleanName : null, true);
    mci_mail_send_admin_new_user_registered($cleanEmail, $cleanName !== '' ? $cleanName : null, 'oauth_' . $provider);

    return _mci_oauth_issue_token($userId, $cleanEmail, $role);
}

/**
 * Issue a JWT and return the standard login result array.
 *
 * @return array{ok:true, user: array{id:string, email:string, role:string}, token:string, exp:int}
 */
function _mci_oauth_issue_token(string $userId, string $email, string $role): array
{
    $exp = time() + 28800; // 8 hours, same as email login
    $jwt = api_jwt_sign(['sub' => $userId, 'role' => $role, 'iat' => time(), 'exp' => $exp]);
    return [
        'ok'    => true,
        'user'  => ['id' => $userId, 'email' => $email, 'role' => $role],
        'token' => $jwt,
        'exp'   => $exp,
    ];
}

// ── Session + cookie finaliser ────────────────────────────────────────────────

/**
 * Write session variables and JWT cookie then redirect — identical to what
 * login/index.php does after a successful email/password login.
 */
function mci_oauth_finish_login(array $result, string $returnUrl = '/subscriber/dashboard/'): never
{
    $user   = $result['user'] ?? [];
    $userId = (string)($user['id']    ?? '');
    $email  = (string)($user['email'] ?? '');
    $role   = (string)($user['role']  ?? 'subscriber');

    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }

    $_SESSION['mci_user_id']   = $userId !== '' ? $userId : bin2hex(random_bytes(16));
    $_SESSION['mci_logged_in'] = true;
    $_SESSION['mci_role']      = $role;

    if ($email !== '' && empty($_SESSION['mci_sub_profile_name'])) {
        $local = trim(explode('@', $email, 2)[0] ?? '');
        if ($local !== '') {
            $_SESSION['mci_sub_profile_name'] = $local;
        }
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    setcookie('mci_api_token', (string)$result['token'], [
        'expires'  => (int)($result['exp'] ?? time() + 28800),
        'path'     => '/',
        'secure'   => $scheme === 'https',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    header('Location: ' . $returnUrl);
    exit;
}

// ── Authorization URL builders ────────────────────────────────────────────────

/**
 * Build the Google authorization URL and return it.
 * Stores a CSRF state token in session as a side-effect.
 */
function mci_oauth_google_auth_url(string $redirectUri): string
{
    $clientId = (string)(getenv('MCI_GOOGLE_CLIENT_ID') ?: ($_ENV['MCI_GOOGLE_CLIENT_ID'] ?? ''));
    if ($clientId === '') {
        throw new RuntimeException('MCI_GOOGLE_CLIENT_ID env var not set.');
    }
    $state = mci_oauth_make_state('google');
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);
}

/**
 * Build the Facebook authorization URL and return it.
 * Stores a CSRF state token in session as a side-effect.
 */
function mci_oauth_facebook_auth_url(string $redirectUri): string
{
    $appId = (string)(getenv('MCI_FACEBOOK_APP_ID') ?: ($_ENV['MCI_FACEBOOK_APP_ID'] ?? ''));
    if ($appId === '') {
        throw new RuntimeException('MCI_FACEBOOK_APP_ID env var not set.');
    }
    $state = mci_oauth_make_state('facebook');
    return 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query([
        'client_id'     => $appId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'scope'         => 'email,public_profile',
        'state'         => $state,
    ]);
}
