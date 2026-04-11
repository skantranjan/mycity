<?php
/**
 * CP sidebar partial.
 *
 * Expects:
 *   $cpActive  — one of: dashboard | listings | anonymous | categories | subscribers | coadmins | add-business | error-log | ''
 *   $cpRole    — 'super_admin' | 'co_admin'  (falls back to session)
 */
$cpActive = $cpActive ?? 'dashboard';
$cpRole   = $cpRole   ?? (string) ($_SESSION['mci_cp_role'] ?? 'co_admin');

// ── Real badge counts ────────────────────────────────────
$cpBadgeCounts = ['listings' => 0, 'anonymous' => 0, 'errors' => 0, 'scraper' => 0];

try {
    if (function_exists('api_db')) {
        $pdo = api_db();

        // Pending business listings (draft status)
        $r = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE status = 'draft'");
        $cpBadgeCounts['listings'] = (int) ($r ? $r->fetchColumn() : 0);

        // Error log — entries in last 24 h
        $r = $pdo->query("SELECT COUNT(*) FROM mci_error_log WHERE created_at >= NOW() - INTERVAL 1 DAY");
        $cpBadgeCounts['errors'] = (int) ($r ? $r->fetchColumn() : 0);

        // Scraper — pending review count
        $r = $pdo->query("SELECT COUNT(*) FROM mci_scraped_businesses WHERE status = 'pending_review'");
        $cpBadgeCounts['scraper'] = (int) ($r ? $r->fetchColumn() : 0);
    }
} catch (Throwable $ignored) {
    // DB not ready yet — silently skip badge counts
}

function cpNavLink(string $key, string $href, string $icon, string $label, string $cpActive, ?int $badge = null, string $badgeClass = 'text-bg-warning'): void
{
    $cls = ($cpActive !== '' && $key === $cpActive) ? 'mci-app-nav-link is-active' : 'mci-app-nav-link';
    $iHtml = '<i class="bi ' . htmlspecialchars($icon) . '" aria-hidden="true"></i>';
    $labelHtml = htmlspecialchars($label);

    if ($badge !== null && $badge > 0) {
        echo '<a class="' . $cls . '" href="' . htmlspecialchars($href) . '">';
        echo '  <span class="d-flex align-items-center justify-content-between gap-1 w-100">';
        echo '    <span class="d-flex align-items-center gap-2">' . $iHtml . ' ' . $labelHtml . '</span>';
        echo '    <span class="badge rounded-pill ' . $badgeClass . '" aria-label="' . $badge . ' items">' . $badge . '</span>';
        echo '  </span>';
        echo '</a>';
    } else {
        echo '<a class="' . $cls . '" href="' . htmlspecialchars($href) . '">' . $iHtml . ' ' . $labelHtml . '</a>';
    }
}

// Which cpActive keys belong to the listings sub-section
$listingKeys = ['listings-all','listings-draft','listings-live','listings-rejected','listings-suspended','listings-awaiting','listings-anonymous','listings-admin'];
$listingsOpen = in_array($cpActive, $listingKeys, true);

$isSuperAdmin = ($cpRole === 'super_admin');
?>

