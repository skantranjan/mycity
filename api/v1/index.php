<?php
declare(strict_types=1);

// Minimal v1 API router entrypoint.
// This file is intentionally small; the full DB/JWT implementation is added in later steps.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$base = '/api/v1';
$uriPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if (!is_string($uriPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found'], JSON_UNESCAPED_SLASHES);
    exit;
}

if (str_starts_with($uriPath, $base) === false) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found'], JSON_UNESCAPED_SLASHES);
    exit;
}

$rel = substr($uriPath, strlen($base));
$rel = trim((string)$rel, '/');
$segments = $rel === '' ? [] : explode('/', $rel);

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

require_once __DIR__ . '/lib/response.php';
require_once __DIR__ . '/lib/request.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth_middleware.php';
require_once __DIR__ . '/lib/jwt.php';
require_once __DIR__ . '/lib/auth_cookie.php';
require_once __DIR__ . '/lib/auth_direct.php';
require_once __DIR__ . '/lib/uuid.php';
require_once __DIR__ . '/lib/ip.php';

function api_slugify(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

// Health endpoint (no auth / no DB yet).
if ($method === 'GET' && ($segments[0] ?? '') === 'health') {
    http_response_code(200);
    echo json_encode(['ok' => true, 'version' => 'v1'], JSON_UNESCAPED_SLASHES);
    exit;
}

// Auth endpoints (v1)
if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'register') {
    $data = api_request_data();
    $res = api_direct_subscriber_register($data);
    if (!$res['ok']) {
        $extra = [];
        if (($res['error'] ?? '') === 'password_too_short' && isset($res['min'])) {
            $extra['min'] = $res['min'];
        }
        api_error($res['error'], $res['status'] ?? 400, $extra);
    }
    api_write_auth_token_cookie($res['token'], $res['exp']);
    api_json(['ok' => true, 'user' => $res['user'], 'token' => $res['token']]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'login') {
    $data = api_request_data();
    $email = mb_strtolower(trim(api_body_get_string($data, 'email')));
    $password = api_body_get_string($data, 'password');
    $res = api_direct_subscriber_login($email, $password);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_write_auth_token_cookie($res['token'], $res['exp']);
    api_json(['ok' => true, 'user' => $res['user'], 'token' => $res['token']]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'logout') {
    $auth = api_require_auth(['subscriber']);
    $pdo = api_db();
    $ip = api_client_ip();
    $upd = $pdo->prepare('UPDATE mci_users SET is_logged_in = 0, last_update_ip = ? WHERE id = ?');
    $upd->execute([$ip, $auth['user_id']]);
    api_clear_auth_token_cookie();
    api_json(['ok' => true]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'login') {
    $data = api_request_data();
    $email = mb_strtolower(trim(api_body_get_string($data, 'email')));
    $password = api_body_get_string($data, 'password');
    $res = api_direct_cp_login($email, $password);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_write_auth_token_cookie($res['token'], $res['exp']);
    api_json(['ok' => true, 'user' => $res['user'], 'token' => $res['token']]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'logout') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();
    $ip = api_client_ip();
    $upd = $pdo->prepare('UPDATE mci_users SET is_logged_in = 0, last_update_ip = ? WHERE id = ?');
    $upd->execute([$ip, $auth['user_id']]);
    api_clear_auth_token_cookie();
    api_json(['ok' => true]);
}

// Categories CRUD
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'categories') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();
    $rows = $pdo->query('SELECT id, name, slug, created_at FROM mci_categories ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'categories' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'categories') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');

    $pdo = api_db();
    if ($action === 'create') {
        $name = trim(api_body_get_string($data, 'name'));
        if ($name === '') api_error('name_required', 400);
        $slug = api_slugify($name);
        try {
            $ins = $pdo->prepare('INSERT INTO mci_categories (name, slug) VALUES (?, ?)');
            $ins->execute([$name, $slug]);
            api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        } catch (Throwable $e) {
            api_error('category_already_exists_or_invalid', 409);
        }
    }

    if ($action === 'update') {
        $id = (int)api_body_get_string($data, 'id');
        $name = trim(api_body_get_string($data, 'name'));
        if ($id <= 0) api_error('id_required', 400);
        if ($name === '') api_error('name_required', 400);
        $slug = api_slugify($name);
        $upd = $pdo->prepare('UPDATE mci_categories SET name = ?, slug = ? WHERE id = ?');
        $upd->execute([$name, $slug, $id]);
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)api_body_get_string($data, 'id');
        if ($id <= 0) api_error('id_required', 400);
        $del = $pdo->prepare('DELETE FROM mci_categories WHERE id = ?');
        $del->execute([$id]);
        api_json(['ok' => true]);
    }

    api_error('invalid_action', 400);
}

