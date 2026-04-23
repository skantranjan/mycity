<?php
declare(strict_types=1);

// Minimal v1 API router entrypoint.
// This file is intentionally small; the full DB/JWT implementation is added in later steps.

require_once __DIR__ . '/lib/http_security.php';
api_v1_send_cors_and_security_headers();
header('Content-Type: application/json; charset=utf-8');

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
require_once dirname(__DIR__, 2) . '/includes/mci_cache.php';
require_once __DIR__ . '/lib/auth_middleware.php';
require_once __DIR__ . '/lib/jwt.php';
require_once __DIR__ . '/lib/auth_cookie.php';
require_once __DIR__ . '/lib/auth_direct.php';
require_once __DIR__ . '/lib/uuid.php';
require_once __DIR__ . '/lib/ip.php';
require_once __DIR__ . '/lib/rate_limit.php';
require_once __DIR__ . '/lib/account_service.php';
require_once __DIR__ . '/lib/cp_users_service.php';
require_once __DIR__ . '/lib/mci_mailer.php';
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
    if (!api_rate_limit_allow('auth_login:' . api_client_ip(), 40, 900)) {
        api_error('too_many_requests', 429);
    }
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

    $rememberLong = false;
    if ($audience === 'subscriber' || $audience === 'sub') {
        $rememberLong = !empty($data['remember_me']) || !empty($data['remember']);
    }
    $res = api_direct_auth_login($email, $password, $audience, $rememberLong);
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
    if (!api_rate_limit_allow('sub_register:' . api_client_ip(), 20, 3600)) {
        api_error('too_many_requests', 429);
    }
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
    if (!api_rate_limit_allow('sub_login:' . api_client_ip(), 40, 900)) {
        api_error('too_many_requests', 429);
    }
    $data = api_request_data();
    $email = mb_strtolower(trim(api_body_get_string($data, 'email')));
    $password = api_body_get_string($data, 'password');
    $rememberLong = !empty($data['remember_me']) || !empty($data['remember']);
    $res = api_direct_auth_login($email, $password, 'subscriber', $rememberLong);
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
    if (!api_rate_limit_allow('cp_login:' . api_client_ip(), 40, 900)) {
        api_error('too_many_requests', 429);
    }
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
    if (!api_rate_limit_allow('forgot_pw:' . api_client_ip(), 15, 3600)) {
        api_error('too_many_requests', 429);
    }
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
    $sort = trim((string) ($_GET['sort'] ?? 'created_at'));
    $sortDir = trim((string) ($_GET['sort_dir'] ?? 'desc'));
    if ($sortDir !== 'asc' && $sortDir !== 'desc') {
        $sortDir = 'desc';
    }

    $res = mci_cp_users_list($pdo, $page, $perPage, $q, $role, $includeDeleted, $sort, $sortDir);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json([
        'ok' => true,
        'users' => $res['users'],
        'total' => $res['total'],
        'page' => $res['page'],
        'per_page' => $res['per_page'],
        'sort' => $res['sort'],
        'sort_dir' => $res['sort_dir'],
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

// Super-admin: invalidate public site cache (listings, home snapshots, public API read models)
if ($method === 'POST' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'cache' && ($segments[2] ?? '') === 'clear-public' && !isset($segments[3])) {
    api_require_auth(['super_admin']);
    require_once __DIR__ . '/lib/business_service.php';
    $cacheEnabled = mci_cache_enabled();
    api_business_invalidate_public_directory_cache();
    $version = mci_cache_public_version();
    api_json([
        'ok' => true,
        'cache_enabled' => $cacheEnabled,
        'public_directory_version' => $version,
    ]);
}

// Categories CRUD (hierarchical: parent_id NULL = root; subcategories only under roots)
if ($method === 'GET' && ($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'categories') {
    $auth = api_require_auth(['super_admin', 'co_admin']);
    try {
        $pdo = api_db();
        $rows = $pdo->query('
      SELECT c.id, c.parent_id, c.name, c.slug, c.icon, c.sort_order,
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
        $icon = isset($data['icon']) ? trim((string) $data['icon']) : null;
        if ($icon === '') {
            $icon = null;
        }
        try {
            $ins = $pdo->prepare(
                'INSERT INTO mci_categories (name, slug, icon, parent_id, sort_order, page_title, meta_keywords, meta_description, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$name, $slug, $icon, $parentId, $sortOrder, $pageTitle, $metaKeywords, $metaDescription, $description]);
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
        $hasIcon = array_key_exists('icon', $data);
        $iconVal = $hasIcon ? (trim((string) $data['icon']) ?: null) : null;

        if (array_key_exists('parent_id', $data)) {
            $sets = ['name = ?', 'slug = ?', 'parent_id = ?', 'sort_order = ?', 'page_title = ?', 'meta_keywords = ?', 'meta_description = ?'];
            $params = [$name, $slug, $parentId, $sortOrder, $pageTitle, $metaKeywords, $metaDescription];
            if ($hasIcon) {
                $sets[] = 'icon = ?';
                $params[] = $iconVal;
            }
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
            if ($hasIcon) {
                $sets[] = 'icon = ?';
                $params[] = $iconVal;
            }
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

        mci_mail_send_account_invited($email, $displayName !== '' ? $displayName : null, 'co_admin');

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

        if ($password !== '') {
            $emStmt = $pdo->prepare('SELECT email FROM mci_users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
            $emStmt->execute([$id]);
            $emRow = $emStmt->fetch();
            $acctEmail = is_array($emRow) && isset($emRow['email']) ? (string) $emRow['email'] : '';
            if ($acctEmail !== '') {
                mci_mail_send_password_changed($acctEmail, 'admin');
            }
        }

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

// =============================================================================
// Business registration — image upload
// Must be matched BEFORE api_request_data() calls below (multipart)
// POST /api/v1/upload/image
// =============================================================================
if ($method === 'POST' && ($segments[0] ?? '') === 'upload' && ($segments[1] ?? '') === 'image') {
    if (!api_rate_limit_allow('upload_img:' . api_client_ip(), 120, 3600)) {
        api_error('too_many_requests', 429);
    }
    $allowedTypes = ['logo', 'profile', 'banner', 'gallery', 'item_image'];
    $type = trim((string)($_POST['type'] ?? ''));
    if (!in_array($type, $allowedTypes, true)) {
        api_error('invalid_type', 400, ['allowed' => $allowedTypes]);
    }
    $businessIdEarly = trim((string)($_POST['business_id'] ?? ''));
    $uploadTokPost    = trim((string)($_POST['image_upload_token'] ?? ''));
    $uploadTokHdr     = trim((string)($_SERVER['HTTP_X_MCI_IMAGE_UPLOAD_TOKEN'] ?? ''));
    $uploadTok        = $uploadTokPost !== '' ? $uploadTokPost : $uploadTokHdr;
    require_once __DIR__ . '/lib/business_helpers.php';
    $pdoUp  = api_db();
    $authUp = api_business_try_auth();
    api_assert_can_upload_business_file($pdoUp, $businessIdEarly, $authUp, $uploadTok);

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErr = $_FILES['file']['error'] ?? -1;
        if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
            api_error('file_too_large', 413, ['max_bytes' => 2 * 1024 * 1024]);
        }
        api_error('file_required', 400);
    }
    $file     = $_FILES['file'];
    $tmpPath  = (string)$file['tmp_name'];
    $origName = (string)($file['name'] ?? '');
    $maxBytes = 2 * 1024 * 1024; // 2 MB per image (schema / business listing cap)
    if ($file['size'] > $maxBytes) {
        api_error('file_too_large', 413, ['max_bytes' => $maxBytes]);
    }
    $mime = mime_content_type($tmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMimes, true)) {
        api_error('invalid_mime', 415, ['mime' => $mime]);
    }
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext    = $extMap[$mime] ?? 'jpg';
    // ── Per-business folder routing ──────────────────────────────────
    $businessId = $businessIdEarly;
    $subtype    = trim((string)($_POST['subtype']     ?? ''));
    $uuidV4Re   = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    $docRoot    = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    if ($businessId !== '' && preg_match($uuidV4Re, $businessId)) {
        // Per-business path
        $folder = $type;
        if ($type === 'item_image') {
            $folder = in_array($subtype, ['services', 'products'], true) ? $subtype : 'products';
        }
        $dir      = $docRoot . '/storage/uploads/businesses/' . $businessId . '/' . $folder;
        $pathBase = '/storage/uploads/businesses/' . $businessId . '/' . $folder;
    } else {
        // Flat backward-compat path (item_image → /storage/uploads/item_image/)
        $dir      = $docRoot . '/storage/uploads/' . $type;
        $pathBase = '/storage/uploads/' . $type;
    }
    // ────────────────────────────────────────────────────────────────

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    require_once __DIR__ . '/lib/uuid.php';
    require_once __DIR__ . '/lib/mci_upload_image_process.php';
    $uuidBase = api_uuid_v4();
    $maxEdge  = 1920;
    $optimized = mci_business_upload_optimize($tmpPath, $mime, $dir, $uuidBase, $maxBytes, $maxEdge);
    if ($optimized !== null) {
        $filename = $optimized;
    } else {
        $filename = $uuidBase . '.' . $ext;
        $dest     = $dir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $dest)) {
            api_error('upload_failed', 500);
        }
        $finalSize = @filesize($dest);
        if ($finalSize === false || $finalSize > $maxBytes) {
            @unlink($dest);
            api_error('file_too_large', 413, ['max_bytes' => $maxBytes]);
        }
    }
    $path = $pathBase . '/' . $filename;
    api_json(['ok' => true, 'path' => $path]);
}

// =============================================================================
// Public — categories tree (no auth)
// GET /api/v1/public/categories
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'categories') {
    $ttl = mci_cache_ttl_default();
    header('Cache-Control: public, max-age=' . min(86400, max(0, $ttl)));
    $pdo = api_db();
    $result = mci_cache_remember(
        mci_cache_public_key('api:pub:categories:v1'),
        $ttl,
        static function () use ($pdo): array {
            $stmt = $pdo->query('SELECT id, name, slug, parent_id FROM mci_categories ORDER BY parent_id IS NOT NULL, name');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $roots = [];
            $children = [];
            foreach ($rows as $row) {
                if ($row['parent_id'] === null) {
                    $row['children'] = [];
                    $roots[$row['id']] = $row;
                } else {
                    $children[(int)$row['parent_id']][] = [
                        'id'   => (int)$row['id'],
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                    ];
                }
            }
            $result = [];
            foreach ($roots as $root) {
                $root['id']       = (int)$root['id'];
                $root['children'] = $children[$root['id']] ?? [];
                $result[]         = $root;
            }

            return $result;
        }
    );
    api_json(['ok' => true, 'categories' => $result]);
}

// =============================================================================
// Public — tags flat list (no auth)
// GET /api/v1/public/tags
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'tags') {
    $ttl = mci_cache_ttl_default();
    header('Cache-Control: public, max-age=' . min(86400, max(0, $ttl)));
    $pdo = api_db();
    $tags = mci_cache_remember(
        mci_cache_public_key('api:pub:tags:v1'),
        $ttl,
        static function () use ($pdo): array {
            $stmt = $pdo->query('SELECT id, name, slug FROM mci_tags ORDER BY name');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$t) {
                $t['id'] = (int)$t['id'];
            }
            unset($t);

            return $rows;
        }
    );
    api_json(['ok' => true, 'tags' => $tags]);
}

// =============================================================================
// Public — city / area autocomplete (no auth)
// GET /api/v1/public/cities?q=pune&limit=10
// Returns distinct cities from live business branches that match the query.
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'cities') {
    $q     = trim((string)($_GET['q'] ?? ''));
    $limit = max(1, min(20, (int)($_GET['limit'] ?? 10)));

    if ($q === '' || strlen($q) < 1) {
        api_json(['ok' => true, 'cities' => []]);
    }

    $pdo  = api_db();
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.city
        FROM mci_business_branches b
        INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
        WHERE b.city != '' AND b.city LIKE :q
        ORDER BY b.city
        LIMIT :lim
    ");
    $stmt->bindValue(':q',   '%' . $q . '%', PDO::PARAM_STR);
    $stmt->bindValue(':lim', $limit,         PDO::PARAM_INT);
    $stmt->execute();
    $cities = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'city');
    api_json(['ok' => true, 'cities' => $cities]);
}

