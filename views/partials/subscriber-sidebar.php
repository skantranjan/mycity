<?php
// Subscriber sidebar partial.
// Expects $subActive: one of dashboard|listings|profile|change-password|logout (optional).
$subActive = $subActive ?? 'dashboard';

function subLinkClass(string $key, string $subActive): string {
  return $key === $subActive ? 'active fw-semibold' : 'text-muted';
}
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-3">
    <div class="fw-semibold mb-3">Subscriber</div>

    <div class="d-flex flex-column gap-1">
      <a class="btn btn-light text-start <?= subLinkClass('dashboard', $subActive) ?> "
         href="/subscriber/dashboard.php">
        Dashboard
      </a>
      <a class="btn btn-light text-start <?= subLinkClass('listings', $subActive) ?> "
         href="/subscriber/listings.php">
        My Listings
      </a>
      <a class="btn btn-light text-start <?= subLinkClass('profile', $subActive) ?> "
         href="/subscriber/profile.php">
        Profile
      </a>
      <a class="btn btn-light text-start <?= subLinkClass('change-password', $subActive) ?> "
         href="/subscriber/change-password.php">
        Change Password
      </a>
      <a class="btn btn-light text-start <?= subLinkClass('logout', $subActive) ?> "
         href="/subscriber/logout.php">
        Logout
      </a>
    </div>

    <div class="text-muted small mt-3">
      UI placeholders; authentication/CRUD wiring will be added in the backend phase.
    </div>
  </div>
</div>

