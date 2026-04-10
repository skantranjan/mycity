<?php

declare(strict_types=1);

/**
 * Small application cache: APCu when available (typical PHP-FPM), else JSON files under storage/cache/app.
 *
 * Env:
 * - MCI_CACHE_TTL — default TTL in seconds for helpers (default 120)
 * - MCI_CACHE_DISABLE=1 — bypass cache (always compute)
 */

function mci_cache_enabled(): bool
{
    static $out = null;
    if ($out !== null) {
        return $out;
    }
    $raw = getenv('MCI_CACHE_DISABLE');
    if (!is_string($raw) || trim($raw) === '') {
        $raw = (string)($_ENV['MCI_CACHE_DISABLE'] ?? $_SERVER['MCI_CACHE_DISABLE'] ?? '');
    }
    $raw = strtolower(trim($raw));
    $out = !($raw === '1' || $raw === 'true' || $raw === 'yes');

    return $out;
}

function mci_cache_ttl_default(): int
{
    static $ttl = null;
    if ($ttl !== null) {
        return $ttl;
    }
    $raw = getenv('MCI_CACHE_TTL');
    if (!is_string($raw) || trim($raw) === '') {
        $raw = (string)($_ENV['MCI_CACHE_TTL'] ?? $_SERVER['MCI_CACHE_TTL'] ?? '');
    }
    $n = (int)trim($raw);
    $ttl = ($n > 0 && $n <= 86400) ? $n : 120;

    return $ttl;
}

function mci_cache_apcu_available(): bool
{
    if (!function_exists('apcu_fetch') || !function_exists('apcu_store')) {
        return false;
    }
    if (function_exists('apcu_enabled') && !apcu_enabled()) {
        return false;
    }

    return true;
}

function mci_cache_dir(): string
{
    return dirname(__DIR__) . '/storage/cache/app';
}

/** @return array{v: int|null} */
function &mci_cache_public_version_request_cache(): array
{
    static $h = ['v' => null];

    return $h;
}

/**
 * Monotonic counter: bump with mci_cache_invalidate_public() so all public-directory cache keys move to a new namespace.
 */
function mci_cache_public_version_read_uncached(): int
{
    if (mci_cache_apcu_available()) {
        $ok = false;
        $fetched = apcu_fetch('mci1:publicDirectoryVersion', $ok);
        if ($ok && is_numeric($fetched)) {
            return (int)$fetched;
        }
    }
    $path = mci_cache_dir() . '/public_directory_version.txt';
    if (is_readable($path)) {
        $raw = trim((string)file_get_contents($path));
        if ($raw !== '' && ctype_digit($raw)) {
            return (int)$raw;
        }
    }

    return 0;
}

function mci_cache_public_version(): int
{
    $h = &mci_cache_public_version_request_cache();
    if ($h['v'] !== null) {
        return $h['v'];
    }
    $h['v'] = mci_cache_public_version_read_uncached();

    return $h['v'];
}

/**
 * Drop cached public listings, home snapshots, and public API read models (categories/tags/businesses lists).
 * Safe to call after moderation or edits that affect live directory data.
 */
function mci_cache_invalidate_public(): void
{
    if (!mci_cache_enabled()) {
        return;
    }
    $next = mci_cache_public_version_read_uncached() + 1;
    $dir  = mci_cache_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return;
    }
    @file_put_contents($dir . '/public_directory_version.txt', (string)$next, LOCK_EX);
    if (mci_cache_apcu_available()) {
        @apcu_store('mci1:publicDirectoryVersion', $next);
    }
    mci_cache_public_version_request_cache()['v'] = $next;
}

/** Prefix public cache entries so invalidation bumps apply. */
function mci_cache_public_key(string $suffix): string
{
    return 'mci1:pdv' . mci_cache_public_version() . ':' . $suffix;
}

/** Like mci_cache_key_filters but scoped to the public-directory version (invalidation-friendly). */
function mci_cache_key_public_filters(string $logicalPrefix, array $filters): string
{
    $copy = $filters;
    ksort($copy);

    return mci_cache_public_key($logicalPrefix . hash('sha256', json_encode($copy, JSON_UNESCAPED_UNICODE)));
}

function mci_cache_file_path(string $key): string
{
    return mci_cache_dir() . '/' . hash('sha256', $key) . '.json';
}

/**
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function mci_cache_remember(string $key, int $ttlSeconds, callable $callback)
{
    if (!mci_cache_enabled()) {
        return $callback();
    }

    $hit = mci_cache_get($key);
    if ($hit !== null) {
        return $hit;
    }

    $value = $callback();
    mci_cache_set($key, $value, $ttlSeconds);

    return $value;
}

/**
 * @return mixed|null Null on miss; stored null is represented as not used (we only cache arrays).
 */
function mci_cache_get(string $key): mixed
{
    if (!mci_cache_enabled()) {
        return null;
    }

    if (mci_cache_apcu_available()) {
        $ok = false;
        $v  = apcu_fetch($key, $ok);

        return $ok ? $v : null;
    }

    $path = mci_cache_file_path($key);
    if (!is_readable($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return null;
    }
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }
    if (!is_array($decoded) || !array_key_exists('exp', $decoded) || !array_key_exists('data', $decoded)) {
        return null;
    }
    if (time() >= (int)$decoded['exp']) {
        @unlink($path);

        return null;
    }

    return $decoded['data'];
}

function mci_cache_set(string $key, mixed $value, int $ttlSeconds): void
{
    if (!mci_cache_enabled() || $ttlSeconds <= 0) {
        return;
    }

    if (mci_cache_apcu_available()) {
        @apcu_store($key, $value, $ttlSeconds);

        return;
    }

    $dir = mci_cache_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        return;
    }
    $payload = [
        'exp'  => time() + $ttlSeconds,
        'data' => $value,
    ];
    try {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    } catch (Throwable) {
        return;
    }
    $path = mci_cache_file_path($key);
    @file_put_contents($path, $json, LOCK_EX);
}

/** Stable cache key for filter arrays (public API / listing pages). */
function mci_cache_key_filters(string $prefix, array $filters): string
{
    $copy = $filters;
    ksort($copy);

    return $prefix . hash('sha256', json_encode($copy, JSON_UNESCAPED_UNICODE));
}
