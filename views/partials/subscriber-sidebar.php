<?php
// Subscriber sidebar partial.
// Expects $subActive: dashboard | list-business | listings | leads | favourites | enquiries | reviews | '' (no highlight).
$subActive = $subActive ?? 'dashboard';

// Demo notification counts (replace with real DB queries when backend is ready)
$subBadgeCounts = [
    'leads'     => 2, // new leads
    'enquiries' => 1, // unread enquiries
];

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
    <a class="<?= subLinkClass('dashboard', $subActive) ?>" href="/subscriber/dashboard/">
      <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
    </a>
    <a class="<?= subLinkClass('list-business', $subActive) ?>" href="/subscriber/list-business/">
      <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> List your business
    </a>
    <a class="<?= subLinkClass('listings', $subActive) ?>" href="/subscriber/listings/">
      <i class="bi bi-shop-window" aria-hidden="true"></i> My listings
    </a>
    <a class="<?= subLinkClass('favourites', $subActive) ?>" href="/subscriber/favourites/">
      <i class="bi bi-heart-fill" aria-hidden="true"></i> Favourites
    </a>
    <a class="<?= subLinkClass('leads', $subActive) ?>" href="/subscriber/leads/">
      <span class="d-flex align-items-center justify-content-between gap-1 w-100">
        <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i> Leads</span>
        <?php if ($subBadgeCounts['leads'] > 0): ?>
          <span class="badge rounded-pill text-bg-danger" aria-label="<?= $subBadgeCounts['leads'] ?> new leads"><?= $subBadgeCounts['leads'] ?></span>
        <?php endif; ?>
      </span>
    </a>
    <a class="<?= subLinkClass('enquiries', $subActive) ?>" href="/subscriber/enquiries/">
      <span class="d-flex align-items-center justify-content-between gap-1 w-100">
        <span><i class="bi bi-chat-left-text" aria-hidden="true"></i> Enquiries</span>
        <?php if ($subBadgeCounts['enquiries'] > 0): ?>
          <span class="badge rounded-pill text-bg-danger" aria-label="<?= $subBadgeCounts['enquiries'] ?> new enquiries"><?= $subBadgeCounts['enquiries'] ?></span>
        <?php endif; ?>
      </span>
    </a>
    <a class="<?= subLinkClass('reviews', $subActive) ?>" href="/subscriber/reviews/">
      <i class="bi bi-star-half" aria-hidden="true"></i> Comments &amp; ratings
    </a>
  </nav>
</div>
