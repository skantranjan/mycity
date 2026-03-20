<?php
// Subscriber sidebar partial.
// Expects $subActive: dashboard | list-business | listings | enquiries | reviews | '' (no highlight).
$subActive = $subActive ?? 'dashboard';

function subLinkClass(string $key, string $subActive): string
{
    return ($subActive !== '' && $key === $subActive) ? 'mci-app-nav-link is-active' : 'mci-app-nav-link';
}
?>

<div class="mci-app-sidebar">
  <div class="mci-app-sidebar__head">
    <div class="mci-app-sidebar__title">My account</div>
    <div class="mci-app-sidebar__sub">Manage your listings — use the top bar for profile &amp; logout.</div>
  </div>

  <nav class="mci-app-sidebar__nav" aria-label="Subscriber navigation">
    <a class="<?= subLinkClass('dashboard', $subActive) ?>" href="/subscriber/dashboard.php">
      <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
    </a>
    <a class="<?= subLinkClass('list-business', $subActive) ?>" href="/subscriber/list-business.php">
      <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> List business
    </a>
    <a class="<?= subLinkClass('listings', $subActive) ?>" href="/subscriber/listings.php">
      <i class="bi bi-shop-window" aria-hidden="true"></i> My listings
    </a>
    <a class="<?= subLinkClass('enquiries', $subActive) ?>" href="/subscriber/enquiries.php">
      <i class="bi bi-chat-left-text" aria-hidden="true"></i> Enquiries
    </a>
    <a class="<?= subLinkClass('reviews', $subActive) ?>" href="/subscriber/reviews.php">
      <i class="bi bi-star-half" aria-hidden="true"></i> Comments &amp; ratings
    </a>
  </nav>

  <div class="mci-app-sidebar__foot">
    UI placeholders; authentication and listing tools connect when the backend is ready.
  </div>
</div>
