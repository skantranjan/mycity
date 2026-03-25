<?php

declare(strict_types=1);

/**
 * In-process auth (same logic as /api/v1 subscriber + cp login).
 * Used by PHP pages (login, cp/login) so they do not rely on HTTP self-requests,
 * which often fail on Windows/local PHP (allow_url_fopen, loopback, routing).
 *
 * Does not set cookies or emit JSON — callers handle that.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/ip.php';

/**
 * Unified login — same contract as `POST /api/v1/auth/login` (`audience` / `type`).
 * Use `subscriber` (or `sub`) for public accounts; `cp` (or `admin`, `super_admin`, `co_admin`, `coadmin`) for control panel.
 *
 * @return array{ok:true, user: array{id: string, email: string, role: string}, token: string, exp: int}|array{ok:false, error: string, status?: int}
 */
function api_direct_auth_login(string $email, string $password, string $audience): array
{
    $audience = mb_strtolower(trim($audience));
    if ($audience === 'subscriber' || $audience === 'sub') {
        return api_direct_subscriber_login($email, $password);
    }
    if (
        $audience === 'cp'
        || $audience === 'admin'
        || $audience === 'super_admin'
        || $audience === 'coadmin'
        || $audience === 'co_admin'
    ) {
        return api_direct_cp_login($email, $password);
    }

    return ['ok' => false, 'error' => 'invalid_audience', 'status' => 400];
}

/**
 * @return array{ok:true, user: array{id: string, email: string, role: string}, token: string, exp: int}|array{ok:false, error: string, status?: int}
 */
function api_direct_subscriber_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'email_and_password_required', 'status' => 400];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $stmt = $pdo->prepare('
      SELECT u.id, u.password_hash, r.short_name AS role
      FROM mci_users u
      JOIN mci_roles r ON u.role_id = r.id
      WHERE u.email = ? AND r.short_name = ?
        AND u.deleted_at IS NULL
        AND (u.locked_until IS NULL OR u.locked_until <= NOW(6))
      LIMIT 1
    ');
    $stmt->execute([$email, 'subscriber']);
    $row = $stmt->fetch();

    if (!$row) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'status' => 401];
    }
    if (!is_string($row['password_hash']) || !password_verify($password, $row['password_hash'])) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'status' => 401];
    }

    $userId = (string)$row['id'];
    $role = (string)($row['role'] ?? 'subscriber');
    $ip = api_client_ip();
    $upd = $pdo->prepare('UPDATE mci_users SET last_login_at = NOW(6), is_logged_in = 1, last_update_ip = ? WHERE id = ?');
    $upd->execute([$ip, $userId]);

    $exp = time() + 28800;
    $jwt = api_jwt_sign(['sub' => $userId, 'role' => $role, 'iat' => time(), 'exp' => $exp]);

    return [
        'ok' => true,
        'user' => ['id' => $userId, 'email' => $email, 'role' => $role],
        'token' => $jwt,
        'exp' => $exp,
    ];
}

/**
 * @return array{ok:true, user: array{id: string, email: string, role: string}, token: string, exp: int}|array{ok:false, error: string, status?: int}
 */
