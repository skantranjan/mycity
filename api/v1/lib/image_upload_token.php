<?php

declare(strict_types=1);

require_once __DIR__ . '/jwt.php';

const API_IMAGE_UPLOAD_SCOPE = 'business_image_upload';

function api_image_upload_token_ttl_seconds(): int
{
    return 900;
}

/**
 * Short-lived JWT allowing POST /upload/image and PATCH /businesses/{id}/images for one group (e.g. guest submit flow).
 */
function api_image_upload_token_issue(string $groupId): string
{
    $now = time();

    return api_jwt_sign([
        'scope' => API_IMAGE_UPLOAD_SCOPE,
        'gid'   => $groupId,
        'iat'   => $now,
        'exp'   => $now + api_image_upload_token_ttl_seconds(),
    ]);
}

function api_image_upload_token_verify(string $token, string $groupId): bool
{
    $token = trim($token);
    if ($token === '') {
        return false;
    }
    $payload = api_jwt_verify($token);
    if (!is_array($payload)) {
        return false;
    }
    if (($payload['scope'] ?? '') !== API_IMAGE_UPLOAD_SCOPE) {
        return false;
    }
    if (($payload['gid'] ?? '') !== $groupId) {
        return false;
    }

    return true;
}
