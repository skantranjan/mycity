<?php
$pageTitle = 'Privacy Policy - My City Info';
$activePage = '';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-3">Privacy Policy</h1>
    <p class="text-muted">
      This is placeholder content for the privacy policy. In the backend phase, we will document how data is collected, stored, and used.
    </p>

    <div class="mt-3">
      <h2 class="h6 fw-semibold">What we collect</h2>
      <p class="text-muted small mb-3">
        Example: account email, listing submissions, and contact messages.
      </p>

      <h2 class="h6 fw-semibold">How we use data</h2>
      <p class="text-muted small mb-3">
        Example: to moderate submissions, publish approved listings, and respond to inquiries.
      </p>

      <h2 class="h6 fw-semibold">Your choices</h2>
      <p class="text-muted small mb-0">
        Example: delete your account request and update profile information.
      </p>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