function api_direct_cp_login(string $email, string $password): array
{
    $email = mb_strtolower(trim($email));
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'email_and_password_required', 'status' => 400];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $count = (int)$pdo->query('SELECT COUNT(*) AS c FROM mci_users')->fetch()['c'];
    if ($count === 0) {
        $defEmail = getenv('MCI_DEFAULT_SUPERADMIN_EMAIL') ?: ($_ENV['MCI_DEFAULT_SUPERADMIN_EMAIL'] ?? null);
        $defPass = getenv('MCI_DEFAULT_SUPERADMIN_PASSWORD') ?: ($_ENV['MCI_DEFAULT_SUPERADMIN_PASSWORD'] ?? null);
        if (is_string($defEmail) && is_string($defPass) && $email === mb_strtolower(trim($defEmail))) {
            if ($defPass === '') {
                return ['ok' => false, 'error' => 'invalid_server_bootstrap', 'status' => 500];
            }
            $userId = api_uuid_v4();
            $hash = password_hash($defPass, PASSWORD_DEFAULT);
            $ip = api_client_ip();
            $ins = $pdo->prepare(
                'INSERT INTO mci_users (id, email, password_hash, role_id, display_name, registration_ip, last_update_ip, password_changed_at) VALUES (?, ?, ?, (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1), ?, ?, ?, NOW(6))'
            );
            $ins->execute([$userId, $email, $hash, 'super_admin', 'Super admin', $ip, $ip]);
        }
    }

    $stmt = $pdo->prepare('
      SELECT u.id, u.password_hash, r.short_name AS role
      FROM mci_users u
      JOIN mci_roles r ON u.role_id = r.id
      WHERE u.email = ? AND r.short_name IN (?, ?)
        AND u.deleted_at IS NULL
        AND (u.locked_until IS NULL OR u.locked_until <= NOW(6))
      LIMIT 1
    ');
    $stmt->execute([$email, 'super_admin', 'co_admin']);
    $row = $stmt->fetch();

    if (!$row) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'status' => 401];
    }
    if (!is_string($row['password_hash']) || !password_verify($password, $row['password_hash'])) {
        return ['ok' => false, 'error' => 'invalid_credentials', 'status' => 401];
    }

    $userId = (string)$row['id'];
    $role = (string)$row['role'];
    $ip = api_client_ip();
    $upd = $pdo->prepare('UPDATE mci_users SET last_login_at = NOW(6), is_logged_in = 1, last_update_ip = ? WHERE id = ?');
    $upd->execute([$ip, $userId]);

    $exp = time() + 28800;
    $jwt = api_jwt_sign(['sub' => $userId, 'role' => $role, 'iat' => time(), 'exp' => $exp]);

    return [
        'ok' => true,
        'user' => ['id' => $userId, 'email' => $email, 'role' => $role],
        'token' => $jwt,
        'exp' => $exp,
    ];
}

/**
 * Same logic as POST /api/v1/subscriber/register.
 *
 * @param array<string, mixed> $data
 * @return array{ok:true, user: array{id: string, email: string, role: string}, token: string, exp: int}|array{ok:false, error: string, status?: int, min?: int}
 */
function api_direct_subscriber_register(array $data): array
{
    $email = mb_strtolower(trim((string)($data['email'] ?? '')));
    $password = (string)($data['password'] ?? '');
    $displayName = trim((string)($data['display_name'] ?? ''));

    $acceptTerms = false;
    $acceptPrivacy = false;
    foreach (['accept_terms', 'accept_privacy'] as $i => $key) {
        $v = $data[$key] ?? null;
        $on = $v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on';
        if ($i === 0) {
            $acceptTerms = $on;
        } else {
            $acceptPrivacy = $on;
        }
    }

    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'email_and_password_required', 'status' => 400];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email', 'status' => 400];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'password_too_short', 'status' => 400, 'min' => 8];
    }
    if ($acceptTerms === false || $acceptPrivacy === false) {
        return ['ok' => false, 'error' => 'terms_and_privacy_required', 'status' => 400];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $stmt = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'email_already_registered', 'status' => 409];
    }

    $userId = api_uuid_v4();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'subscriber';
    $ip = api_client_ip();

    $ins = $pdo->prepare(
        'INSERT INTO mci_users (
          id, email, password_hash, role_id, display_name,
          terms_accepted_at, privacy_policy_accepted_at,
          registration_ip, last_update_ip,
          password_changed_at, last_login_at, is_logged_in
        ) VALUES (
          ?, ?, ?, (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1), ?,
          NOW(6), NOW(6),
          ?, ?,
          NOW(6), NOW(6), 1
        )'
    );
    $ins->execute([
        $userId,
        $email,
        $hash,
        $role,
        $displayName !== '' ? $displayName : null,
        $ip,
        $ip,
    ]);

    $exp = time() + 28800;
    $jwt = api_jwt_sign(['sub' => $userId, 'role' => $role, 'iat' => time(), 'exp' => $exp]);

    return [
        'ok' => true,
        'user' => ['id' => $userId, 'email' => $email, 'role' => $role],
        'token' => $jwt,
        'exp' => $exp,
    ];
}
