<?php

declare(strict_types=1);

/**
 * Router for PHP built-in dev server (Apache .htaccess is NOT applied).
 *
 * From project root (folder that contains api/, cp/, index.php):
 *   php -S localhost:8080 router.php
 *
 * Then open http://localhost:8080/ — /api/v1/* is handled by api/v1/index.php.
 *
 * Mirrors the Apache RewriteRule logic in .htaccess so pretty URLs work
 * in the dev server the same way they do on Apache.
 */

$uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
if (!is_string($uri)) {
    return false;
}

// ── Serve real files (css, js, images, fonts, etc.) as-is ───────────────────
$localFile = __DIR__ . $uri;
if (is_file($localFile)) {
    return false; // let PHP built-in server serve it directly
}

// ── robots.txt (dynamic Sitemap URL) ─────────────────────────────────────────
$uriNorm = rtrim($uri, '/') ?: '/';
if ($uri === '/robots.txt' || $uriNorm === '/robots') {
    require __DIR__ . '/robots/index.php';
    return true;
}

// ── API ──────────────────────────────────────────────────────────────────────
if (str_starts_with($uri, '/api/v1')) {
    require __DIR__ . '/api/v1/index.php';
    return true;
}

if (preg_match('#^/sitemap-(common|businesses|business-categories|products|services|locations|tags)-([1-9][0-9]*)\.xml$#', $uri, $m)) {
    $_GET['kind'] = (string) $m[1];
    $_GET['part'] = (int) $m[2];
    require __DIR__ . '/sitemap/index.php';
    return true;
}
if (preg_match('#^/sitemap-(common|businesses|business-categories|products|services|locations|tags)\.xml$#', $uri, $m)) {
    $_GET['kind'] = (string) $m[1];
    require __DIR__ . '/sitemap/index.php';
    return true;
}
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap/index.php';
    return true;
}

// Browser auto-fetch fallback.
if ($uri === '/favicon.ico') {
    $fallback = __DIR__ . '/assets/images/logo.png';
    if (is_file($fallback)) {
        header('Content-Type: image/png');
        readfile($fallback);
        return true;
    }
}

// ── Pretty slug URL mappings (mirrors .htaccess RewriteRules) ────────────────

