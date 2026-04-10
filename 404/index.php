<?php

declare(strict_types=1);

http_response_code(404);

require_once __DIR__ . '/../includes/mci_paths.php';

$pageTitle = 'Page not found - My City Info';
$activePage = '';
$metaDescription = 'The page you requested could not be found. Return home, browse business listings, or submit your business on My City Info.';
$canonicalUrl = rtrim(mci_site_base_url(), '/') . mci_web_path('/404/');
$extraHead = '<meta name="robots" content="noindex, follow" />';

$homeHref = mci_web_path('/');
$listingHref = mci_web_path('/business-listing/');
$submitHref = mci_web_path('/submit-business-listing/');

ob_start();
?>

<div class="card border-0 shadow-sm bg-white mb-4">
  <div class="card-body p-4 p-md-5 text-center">
    <p class="text-muted small text-uppercase fw-semibold mb-2" style="letter-spacing:0.06em;">Error 404</p>
    <h1 class="h3 fw-bold mb-3">Page not found</h1>
    <p class="text-muted mx-auto mb-4" style="max-width:32rem;line-height:1.65;">
      The link may be broken or the page may have been removed. Use the options below to keep exploring My City Info.
    </p>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-center">
      <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-dark mci-touch-target">Home</a>
      <a href="<?= htmlspecialchars($listingHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark mci-touch-target">Browse businesses</a>
      <a href="<?= htmlspecialchars($submitHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary mci-touch-target">Submit your business</a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
