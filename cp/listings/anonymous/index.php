<?php
declare(strict_types=1);

$statusFilter = null;
$roleFilter   = 'anonymous';
$pageBase     = '/cp/listings/anonymous/';

require_once __DIR__ . '/../_load.php';

$pageTitle = 'Anonymous Postings — My City Info CP';
$activePage = '';
$cpActive   = 'listings-anonymous';
$hideCta    = true;
$appArea    = 'cp';

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge text-bg-light border px-2 py-1" style="font-size:var(--mci-text-xs);">
                <i class="bi bi-incognito me-1" aria-hidden="true"></i>Anonymous
              </span>
              <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Anonymous postings</div>
            </div>
            <div class="text-muted small mt-1">Listings submitted by unregistered / guest users.</div>
          </div>
          <span class="badge text-bg-light border fs-6"><?= $counts['anonymous'] ?> anonymous</span>
        </div>
        <?php
          $showStatus = true;
          $showRole   = false;
          include __DIR__ . '/../../../views/partials/cp-listings-table.php';
        ?>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../../views/layout.php';
?>
