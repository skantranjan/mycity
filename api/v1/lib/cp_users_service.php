<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/uuid.php';
require_once __DIR__ . '/ip.php';
require_once __DIR__ . '/business_service.php';

/**
 * @return array{ok:true, count:int}|array{ok:false, error:string, status:int}
 */
function mci_cp_count_active_super_admins(PDO $pdo): array
{
    try {
        $stmt = $pdo->query('
            SELECT COUNT(*) AS c
            FROM mci_users u
            JOIN mci_roles r ON r.id = u.role_id
            WHERE r.short_name = "super_admin" AND u.deleted_at IS NULL
        ');
        $row = $stmt->fetch();

        return ['ok' => true, 'count' => (int) ($row['c'] ?? 0)];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }
}

/**
 * @return array{ok:true, users: array<int, array>, total: int, page: int, per_page: int}|array{ok:false, error:string, status:int}
 */
function mci_cp_users_list(
    PDO $pdo,
    int $page,
    int $perPage,
    ?string $q,
    ?string $roleShort,
    bool $includeDeleted
): array {
    $perPage = max(1, min(100, $perPage));
    $page = max(1, $page);
    $offset = ($page - 1) * $perPage;

    $where = ['1=1'];
    $params = [];

    if (!$includeDeleted) {
        $where[] = 'u.deleted_at IS NULL';
    }

    if ($q !== null && $q !== '') {
        $where[] = '(u.email LIKE ? OR u.display_name LIKE ? OR u.phone LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ($roleShort !== null && $roleShort !== '') {
        $where[] = 'r.short_name = ?';
        $params[] = $roleShort;
    }

    $whereSql = implode(' AND ', $where);

    try {
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM mci_users u
            JOIN mci_roles r ON r.id = u.role_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $total = (int) ($countStmt->fetch()['c'] ?? 0);

        $sql = "
            SELECT u.id, u.email, u.display_name, u.phone, u.status, u.email_verified_at, u.phone_verified_at,
                   u.created_at, u.updated_at, u.deleted_at, u.last_login_at,
                   r.short_name AS role
            FROM mci_users u
            JOIN mci_roles r ON r.id = u.role_id
            WHERE {$whereSql}
            ORDER BY u.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $users = array_map(static function (array $row): array {
            return [
                'id' => (string) $row['id'],
                'email' => (string) $row['email'],
                'display_name' => $row['display_name'],
                'phone' => $row['phone'],
                'status' => (string) $row['status'],
                'role' => (string) $row['role'],
                'email_verified_at' => $row['email_verified_at'],
                'phone_verified_at' => $row['phone_verified_at'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'deleted_at' => $row['deleted_at'],
                'last_login_at' => $row['last_login_at'],
            ];
        }, $rows);

        return [
            'ok' => true,
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }
}

/**
 * @param array<string, mixed> $data
 * @return array{ok:true, id:string}|array{ok:false, error:string, status:int}
 */
function mci_cp_users_create(PDO $pdo, array $data): array
{
    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $roleShort = trim((string) ($data['role'] ?? ''));
    $displayName = trim((string) ($data['display_name'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));

    $allowedRoles = ['subscriber', 'co_admin', 'super_admin'];
    if (!in_array($roleShort, $allowedRoles, true)) {
        return ['ok' => false, 'error' => 'invalid_role', 'status' => 400];
    }

    $allowedStatus = ['active', 'inactive', 'blocked'];
    if (!in_array($status, $allowedStatus, true)) {
        return ['ok' => false, 'error' => 'invalid_status', 'status' => 400];
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'invalid_email', 'status' => 400];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'password_too_short', 'status' => 400];
    }

    $stmt = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'email_already_registered', 'status' => 409];
    }

    $userId = api_uuid_v4();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ip = api_client_ip();

    try {
        $ins = $pdo->prepare('
            INSERT INTO mci_users (
              id, email, password_hash, role_id, display_name, phone, status,
              registration_ip, last_update_ip, password_changed_at
            ) VALUES (
              ?, ?, ?,
              (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1),
              ?, ?, ?,
              ?, ?, NOW(6)
            )
        ');
        $ins->execute([
            $userId,
            $email,
            $hash,
            $roleShort,
            $displayName !== '' ? $displayName : null,
            $phone !== '' ? $phone : null,
            $status,
            $ip,
            $ip,
        ]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'create_failed', 'status' => 500];
    }

    return ['ok' => true, 'id' => $userId];
}

/**
 * @param array<string, mixed> $data
 * @return array{ok:true}|array{ok:false, error:string, status:int}
 */
function mci_cp_users_update(PDO $pdo, string $actorUserId, array $data): array
{
    $id = trim((string) ($data['id'] ?? ''));
    if ($id === '') {
        return ['ok' => false, 'error' => 'id_required', 'status' => 400];
    }

    $stmt = $pdo->prepare('
        SELECT u.id, r.short_name AS role
        FROM mci_users u
        JOIN mci_roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.deleted_at IS NULL
        LIMIT 1
    ');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) {
        return ['ok' => false, 'error' => 'user_not_found', 'status' => 404];
    }

    $currentRole = (string) $target['role'];

    $email = isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : null;
    $displayName = array_key_exists('display_name', $data) ? trim((string) $data['display_name']) : null;
    $phone = array_key_exists('phone', $data) ? trim((string) $data['phone']) : null;
    $password = array_key_exists('password', $data) ? (string) $data['password'] : null;
    $roleShort = array_key_exists('role', $data) ? trim((string) $data['role']) : null;
    $status = array_key_exists('status', $data) ? trim((string) $data['status']) : null;

    $allowedRoles = ['subscriber', 'co_admin', 'super_admin'];
    $allowedStatus = ['active', 'inactive', 'blocked'];

    if ($email !== null) {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'invalid_email', 'status' => 400];
        }
        $chk = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? AND id <> ? LIMIT 1');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) {
            return ['ok' => false, 'error' => 'email_already_registered', 'status' => 409];
        }
    }

    if ($password !== null && $password !== '') {
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'password_too_short', 'status' => 400];
        }
    }

    if ($roleShort !== null && $roleShort !== '') {
        if (!in_array($roleShort, $allowedRoles, true)) {
            return ['ok' => false, 'error' => 'invalid_role', 'status' => 400];
        }

        if ($currentRole === 'super_admin' && $roleShort !== 'super_admin') {
            $cnt = mci_cp_count_active_super_admins($pdo);
            if (!$cnt['ok']) {
                return ['ok' => false, 'error' => $cnt['error'], 'status' => $cnt['status']];
            }
            if ($cnt['count'] <= 1) {
                return ['ok' => false, 'error' => 'last_super_admin', 'status' => 400];
            }
        }
    }

    if ($status !== null && $status !== '') {
        if (!in_array($status, $allowedStatus, true)) {
            return ['ok' => false, 'error' => 'invalid_status', 'status' => 400];
        }
    }

    $fields = [];
    $params = [];

    if ($email !== null) {
        $fields[] = 'email = ?';
        $params[] = $email;
    }
    if ($displayName !== null) {
        $fields[] = 'display_name = ?';
        $params[] = $displayName === '' ? null : $displayName;
    }
    if ($phone !== null) {
        $fields[] = 'phone = ?';
        $params[] = $phone === '' ? null : $phone;
    }
    if ($status !== null && $status !== '') {
        $fields[] = 'status = ?';
        $params[] = $status;
    }
    if ($password !== null && $password !== '') {
        $fields[] = 'password_hash = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $fields[] = 'password_changed_at = NOW(6)';
    }
    if ($roleShort !== null && $roleShort !== '') {
        $fields[] = 'role_id = (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1)';
        $params[] = $roleShort;
    }

    if ($fields === []) {
        return ['ok' => false, 'error' => 'nothing_to_update', 'status' => 400];
    }

    $fields[] = 'last_update_ip = ?';
    $params[] = api_client_ip();
    $params[] = $id;

    try {
        $sql = 'UPDATE mci_users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'update_failed', 'status' => 500];
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false, error:string, status:int}
 */
