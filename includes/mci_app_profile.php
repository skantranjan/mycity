<?php

declare(strict_types=1);

/**
 * Demo profile display name + avatar for subscriber / CP app shells (session).
 */
function mci_app_profile_keys(string $appArea): array
{
    if ($appArea === 'cp') {
        return ['name' => 'mci_cp_profile_name', 'avatar' => 'mci_cp_profile_avatar'];
    }

    return ['name' => 'mci_sub_profile_name', 'avatar' => 'mci_sub_profile_avatar'];
}

function mci_app_profile_display_name(string $appArea): string
{
    $k = mci_app_profile_keys($appArea);
    $n = trim((string) ($_SESSION[$k['name']] ?? ''));
    if ($n !== '') {
        return $n;
    }

    return $appArea === 'cp' ? 'Super admin' : 'My account';
}

/** @return non-empty-string|null */
function mci_app_profile_avatar_data_uri(string $appArea): ?string
{
    $k = mci_app_profile_keys($appArea);
    $a = $_SESSION[$k['avatar']] ?? null;
    if (!is_string($a) || strlen($a) < 30) {
        return null;
    }
    if (!str_starts_with($a, 'data:image/')) {
        return null;
    }

    return $a;
}

/** Max raw file size for avatar upload (bytes). */
const MCI_AVATAR_MAX_BYTES = 512_000;

/**
 * Persist profile fields from POST + optional avatar file upload.
 *
 * @return array{ok:bool, error?:string}
 */
function mci_app_profile_save_from_request(string $appArea): array
{
    $keys = mci_app_profile_keys($appArea);

    if (isset($_POST['full_name'])) {
        $_SESSION[$keys['name']] = trim((string) $_POST['full_name']);
    }

    if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
        return ['ok' => true];
    }

    $err = (int) ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true];
    }
    if ($err !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Could not upload photo. Please try again.'];
    }

    $tmp = (string) ($_FILES['avatar']['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $size = (int) ($_FILES['avatar']['size'] ?? 0);
    if ($size < 1 || $size > MCI_AVATAR_MAX_BYTES) {
        return ['ok' => false, 'error' => 'Photo must be under 500 KB.'];
    }

    $info = @getimagesize($tmp);
    if ($info === false || !isset($info['mime'])) {
        return ['ok' => false, 'error' => 'Please upload a JPG, PNG, GIF, or WebP image.'];
    }

    $mime = (string) $info['mime'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'Please upload a JPG, PNG, GIF, or WebP image.'];
    }

    $raw = @file_get_contents($tmp);
    if ($raw === false || $raw === '') {
        return ['ok' => false, 'error' => 'Could not read the uploaded file.'];
    }

    $_SESSION[$keys['avatar']] = 'data:' . $mime . ';base64,' . base64_encode($raw);

    return ['ok' => true];
}

function mci_app_profile_full_name_for_form(string $appArea): string
{
    $k = mci_app_profile_keys($appArea);

    return (string) ($_SESSION[$k['name']] ?? '');
}

/**
 * Optional HTTP(S) image URL from DB profile (session), used when no uploaded avatar data URI exists.
 */
function mci_app_profile_avatar_url_from_session(string $appArea): ?string
{
    $k = $appArea === 'cp' ? 'mci_cp_profile_image_url' : 'mci_sub_profile_image_url';
    $u = trim((string) ($_SESSION[$k] ?? ''));
    if ($u === '') {
        return null;
    }
    if (!preg_match('#^https?://#i', $u)) {
        return null;
    }

    return $u;
}

/**
 * Avatar for header: uploaded data URI wins; otherwise profile image URL from session.
 *
 * @return non-empty-string|null
 */
function mci_app_profile_avatar_for_header(string $appArea): ?string
{
    $data = mci_app_profile_avatar_data_uri($appArea);
    if ($data !== null) {
        return $data;
    }

    return mci_app_profile_avatar_url_from_session($appArea);
}

/**
 * Sync display name + profile image URL into session from mci_account_get_profile_bundle() result.
 *
 * @param array<string, mixed> $bundle
 */
function mci_app_profile_apply_bundle_to_session(string $appArea, array $bundle): void
{
    if (empty($bundle['ok'])) {
        return;
    }
    $keys = mci_app_profile_keys($appArea);
    $u = $bundle['user'] ?? [];
    $p = $bundle['profile'] ?? null;

    $display = trim((string) ($u['display_name'] ?? ''));
    if ($display === '' && is_array($p)) {
        $fn = trim((string) ($p['first_name'] ?? ''));
        $ln = trim((string) ($p['last_name'] ?? ''));
        $display = trim($fn . ' ' . $ln);
    }
    if ($display !== '') {
        $_SESSION[$keys['name']] = $display;
    }

    $imgKey = $appArea === 'cp' ? 'mci_cp_profile_image_url' : 'mci_sub_profile_image_url';
    $url = '';
    if (is_array($p) && isset($p['profile_image'])) {
        $url = trim((string) $p['profile_image']);
    }
    if ($url !== '' && preg_match('#^https?://#i', $url)) {
        $_SESSION[$imgKey] = $url;
    } else {
        unset($_SESSION[$imgKey]);
    }
}