// =============================================================================
// Public — business listings search (no auth)
// GET /api/v1/public/businesses?q=...&city=...&category_slug=...&subcategory_slug=...
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'businesses') {
    require_once __DIR__ . '/lib/business_service.php';
    require_once dirname(__DIR__, 2) . '/includes/mci_config.php';

    $filters = [
        'q'                => trim((string)($_GET['q'] ?? '')),
        'city'             => trim((string)($_GET['city'] ?? '')),
        'category_slug'    => trim((string)($_GET['category_slug'] ?? '')),
        'subcategory_slug' => trim((string)($_GET['subcategory_slug'] ?? '')),
        'tag_slug'         => trim((string)($_GET['tag_slug'] ?? '')),
        'price_range'      => trim((string)($_GET['price_range'] ?? '')),
        'sort'             => trim((string)($_GET['sort'] ?? 'newest')),
        'page'             => max(1, (int)($_GET['page'] ?? 1)),
        'per_page'         => min(48, max(1, (int)($_GET['per_page'] ?? (defined('MCI_LISTING_PER_PAGE') ? MCI_LISTING_PER_PAGE : 12)))),
    ];
    $filters = array_filter($filters, static fn($v): bool => $v !== '');

    $ttl = mci_cache_ttl_default();
    header('Cache-Control: public, max-age=' . min(86400, max(0, $ttl)));

    $pdo = api_db();
    $cacheKey = mci_cache_key_public_filters('api:pub:businesses:', $filters);
    $res      = mci_cache_remember(
        $cacheKey,
        $ttl,
        static function () use ($pdo, $filters): array {
            return api_business_list_public($pdo, $filters);
        }
    );
    api_json(['ok' => true] + $res);
}

