<?php
// Subscriber sidebar partial.
// Expects $subActive: dashboard | list-business | listings | leads | favourites | reviews | '' (no highlight).
$subActive = $subActive ?? 'dashboard';

$subPackageLabel = null;
$subPackageStatus = null;
try {
    require_once __DIR__ . '/../../api/v1/lib/db.php';
    require_once __DIR__ . '/../../api/v1/lib/subscription_service.php';
    $subUid = (string) ($_SESSION['mci_user_id'] ?? '');
    if ($subUid !== '') {
        $pdo = api_db();
        $cur = mci_subscription_get_user_current($pdo, $subUid);
        if (!empty($cur['ok']) && is_array($cur['subscription'] ?? null)) {
            $subPackageLabel = (string) (($cur['subscription']['package']['package_name'] ?? ''));
            $subPackageStatus = (string) (($cur['subscription']['package']['effective_status'] ?? 'active'));
        }
    }
} catch (Throwable $ignored) {
    // keep sidebar lean if DB/service is unavailable
}

// Demo notification counts (replace with real DB queries when backend is ready)
$subBadgeCounts = [
    'leads' => 2, // new leads
];

function subLinkClass(string $key, string $subActive): string
{
    return ($subActive !== '' && $key === $subActive) ? 'mci-app-nav-link is-active' : 'mci-app-nav-link';
}
?>

<div class="mci-app-sidebar">
  <div class="mci-app-sidebar__head">
    <div class="d-flex align-items-center justify-content-between gap-2">
      <div class="mci-app-sidebar__title">My account</div>
      <i class="bi bi-chevron-down mci-sidebar-toggle-icon" aria-hidden="true"></i>
    </div>
    <div class="mci-app-sidebar__sub d-none d-lg-block">Manage your listings — use the top bar for profile &amp; logout.</div>
    <?php if ($subPackageLabel !== null && $subPackageLabel !== ''): ?>
      <div class="mt-2 d-none d-lg-flex align-items-center gap-2">
        <span class="badge text-bg-light border"><?= htmlspecialchars($subPackageLabel, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="small text-muted"><?= htmlspecialchars($subPackageStatus ?? 'active', ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>
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
    <a class="<?= subLinkClass('reviews', $subActive) ?>" href="/subscriber/reviews/">
      <i class="bi bi-star-half" aria-hidden="true"></i> Comments &amp; ratings
    </a>
  </nav>
</div>

<script>
(function () {
  var sidebars = document.querySelectorAll('.mci-app-sidebar');
  sidebars.forEach(function (sidebar) {
    var head = sidebar.querySelector('.mci-app-sidebar__head');
    if (head && window.matchMedia('(max-width: 991.98px)').matches) {
      head.addEventListener('click', function (e) {
        if (e.target.closest('a')) return;
        sidebar.classList.toggle('mci-sidebar-open');
      });
    }
  });
}());
</script>
