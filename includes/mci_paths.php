<?php

declare(strict_types=1);

/**
 * Web path prefix for this app relative to the document root (e.g. "" or "/mycity").
 * Used so fetch('/api/v1/...') works when the site is not hosted at domain root.
 *
 * Override with env MCI_BASE_PATH (e.g. "/mycity") if auto-detection fails.
 */
function mci_app_web_base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $override = getenv('MCI_BASE_PATH');
    if (is_string($override) && $override !== '') {
        $cached = rtrim(str_replace('\\', '/', $override), '/');

        return $cached;
    }

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($docRoot === '') {
        $cached = '';

        return $cached;
    }

    $docRootReal = realpath($docRoot);
    if ($docRootReal === false) {
        $cached = '';

        return $cached;
    }

    // Project root = parent of /includes (this file lives in includes/)
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        $cached = '';

        return $cached;
    }

    $docNorm = str_replace('\\', '/', $docRootReal);
    $projNorm = str_replace('\\', '/', $projectRoot);
    $len = strlen($docNorm);
    if ($len === 0 || strncasecmp($projNorm, $docNorm, $len) !== 0) {
        $cached = '';

        return $cached;
    }

    $rel = substr($projNorm, $len);
    $rel = str_replace('\\', '/', $rel);
    $cached = rtrim($rel, '/');

    return $cached;
}

/** Base URL path for API v1, e.g. "/mycity/api/v1" or "/api/v1". */
function mci_api_v1_base(): string
{
    $base = mci_app_web_base_path();

    return ($base === '' ? '' : $base) . '/api/v1';
}