// =============================================================================
// Public — items search (products or services) — no auth
// GET /api/v1/public/items?type=products&q=...&city=...&category=...&page=1
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'items') {
    require_once __DIR__ . '/lib/item_search_service.php';
    $params = [
        'type'      => trim((string)($_GET['type']      ?? 'products')),
        'q'         => trim((string)($_GET['q']         ?? '')),
        'city'      => trim((string)($_GET['city']      ?? '')),
        'category'  => trim((string)($_GET['category']  ?? '')),
        'price_min' => trim((string)($_GET['price_min'] ?? '')),
        'price_max' => trim((string)($_GET['price_max'] ?? '')),
        'sort'      => trim((string)($_GET['sort']      ?? 'relevance')),
        'page'      => max(1, (int)($_GET['page']     ?? 1)),
        'per_page'  => min(48, max(1, (int)($_GET['per_page'] ?? 12))),
    ];
    $pdo = api_db();
    $res = api_items_search($pdo, $params);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json($res);
}

// =============================================================================
// Public — items name suggestions (typeahead) — no auth
// GET /api/v1/public/items/suggest?type=products&q=foo
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'items' && ($segments[2] ?? '') === 'suggest') {
    $type = trim((string)($_GET['type'] ?? ''));
    if (!in_array($type, ['products', 'services'], true)) {
        api_error('invalid_type', 400);
    }
    $q = substr(strip_tags(trim((string)($_GET['q'] ?? ''))), 0, 120);
    if (strlen($q) < 3) {
        api_json(['ok' => true, 'suggestions' => []]);
    }
    $table = $type === 'products' ? 'mci_business_products' : 'mci_business_services';
    $pdo   = api_db();
    $stmt  = $pdo->prepare("
        SELECT DISTINCT p.name
        FROM {$table} p
        INNER JOIN mci_business_groups g ON g.id = p.business_group_id AND g.status = 'live'
        WHERE p.is_active = 1 AND p.name LIKE ?
        ORDER BY p.name
        LIMIT 8
    ");
    $stmt->execute(['%' . $q . '%']);
    $names = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    api_json(['ok' => true, 'suggestions' => $names]);
}

// =============================================================================
// Public — submit a lead or enquiry for a business listing — no auth
// POST /api/v1/public/leads
// Body (JSON or form): business_group_id, type ('lead'|'enquiry'),
//   sender_name, sender_phone, sender_email, message
// =============================================================================
if ($method === 'POST' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'leads' && !isset($segments[2])) {
    if (!api_rate_limit_allow('public_lead:' . api_client_ip(), 40, 3600)) {
        api_error('too_many_requests', 429);
    }
    require_once __DIR__ . '/lib/leads_service.php';

    $data = api_request_data();

    $bgId    = substr(trim((string)($data['business_group_id'] ?? '')), 0, 36);
    $type    = trim((string)($data['type'] ?? 'lead'));
    $name    = substr(trim((string)($data['sender_name']  ?? '')), 0, 160);
    $phone   = substr(trim((string)($data['sender_phone'] ?? '')), 0, 40);
    $email   = substr(trim((string)($data['sender_email'] ?? '')), 0, 254);
    $message = trim((string)($data['message'] ?? ''));

    if ($bgId === '' || $message === '') {
        api_error('missing_fields', 400);
    }
    if (!in_array($type, ['lead', 'enquiry'], true)) {
        $type = 'lead';
    }

    // Verify the business group exists and is live
    $pdo    = api_db();
    $bgStmt = $pdo->prepare("SELECT id, name, slug, email FROM mci_business_groups WHERE id = ? AND status = 'live' LIMIT 1");
    $bgStmt->execute([$bgId]);
    $bg = $bgStmt->fetch(PDO::FETCH_ASSOC);
    if (!$bg) {
        api_error('business_not_found', 404);
    }

    $newId = leads_create($pdo, $bgId, $type, $name, $phone, $email, $message);
    if ($newId === '') {
        api_error('create_failed', 500);
    }

    try {
        mci_mail_send_business_enquiry_received(
            trim((string)($bg['email'] ?? '')),
            trim((string)($bg['name'] ?? '')),
            trim((string)($bg['slug'] ?? '')),
            $name,
            $phone,
            $email,
            $message,
            $type
        );
    } catch (Throwable $ignored) {}

    api_json(['ok' => true, 'id' => $newId], 201);
}

// =============================================================================
// Public — flag a business listing as inappropriate (auth optional)
// POST /api/v1/public/business-flags
// =============================================================================
if ($method === 'POST' && ($segments[0] ?? '') === 'public' && ($segments[1] ?? '') === 'business-flags' && !isset($segments[2])) {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';
    $auth = api_business_try_auth();
    $data = api_request_data();
    $res = api_business_flag_create(api_db(), $data, $auth);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 400);
    }
    api_json(['ok' => true, 'id' => $res['id']], 201);
}

