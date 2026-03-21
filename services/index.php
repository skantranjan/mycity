<?php

declare(strict_types=1);

$pageTitle = 'Services - My City Info';
$activePage = 'services';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Services</h1>
    <p class="text-muted mb-0">
      Find trusted local services—health, trades, hospitality, and more—on My City Info. This page introduces how we help you connect with providers; detailed search and filters will expand as the directory grows.
    </p>
    <div class="row g-3 mt-4">
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Explore services</div>
          <p class="text-muted small mb-3">
            Start from all listings or refine by location and tags.
          </p>
          <a class="btn btn-sm btn-dark" href="/business-listing/">Listed Business</a>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">List your service</div>
          <p class="text-muted small mb-3">
            Add your business so locals can find you.
          </p>
          <a class="btn btn-sm btn-outline-dark" href="/submit-business-listing/">Add Business</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
