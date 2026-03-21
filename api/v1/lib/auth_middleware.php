<?php
declare(strict_types=1);

require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth_cookie.php';
require_once __DIR__ . '/response.php';

/**
 * @return array{user_id:string, role:string}
 */
function api_require_auth(array $allowedRoles): array
{
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
    if ($userId === '' || $role === '') {
        api_error('unauthorized', 401);
    }

    if (!in_array($role, $allowedRoles, true)) {
        api_error('forbidden', 403, ['role' => $role]);
    }

    return ['user_id' => $userId, 'role' => $role];
}

