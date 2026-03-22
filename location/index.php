<?php
/**
 * location/index.php — Pretty URL handler for /location/{slug}/
 *
 * Receives the city slug via $_GET['slug'] (injected by .htaccess RewriteRule).
 * City slugs are lowercase, hyphens for spaces (e.g. "new-delhi" → "New Delhi").
 * Pre-sets $_GET['where'] so the business-listing page filters by that city.
 */

$citySlug = trim((string)($_GET['slug'] ?? ''));

if ($citySlug === '') {
    // No slug — redirect to plain listing page showing all
    header('Location: /business-listing/', true, 301);
    exit;
}

// De-slugify: replace hyphens with spaces, title-case
$cityName = ucwords(str_replace('-', ' ', $citySlug));

// Inject location filter so business-listing/index.php picks it up
$_GET['where']     = $cityName;
$_REQUEST['where'] = $cityName;

require __DIR__ . '/../business-listing/index.php';