// =============================================================================
// Business registration
// POST /api/v1/businesses
// =============================================================================
if ($method === 'POST' && ($segments[0] ?? '') === 'businesses' && !isset($segments[1])) {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';
    require_once dirname(__DIR__, 2) . '/includes/mci_config.php';

    $auth = api_business_try_auth(); // optional — null for guests
    $data = api_request_data();

    try {
        $pdo = api_db();
        $res = api_business_create($pdo, $data, $auth);
    } catch (Throwable $e) {
        mci_log_error('POST /api/v1/businesses', $e);
        $extra = [];
        if (api_env_flag('MCI_DEBUG_API')) {
            $extra['detail'] = get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
        }
        api_error('server_error', 500, $extra);
    }

    if (!$res['ok']) {
        $extra = isset($res['detail']) ? ['detail' => $res['detail']] : [];
        api_error($res['error'], $res['status'] ?? 400, $extra);
    }

    // Set data_source after create based on submission context
    $ctx = (string)($data['context'] ?? 'guest');
    if ($ctx === 'cp_admin' && !empty($res['id'])) {
        try {
            $pdo->prepare('UPDATE mci_business_groups SET data_source = \'manual_cp\' WHERE id = ?')
                ->execute([$res['id']]);
        } catch (Throwable) {}
    } elseif (($ctx === 'subscriber' || ($ctx === 'guest' && ($data['posting_type'] ?? '') === 'registered')) && !empty($res['id'])) {
        try {
            $pdo->prepare('UPDATE mci_business_groups SET data_source = \'user_submission\' WHERE id = ?')
                ->execute([$res['id']]);
        } catch (Throwable) {}
    } elseif ($ctx === 'guest' && !empty($res['id'])) {
        try {
            $pdo->prepare('UPDATE mci_business_groups SET data_source = \'anonymous_submission\' WHERE id = ?')
                ->execute([$res['id']]);
        } catch (Throwable) {}
    }

    $out = [
        'ok'          => true,
        'id'          => $res['id'],
        'slug'        => $res['slug'],
        'branch_id'   => $res['branch_id'],
        'product_ids' => $res['product_ids'] ?? [],
        'service_ids' => $res['service_ids'] ?? [],
    ];
    if (!empty($res['id'])) {
        require_once __DIR__ . '/lib/image_upload_token.php';
        $out['image_upload_token'] = api_image_upload_token_issue((string) $res['id']);
    }
    if (isset($res['token'])) {
        // Guest created an account at submit — return JWT so client can log in
        $out['token']     = $res['token'];
        $out['token_exp'] = $res['token_exp'];
        api_write_auth_token_cookie($res['token'], $res['token_exp']);
    }
    api_json($out, 201);
}

