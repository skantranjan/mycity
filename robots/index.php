<?php

declare(strict_types=1);

/**
 * Dynamic robots.txt: Sitemap URL matches current host (and MCI_BASE_PATH when set).
 */
require_once __DIR__ . '/../includes/mci_paths.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=86400');

$sitemap = rtrim(mci_site_base_url(), '/') . mci_web_path('/sitemap.xml');

echo <<<TXT
User-agent: *
Allow: /

Disallow: /cp/
Disallow: /subscriber/
Disallow: /api/
Disallow: /logs/
Disallow: /database/
Disallow: /storage/
Disallow: /tools/
Disallow: /migrate.php
Disallow: /listing-preview/
Disallow: /login/
Disallow: /register/
Disallow: /forgot-password/
Disallow: /reset-password/
Disallow: /logout/

Sitemap: {$sitemap}

TXT;