<div class="mci-app-sidebar mci-app-area--cp">
  <!-- Header -->
  <div class="mci-app-sidebar__head">
    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
      <div class="mci-app-sidebar__title">
        <?php if ($isSuperAdmin): ?>
          Control panel <span class="mci-app-badge">Super admin</span>
        <?php else: ?>
          Control panel <span class="mci-app-badge" style="background:rgba(124,58,237,0.15);color:#c4b5fd;">Co-admin</span>
        <?php endif; ?>
      </div>
      <i class="bi bi-chevron-down mci-sidebar-toggle-icon" aria-hidden="true"></i>
    </div>
    <div class="mci-app-sidebar__sub mt-1 d-none d-lg-block">Profile &amp; sign-out are in the top bar.</div>
  </div>

  <nav class="mci-app-sidebar__nav" aria-label="Admin navigation">

    <!-- ── Overview ─────────────────────────── -->
    <div class="mci-sidebar-group-label">Overview</div>
    <?php cpNavLink('dashboard', '/cp/dashboard/', 'bi-grid-1x2-fill', 'Dashboard', $cpActive); ?>

    <!-- ── Content ──────────────────────────── -->
    <div class="mci-sidebar-group-label mt-2">Content</div>

    <!-- Businesses expandable group -->
    <div class="mci-sidebar-group <?= $listingsOpen ? 'is-open' : '' ?>">
      <!-- Group header (always links to All Listings) -->
      <a class="mci-app-nav-link mci-sidebar-group__head <?= $listingsOpen ? 'is-active' : '' ?>"
         href="/cp/listings/"
         data-mci-toggle="listings-group"
         aria-expanded="<?= $listingsOpen ? 'true' : 'false' ?>">
        <span class="d-flex align-items-center gap-2 flex-grow-1">
          <i class="bi bi-collection" aria-hidden="true"></i>
          <span>Businesses</span>
        </span>
        <?php if ($cpBadgeCounts['listings'] > 0): ?>
          <span class="badge rounded-pill text-bg-warning"><?= $cpBadgeCounts['listings'] ?></span>
        <?php endif; ?>
        <i class="bi bi-chevron-down mci-sidebar-group__chevron ms-1" aria-hidden="true" style="font-size:0.7rem;transition:transform 0.2s;<?= $listingsOpen ? 'transform:rotate(180deg);' : '' ?>"></i>
      </a>

      <!-- Sub-links -->
      <div class="mci-sidebar-group__body <?= $listingsOpen ? '' : 'd-none' ?>" id="listingsGroup">
        <?php cpNavLink('listings-all',       '/cp/listings/',                  'bi-list-ul',         'All businesses',     $cpActive); ?>
        <?php cpNavLink('listings-awaiting',  '/cp/listings/awaiting-approval/','bi-hourglass-split', 'Awaiting approval',  $cpActive, $cpBadgeCounts['listings'] ?: null); ?>
        <?php cpNavLink('listings-draft',     '/cp/listings/draft/',            'bi-file-earmark',    'Draft / Pending',    $cpActive); ?>
        <?php cpNavLink('listings-live',      '/cp/listings/live/',             'bi-check-circle',    'Live',               $cpActive); ?>
        <?php cpNavLink('listings-rejected',  '/cp/listings/rejected/',         'bi-x-circle',        'Rejected',           $cpActive); ?>
        <?php cpNavLink('listings-suspended', '/cp/listings/suspended/',        'bi-pause-circle',    'Suspended',          $cpActive); ?>
        <?php cpNavLink('listings-anonymous', '/cp/listings/anonymous/',        'bi-incognito',       'Anonymous postings', $cpActive); ?>
        <?php cpNavLink('listings-admin',     '/cp/listings/admin-posted/',     'bi-shield-fill',     'Admin posted',       $cpActive); ?>
      </div>
    </div>

    <?php cpNavLink('categories', '/cp/categories/', 'bi-tags', 'Categories & Tags', $cpActive); ?>

    <!-- ── Users ────────────────────────────── -->
    <div class="mci-sidebar-group-label mt-2">Users</div>
    <?php cpNavLink('subscribers', '/cp/subscribers/', 'bi-people-fill', 'Subscribers', $cpActive); ?>
    <?php if ($isSuperAdmin): ?>
      <?php cpNavLink('coadmins', '/cp/coadmins/', 'bi-shield-lock', 'Co-admins', $cpActive); ?>
    <?php endif; ?>

    <!-- ── Tools ────────────────────────────── -->
    <div class="mci-sidebar-group-label mt-2">Tools</div>
    <?php cpNavLink('add-business', '/cp/anonymous-business/', 'bi-plus-circle', 'Add business', $cpActive); ?>
    <?php cpNavLink('scraper', '/cp/scraper/', 'bi-cloud-download', 'Business scraper', $cpActive, $cpBadgeCounts['scraper'] ?: null); ?>
    <?php cpNavLink('url-import', '/cp/url-import/', 'bi-link-45deg', 'Import from URLs', $cpActive); ?>

    <!-- ── System (super_admin only) ────────── -->
    <?php if ($isSuperAdmin): ?>
      <div class="mci-sidebar-group-label mt-2">System</div>
      <?php cpNavLink('cache-help', '/cp/dashboard/#mciCpPublicCacheCard', 'bi-arrow-clockwise', 'Clear public cache', $cpActive); ?>
      <?php cpNavLink('error-log', '/cp/error-log/', 'bi-bug', 'Error log', $cpActive, $cpBadgeCounts['errors'] ?: null, 'text-bg-danger'); ?>
    <?php endif; ?>

  </nav>

  <div class="mci-app-sidebar__foot">
    My City Info &copy; <?= date('Y') ?> &mdash; Admin panel
  </div>
</div>

<script>
(function () {
  // Mobile sidebar collapse toggle
  var sidebar = document.querySelector('.mci-app-sidebar');
  var sidebarHead = sidebar ? sidebar.querySelector('.mci-app-sidebar__head') : null;
  if (sidebarHead && window.matchMedia('(max-width: 991.98px)').matches) {
    sidebarHead.addEventListener('click', function (e) {
      // Don't fire if a link inside head was clicked
      if (e.target.closest('a')) return;
      sidebar.classList.toggle('mci-sidebar-open');
    });
  }
}());

(function () {
  var head = document.querySelector('[data-mci-toggle="listings-group"]');
  var body = document.getElementById('listingsGroup');
  if (!head || !body) return;

  head.addEventListener('click', function (e) {
    // Only toggle — don't prevent navigation; if already open let the link navigate
    var open = !body.classList.contains('d-none');
    if (open) {
      // collapse without navigating
      e.preventDefault();
      body.classList.add('d-none');
      head.setAttribute('aria-expanded', 'false');
      var chev = head.querySelector('.mci-sidebar-group__chevron');
      if (chev) chev.style.transform = '';
    } else {
      // expand (and navigate to /cp/listings/)
      body.classList.remove('d-none');
      head.setAttribute('aria-expanded', 'true');
      var chev = head.querySelector('.mci-sidebar-group__chevron');
      if (chev) chev.style.transform = 'rotate(180deg)';
    }
  });
}());
</script>