function mci_cp_users_soft_delete(PDO $pdo, string $actorUserId, string $targetId): array
{
    if ($targetId === '') {
        return ['ok' => false, 'error' => 'id_required', 'status' => 400];
    }
    if ($targetId === $actorUserId) {
        return ['ok' => false, 'error' => 'cannot_delete_self', 'status' => 400];
    }

    $stmt = $pdo->prepare('
        SELECT u.id, r.short_name AS role
        FROM mci_users u
        JOIN mci_roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.deleted_at IS NULL
        LIMIT 1
    ');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();
    if (!$target) {
        return ['ok' => false, 'error' => 'user_not_found', 'status' => 404];
    }

    if ((string) $target['role'] === 'super_admin') {
        $cnt = mci_cp_count_active_super_admins($pdo);
        if (!$cnt['ok']) {
            return ['ok' => false, 'error' => $cnt['error'], 'status' => $cnt['status']];
        }
        if ($cnt['count'] <= 1) {
            return ['ok' => false, 'error' => 'last_super_admin', 'status' => 400];
        }
    }

    try {
        $pdo->beginTransaction();
        $upd = $pdo->prepare('
            UPDATE mci_users
            SET deleted_at = NOW(6), status = "deleted", last_update_ip = ?
            WHERE id = ?
        ');
        $upd->execute([api_client_ip(), $targetId]);

        $bizStmt = $pdo->prepare("
            SELECT id FROM mci_business_groups
            WHERE added_by_user_id = ? AND status != 'deleted'
        ");
        $bizStmt->execute([$targetId]);
        $businessIds = $bizStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($businessIds as $businessId) {
            api_business_update_status($pdo, (string)$businessId, 'deleted', $actorUserId, 'Owner account soft-deleted');
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'delete_failed', 'status' => 500];
    }

    return ['ok' => true];
}
