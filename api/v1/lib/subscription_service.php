<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/uuid.php';

/**
 * @return array<string, mixed>
 */
function mci_subscription_decode_features(mixed $raw): array
{
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function mci_subscription_effective_status(string $packageStatus, ?string $activationDate, ?string $expiryDate, ?int $nowTs = null): string
{
    $nowTs = $nowTs ?? time();
    $activationTs = $activationDate ? strtotime($activationDate) : null;
    $expiryTs = $expiryDate ? strtotime($expiryDate) : null;

    if ($packageStatus === 'disabled') {
        return 'inactive';
    }
    if ($activationTs !== null && $activationTs > $nowTs) {
        return 'pending_activation';
    }
    if ($expiryTs !== null && $expiryTs < $nowTs) {
        return 'expired';
    }
    return 'active';
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function mci_subscription_format_package_row(array $row): array
{
    return [
        'id' => (string) ($row['id'] ?? ''),
        'package_name' => (string) ($row['package_name'] ?? ''),
        'package_type' => (string) ($row['package_type'] ?? 'free'),
        'is_default' => (int) ($row['is_default'] ?? 0) === 1,
        'status' => (string) ($row['status'] ?? 'disabled'),
        'effective_status' => mci_subscription_effective_status(
            (string) ($row['status'] ?? 'disabled'),
            isset($row['activation_date']) ? (string) $row['activation_date'] : null,
            isset($row['expiry_date']) ? (string) $row['expiry_date'] : null
        ),
        'activation_date' => $row['activation_date'] ?? null,
        'expiry_date' => $row['expiry_date'] ?? null,
        'price' => isset($row['price']) ? (float) $row['price'] : 0.0,
        'features' => mci_subscription_decode_features($row['features_json'] ?? null),
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

/**
 * @return array{ok:true, package:array<string, mixed>}|array{ok:false, error:string, status:int}
 */
function mci_subscription_get_default_package(PDO $pdo): array
{
    try {
        $stmt = $pdo->query('
            SELECT id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json, created_at, updated_at
            FROM mci_subscription_packages
            WHERE is_default = 1
            ORDER BY updated_at DESC, created_at DESC
            LIMIT 1
        ');
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    if (!$row) {
        return ['ok' => false, 'error' => 'default_package_not_found', 'status' => 500];
    }

    return ['ok' => true, 'package' => mci_subscription_format_package_row($row)];
}

/**
 * @return array{ok:true, packages:array<int, array<string, mixed>>}|array{ok:false, error:string, status:int}
 */
function mci_subscription_list_packages(PDO $pdo): array
{
    try {
        $rows = $pdo->query('
            SELECT id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json, created_at, updated_at
            FROM mci_subscription_packages
            ORDER BY is_default DESC, package_type ASC, package_name ASC
        ')->fetchAll() ?: [];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    return ['ok' => true, 'packages' => array_map('mci_subscription_format_package_row', $rows)];
}

/**
 * @return array{ok:true, package:array<string, mixed>}|array{ok:false, error:string, status:int}
 */
function mci_subscription_get_package_by_id(PDO $pdo, string $packageId): array
{
    if ($packageId === '') {
        return ['ok' => false, 'error' => 'package_id_required', 'status' => 400];
    }
    try {
        $stmt = $pdo->prepare('
            SELECT id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json, created_at, updated_at
            FROM mci_subscription_packages
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->execute([$packageId]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    if (!$row) {
        return ['ok' => false, 'error' => 'package_not_found', 'status' => 404];
    }
    return ['ok' => true, 'package' => mci_subscription_format_package_row($row)];
}

/**
 * @return array{ok:true, subscription:?array<string,mixed>}|array{ok:false, error:string, status:int}
 */
function mci_subscription_get_user_current(PDO $pdo, string $userId): array
{
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user_id_required', 'status' => 400];
    }

    try {
        $stmt = $pdo->prepare('
            SELECT
              us.id AS subscription_id,
              us.user_id,
              us.package_id,
              us.subscription_start_date,
              us.subscription_end_date,
              us.subscription_status,
              us.auto_assigned,
              us.upgrade_source,
              us.created_at AS subscription_created_at,
              us.updated_at AS subscription_updated_at,
              p.package_name,
              p.package_type,
              p.is_default,
              p.status AS package_status,
              p.activation_date,
              p.expiry_date,
              p.price,
              p.features_json
            FROM mci_user_subscriptions us
            JOIN mci_subscription_packages p ON p.id = us.package_id
            WHERE us.user_id = ?
            ORDER BY
              (us.subscription_status = "active") DESC,
              (us.subscription_status = "pending_activation") DESC,
              us.subscription_start_date DESC,
              us.created_at DESC
            LIMIT 1
        ');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    if (!$row) {
        return ['ok' => true, 'subscription' => null];
    }

    $packageStatus = (string) ($row['package_status'] ?? 'disabled');
    $effectivePackageStatus = mci_subscription_effective_status(
        $packageStatus,
        isset($row['activation_date']) ? (string) $row['activation_date'] : null,
        isset($row['expiry_date']) ? (string) $row['expiry_date'] : null
    );

    return [
        'ok' => true,
        'subscription' => [
            'id' => (string) ($row['subscription_id'] ?? ''),
            'user_id' => (string) ($row['user_id'] ?? ''),
            'package_id' => (string) ($row['package_id'] ?? ''),
            'subscription_start_date' => $row['subscription_start_date'] ?? null,
            'subscription_end_date' => $row['subscription_end_date'] ?? null,
            'subscription_status' => (string) ($row['subscription_status'] ?? 'inactive'),
            'auto_assigned' => (int) ($row['auto_assigned'] ?? 0) === 1,
            'upgrade_source' => $row['upgrade_source'] ?? null,
            'created_at' => $row['subscription_created_at'] ?? null,
            'updated_at' => $row['subscription_updated_at'] ?? null,
            'package' => [
                'id' => (string) ($row['package_id'] ?? ''),
                'package_name' => (string) ($row['package_name'] ?? ''),
                'package_type' => (string) ($row['package_type'] ?? 'free'),
                'is_default' => (int) ($row['is_default'] ?? 0) === 1,
                'status' => $packageStatus,
                'effective_status' => $effectivePackageStatus,
                'activation_date' => $row['activation_date'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
                'price' => isset($row['price']) ? (float) $row['price'] : 0.0,
                'features' => mci_subscription_decode_features($row['features_json'] ?? null),
            ],
        ],
    ];
}

/**
 * @return array{ok:true, subscription:array<string, mixed>}|array{ok:false, error:string, status:int}
 */
function mci_subscription_assign_default_to_user(PDO $pdo, string $userId, string $source = 'auto_registration'): array
{
    if ($userId === '') {
        return ['ok' => false, 'error' => 'user_id_required', 'status' => 400];
    }

    $existing = mci_subscription_get_user_current($pdo, $userId);
    if (!$existing['ok']) {
        return $existing;
    }
    if ($existing['subscription'] !== null) {
        return ['ok' => true, 'subscription' => $existing['subscription']];
    }

    $default = mci_subscription_get_default_package($pdo);
    if (!$default['ok']) {
        return $default;
    }
    $package = $default['package'];
    $now = date('Y-m-d H:i:s.u');
    $status = mci_subscription_effective_status(
        (string) ($package['status'] ?? 'disabled'),
        isset($package['activation_date']) ? (string) $package['activation_date'] : null,
        isset($package['expiry_date']) ? (string) $package['expiry_date'] : null
    );

    try {
        $ins = $pdo->prepare('
            INSERT INTO mci_user_subscriptions (
              id, user_id, package_id, subscription_start_date, subscription_end_date,
              subscription_status, auto_assigned, upgrade_source
            ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ');
        $ins->execute([
            api_uuid_v4(),
            $userId,
            (string) $package['id'],
            $now,
            $package['expiry_date'] ?? null,
            $status,
            $source !== '' ? $source : 'auto_registration',
        ]);
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'subscription_assign_failed', 'status' => 500];
    }

    $created = mci_subscription_get_user_current($pdo, $userId);
    if (!$created['ok'] || $created['subscription'] === null) {
        return ['ok' => false, 'error' => 'subscription_assign_failed', 'status' => 500];
    }
    return ['ok' => true, 'subscription' => $created['subscription']];
}

function mci_subscription_user_has_feature(PDO $pdo, string $userId, string $featureKey): bool
{
    if ($userId === '' || $featureKey === '') {
        return false;
    }
    $res = mci_subscription_get_user_current($pdo, $userId);
    if (!$res['ok'] || $res['subscription'] === null) {
        return false;
    }
    $features = $res['subscription']['package']['features'] ?? [];
    if (!is_array($features)) {
        return false;
    }
    return !empty($features[$featureKey]);
}

/**
 * @param array<string,mixed> $data
 * @return array{ok:true, id:string}|array{ok:false,error:string,status:int}
 */
function mci_cp_subscription_packages_create(PDO $pdo, array $data): array
{
    $name = strtoupper(trim((string) ($data['package_name'] ?? '')));
    $type = trim((string) ($data['package_type'] ?? 'free'));
    $status = trim((string) ($data['status'] ?? 'active'));
    $activationDate = trim((string) ($data['activation_date'] ?? ''));
    $expiryDate = trim((string) ($data['expiry_date'] ?? ''));
    $price = isset($data['price']) ? (float) $data['price'] : 0.0;
    $isDefault = !empty($data['is_default']);
    $features = mci_subscription_decode_features($data['features_json'] ?? ($data['features'] ?? []));

    if ($name === '') {
        return ['ok' => false, 'error' => 'package_name_required', 'status' => 400];
    }
    if (!in_array($type, ['free', 'premium'], true)) {
        return ['ok' => false, 'error' => 'invalid_package_type', 'status' => 400];
    }
    if (!in_array($status, ['active', 'coming_soon', 'disabled'], true)) {
        return ['ok' => false, 'error' => 'invalid_status', 'status' => 400];
    }
    if ($status === 'active' && $activationDate !== '' && strtotime($activationDate) > time()) {
        return ['ok' => false, 'error' => 'activation_date_in_future', 'status' => 400];
    }

    try {
        $pdo->beginTransaction();
        if ($isDefault) {
            $pdo->exec('UPDATE mci_subscription_packages SET is_default = 0');
        }

        $ins = $pdo->prepare('
            INSERT INTO mci_subscription_packages (
              id, package_name, package_type, is_default, status, activation_date, expiry_date, price, features_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $id = api_uuid_v4();
        $ins->execute([
            $id,
            $name,
            $type,
            $isDefault ? 1 : 0,
            $status,
            $activationDate !== '' ? $activationDate : null,
            $expiryDate !== '' ? $expiryDate : null,
            $price < 0 ? 0 : $price,
            json_encode($features, JSON_UNESCAPED_SLASHES),
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'package_create_failed', 'status' => 500];
    }

    return ['ok' => true, 'id' => $id];
}

/**
 * @param array<string,mixed> $data
 * @return array{ok:true}|array{ok:false,error:string,status:int}
 */
function mci_cp_subscription_packages_update(PDO $pdo, array $data): array
{
    $id = trim((string) ($data['id'] ?? ''));
    if ($id === '') {
        return ['ok' => false, 'error' => 'id_required', 'status' => 400];
    }

    $fields = [];
    $params = [];

    if (array_key_exists('package_name', $data)) {
        $name = strtoupper(trim((string) $data['package_name']));
        if ($name === '') {
            return ['ok' => false, 'error' => 'package_name_required', 'status' => 400];
        }
        $fields[] = 'package_name = ?';
        $params[] = $name;
    }
    if (array_key_exists('package_type', $data)) {
        $type = trim((string) $data['package_type']);
        if (!in_array($type, ['free', 'premium'], true)) {
            return ['ok' => false, 'error' => 'invalid_package_type', 'status' => 400];
        }
        $fields[] = 'package_type = ?';
        $params[] = $type;
    }
    if (array_key_exists('status', $data)) {
        $status = trim((string) $data['status']);
        if (!in_array($status, ['active', 'coming_soon', 'disabled'], true)) {
            return ['ok' => false, 'error' => 'invalid_status', 'status' => 400];
        }
        $fields[] = 'status = ?';
        $params[] = $status;
    }
    if (array_key_exists('activation_date', $data)) {
        $activationDate = trim((string) $data['activation_date']);
        $fields[] = 'activation_date = ?';
        $params[] = $activationDate !== '' ? $activationDate : null;
    }
    if (array_key_exists('expiry_date', $data)) {
        $expiryDate = trim((string) $data['expiry_date']);
        $fields[] = 'expiry_date = ?';
        $params[] = $expiryDate !== '' ? $expiryDate : null;
    }
    if (array_key_exists('price', $data)) {
        $price = max(0.0, (float) $data['price']);
        $fields[] = 'price = ?';
        $params[] = $price;
    }
    if (array_key_exists('features_json', $data) || array_key_exists('features', $data)) {
        $features = mci_subscription_decode_features($data['features_json'] ?? ($data['features'] ?? []));
        $fields[] = 'features_json = ?';
        $params[] = json_encode($features, JSON_UNESCAPED_SLASHES);
    }
    if (array_key_exists('is_default', $data)) {
        $isDefault = !empty($data['is_default']);
        try {
            if ($isDefault) {
                $pdo->beginTransaction();
                $pdo->exec('UPDATE mci_subscription_packages SET is_default = 0');
                $stmt = $pdo->prepare('UPDATE mci_subscription_packages SET is_default = 1 WHERE id = ?');
                $stmt->execute([$id]);
                $pdo->commit();
            } else {
                $stmt = $pdo->prepare('UPDATE mci_subscription_packages SET is_default = 0 WHERE id = ?');
                $stmt->execute([$id]);
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'package_update_failed', 'status' => 500];
        }
    }

    if ($fields !== []) {
        $params[] = $id;
        try {
            $sql = 'UPDATE mci_subscription_packages SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute($params);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'package_update_failed', 'status' => 500];
        }
    }

    return ['ok' => true];
}

/**
 * @return array{ok:true}|array{ok:false,error:string,status:int}
 */
function mci_cp_subscription_packages_set_default(PDO $pdo, string $packageId): array
{
    if ($packageId === '') {
        return ['ok' => false, 'error' => 'id_required', 'status' => 400];
    }
    try {
        $pdo->beginTransaction();
        $pdo->exec('UPDATE mci_subscription_packages SET is_default = 0');
        $stmt = $pdo->prepare('UPDATE mci_subscription_packages SET is_default = 1 WHERE id = ?');
        $stmt->execute([$packageId]);
        if ($stmt->rowCount() < 1) {
            $pdo->rollBack();
            return ['ok' => false, 'error' => 'package_not_found', 'status' => 404];
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'package_update_failed', 'status' => 500];
    }
    return ['ok' => true];
}

/**
 * @return array{ok:true, items:array<int,array<string,mixed>>, total:int, page:int, per_page:int}|array{ok:false,error:string,status:int}
 */
function mci_cp_user_subscriptions_list(PDO $pdo, int $page = 1, int $perPage = 25, ?string $q = null): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    $where = 'u.deleted_at IS NULL';
    $params = [];

    if ($q !== null && trim($q) !== '') {
        $where .= ' AND (u.email LIKE ? OR u.display_name LIKE ?)';
        $like = '%' . trim($q) . '%';
        $params[] = $like;
        $params[] = $like;
    }

    try {
        $countSql = '
            SELECT COUNT(*)
            FROM mci_user_subscriptions us
            JOIN mci_users u ON u.id = us.user_id
            WHERE ' . $where;
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = '
            SELECT
              us.id, us.user_id, us.package_id, us.subscription_start_date, us.subscription_end_date,
              us.subscription_status, us.auto_assigned, us.upgrade_source, us.created_at, us.updated_at,
              u.email, u.display_name,
              p.package_name, p.package_type, p.status AS package_status
            FROM mci_user_subscriptions us
            JOIN mci_users u ON u.id = us.user_id
            JOIN mci_subscription_packages p ON p.id = us.package_id
            WHERE ' . $where . '
            ORDER BY us.created_at DESC
            LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'server_error', 'status' => 500];
    }

    $items = array_map(static function (array $row): array {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'user_id' => (string) ($row['user_id'] ?? ''),
            'package_id' => (string) ($row['package_id'] ?? ''),
            'subscription_start_date' => $row['subscription_start_date'] ?? null,
            'subscription_end_date' => $row['subscription_end_date'] ?? null,
            'subscription_status' => (string) ($row['subscription_status'] ?? ''),
            'auto_assigned' => (int) ($row['auto_assigned'] ?? 0) === 1,
            'upgrade_source' => $row['upgrade_source'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'user_email' => (string) ($row['email'] ?? ''),
            'user_display_name' => $row['display_name'] ?? null,
            'package_name' => (string) ($row['package_name'] ?? ''),
            'package_type' => (string) ($row['package_type'] ?? ''),
            'package_status' => (string) ($row['package_status'] ?? ''),
        ];
    }, $rows);

    return ['ok' => true, 'items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
}

/**
 * @param array<string,mixed> $data
 * @return array{ok:true,id:string}|array{ok:false,error:string,status:int}
 */
function mci_cp_user_subscription_assign(PDO $pdo, array $data): array
{
    $userId = trim((string) ($data['user_id'] ?? ''));
    $packageId = trim((string) ($data['package_id'] ?? ''));
    $source = trim((string) ($data['upgrade_source'] ?? 'manual_admin'));
    $status = trim((string) ($data['subscription_status'] ?? 'active'));

    if ($userId === '' || $packageId === '') {
        return ['ok' => false, 'error' => 'user_id_and_package_id_required', 'status' => 400];
    }
    if (!in_array($status, ['active', 'inactive', 'expired', 'cancelled', 'pending_activation'], true)) {
        return ['ok' => false, 'error' => 'invalid_subscription_status', 'status' => 400];
    }

    $packageRes = mci_subscription_get_package_by_id($pdo, $packageId);
    if (!$packageRes['ok']) {
        return $packageRes;
    }
    $pkg = $packageRes['package'];
    if (($pkg['status'] ?? '') === 'coming_soon' && (($pkg['activation_date'] ?? null) !== null)) {
        $actTs = strtotime((string) $pkg['activation_date']);
        if ($actTs !== false && $actTs > time()) {
            return ['ok' => false, 'error' => 'package_coming_soon', 'status' => 400];
        }
    }

    try {
        $pdo->beginTransaction();
        $deactivate = $pdo->prepare('
            UPDATE mci_user_subscriptions
            SET subscription_status = "inactive", updated_at = NOW(6)
            WHERE user_id = ? AND subscription_status IN ("active", "pending_activation")
        ');
        $deactivate->execute([$userId]);

        $newId = api_uuid_v4();
        $ins = $pdo->prepare('
            INSERT INTO mci_user_subscriptions (
              id, user_id, package_id, subscription_start_date, subscription_end_date,
              subscription_status, auto_assigned, upgrade_source
            ) VALUES (?, ?, ?, NOW(6), ?, ?, 0, ?)
        ');
        $ins->execute([
            $newId,
            $userId,
            $packageId,
            $pkg['expiry_date'] ?? null,
            $status,
            $source !== '' ? $source : 'manual_admin',
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'subscription_assign_failed', 'status' => 500];
    }

    return ['ok' => true, 'id' => $newId];
}

/**
 * @return array{ok:true, packages:array<int,array<string,mixed>>, current_subscription:?array<string,mixed>, can_upgrade_now:bool, paid_coming_soon:bool}|array{ok:false,error:string,status:int}
 */
function mci_subscription_build_user_summary(PDO $pdo, string $userId): array
{
    $currentRes = mci_subscription_get_user_current($pdo, $userId);
    if (!$currentRes['ok']) {
        return $currentRes;
    }
    $listRes = mci_subscription_list_packages($pdo);
    if (!$listRes['ok']) {
        return $listRes;
    }

    $nowTs = time();
    $canUpgradeNow = false;
    $paidComingSoon = false;
    foreach ($listRes['packages'] as $pkg) {
        if (($pkg['package_type'] ?? '') !== 'premium') {
            continue;
        }
        $status = (string) ($pkg['status'] ?? 'disabled');
        $act = $pkg['activation_date'] ?? null;
        $actTs = $act ? strtotime((string) $act) : null;
        if ($status === 'active' && ($actTs === null || $actTs <= $nowTs)) {
            $canUpgradeNow = true;
        }
        if ($status === 'coming_soon' || ($actTs !== null && $actTs > $nowTs)) {
            $paidComingSoon = true;
        }
    }

    return [
        'ok' => true,
        'packages' => $listRes['packages'],
        'current_subscription' => $currentRes['subscription'],
        'can_upgrade_now' => $canUpgradeNow,
        'paid_coming_soon' => $paidComingSoon,
    ];
}