// =============================================================================
// Business images — save uploaded paths after creation
// PATCH /api/v1/businesses/{id}/images
// =============================================================================
if ($method === 'PATCH' && ($segments[0] ?? '') === 'businesses' && ($segments[2] ?? '') === 'images' && isset($segments[1])) {
    if (!api_rate_limit_allow('patch_img:' . api_client_ip(), 200, 3600)) {
        api_error('too_many_requests', 429);
    }
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $groupId = (string)$segments[1];
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $groupId)) {
        api_error('not_found', 404);
    }
    $data = api_request_data();

    $uploadTok = trim((string)($data['image_upload_token'] ?? ''));
    if ($uploadTok === '') {
        $uploadTok = trim((string)($_SERVER['HTTP_X_MCI_IMAGE_UPLOAD_TOKEN'] ?? ''));
    }
    unset($data['image_upload_token']);

    $auth = api_business_try_auth();
    $pdo  = api_db();
    api_assert_can_patch_business_images($pdo, $groupId, $auth, $uploadTok !== '' ? $uploadTok : null);

    $actorId = $auth['user_id'] ?? '00000000-0000-0000-0000-000000000000';

    $res = api_business_patch_images($pdo, $groupId, $data, $actorId);
    if (!$res['ok']) {
        api_error($res['error'], $res['status'] ?? 500);
    }
    api_json(['ok' => true]);
}