// Tags CRUD
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'tags') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();
    $rows = $pdo->query('SELECT id, name, created_at FROM mci_tags ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'tags' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'tags') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');
    $pdo = api_db();

    if ($action === 'create') {
        $name = trim(api_body_get_string($data, 'name'));
        if ($name === '') api_error('name_required', 400);
        try {
            $ins = $pdo->prepare('INSERT INTO mci_tags (name) VALUES (?)');
            $ins->execute([$name]);
            api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        } catch (Throwable $e) {
            api_error('tag_already_exists_or_invalid', 409);
        }
    }

    if ($action === 'update') {
        $id = (int)api_body_get_string($data, 'id');
        $name = trim(api_body_get_string($data, 'name'));
        if ($id <= 0) api_error('id_required', 400);
        if ($name === '') api_error('name_required', 400);
        $upd = $pdo->prepare('UPDATE mci_tags SET name = ? WHERE id = ?');
        $upd->execute([$name, $id]);
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int)api_body_get_string($data, 'id');
        if ($id <= 0) api_error('id_required', 400);
        $del = $pdo->prepare('DELETE FROM mci_tags WHERE id = ?');
        $del->execute([$id]);
        api_json(['ok' => true]);
    }

    api_error('invalid_action', 400);
}

// Category request queue
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'category-requests') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();
    $rows = $pdo->query('SELECT id, requester_id, requested_category_name, reason, status, created_at, resolved_by_id, resolved_at FROM mci_category_requests ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'requests' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'category-requests') {
    $auth = api_require_auth(['subscriber']);
    $data = api_request_data();

    $requestedName = trim(api_body_get_string($data, 'requested_category_name'));
    if ($requestedName === '') {
        $requestedName = trim(api_body_get_string($data, 'category'));
    }

    $reason = trim(api_body_get_string($data, 'category_request_reason'));
    if ($reason === '') {
        $reason = trim(api_body_get_string($data, 'reason'));
    }

    if ($requestedName === '') api_error('requested_category_name_required', 400);

    $pdo = api_db();
    $ins = $pdo->prepare('INSERT INTO mci_category_requests (requester_id, requested_category_name, reason, status) VALUES (?, ?, ?, ?)');
    $ins->execute([$auth['user_id'], $requestedName, $reason !== '' ? $reason : null, 'pending']);

    api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

// Approve/reject category requests (CP)
if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'category-requests' && isset($segments[2]) && isset($segments[3])) {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $id = (int)$segments[2];
    $action = (string)$segments[3];
    if ($id <= 0) api_error('id_required', 400);

    $pdo = api_db();
    if ($action === 'approve') {
        $req = $pdo->prepare('SELECT requested_category_name FROM mci_category_requests WHERE id = ?');
        $req->execute([$id]);
        $r = $req->fetch();
        if (!$r) api_error('request_not_found', 404);

        $name = (string)$r['requested_category_name'];
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare('UPDATE mci_category_requests SET status = ?, resolved_by_id = ?, resolved_at = NOW(6) WHERE id = ?');
            $upd->execute(['approved', $auth['user_id'], $id]);

            $slug = api_slugify($name);
            $ins = $pdo->prepare('INSERT INTO mci_categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE slug = VALUES(slug)');
            $ins->execute([$name, $slug]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            api_error('approve_failed', 500);
        }

        api_json(['ok' => true]);
    }

    if ($action === 'reject') {
        $upd = $pdo->prepare('UPDATE mci_category_requests SET status = ?, resolved_by_id = ?, resolved_at = NOW(6) WHERE id = ?');
        $upd->execute(['rejected', $auth['user_id'], $id]);
        api_json(['ok' => true]);
    }
}

