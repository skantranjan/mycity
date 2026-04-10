<?php

declare(strict_types=1);

require_once __DIR__ . '/slug.php';
require_once __DIR__ . '/auth_cookie.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/image_upload_token.php';

// ---------------------------------------------------------------------------
// Slug helpers
// ---------------------------------------------------------------------------

/**
 * Generate a globally unique slug for mci_business_groups.
 * Uses api_slugify(), then appends -2, -3, … until unique.
 */
function api_business_group_next_unique_slug(PDO $pdo, string $baseName, ?string $excludeId = null): string
{
    $slug = api_slugify($baseName);
    if ($slug === '') {
        $slug = 'business';
    }
    $candidate = $slug;
    $n = 0;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM mci_business_groups WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM mci_business_groups WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $n++;
        $candidate = $slug . '-' . $n;
    }
}

/**
 * Generate a unique slug for mci_business_branches.
 * Pattern: {group-slug}-{city-slug}, then appends -2, -3, … until unique.
 */
function api_business_branch_next_unique_slug(PDO $pdo, string $groupSlug, string $city, ?string $excludeId = null): string
{
    $citySlug = api_slugify($city);
    if ($citySlug === '') {
        $citySlug = 'branch';
    }
    $slug = $groupSlug . '-' . $citySlug;
    $candidate = $slug;
    $n = 0;
    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM mci_business_branches WHERE slug = ? AND id <> ? LIMIT 1');
            $stmt->execute([$candidate, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM mci_business_branches WHERE slug = ? LIMIT 1');
            $stmt->execute([$candidate]);
        }
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $n++;
        $candidate = $slug . '-' . $n;
    }
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

/**
 * Enforce minimum submission requirements:
 *   - group.name must be non-empty
 *   - at least one of branch.phone, branch.email_contact, branch.website must be non-empty
 *
 * Returns an error code string on failure, null on success.
 */
function api_business_validate_minimum(array $data): ?string
{
    $name = trim((string)($data['group']['name'] ?? ''));
    if ($name === '') {
        return 'business_name_required';
    }

    $branch = $data['branch'] ?? [];
    $phone = trim((string)($branch['phone'] ?? ''));
    $email = trim((string)($branch['email_contact'] ?? ''));
    $website = trim((string)($branch['website'] ?? ''));

    if ($phone === '' && $email === '' && $website === '') {
        return 'contact_detail_required';
    }

    return null;
}

// ---------------------------------------------------------------------------
// Context resolution
// ---------------------------------------------------------------------------

/**
 * Resolve submission context to DB values.
 *
 * Returns array{added_by_role: string, added_by_user_id: string, status: string}.
 *
 * Context rules:
 *   cp_admin  → added_by_role=cp_admin,   status=live,  user=auth user UUID
 *   subscriber → added_by_role=subscriber, status=draft, user=auth user UUID
 *   guest      → added_by_role=anonymous,  status=draft, user=nil UUID
 *
 * If $auth is null or role doesn't match expected context, falls back to guest.
 */
function api_business_resolve_context(array $data, ?array $auth): array
{
    $context = (string)($data['context'] ?? 'guest');
    $nilUuid = '00000000-0000-0000-0000-000000000000';

    if ($context === 'cp_admin' && $auth !== null) {
        $role = (string)($auth['role'] ?? '');
        if (in_array($role, ['super_admin', 'co_admin'], true)) {
            return [
                'added_by_role' => 'cp_admin',
                'added_by_user_id' => (string)($auth['user_id'] ?? $nilUuid),
                'status' => 'live',
            ];
        }
    }

    if ($context === 'subscriber' && $auth !== null) {
        $role = (string)($auth['role'] ?? '');
        if ($role === 'subscriber') {
            return [
                'added_by_role' => 'subscriber',
                'added_by_user_id' => (string)($auth['user_id'] ?? $nilUuid),
                'status' => 'draft',
            ];
        }
    }

    // guest (or any unrecognised/mismatched context)
    return [
        'added_by_role' => 'anonymous',
        'added_by_user_id' => $nilUuid,
        'status' => 'draft',
    ];
}

/**
 * Attempt to read the JWT token from the current request without aborting.
 * Returns null if absent or invalid (safe for optional-auth endpoints).
 *
 * @return array{user_id:string, role:string}|null
 */
function api_business_try_auth(): ?array
{
    $token = api_read_auth_token_from_request();
    if ($token === null) {
        return null;
    }
    $payload = api_jwt_verify($token);
    if (!is_array($payload)) {
        return null;
    }
    $userId = (string)($payload['sub'] ?? '');
    $role   = (string)($payload['role'] ?? '');
    if ($userId === '' || $role === '') {
        return null;
    }
    return ['user_id' => $userId, 'role' => $role];
}

/**
 * Authorize POST /upload/image: per-business folder needs upload JWT, admin, or owner; flat path needs authenticated portal user.
 *
 * @param string $businessIdPost Raw POST business_id (may be empty)
 */
function api_assert_can_upload_business_file(PDO $pdo, string $businessIdPost, ?array $auth, ?string $uploadToken): void
{
    $uuidV4Re = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    $bid      = trim($businessIdPost);
    $tok      = $uploadToken !== null ? trim($uploadToken) : '';

    if ($bid !== '' && preg_match($uuidV4Re, $bid) === 1) {
        if ($tok !== '' && api_image_upload_token_verify($tok, $bid)) {
            return;
        }
        if ($auth === null) {
            api_error('unauthorized', 401);
        }
        $role = (string) ($auth['role'] ?? '');
        if (in_array($role, ['super_admin', 'co_admin'], true)) {
            return;
        }
        if ($role === 'subscriber') {
            $st = $pdo->prepare('SELECT added_by_user_id FROM mci_business_groups WHERE id = ? LIMIT 1');
            $st->execute([$bid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && (string) ($row['added_by_user_id'] ?? '') === $auth['user_id']) {
                return;
            }
        }
        api_error('forbidden', 403);
    }

    if ($auth === null || !in_array($auth['role'], ['subscriber', 'super_admin', 'co_admin'], true)) {
        api_error('unauthorized', 401);
    }
}

/**
 * Authorize PATCH /businesses/{id}/images (same rules as upload).
 */
function api_assert_can_patch_business_images(PDO $pdo, string $groupId, ?array $auth, ?string $uploadToken): void
{
    $tok = $uploadToken !== null ? trim($uploadToken) : '';
    if ($tok !== '' && api_image_upload_token_verify($tok, $groupId)) {
        return;
    }
    if ($auth === null) {
        api_error('unauthorized', 401);
    }
    $role = (string) ($auth['role'] ?? '');
    if (in_array($role, ['super_admin', 'co_admin'], true)) {
        return;
    }
    if ($role === 'subscriber') {
        $st = $pdo->prepare('SELECT added_by_user_id FROM mci_business_groups WHERE id = ? LIMIT 1');
        $st->execute([$groupId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row && (string) ($row['added_by_user_id'] ?? '') === $auth['user_id']) {
            return;
        }
    }
    api_error('forbidden', 403);
}
