<?php

declare(strict_types=1);

$pageTitle = 'Disclaimer - My City Info';
$activePage = '';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Disclaimer</h1>
    <p class="text-muted">
      This page contains placeholder disclaimer content. We will replace this with your final disclaimer text.
    </p>

    <ul class="text-muted small mb-0">
      <li class="mb-2">Listings are provided by business owners or submitted by users.</li>
      <li class="mb-2">We may moderate listings before publishing.</li>
      <li class="mb-2">We are not responsible for the accuracy of information submitted by third parties.</li>
    </ul>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
