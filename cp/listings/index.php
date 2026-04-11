<?php
declare(strict_types=1);

$statusFilter = null;
$roleFilter   = null;
$pageBase     = '/cp/listings/';
$cpListingsSkipTableFetch = true;

require_once __DIR__ . '/_load.php';

$pageTitle = 'All Listings — My City Info CP';
$activePage = '';
$cpActive   = 'listings-all';
$hideCta    = true;
$appArea    = 'cp';

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div>
            <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">All businesses</div>
            <div class="text-muted small mt-1">Every listing across all statuses and submission types.</div>
          </div>
          <a href="/cp/listings/draft/" class="btn btn-sm btn-warning d-flex align-items-center gap-1">
            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
            <span>Pending review</span>
            <?php if ($counts['draft'] > 0): ?>
              <span class="badge bg-white text-warning"><?= $counts['draft'] ?></span>
            <?php endif; ?>
          </a>
        </div>
        <?php
          $showStatus = true;
          $showRole   = true;
          $cpListingsClientMode = true;
          include __DIR__ . '/../../views/partials/cp-listings-table.php';
        ?>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