// =============================================================================
// CP — business moderation
// GET  /api/v1/cp/businesses
// POST /api/v1/cp/businesses/{id}/approve
// POST /api/v1/cp/businesses/{id}/reject
// POST /api/v1/cp/businesses/{id}/suspend
// =============================================================================
if (($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'businesses') {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo  = api_db();

    // GET /api/v1/cp/businesses
    if ($method === 'GET' && !isset($segments[2])) {
        $qp = $_GET;
        $sortDir = trim((string)($qp['sort_dir'] ?? 'desc'));
        if ($sortDir !== 'asc' && $sortDir !== 'desc') {
            $sortDir = 'desc';
        }
        $filters = [
            'status'        => $qp['status']       ?? null,
            'added_by_role' => $qp['role']          ?? null,
            'category_id'   => isset($qp['category_id']) ? (int)$qp['category_id'] : null,
            'q'             => $qp['q']             ?? null,
            'page'          => (int)($qp['page']    ?? 1),
            'per_page'      => (int)($qp['per_page'] ?? 25),
            'sort'          => trim((string)($qp['sort'] ?? 'created_at')),
            'sort_dir'      => $sortDir,
        ];
        api_json(api_business_list_cp($pdo, $filters));
    }

    // GET /api/v1/cp/businesses/{id}
    if ($method === 'GET' && isset($segments[2]) && !isset($segments[3])) {
        $groupId = (string)$segments[2];
        $data = api_business_fetch($pdo, $groupId);
        if (!$data) {
            api_error('business_not_found', 404);
        }
        api_json(['ok' => true, 'business' => $data]);
    }

    // POST /api/v1/cp/businesses/{id}/approve|reject|suspend
    if ($method === 'POST' && isset($segments[2], $segments[3])) {
        $groupId = (string)$segments[2];
        $action  = (string)$segments[3];
        $body    = api_request_data();
        $notes   = trim((string)($body['notes'] ?? '')) ?: null;

        $statusMap = ['approve' => 'live', 'reject' => 'rejected', 'suspend' => 'suspended'];
        if (!isset($statusMap[$action])) {
            api_error('unknown_action', 400);
        }

        $ok = api_business_update_status($pdo, $groupId, $statusMap[$action], $auth['user_id'], $notes);
        if (!$ok) {
            api_error('business_not_found', 404);
        }
        api_json(['ok' => true]);
    }
}

// =============================================================================
// CP — business flags moderation
// GET  /api/v1/cp/business-flags
// POST /api/v1/cp/business-flags/{id}/resolve
// POST /api/v1/cp/business-flags/{id}/dismiss
// =============================================================================
if (($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'business-flags') {
    require_once __DIR__ . '/lib/business_service.php';
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo = api_db();

    if ($method === 'GET' && !isset($segments[2])) {
        $status = trim((string)($_GET['status'] ?? 'open'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 25)));
        api_json(['ok' => true] + api_business_flag_list_cp($pdo, $page, $perPage, $status));
    }

    if ($method === 'POST' && isset($segments[2], $segments[3])) {
        $flagId = (string)$segments[2];
        $action = (string)$segments[3];
        $data = api_request_data();
        $status = $action === 'resolve' ? 'resolved' : ($action === 'dismiss' ? 'dismissed' : '');
        if ($status === '') {
            api_error('not_found', 404);
        }
        $ok = api_business_flag_resolve($pdo, $flagId, $status, (string)$auth['user_id'], trim((string)($data['admin_note'] ?? '')));
        if (!$ok) {
            api_error('flag_not_found_or_closed', 404);
        }
        if ($action === 'resolve' && !empty($data['suspend_listing'])) {
            $flagStmt = $pdo->prepare('SELECT business_group_id FROM mci_business_flags WHERE id = ? LIMIT 1');
            $flagStmt->execute([$flagId]);
            $groupId = (string)($flagStmt->fetchColumn() ?: '');
            if ($groupId !== '') {
                api_business_update_status($pdo, $groupId, 'suspended', (string)$auth['user_id'], 'Suspended from inappropriate listing report');
            }
        }
        api_json(['ok' => true]);
    }
}

// =============================================================================
// Subscriber — own business list
// GET /api/v1/businesses?owner=me
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'businesses' && !isset($segments[1]) && ($_GET['owner'] ?? '') === 'me') {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $auth = api_require_auth(['subscriber', 'super_admin', 'co_admin']);
    $pdo  = api_db();
    api_json(api_business_list_owner($pdo, $auth['user_id']));
}

// =============================================================================
// Subscriber — fetch single owned business (full details)
// GET /api/v1/subscriber/businesses/{id}
// =============================================================================
if ($method === 'GET' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'businesses' && isset($segments[2]) && !isset($segments[3])) {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $auth    = api_require_auth(['subscriber', 'super_admin', 'co_admin']);
    $pdo     = api_db();
    $groupId = (string)$segments[2];

    $data = api_business_fetch($pdo, $groupId);
    if (!$data) {
        api_error('business_not_found', 404);
    }
    // Ownership check — subscribers may only view their own listings
    if ($auth['role'] === 'subscriber' && (string)($data['added_by_user_id'] ?? '') !== $auth['user_id']) {
        api_error('forbidden', 403);
    }
    api_json(['ok' => true, 'business' => $data]);
}

