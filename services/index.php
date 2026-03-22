<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_category_icons.php';
require_once __DIR__ . '/../api/v1/lib/db.php';

$pageTitle = 'Services - My City Info';
$activePage = 'services';
$metaDescription = 'Find trusted local services — health, trades, hospitality, and more on My City Info. Search by keyword or category and connect with providers in your city.';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

// ── Active city (from URL param or cookie) ────────────────────────────────────
$activeCity = trim((string)($_GET['location'] ?? ''));
if ($activeCity === '') {
    $activeCity = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}

// ── Load categories that have at least one live business with services ─────────
$serviceCategories = [];
try {
    $pdo  = api_db();
    $stmt = $pdo->query("
        SELECT DISTINCT c.name, c.slug, c.icon
        FROM mci_categories c
        INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
        INNER JOIN mci_business_services s ON s.business_group_id = g.id AND s.is_active = 1
        WHERE c.parent_id IS NULL
        ORDER BY c.sort_order, c.name
    ");
    foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
        $serviceCategories[] = [
            'name' => $row['name'],
            'slug' => $row['slug'],
            'icon' => $row['icon'] ?: mci_category_icon($row['slug']),
        ];
    }
} catch (Throwable $ignored) {}

// Fallback to all live-business categories if no service-specific ones found
if (empty($serviceCategories)) {
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT c.name, c.slug, c.icon
            FROM mci_categories c
            INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
            WHERE c.parent_id IS NULL
            ORDER BY c.sort_order, c.name
            LIMIT 12
        ");
        foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $serviceCategories[] = [
                'name' => $row['name'],
                'slug' => $row['slug'],
                'icon' => $row['icon'] ?: mci_category_icon($row['slug']),
            ];
        }
    } catch (Throwable $ignored) {}
}

ob_start();
?>

<div class="card border-0 shadow-sm bg-white mb-4">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-2">Services</h1>
    <p class="text-muted mb-4">
      Find trusted local services — health, trades, hospitality, and more — on My City Info. Search by keyword or category, send an enquiry, and connect with providers in your city.
    </p>

    <!-- How it works -->
    <div class="fw-semibold mb-3">How it works</div>
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">🔍</div>
          <div>
            <div class="fw-semibold small mb-1">1. Browse</div>
            <p class="text-muted small mb-0">Search by keyword, location, or category. Use tags to narrow down specialists in your area.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">📞</div>
          <div>
            <div class="fw-semibold small mb-1">2. Contact</div>
            <p class="text-muted small mb-0">Send an enquiry from the listing page. Claimed businesses receive your message directly and respond.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">🤝</div>
          <div>
            <div class="fw-semibold small mb-1">3. Connect</div>
            <p class="text-muted small mb-0">Work with a local service provider backed by real ratings and reviews from the community.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Popular service categories -->
    <div class="fw-semibold mb-3 mt-2">Browse by service category</div>
    <div class="row g-2 mb-4">
      <?php foreach ($serviceCategories as $sc): ?>
        <div class="col-6 col-md-4 col-lg-2">
          <?php $catUrl = '/services/' . urlencode($sc['slug']) . '/' . ($activeCity !== '' ? '?location=' . urlencode($activeCity) : ''); ?>
          <a href="<?= htmlspecialchars($catUrl) ?>" class="text-decoration-none">
            <div class="home-category-tile w-100 text-center flex-column gap-1 py-3">
              <span class="home-category-icon"><?= mci_render_category_icon((string)$sc['icon'], '') ?></span>
              <span class="small"><?= htmlspecialchars($sc['name']) ?></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- CTAs -->
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Explore all services</div>
          <p class="text-muted small mb-3">Start from all listings or refine by location, category, and tags.</p>
          <a class="btn btn-sm btn-dark" href="/business-listing/">View all listings</a>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">List your service</div>
          <p class="text-muted small mb-3">Add your business so locals can find you and send enquiries directly.</p>
          <a class="btn btn-sm btn-outline-dark" href="/submit-business-listing/">Add your business</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
