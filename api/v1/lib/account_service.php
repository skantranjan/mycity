<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/ip.php';
require_once dirname(__DIR__, 3) . '/includes/mci_timezone.php';
require_once __DIR__ . '/mci_mailer.php';
require_once __DIR__ . '/subscription_service.php';

/** @return array{ok:true, profile_id:string}|array{ok:false, error:string} */
function mci_account_ensure_profile_row(PDO $pdo, string $userId): array
{
    $stmt = $pdo->prepare('SELECT id FROM mci_userprofiles WHERE userid = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['id'])) {
        return ['ok' => true, 'profile_id' => (string)$row['id']];
    }
    $pid = api_uuid_v4();
    $ins = $pdo->prepare('INSERT INTO mci_userprofiles (id, userid, created_at) VALUES (?, ?, NOW(6))');
    $ins->execute([$pid, $userId]);

    return ['ok' => true, 'profile_id' => $pid];
}

/**
 * @return array{ok:true, user: array, profile: ?array, auth_providers: array<int, array>, subscription:?array, packages:array<int,array>, can_upgrade_now:bool, paid_coming_soon:bool}|array{ok:false, error:string, status?:int}
 */
function mci_account_get_profile_bundle(string $userId): array
{
    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $stmt = $pdo->prepare('
      SELECT u.id, u.email, u.display_name, u.phone, u.email_verified_at, u.phone_verified_at,
             u.created_at, u.updated_at, r.short_name AS role
      FROM mci_users u
      JOIN mci_roles r ON u.role_id = r.id
      WHERE u.id = ? AND u.deleted_at IS NULL
      LIMIT 1
    ');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['ok' => false, 'error' => 'user_not_found', 'status' => 404];
    }

    $stmt = $pdo->prepare('
      SELECT id, first_name, last_name, profile_image, gender, date_of_birth, timezone, created_at, updated_at
      FROM mci_userprofiles WHERE userid = ? LIMIT 1
    ');
    $stmt->execute([$userId]);
    $profile = $stmt->fetch() ?: null;

    $stmt = $pdo->prepare('
      SELECT id, provider, provider_email, provider_profile_image, created_at, updated_at
      FROM mci_user_auth_providers
      WHERE user_id = ?
      ORDER BY provider ASC
    ');
    $stmt->execute([$userId]);
    $providers = $stmt->fetchAll() ?: [];

    $subSummary = mci_subscription_build_user_summary($pdo, $userId);
    if (!$subSummary['ok']) {
        return ['ok' => false, 'error' => (string) ($subSummary['error'] ?? 'subscription_error'), 'status' => $subSummary['status'] ?? 500];
    }

    return [
        'ok' => true,
        'user' => [
            'id' => (string)$user['id'],
            'email' => (string)$user['email'],
            'display_name' => $user['display_name'],
            'phone' => $user['phone'],
            'email_verified_at' => $user['email_verified_at'],
            'phone_verified_at' => $user['phone_verified_at'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
            'role' => (string)$user['role'],
        ],
        'profile' => $profile ? [
            'id' => (string)$profile['id'],
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'profile_image' => $profile['profile_image'],
            'gender' => $profile['gender'],
            'date_of_birth' => $profile['date_of_birth'],
            'timezone' => $profile['timezone'],
            'created_at' => $profile['created_at'],
            'updated_at' => $profile['updated_at'],
        ] : null,
        'auth_providers' => array_map(static function (array $p): array {
            return [
                'id' => (string)$p['id'],
                'provider' => (string)$p['provider'],
                'provider_email' => $p['provider_email'],
                'provider_profile_image' => $p['provider_profile_image'],
                'created_at' => $p['created_at'],
                'updated_at' => $p['updated_at'],
            ];
        }, $providers),
        'subscription' => $subSummary['current_subscription'],
        'packages' => $subSummary['packages'],
        'can_upgrade_now' => (bool) ($subSummary['can_upgrade_now'] ?? false),
        'paid_coming_soon' => (bool) ($subSummary['paid_coming_soon'] ?? false),
    ];
}

/**
 * @param array<string, mixed> $patch
 * @return array{ok:true}|array{ok:false, error:string, status?:int}
 */
function mci_account_patch_profile(string $userId, array $patch, string $clientIp): array
{
    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $ensure = mci_account_ensure_profile_row($pdo, $userId);
    if (!$ensure['ok']) {
        return ['ok' => false, 'error' => 'profile_error', 'status' => 500];
    }

    if (array_key_exists('timezone', $patch)) {
        $tv = $patch['timezone'];
        $ts = is_string($tv) ? trim($tv) : '';
        if ($ts !== '' && !mci_timezone_is_valid($ts)) {
            return ['ok' => false, 'error' => 'invalid_timezone', 'status' => 400];
        }
    }

    $uFields = [];
    $uParams = [];
    foreach (['display_name', 'phone'] as $k) {
        if (array_key_exists($k, $patch)) {
            $v = $patch[$k];
            $uFields[] = "{$k} = ?";
            $uParams[] = is_string($v) ? ($v === '' ? null : $v) : (is_null($v) ? null : (string)$v);
        }
    }
    if ($uFields !== []) {
        $uFields[] = 'last_update_ip = ?';
        $uParams[] = $clientIp;
        $uParams[] = $userId;
        $sql = 'UPDATE mci_users SET ' . implode(', ', $uFields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($uParams);
    }

    $pFields = [];
    $pParams = [];
    foreach (['first_name', 'last_name', 'profile_image', 'gender', 'date_of_birth', 'timezone'] as $k) {
        if (!array_key_exists($k, $patch)) {
            continue;
        }
        $v = $patch[$k];
        if ($k === 'date_of_birth') {
            $s = is_string($v) ? trim($v) : '';
            if ($s === '' || $s === 'null') {
                $pFields[] = "{$k} = NULL";
            } else {
                $pFields[] = "{$k} = ?";
                $pParams[] = $s;
            }
            continue;
        }
        $pFields[] = "{$k} = ?";
        $pParams[] = is_string($v) ? ($v === '' ? null : $v) : (is_null($v) ? null : (string)$v);
    }
    if ($pFields !== []) {
        $pFields[] = 'updated_at = NOW(6)';
        $pParams[] = $userId;
        $sql = 'UPDATE mci_userprofiles SET ' . implode(', ', $pFields) . ' WHERE userid = ?';
        $pdo->prepare($sql)->execute($pParams);
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false, error:string, status?:int}
 */
function mci_account_change_password(string $userId, string $currentPassword, string $newPassword): array
{
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'password_too_short', 'status' => 400];
    }
    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM mci_users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || !is_string($row['password_hash'])) {
        return ['ok' => false, 'error' => 'user_not_found', 'status' => 404];
    }
    if (!password_verify($currentPassword, $row['password_hash'])) {
        return ['ok' => false, 'error' => 'invalid_current_password', 'status' => 401];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $ip = api_client_ip();
    $upd = $pdo->prepare('UPDATE mci_users SET password_hash = ?, password_changed_at = NOW(6), last_update_ip = ? WHERE id = ?');
    $upd->execute([$hash, $ip, $userId]);

    $emStmt = $pdo->prepare('SELECT email FROM mci_users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $emStmt->execute([$userId]);
    $emRow = $emStmt->fetch();
    $acctEmail = is_array($emRow) && isset($emRow['email']) ? (string) $emRow['email'] : '';
    if ($acctEmail !== '') {
        mci_mail_send_password_changed($acctEmail, 'self');
    }

    return ['ok' => true];
}

/**
 * Always returns generic success to avoid email enumeration.
 * @return array{ok:true, message:string, debug_token?: string}
 */
function mci_account_request_password_reset(string $email): array
{
    $email = mb_strtolower(trim($email));
    $msg = 'If an account exists for that email, password reset instructions will follow.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => true, 'message' => $msg];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => true, 'message' => $msg];
    }

    $stmt = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => true, 'message' => $msg];
    }

    $userId = (string)$row['id'];
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $id = api_uuid_v4();
    $expires = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s.u');

    $ins = $pdo->prepare(
        'INSERT INTO mci_password_reset_tokens (id, user_id, token_hash, expires_at) VALUES (?, ?, ?, ?)'
    );
    try {
        $ins->execute([$id, $userId, $hash, $expires]);
    } catch (Throwable $e) {
        // Table might not exist yet — still return generic message
        return ['ok' => true, 'message' => $msg];
    }

    mci_mail_send_password_reset($email, $raw);

    $out = ['ok' => true, 'message' => $msg];
    if (api_env_flag('MCI_DEBUG_PASSWORD_RESET')) {
        $out['debug_token'] = $raw;
        $out['debug_reset_url'] = '/reset-password/?token=' . rawurlencode($raw);
    }

    return $out;
}

/**
 * @return array{ok:true}|array{ok:false, error:string, status?:int}
 */
function mci_account_reset_password_with_token(string $rawToken, string $newPassword): array
{
    if (strlen($newPassword) < 8) {
        return ['ok' => false, 'error' => 'password_too_short', 'status' => 400];
    }
    $rawToken = trim($rawToken);
    if ($rawToken === '') {
        return ['ok' => false, 'error' => 'token_required', 'status' => 400];
    }

    $hash = hash('sha256', $rawToken);

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $stmt = $pdo->prepare('
      SELECT id, user_id FROM mci_password_reset_tokens
      WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW(6)
      LIMIT 1
    ');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'error' => 'invalid_or_expired_token', 'status' => 400];
    }

    $userId = (string)$row['user_id'];
    $tid = (string)$row['id'];
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $ip = api_client_ip();

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE mci_users SET password_hash = ?, password_changed_at = NOW(6), last_update_ip = ? WHERE id = ?')
            ->execute([$newHash, $ip, $userId]);
        $pdo->prepare('UPDATE mci_password_reset_tokens SET used_at = NOW(6) WHERE id = ?')->execute([$tid]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();

        return ['ok' => false, 'error' => 'reset_failed', 'status' => 500];
    }

    $emStmt = $pdo->prepare('SELECT email FROM mci_users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
    $emStmt->execute([$userId]);
    $emRow = $emStmt->fetch();
    $acctEmail = is_array($emRow) && isset($emRow['email']) ? (string) $emRow['email'] : '';
    if ($acctEmail !== '') {
        mci_mail_send_password_changed($acctEmail, 'reset_token');
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false, error:string, status?:int}
 */
function mci_account_unlink_auth_provider(string $userId, string $provider): array
{
    $allowed = ['google', 'facebook', 'apple', 'linkedin'];
    if (!in_array($provider, $allowed, true)) {
        return ['ok' => false, 'error' => 'invalid_provider', 'status' => 400];
    }
    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }
    $del = $pdo->prepare('DELETE FROM mci_user_auth_providers WHERE user_id = ? AND provider = ?');
    $del->execute([$userId, $provider]);
    if ($del->rowCount() < 1) {
        return ['ok' => false, 'error' => 'not_linked', 'status' => 404];
    }

    return ['ok' => true];
}

/**
 * Dev / manual linking — real OAuth should replace this.
 *
 * @return array{ok:true}|array{ok:false, error:string, status?:int}
 */
function mci_account_link_auth_provider_manual(string $userId, string $provider, string $providerUserId, ?string $providerEmail): array
{
    if (!api_env_flag('MCI_ALLOW_DEV_SOCIAL_LINK')) {
        return ['ok' => false, 'error' => 'social_link_disabled', 'status' => 403];
    }
    $allowed = ['google', 'facebook', 'apple', 'linkedin'];
    if (!in_array($provider, $allowed, true)) {
        return ['ok' => false, 'error' => 'invalid_provider', 'status' => 400];
    }
    $providerUserId = trim($providerUserId);
    if ($providerUserId === '') {
        return ['ok' => false, 'error' => 'provider_user_id_required', 'status' => 400];
    }

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_config', 'status' => 500];
    }

    $pdo->prepare('DELETE FROM mci_user_auth_providers WHERE user_id = ? AND provider = ?')->execute([$userId, $provider]);

    $id = api_uuid_v4();
    $ins = $pdo->prepare('
      INSERT INTO mci_user_auth_providers
        (id, user_id, provider, provider_user_id, provider_email, created_at)
      VALUES (?, ?, ?, ?, ?, NOW(6))
    ');
    try {
        $ins->execute([$id, $userId, $provider, $providerUserId, $providerEmail]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'already_linked_or_conflict', 'status' => 409];
    }

    return ['ok' => true];
}
