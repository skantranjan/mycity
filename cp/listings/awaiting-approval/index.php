<?php
declare(strict_types=1);

// Awaiting approval = draft status submitted by non-admin roles
$statusFilter = 'draft';
$roleFilter   = null;   // draft from any role; admins can filter further
$pageBase     = '/cp/listings/awaiting-approval/';

require_once __DIR__ . '/../_load.php';

$pageTitle = 'Awaiting Approval — My City Info CP';
$activePage = '';
$cpActive   = 'listings-awaiting';
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
              <span class="badge text-bg-warning px-2 py-1" style="font-size:var(--mci-text-xs);">Action needed</span>
              <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Awaiting approval</div>
            </div>
            <div class="text-muted small mt-1">All draft listings waiting for an admin to approve or reject.</div>
          </div>
          <?php if ($counts['draft'] > 0): ?>
            <span class="badge text-bg-warning fs-6"><?= $counts['draft'] ?> need action</span>
          <?php endif; ?>
        </div>
        <?php
          $showStatus = false;
          $showRole   = true;
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