// /business/{slug}/ → business/index.php?slug={slug}
if (preg_match('#^/business/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/business/index.php';
    return true;
}

// /tag/{slug}/ → tag/index.php?slug={slug}
if (preg_match('#^/tag/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/tag/index.php';
    return true;
}

// /location/{slug}/ → location/index.php?slug={slug}
if (preg_match('#^/location/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/location/index.php';
    return true;
}

// /products/{slug}/ → products/index.php?slug={slug}
if (preg_match('#^/products/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/products/index.php';
    return true;
}

// /services/{slug}/ → services/index.php?slug={slug}
if (preg_match('#^/services/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/services/index.php';
    return true;
}

// /business-category/{slug}/ → business-category/detail.php?slug={slug}
if (preg_match('#^/business-category/([^/]+)/?$#', $uri, $m)) {
    $_GET['slug'] = urldecode($m[1]);
    require __DIR__ . '/business-category/detail.php';
    return true;
}

// ── Static folder → index.php mappings (mirrors .htaccess pretty URLs) ───────
$folderMap = [
    '/business-listing'          => '/business-listing/index.php',
    '/submit-business-listing'   => '/submit-business-listing/index.php',
    '/register'                  => '/register/index.php',
    '/login'                     => '/login/index.php',
    '/about'                     => '/about/index.php',
    '/404'                       => '/404/index.php',
    '/contact'                   => '/contact/index.php',
    '/privacy-policy'            => '/privacy-policy/index.php',
    '/terms-of-use'              => '/terms-of-use/index.php',
    '/disclaimer'                => '/disclaimer/index.php',
    '/cookies'                   => '/cookies/index.php',
    '/business'                  => '/business/index.php',
    '/forgot-password'           => '/forgot-password/index.php',
    '/reset-password'            => '/reset-password/index.php',
    '/logout'                    => '/logout/index.php',
    '/listing-preview'           => '/listing-preview/index.php',
    '/products'                  => '/products/index.php',
    '/services'                  => '/services/index.php',
    '/business-category'         => '/business-category/index.php',
    '/tag'                       => '/tag/index.php',
    '/location'                  => '/location/index.php',
    '/subscriber/dashboard'      => '/subscriber/dashboard/index.php',
    '/subscriber/list-business'  => '/subscriber/list-business/index.php',
    '/subscriber/listings'       => '/subscriber/listings/index.php',
    '/subscriber/favourites'     => '/subscriber/favourites/index.php',
    '/subscriber/enquiries'      => '/subscriber/enquiries/index.php',
    '/subscriber/reviews'        => '/subscriber/reviews/index.php',
    '/subscriber/profile'        => '/subscriber/profile/index.php',
    '/subscriber/change-password'=> '/subscriber/change-password/index.php',
    '/subscriber/logout'         => '/subscriber/logout/index.php',
    '/subscriber/listing-delete' => '/subscriber/listing-delete/index.php',
    '/cp/dashboard'              => '/cp/dashboard/index.php',
    '/cp/subscribers'            => '/cp/users/index.php',
    '/cp/users'                  => '/cp/users/index.php',
    '/cp/listings/awaiting-approval' => '/cp/listings/awaiting-approval/index.php',
    '/cp/listings/draft'         => '/cp/listings/draft/index.php',
    '/cp/listings/live'          => '/cp/listings/live/index.php',
    '/cp/listings/rejected'      => '/cp/listings/rejected/index.php',
    '/cp/listings/suspended'     => '/cp/listings/suspended/index.php',
    '/cp/listings/anonymous'     => '/cp/listings/anonymous/index.php',
    '/cp/listings/admin-posted'  => '/cp/listings/admin-posted/index.php',
    '/cp/listings'               => '/cp/listings/index.php',
    '/cp/anonymous-approvals'    => '/cp/anonymous-approvals/index.php',
    '/cp/scraper/results'        => '/cp/scraper/results/index.php',
    '/cp/scraper/review'         => '/cp/scraper/review/index.php',
    '/cp/scraper'                => '/cp/scraper/index.php',
    '/cp/url-import'             => '/cp/url-import/index.php',
    '/cp/error-log'              => '/cp/error-log/index.php',
    '/cp/profile'                => '/cp/profile/index.php',
    '/cp/change-password'        => '/cp/change-password/index.php',
    '/cp/logout'                 => '/cp/logout/index.php',
    '/cp/login'                  => '/cp/login/index.php',
    '/cp/categories'             => '/cp/categories/index.php',
    '/cp/coadmins'               => '/cp/coadmins/index.php',
    '/cp/anonymous-business'     => '/cp/anonymous-business/index.php',
    '/cp/subscription-packages'  => '/cp/subscription-packages/index.php',
    '/cp/user-subscriptions'     => '/cp/user-subscriptions/index.php',
];

$uriNormalized = rtrim($uri, '/');
foreach ($folderMap as $prefix => $target) {
    if ($uriNormalized === $prefix || $uri === $prefix . '/') {
        require __DIR__ . $target;
        return true;
    }
}

// ── Root / ───────────────────────────────────────────────────────────────────
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    return true;
}

// ── Unknown path → branded 404 (skip likely static-asset URLs) ───────────────
$pathExt = strtolower(pathinfo($uri, PATHINFO_EXTENSION) ?: '');
$staticExt = ['css', 'js', 'mjs', 'map', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot', 'json', 'xml', 'txt', 'pdf', 'zip', 'webmanifest'];
if ($pathExt !== '' && in_array($pathExt, $staticExt, true)) {
    return false;
}

http_response_code(404);
require __DIR__ . '/404/index.php';
return true;
