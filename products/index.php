<?php

declare(strict_types=1);

$pageTitle = 'Products - My City Info';
$activePage = 'products';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Products</h1>
    <p class="text-muted mb-0">
      Discover products from local businesses on My City Info. This landing page is a starting point—browse listings and categories to find what you need, or add your business to reach nearby customers.
    </p>
    <div class="row g-3 mt-4">
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Browse listings</div>
          <p class="text-muted small mb-3">
            Explore businesses and what they offer in your area.
          </p>
          <a class="btn btn-sm btn-dark" href="/business-listing/">Listed Business</a>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">By category</div>
          <p class="text-muted small mb-3">
            Filter by business category to narrow your search.
          </p>
          <a class="btn btn-sm btn-outline-dark" href="/business-category/">Business Categories</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
