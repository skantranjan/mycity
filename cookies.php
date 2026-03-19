<?php
$pageTitle = 'Cookies - My City Info';
$activePage = '';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Cookies</h1>
    <p class="text-muted">
      This is placeholder cookie policy content. In the backend phase, we can add a cookie consent banner and document cookie usage.
    </p>
    <h2 class="h6 fw-semibold mt-3">Types of cookies</h2>
    <ul class="text-muted small">
      <li class="mb-2">Essential cookies (required for basic site functionality)</li>
      <li class="mb-2">Analytics cookies (help us understand usage)</li>
      <li>Preference cookies (remember user settings)</li>
    </ul>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

