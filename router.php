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

// ── API ──────────────────────────────────────────────────────────────────────
if (str_starts_with($uri, '/api/v1')) {
    require __DIR__ . '/api/v1/index.php';
    return true;
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
    '/cp/users'                  => '/cp/users/index.php',
    '/cp/listings'               => '/cp/listings/index.php',
    '/cp/anonymous-approvals'    => '/cp/anonymous-approvals/index.php',
    '/cp/profile'                => '/cp/profile/index.php',
    '/cp/change-password'        => '/cp/change-password/index.php',
    '/cp/logout'                 => '/cp/logout/index.php',
    '/cp/login'                  => '/cp/login/index.php',
    '/cp/categories'             => '/cp/categories/index.php',
    '/cp/coadmins'               => '/cp/coadmins/index.php',
    '/cp/anonymous-business'     => '/cp/anonymous-business/index.php',
    '/cp/url-import'             => '/cp/url-import/index.php',
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

// ── Not found — let PHP built-in server handle (will show 404) ───────────────
return false;