// =============================================================================
// Subscriber — update own business (full replace of mutable fields)
// PUT /api/v1/subscriber/businesses/{id}
// =============================================================================
if ($method === 'PUT' && ($segments[0] ?? '') === 'subscriber' && ($segments[1] ?? '') === 'businesses' && isset($segments[2]) && !isset($segments[3])) {
    require_once __DIR__ . '/lib/business_helpers.php';
    require_once __DIR__ . '/lib/business_service.php';

    $auth    = api_require_auth(['subscriber', 'super_admin', 'co_admin']);
    $pdo     = api_db();
    $groupId = (string)$segments[2];

    // Verify existence + ownership
    $existing = api_business_fetch($pdo, $groupId);
    if (!$existing) {
        api_error('business_not_found', 404);
    }
    if ($auth['role'] === 'subscriber' && (string)($existing['added_by_user_id'] ?? '') !== $auth['user_id']) {
        api_error('forbidden', 403);
    }

    $data = api_request_data();
    $res  = api_business_update($pdo, $groupId, $data, $auth['user_id']);

    if (!$res['ok']) {
        $extra = isset($res['detail']) ? ['detail' => $res['detail']] : [];
        api_error($res['error'], $res['status'] ?? 500, $extra);
    }

    api_json([
        'ok'          => true,
        'id'          => $res['id'],
        'product_ids' => $res['product_ids'] ?? [],
        'service_ids' => $res['service_ids'] ?? [],
    ]);
}

// =============================================================================
// CP — Scraper routes
// All require super_admin or co_admin role.
// =============================================================================
if (($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'scraper') {
    require_once __DIR__ . '/lib/scraper_service.php';

    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo  = api_db();

    // POST /api/v1/cp/scraper/search
    if ($method === 'POST' && !isset($segments[2])) {
        $body   = api_request_data();
        $result = scraper_search($pdo, $body, $auth['user_id']);
        if (!$result['ok']) {
            api_error($result['error'], 422, array_filter(['source_used' => $result['source_used'] ?? null]));
        }
        api_json($result);
    }

    // GET /api/v1/cp/scraper/results
    if ($method === 'GET' && ($segments[2] ?? '') === 'results' && !isset($segments[3])) {
        $filters = [
            'status'   => $_GET['status']   ?? null,
            'source'   => $_GET['source']   ?? null,
            'city'     => $_GET['city']     ?? null,
            'q'        => $_GET['q']        ?? null,
            'page'     => (int)($_GET['page']     ?? 1),
            'per_page' => (int)($_GET['per_page'] ?? 25),
        ];
        api_json(scraper_list($pdo, $filters));
    }

    // GET /api/v1/cp/scraper/results/{id}
    if ($method === 'GET' && ($segments[2] ?? '') === 'results' && isset($segments[3]) && !isset($segments[4])) {
        $row = scraper_get($pdo, (string)$segments[3]);
        if ($row === null) {
            api_error('not_found', 404);
        }
        api_json($row);
    }

    // POST /api/v1/cp/scraper/results/{id}/update
    if ($method === 'POST' && ($segments[2] ?? '') === 'results' && isset($segments[3]) && ($segments[4] ?? '') === 'update') {
        $body    = api_request_data();
        $payload = $body['payload'] ?? $body;
        $ok      = scraper_update_payload($pdo, (string)$segments[3], $payload, $auth['user_id']);
        if (!$ok) {
            api_error('not_found_or_already_processed', 404);
        }
        api_json(['ok' => true]);
    }

    // POST /api/v1/cp/scraper/results/{id}/approve
    if ($method === 'POST' && ($segments[2] ?? '') === 'results' && isset($segments[3]) && ($segments[4] ?? '') === 'approve') {
        $body         = api_request_data();
        $importStatus = in_array($body['status'] ?? 'live', ['live', 'draft'], true) ? $body['status'] : 'live';
        $result       = scraper_approve($pdo, (string)$segments[3], $auth['user_id'], $importStatus);
        if (!$result['ok']) {
            $httpStatus = $result['error'] === 'not_found' ? 404 : 422;
            api_error($result['error'], $httpStatus);
        }
        api_json($result);
    }

    // POST /api/v1/cp/scraper/results/{id}/reject
    if ($method === 'POST' && ($segments[2] ?? '') === 'results' && isset($segments[3]) && ($segments[4] ?? '') === 'reject') {
        $body   = api_request_data();
        $reason = trim((string)($body['reason'] ?? '')) ?: null;
        $ok     = scraper_reject($pdo, (string)$segments[3], $auth['user_id'], $reason);
        if (!$ok) {
            api_error('not_found_or_already_processed', 404);
        }
        api_json(['ok' => true]);
    }

    // GET /api/v1/cp/scraper/counts
    if ($method === 'GET' && ($segments[2] ?? '') === 'counts') {
        api_json(scraper_counts($pdo));
    }

    // GET /api/v1/cp/scraper/usage
    if ($method === 'GET' && ($segments[2] ?? '') === 'usage') {
        api_json(['adapters' => scraper_adapter_status($pdo)]);
    }
}

// =============================================================================
// cp/url-import — URL list & directory crawler import tool
// =============================================================================
if (($segments[0] ?? '') === 'cp' && ($segments[1] ?? '') === 'url-import') {
    require_once __DIR__ . '/lib/url_import_service.php';
    $auth = api_require_auth(['super_admin', 'co_admin']);
    $pdo  = api_db();

    // POST /api/v1/cp/url-import/jobs
    // Body: {mode:'url_list', urls:[...]} or {mode:'crawler', index_url:'...', pattern:'...', limit:20}
    if ($method === 'POST' && ($segments[2] ?? '') === 'jobs' && !isset($segments[3])) {
        $body = api_request_data();
        $mode = trim((string)($body['mode'] ?? ''));
        if (!in_array($mode, ['url_list', 'crawler'], true)) {
            api_error('invalid_mode — must be url_list or crawler', 400);
        }
        if ($mode === 'url_list') {
            $urls = array_values(array_filter(
                array_map('trim', (array)($body['urls'] ?? [])),
                fn($u) => $u !== '' && filter_var($u, FILTER_VALIDATE_URL) !== false
            ));
            if (empty($urls)) {
                api_error('no_valid_urls', 400);
            }
            $config = ['urls' => $urls];
        } else {
            $indexUrl = trim((string)($body['index_url'] ?? ''));
            if ($indexUrl === '' || filter_var($indexUrl, FILTER_VALIDATE_URL) === false) {
                api_error('invalid_index_url', 400);
            }
            $config = [
                'index_url' => $indexUrl,
                'pattern'   => trim((string)($body['pattern'] ?? '')),
                'limit'     => max(1, min(100, (int)($body['limit'] ?? 20))),
            ];
        }

        $jobId = url_import_create_job($pdo, $auth['user_id'], $mode, $config);

        // Read back the JWT from the request so the processor can auth itself
        $jwt = '';
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^\s*Bearer\s+(\S+)/i', $hdr, $m)) {
            $jwt = $m[1];
        }
        url_import_trigger_processor($jobId, $jwt);

        api_json(['ok' => true, 'job_id' => $jobId]);
    }

    // GET /api/v1/cp/url-import/jobs/{id}
    if ($method === 'GET' && ($segments[2] ?? '') === 'jobs' && isset($segments[3]) && !isset($segments[4])) {
        $job = url_import_get_job($pdo, (string)$segments[3]);
        if (!$job) {
            api_error('job_not_found', 404);
        }
        api_json($job);
    }

    // POST /api/v1/cp/url-import/jobs/{id}/process  (internal — fire-and-forget target)
    if ($method === 'POST' && ($segments[2] ?? '') === 'jobs' && isset($segments[3]) && ($segments[4] ?? '') === 'process') {
        $result = url_import_run_job($pdo, (string)$segments[3], $auth['user_id']);
        api_json($result);
    }
}

