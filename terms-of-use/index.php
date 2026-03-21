<?php

declare(strict_types=1);

$pageTitle = 'Terms of Use - My City Info';
$activePage = '';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Terms of Use</h1>
    <p class="text-muted">
      This is placeholder content for the Terms of Use. We will update it with the exact legal terms for your platform.
    </p>

    <div class="mt-3">
      <h2 class="h6 fw-semibold">Listings</h2>
      <p class="text-muted small mb-3">Example: submissions may require moderation, especially for anonymous postings.</p>

      <h2 class="h6 fw-semibold">Account responsibilities</h2>
      <p class="text-muted small mb-3">Example: keep credentials secure; you are responsible for the content you submit.</p>

      <h2 class="h6 fw-semibold">Limitation of liability</h2>
      <p class="text-muted small mb-0">Example: services are provided as-is, within the limits of applicable law.</p>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
