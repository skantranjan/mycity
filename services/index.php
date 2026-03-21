<?php

declare(strict_types=1);

$pageTitle = 'Services - My City Info';
$activePage = 'services';
$metaDescription = 'Find trusted local services — health, trades, hospitality, and more on My City Info. Search by keyword or category and connect with providers in your city.';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

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
      <?php
      $serviceCategories = [
        ['name' => 'Health', 'slug' => 'health', 'icon' => '⚕️'],
        ['name' => 'Spa & Wellness', 'slug' => 'spa', 'icon' => '🧖'],
        ['name' => 'Gym & Fitness', 'slug' => 'gym', 'icon' => '💪'],
        ['name' => 'Electrician', 'slug' => 'electrician', 'icon' => '⚡'],
        ['name' => 'Plumber', 'slug' => 'plumber', 'icon' => '🔧'],
        ['name' => 'Automotive', 'slug' => 'automotive', 'icon' => '🚗'],
      ];
      foreach ($serviceCategories as $sc):
      ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/business-category/<?= urlencode($sc['slug']) ?>" class="text-decoration-none">
            <div class="home-category-tile w-100 text-center flex-column gap-1 py-3">
              <span class="home-category-icon" aria-hidden="true"><?= $sc['icon'] ?></span>
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