// Co-admin management
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'co-admins') {
    $auth = api_require_auth(['super_admin']);
    $pdo = api_db();
    $rows = $pdo->query('
      SELECT u.id, u.email, u.display_name, u.created_at
      FROM mci_users u
      JOIN mci_roles r ON u.role_id = r.id
      WHERE r.short_name = "co_admin" AND u.deleted_at IS NULL
      ORDER BY u.created_at DESC
    ')->fetchAll();
    api_json(['ok' => true, 'co_admins' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'co-admins') {
    $auth = api_require_auth(['super_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');

    $pdo = api_db();

    if ($action === 'add') {
        $email = mb_strtolower(trim(api_body_get_string($data, 'email')));
        $password = api_body_get_string($data, 'password');
        $displayName = trim(api_body_get_string($data, 'display_name'));
        if ($email === '' || $password === '') api_error('email_and_password_required', 400);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) api_error('invalid_email', 400);
        if (strlen($password) < 8) api_error('password_too_short', 400, ['min' => 8]);

        $stmt = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) api_error('email_already_registered', 409);

        $userId = api_uuid_v4();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ip = api_client_ip();

        $ins = $pdo->prepare(
            'INSERT INTO mci_users (id, email, password_hash, role_id, display_name, registration_ip, last_update_ip, password_changed_at) VALUES (?, ?, ?, (SELECT id FROM mci_roles WHERE short_name = ? LIMIT 1), ?, ?, ?, NOW(6))'
        );
        $ins->execute([$userId, $email, $hash, 'co_admin', $displayName !== '' ? $displayName : null, $ip, $ip]);

        api_json(['ok' => true, 'id' => $userId]);
    }

    if ($action === 'edit') {
        $id = api_body_get_string($data, 'id');
        $displayName = trim(api_body_get_string($data, 'display_name'));
        $email = mb_strtolower(trim(api_body_get_string($data, 'email')));

        if (!is_string($id) || trim($id) === '') api_error('id_required', 400);

        // Optional updates
        $fields = [];
        $params = [];
        if ($displayName !== '') {
            $fields[] = 'display_name = ?';
            $params[] = $displayName;
        }
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) api_error('invalid_email', 400);
            $fields[] = 'email = ?';
            $params[] = $email;
        }
        if ($fields === []) api_error('nothing_to_update', 400);

        $fields[] = 'last_update_ip = ?';
        $params[] = api_client_ip();

        $params[] = $id;
        $sql = 'UPDATE mci_users SET ' . implode(', ', $fields) . ' WHERE id = ? AND role_id = (SELECT id FROM mci_roles WHERE short_name = "co_admin" LIMIT 1)';
        $upd = $pdo->prepare($sql);
        $upd->execute($params);
        api_json(['ok' => true]);
    }

    api_error('invalid_action', 400);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'co-admins' && isset($segments[2]) && ($segments[3] ?? '') === 'revoke') {
    $auth = api_require_auth(['super_admin']);
    $id = (string)$segments[2];
    if (trim($id) === '') api_error('id_required', 400);

    $pdo = api_db();
    $upd = $pdo->prepare('
      UPDATE mci_users
      SET role_id = (SELECT id FROM mci_roles WHERE short_name = "subscriber" LIMIT 1),
          last_update_ip = ?
      WHERE id = ?
        AND role_id = (SELECT id FROM mci_roles WHERE short_name = "co_admin" LIMIT 1)
    ');
    $upd->execute([api_client_ip(), $id]);
    api_json(['ok' => true]);
}

// Anonymous business submissions (moderation queue)
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'anon-business-submissions') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();
    $rows = $pdo->query('SELECT id, submitted_by_role, submitted_by_user_id, title, category, payload_json, status, created_at, resolved_by_id, resolved_at FROM mci_anon_business_submissions ORDER BY created_at DESC')->fetchAll();
    api_json(['ok' => true, 'submissions' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'anon-business-submissions') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();

    $payload = $data['payload'] ?? $data['payload_json'] ?? $data['listing'] ?? null;
    $payloadJson = '';
    if (is_array($payload) || is_object($payload)) {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } elseif (is_string($payload)) {
        $payloadJson = $payload;
    } else {
        $payloadJson = json_encode(new stdClass(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') {
        $title = trim((string)($data['listing_title'] ?? $data['business_name'] ?? ''));
    }

    $category = trim((string)($data['category'] ?? ''));
    if ($category === '') {
        $category = trim((string)($data['listing_category'] ?? $data['business_category'] ?? ''));
    }

    $submittedRole = ((string)$auth['role'] === 'co_admin') ? 'co_admin' : 'super_admin';

    if (trim($payloadJson) === '') api_error('payload_required', 400);

    $pdo = api_db();
    $ins = $pdo->prepare('
      INSERT INTO mci_anon_business_submissions
        (submitted_by_role, submitted_by_user_id, payload_json, title, category, status)
      VALUES (?, ?, ?, ?, ?, ?)
    ');
    $ins->execute([
        $submittedRole,
        $auth['user_id'],
        $payloadJson,
        $title !== '' ? $title : null,
        $category !== '' ? $category : null,
        'pending'
    ]);

    api_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'anon-business-submissions' && isset($segments[2]) && isset($segments[3])) {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $id = (int)$segments[2];
    $action = (string)$segments[3];
    if ($id <= 0) api_error('id_required', 400);

    $pdo = api_db();

    if ($action === 'approve') {
        $body = api_request_data();
        $mode = trim((string)($body['mode'] ?? 'live'));
        // Store as distinct status values (live vs claim) for future UI.
        $nextStatus = $mode === 'claim' ? 'approved_claim' : 'approved_live';

        $upd = $pdo->prepare('UPDATE mci_anon_business_submissions SET status = ?, resolved_by_id = ?, resolved_at = NOW(6) WHERE id = ?');
        $upd->execute([$nextStatus, $auth['user_id'], $id]);
        api_json(['ok' => true]);
    }

    if ($action === 'reject') {
        $upd = $pdo->prepare('UPDATE mci_anon_business_submissions SET status = ?, resolved_by_id = ?, resolved_at = NOW(6) WHERE id = ?');
        $upd->execute(['rejected', $auth['user_id'], $id]);
        api_json(['ok' => true]);
    }
}

http_response_code(404);
echo json_encode([
    'error' => 'not_found',
    'path' => $uriPath,
    'method' => $method,
], JSON_UNESCAPED_SLASHES);

