<?php

declare(strict_types=1);

$pageTitle = 'About Us - My City Info';
$activePage = '';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">About Us</h1>
    <p class="text-muted">
      My City Info helps people discover local businesses and services. This page is UI-first and will be updated with your final company story.
    </p>

    <div class="row g-3 mt-3">
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">Our mission</div>
          <div class="text-muted small">
            Make it easy to find trusted local services and businesses.
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2">How listings work</div>
          <div class="text-muted small">
            Anonymous submissions go through moderation. Registered users can be approved faster (backend phase).
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
