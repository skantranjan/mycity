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
require_once __DIR__ . '/lib/account_service.php';
require_once __DIR__ . '/lib/cp_users_service.php';
require_once __DIR__ . '/lib/slug.php';
require_once __DIR__ . '/lib/category_helpers.php';
require_once __DIR__ . '/lib/tag_helpers.php';
require_once __DIR__ . '/lib/seo_fields.php';

// Health endpoint (no auth / no DB yet).
if ($method === 'GET' && ($segments[0] ?? '') === 'health') {
    http_response_code(200);
    echo json_encode(['ok' => true, 'version' => 'v1'], JSON_UNESCAPED_SLASHES);
    exit;
}

// -----------------------------------------------------------------------------
// Auth API (v1) — unified entry + session probe
// Also: POST /subscriber/login, POST /cp/login (same behaviour; kept for compatibility)
// -----------------------------------------------------------------------------
if ($method === 'POST' && ($segments[0] ?? '') === 'auth' && ($segments[1] ?? '') === 'login') {
    $data = api_request_data();
    $email = mb_strtolower(trim(api_body_get_string($data, 'email')));
    $password = api_body_get_string($data, 'password');
    $audience = mb_strtolower(trim(api_body_get_string($data, 'audience')));
    if ($audience === '') {
        $audience = mb_strtolower(trim(api_body_get_string($data, 'type')));
    }

    if ($email === '' || $password === '') {
        api_error('email_and_password_required', 400);
    }
    if ($audience === '') {
        api_error('audience_required', 400, ['hint' => 'Use audience: "subscriber" or "cp" (control panel: super_admin / co_admin).']);
    }

    $res = api_direct_auth_login($email, $password, $audience);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_write_auth_token_cookie($res['token'], $res['exp']);
    api_json(['ok' => true, 'user' => $res['user'], 'token' => $res['token']]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'auth' && ($segments[1] ?? '') === 'me') {
    $token = api_read_auth_token_from_request();
    if ($token === null) {
        api_error('unauthorized', 401);
    }
    $payload = api_jwt_verify($token);
    if (!is_array($payload)) {
        api_error('unauthorized', 401);
    }
    $userId = (string)($payload['sub'] ?? '');
    $role = (string)($payload['role'] ?? '');
    if ($userId === '') {
        api_error('unauthorized', 401);
    }
    api_json([
        'ok' => true,
        'user' => [
            'id' => $userId,
            'role' => $role,
        ],
    ]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'auth' && ($segments[1] ?? '') === 'logout') {
    $token = api_read_auth_token_from_request();
    if ($token !== null) {
        $payload = api_jwt_verify($token);
        if (is_array($payload)) {
            $userId = (string)($payload['sub'] ?? '');
            if ($userId !== '') {
                try {
                    $pdo = api_db();
                    $ip = api_client_ip();
                    $upd = $pdo->prepare('UPDATE mci_users SET is_logged_in = 0, last_update_ip = ? WHERE id = ?');
                    $upd->execute([$ip, $userId]);
                } catch (Throwable $e) {
                    // still clear cookie
                }
            }
        }
    }
    api_clear_auth_token_cookie();
    api_json(['ok' => true]);
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
    $res = api_direct_auth_login($email, $password, 'subscriber');
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
    $res = api_direct_auth_login($email, $password, 'cp');
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

// -----------------------------------------------------------------------------
// Account: forgot / reset password (public)
// -----------------------------------------------------------------------------
if ($method === 'POST' && ($segments[0] ?? '') === 'auth' && ($segments[1] ?? '') === 'forgot-password') {
    $data = api_request_data();
    $email = api_body_get_string($data, 'email');
    $res = mci_account_request_password_reset($email);
    $out = ['ok' => true, 'message' => $res['message'] ?? 'If an account exists for that email, password reset instructions will follow.'];
    if (!empty($res['debug_token'])) {
        $out['debug_token'] = $res['debug_token'];
        $out['debug_reset_url'] = $res['debug_reset_url'] ?? null;
    }
    api_json($out);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'auth' && ($segments[1] ?? '') === 'reset-password') {
    $data = api_request_data();
    $token = api_body_get_string($data, 'token');
    $newPassword = api_body_get_string($data, 'new_password');
    $res = mci_account_reset_password_with_token($token, $newPassword);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

// -----------------------------------------------------------------------------
// Subscriber account: profile + password + social providers
// -----------------------------------------------------------------------------
if ($method === 'GET' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'profile') {
    $auth = api_require_auth(['subscriber']);
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_error($bundle['error'], $bundle['status'] ?? 500);
    }
    api_json($bundle);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'profile') {
    $auth = api_require_auth(['subscriber']);
    $data = api_request_data();
    $res = mci_account_patch_profile($auth['user_id'], $data, api_client_ip());
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_json(['ok' => true]);
    }
    api_json($bundle);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'change-password') {
    $auth = api_require_auth(['subscriber']);
    $data = api_request_data();
    $res = mci_account_change_password(
        $auth['user_id'],
        api_body_get_string($data, 'current_password'),
        api_body_get_string($data, 'new_password')
    );
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'auth-providers') {
    $auth = api_require_auth(['subscriber']);
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_error($bundle['error'], $bundle['status'] ?? 500);
    }
    api_json(['ok' => true, 'auth_providers' => $bundle['auth_providers']]);
}

if ($method === 'DELETE' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'auth-providers' && isset($segments[2])) {
    $auth = api_require_auth(['subscriber']);
    $provider = (string)$segments[2];
    $res = mci_account_unlink_auth_provider($auth['user_id'], $provider);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'auth-providers' && ($segments[2] ?? '') === 'unlink') {
    $auth = api_require_auth(['subscriber']);
    $data = api_request_data();
    $res = mci_account_unlink_auth_provider($auth['user_id'], api_body_get_string($data, 'provider'));
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'auth-providers' && ($segments[2] ?? '') === 'link') {
    $auth = api_require_auth(['subscriber']);
    $data = api_request_data();
    $res = mci_account_link_auth_provider_manual(
        $auth['user_id'],
        api_body_get_string($data, 'provider'),
        api_body_get_string($data, 'provider_user_id'),
        api_body_get_string($data, 'provider_email') !== '' ? api_body_get_string($data, 'provider_email') : null
    );
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

// -----------------------------------------------------------------------------
// Control panel account: profile + password + social providers
// -----------------------------------------------------------------------------
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'profile') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_error($bundle['error'], $bundle['status'] ?? 500);
    }
    api_json($bundle);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'profile') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $res = mci_account_patch_profile($auth['user_id'], $data, api_client_ip());
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_json(['ok' => true]);
    }
    api_json($bundle);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'change-password') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $res = mci_account_change_password(
        $auth['user_id'],
        api_body_get_string($data, 'current_password'),
        api_body_get_string($data, 'new_password')
    );
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'auth-providers') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $bundle = mci_account_get_profile_bundle($auth['user_id']);
    if (!$bundle['ok']) {
        api_error($bundle['error'], $bundle['status'] ?? 500);
    }
    api_json(['ok' => true, 'auth_providers' => $bundle['auth_providers']]);
}

