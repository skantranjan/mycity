<?php
/**
 * tag/index.php — Pretty URL handler for /tag/{slug}/
 *
 * Receives the tag slug via $_GET['slug'] (injected by .htaccess RewriteRule).
 * Pre-sets $_GET['tag'] and $_REQUEST['tag'] so the business-listing page
 * filters by that tag without any redirect overhead.
 */

$tagSlug = trim((string)($_GET['slug'] ?? ''));

if ($tagSlug === '') {
    // No slug — redirect to plain listing page
    header('Location: /business-listing/', true, 301);
    exit;
}

// Inject tag filter so business-listing/index.php picks it up
$_GET['tag']     = $tagSlug;
$_REQUEST['tag'] = $tagSlug;

require __DIR__ . '/../business-listing/index.php';
