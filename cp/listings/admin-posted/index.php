<?php
declare(strict_types=1);

$statusFilter = null;
$roleFilter   = 'cp_admin';
$pageBase     = '/cp/listings/admin-posted/';

require_once __DIR__ . '/../_load.php';

$pageTitle = 'Admin / Co-admin Posted — My City Info CP';
$activePage = '';
$cpActive   = 'listings-admin';
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
              <span class="badge text-bg-primary px-2 py-1" style="font-size:var(--mci-text-xs);">
                <i class="bi bi-shield-fill me-1" aria-hidden="true"></i>Admin
              </span>
              <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Admin / Co-admin posted</div>
            </div>
            <div class="text-muted small mt-1">Listings added directly by CP admins or co-admins.</div>
          </div>
          <span class="badge text-bg-primary fs-6"><?= $counts['cp_admin'] ?> by admin</span>
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
