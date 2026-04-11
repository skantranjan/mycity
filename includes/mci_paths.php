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

/**
 * Web path from site root for HTML href/src, e.g. "/sitemap.xml" or "/mycity/sitemap.xml"
 * when MCI_BASE_PATH is set.
 */
function mci_web_path(string $path): string
{
    $path = $path !== '' && $path[0] === '/' ? $path : '/' . $path;
    $prefix = mci_app_web_base_path();
    if ($prefix === '') {
        return $path;
    }

    return '/' . trim($prefix, '/') . $path;
}

/** Base URL path for API v1, e.g. "/mycity/api/v1" or "/api/v1". */
function mci_api_v1_base(): string
{
    $base = mci_app_web_base_path();

    return ($base === '' ? '' : $base) . '/api/v1';
}

/** Full absolute URL of the current request, e.g. "https://www.mycityinfo.com/business/slug/". */
function mci_current_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

/** Scheme + host only, e.g. "https://www.mycityinfo.com". */
function mci_site_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com';
    return $scheme . '://' . $host;
}

/**
 * Normalize DB upload paths for HTML src/href: trim, make root-relative, apply {@see mci_app_web_base_path}.
 * Empty input returns ''. http(s) URLs are returned unchanged.
 */
function mci_public_media_src(?string $path): string
{
    $path = trim((string)($path ?? ''));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    if ($path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    return mci_web_path($path);
}

/** Web path for a local listing/banner placeholder (no third-party image CDNs). */
function mci_listing_placeholder_url(): string
{
    return mci_web_path('/assets/images/listing-placeholder.svg');
}

/**
 * Listing card image: prefer logo, then banner; use {@see mci_listing_placeholder_url} when both empty.
 * Trims values; whitespace-only paths are treated as missing.
 */
function mci_listing_card_image_url(?string $logoPath, ?string $bannerPath): string
{
    $logo = mci_public_media_src($logoPath);
    if ($logo !== '') {
        return $logo;
    }
    $banner = mci_public_media_src($bannerPath);
    if ($banner !== '') {
        return $banner;
    }

    return mci_listing_placeholder_url();
}

/**
 * Hero-style preview (e.g. business page summary): prefer banner, then logo, then listing placeholder.
 */
function mci_listing_preview_image_url(?string $bannerPath, ?string $logoPath): string
{
    $banner = mci_public_media_src($bannerPath);
    if ($banner !== '') {
        return $banner;
    }
    $logo = mci_public_media_src($logoPath);

    return $logo !== '' ? $logo : mci_listing_placeholder_url();
}

/** Wide hero/banner slot when a business has no banner image. */
function mci_business_banner_placeholder_url(): string
{
    return mci_web_path('/assets/images/business-banner-placeholder.svg');
}

/** Square logo slot placeholder (cards, modals, admin previews). */
function mci_business_logo_placeholder_url(): string
{
    return mci_web_path('/assets/images/business-logo-placeholder.svg');
}

/** Profile / storefront avatar circle when no profile (or logo fallback) image. */
function mci_business_profile_placeholder_url(): string
{
    return mci_web_path('/assets/images/business-profile-placeholder.svg');
}

/**
 * Build an absolute URL for Open Graph / JSON-LD when the value may be a site path (/...) or already absolute.
 */
function mci_absolute_url(string $url): string
{
    if ($url === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }
    $base = rtrim(mci_site_base_url(), '/');
    $path = $url[0] === '/' ? $url : '/' . $url;

    return $base . $path;
}