if ($method === 'DELETE' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'auth-providers' && isset($segments[2])) {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $provider = (string)$segments[2];
    $res = mci_account_unlink_auth_provider($auth['user_id'], $provider);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'auth-providers' && ($segments[2] ?? '') === 'unlink') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $res = mci_account_unlink_auth_provider($auth['user_id'], api_body_get_string($data, 'provider'));
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'auth-providers' && ($segments[2] ?? '') === 'link') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $res = mci_account_link_auth_provider_manual(
        $auth['user_id'],
        api_body_get_string($data, 'provider'),
        api_body_get_string($data, 'provider_user_id'),
        api_body_get_string($data, 'provider_email') !== '' ? api_body_get_string($data, 'provider_email') : null
    );
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true]);
}

// Super-admin user management (all roles; soft delete)
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'users' && !isset($segments[2])) {
    api_require_auth(['super_admin']);
    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        api_error('server_config', 500);
    }
    $page = (int) ($_GET['page'] ?? 1);
    $perPage = (int) ($_GET['per_page'] ?? 20);
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
    if ($q === '') {
        $q = null;
    }
    $role = isset($_GET['role']) ? trim((string) $_GET['role']) : null;
    if ($role === '') {
        $role = null;
    }
    $includeDeleted = isset($_GET['include_deleted']) && ($_GET['include_deleted'] === '1' || $_GET['include_deleted'] === 'true');

    $res = mci_cp_users_list($pdo, $page, $perPage, $q, $role, $includeDeleted);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json([
        'ok' => true,
        'users' => $res['users'],
        'total' => $res['total'],
        'page' => $res['page'],
        'per_page' => $res['per_page'],
    ]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'users' && !isset($segments[2])) {
    $auth = api_require_auth(['super_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');

    try {
        $pdo = api_db();
    } catch (Throwable $e) {
        api_error('server_config', 500);
    }

    if ($action === 'create') {
        $res = mci_cp_users_create($pdo, $data);
        if (!$res['ok']) {
            api_error($res['error'], $res['status'] ?? 400);
        }
        api_json(['ok' => true, 'id' => $res['id']]);
    }

    if ($action === 'update') {
        $res = mci_cp_users_update($pdo, $auth['user_id'], $data);
        if (!$res['ok']) {
            api_error($res['error'], $res['status'] ?? 400);
        }
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = api_body_get_string($data, 'id');
        $res = mci_cp_users_soft_delete($pdo, $auth['user_id'], $id);
        if (!$res['ok']) {
            api_error($res['error'], $res['status'] ?? 400);
        }
        api_json(['ok' => true]);
    }

    api_error('invalid_action', 400);
}

// Categories CRUD (hierarchical: parent_id NULL = root; subcategories only under roots)
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'categories') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    try {
        $pdo = api_db();
        $rows = $pdo->query('
      SELECT c.id, c.parent_id, c.name, c.slug, c.sort_order,
             c.page_title, c.meta_keywords, c.meta_description, c.description, c.created_at,
             p.name AS parent_name
      FROM mci_categories c
      LEFT JOIN mci_categories p ON p.id = c.parent_id
      ORDER BY (c.parent_id IS NULL) DESC, c.parent_id ASC, c.sort_order ASC, c.name ASC
    ')->fetchAll();
    } catch (Throwable $e) {
        api_error('server_config', 500);
    }
    api_json(['ok' => true, 'categories' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'categories') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');

    $pdo = api_db();

    $parseParentId = static function (array $d): ?int {
        if (!array_key_exists('parent_id', $d)) {
            return null;
        }
        $raw = $d['parent_id'];
        if ($raw === null || $raw === '' || $raw === 'null') {
            return null;
        }

        return (int) $raw > 0 ? (int) $raw : null;
    };

    if ($action === 'create') {
        $name = trim(api_body_get_string($data, 'name'));
        if ($name === '') {
            api_error('name_required', 400);
        }
        $parentId = $parseParentId($data);
        api_category_validate_parent_for_child($pdo, $parentId);
        $slug = api_category_next_unique_slug($pdo, $name, null);
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        if ($sortOrder < 0) {
            $sortOrder = 0;
        }
        [$pageTitle, $metaKeywords, $metaDescription] = api_parse_seo_fields($data);
        $description = api_parse_description_field($data);
        try {
            $ins = $pdo->prepare(
                'INSERT INTO mci_categories (name, slug, parent_id, sort_order, page_title, meta_keywords, meta_description, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$name, $slug, $parentId, $sortOrder, $pageTitle, $metaKeywords, $metaDescription, $description]);
            api_json(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
        } catch (Throwable $e) {
            api_error('category_already_exists_or_invalid', 409);
        }
    }

    if ($action === 'update') {
        $id = (int) api_body_get_string($data, 'id');
        $name = trim(api_body_get_string($data, 'name'));
        if ($id <= 0) {
            api_error('id_required', 400);
        }
        if ($name === '') {
            api_error('name_required', 400);
        }

        $stmt = $pdo->prepare('SELECT parent_id FROM mci_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            api_error('category_not_found', 404);
        }

        $parentId = array_key_exists('parent_id', $data) ? $parseParentId($data) : null;
        if ($parentId !== null && $parentId === $id) {
            api_error('invalid_parent', 400);
        }
        if ($parentId !== null) {
            api_category_validate_parent_for_child($pdo, $parentId);
        }
        if ($parentId !== null && api_category_count_children($pdo, $id) > 0) {
            api_error('cannot_reparent_category_with_children', 400);
        }

        $slug = api_category_next_unique_slug($pdo, $name, $id);
        $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        if ($sortOrder < 0) {
            $sortOrder = 0;
        }
        [$pageTitle, $metaKeywords, $metaDescription] = api_parse_seo_fields($data);
        [$hasDesc, $descVal] = api_parse_description_field_ex($data);

        if (array_key_exists('parent_id', $data)) {
            $sets = ['name = ?', 'slug = ?', 'parent_id = ?', 'sort_order = ?', 'page_title = ?', 'meta_keywords = ?', 'meta_description = ?'];
            $params = [$name, $slug, $parentId, $sortOrder, $pageTitle, $metaKeywords, $metaDescription];
            if ($hasDesc) {
                $sets[] = 'description = ?';
                $params[] = $descVal;
            }
            $params[] = $id;
            $sql = 'UPDATE mci_categories SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute($params);
        } else {
            $sets = ['name = ?', 'slug = ?', 'sort_order = ?', 'page_title = ?', 'meta_keywords = ?', 'meta_description = ?'];
            $params = [$name, $slug, $sortOrder, $pageTitle, $metaKeywords, $metaDescription];
            if ($hasDesc) {
                $sets[] = 'description = ?';
                $params[] = $descVal;
            }
            $params[] = $id;
            $sql = 'UPDATE mci_categories SET ' . implode(', ', $sets) . ' WHERE id = ?';
            $pdo->prepare($sql)->execute($params);
        }
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int) api_body_get_string($data, 'id');
        if ($id <= 0) {
            api_error('id_required', 400);
        }
        if (api_category_count_children($pdo, $id) > 0) {
            api_error('category_has_children', 400);
        }
        $del = $pdo->prepare('DELETE FROM mci_categories WHERE id = ?');
        $del->execute([$id]);
        api_json(['ok' => true]);
    }

    api_error('invalid_action', 400);
}

// Tags CRUD
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'tags') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    try {
        $pdo = api_db();
        $rows = $pdo->query('
      SELECT id, name, slug, page_title, meta_keywords, meta_description, description, created_at
      FROM mci_tags
      ORDER BY name ASC
    ')->fetchAll();
    } catch (Throwable $e) {
        api_error('server_config', 500);
    }
    api_json(['ok' => true, 'tags' => $rows]);
}

if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'tags') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $data = api_request_data();
    $action = api_body_get_string($data, 'action');
    $pdo = api_db();

    if ($action === 'create') {
        $name = trim(api_body_get_string($data, 'name'));
        if ($name === '') {
            api_error('name_required', 400);
        }
        $slug = api_tag_next_unique_slug($pdo, $name, null);
        [$pageTitle, $metaKeywords, $metaDescription] = api_parse_seo_fields($data);
        $description = api_parse_description_field($data);
        try {
            $ins = $pdo->prepare(
                'INSERT INTO mci_tags (name, slug, page_title, meta_keywords, meta_description, description) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$name, $slug, $pageTitle, $metaKeywords, $metaDescription, $description]);
            api_json(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
        } catch (Throwable $e) {
            api_error('tag_already_exists_or_invalid', 409);
        }
    }

    if ($action === 'update') {
        $id = (int) api_body_get_string($data, 'id');
        $name = trim(api_body_get_string($data, 'name'));
        if ($id <= 0) {
            api_error('id_required', 400);
        }
        if ($name === '') {
            api_error('name_required', 400);
        }
        $slug = api_tag_next_unique_slug($pdo, $name, $id);
        [$pageTitle, $metaKeywords, $metaDescription] = api_parse_seo_fields($data);
        [$hasDesc, $descVal] = api_parse_description_field_ex($data);
        $sets = ['name = ?', 'slug = ?', 'page_title = ?', 'meta_keywords = ?', 'meta_description = ?'];
        $params = [$name, $slug, $pageTitle, $metaKeywords, $metaDescription];
        if ($hasDesc) {
            $sets[] = 'description = ?';
            $params[] = $descVal;
        }
        $params[] = $id;
        $sql = 'UPDATE mci_tags SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $pdo->prepare($sql)->execute($params);
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

            $slug = api_category_next_unique_slug($pdo, $name, null);
            $ins = $pdo->prepare(
                'INSERT INTO mci_categories (name, slug, parent_id, sort_order, page_title, meta_keywords, meta_description, description) VALUES (?, ?, NULL, 0, NULL, NULL, NULL, NULL) ON DUPLICATE KEY UPDATE name = VALUES(name)'
            );
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
        $password = api_body_get_string($data, 'password');

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
            $chk = $pdo->prepare('SELECT id FROM mci_users WHERE email = ? AND id <> ? LIMIT 1');
            $chk->execute([$email, $id]);
            if ($chk->fetch()) {
                api_error('email_already_registered', 409);
            }
            $fields[] = 'email = ?';
            $params[] = $email;
        }
        if ($password !== '') {
            if (strlen($password) < 8) api_error('password_too_short', 400, ['min' => 8]);
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
            $fields[] = 'password_changed_at = NOW(6)';
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