// =============================================================================
// Locations — public lookup endpoints (no auth)
// GET  /api/v1/locations/countries
// GET  /api/v1/locations/states?country=India
// =============================================================================

if ($method === 'GET' && ($segments[0] ?? '') === 'locations' && ($segments[1] ?? '') === 'countries') {
    require_once __DIR__ . '/lib/location_service.php';
    $pdo = api_db();
    $rows = $pdo->query(
        "SELECT DISTINCT country FROM mci_locations ORDER BY country"
    )->fetchAll(PDO::FETCH_COLUMN);
    api_json(['ok' => true, 'countries' => array_values($rows)]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'locations' && ($segments[1] ?? '') === 'states') {
    require_once __DIR__ . '/lib/location_service.php';
    $country = mb_substr(trim((string)($_GET['country'] ?? '')), 0, 100);
    if ($country === '') {
        api_json(['ok' => true, 'states' => []]);
    }
    $pdo  = api_db();
    $stmt = $pdo->prepare(
        "SELECT DISTINCT state FROM mci_locations
         WHERE country = ? AND state != ''
         ORDER BY state"
    );
    $stmt->execute([$country]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    api_json(['ok' => true, 'states' => array_values($rows)]);
}

if ($method === 'GET' && ($segments[0] ?? '') === 'locations' && ($segments[1] ?? '') === 'cities') {
    require_once __DIR__ . '/lib/location_service.php';
    $country = mb_substr(trim((string)($_GET['country'] ?? '')), 0, 100);
    $state   = mb_substr(trim((string)($_GET['state']   ?? '')), 0, 100);
    if ($country === '') {
        api_json(['ok' => true, 'cities' => []]);
    }
    $pdo    = api_db();
    $sql    = "SELECT DISTINCT city FROM mci_locations WHERE country = ?";
    $params = [$country];
    if ($state !== '') {
        $sql .= " AND state = ?";
        $params[] = $state;
    }
    $sql .= " ORDER BY city";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    api_json(['ok' => true, 'cities' => array_values($rows)]);
}

// =============================================================================
// 404 fallthrough
// =============================================================================
http_response_code(404);
echo json_encode([
    'error' => 'not_found',
    'path' => $uriPath,
    'method' => $method,
], JSON_UNESCAPED_SLASHES);

