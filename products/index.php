<?php

declare(strict_types=1);

$pageTitle = 'Products - My City Info';
$activePage = 'products';
$metaDescription = 'Discover products from local businesses on My City Info. Browse listings and categories to find what you need, or add your business to reach nearby customers.';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

ob_start();
?>

<div class="card border-0 shadow-sm bg-white mb-4">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-2">Products</h1>
    <p class="text-muted mb-4">
      Discover products from local businesses on My City Info. Browse listings and categories to find what you need, or add your business to reach nearby customers.
    </p>

    <!-- How it works -->
    <div class="fw-semibold mb-3">How it works</div>
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">🔍</div>
          <div>
            <div class="fw-semibold small mb-1">1. Browse</div>
            <p class="text-muted small mb-0">Search listings by keyword, category, or location to find businesses that offer the products you need.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">📞</div>
          <div>
            <div class="fw-semibold small mb-1">2. Contact</div>
            <p class="text-muted small mb-0">Send an enquiry directly through the listing page. The business owner will respond to your request.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div class="fs-3" aria-hidden="true">🤝</div>
          <div>
            <div class="fw-semibold small mb-1">3. Connect</div>
            <p class="text-muted small mb-0">Get the product or service you need from a local business you can trust, backed by real reviews.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Popular product categories -->
    <div class="fw-semibold mb-3 mt-2">Browse by product category</div>
    <div class="row g-2 mb-4">
      <?php
      $productCategories = [
        ['name' => 'Furniture', 'slug' => 'furniture-store', 'icon' => '🛋️'],
        ['name' => 'Bakery', 'slug' => 'bakery', 'icon' => '🥐'],
        ['name' => 'Electronics', 'slug' => 'electronics-store', 'icon' => '📱'],
        ['name' => 'Gift Shop', 'slug' => 'gift-shop', 'icon' => '🎁'],
        ['name' => 'Clothing', 'slug' => 'clothing-store', 'icon' => '👗'],
        ['name' => 'Hardware', 'slug' => 'hardware-store', 'icon' => '🔨'],
      ];
      foreach ($productCategories as $pc):
      ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="/business-category/<?= urlencode($pc['slug']) ?>" class="text-decoration-none">
            <div class="home-category-tile w-100 text-center flex-column gap-1 py-3">
              <span class="home-category-icon" aria-hidden="true"><?= $pc['icon'] ?></span>
              <span class="small"><?= htmlspecialchars($pc['name']) ?></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- CTAs -->
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Browse all listings</div>
          <p class="text-muted small mb-3">Explore businesses and what they offer in your area.</p>
          <a class="btn btn-sm btn-dark" href="/business-listing/">View all listings</a>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Browse by category</div>
          <p class="text-muted small mb-3">Filter by business category to narrow your search.</p>
          <a class="btn btn-sm btn-outline-dark" href="/business-category/">Browse categories</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
