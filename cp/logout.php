<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';

if (isset($_GET['perform']) && $_GET['perform'] === '1') {
    unset(
        $_SESSION['mci_cp_profile_name'],
        $_SESSION['mci_cp_profile_avatar']
    );
    header('Location: /index.php');
    exit;
}

$pageTitle = 'CP Logout - My City Info';
$activePage = '';
$cpActive = '';
$hideCta = true;
$appArea = 'cp';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Logout</div>
        <div class="text-muted small mb-3">Sign out from the admin menu in the top bar, or confirm below.</div>

        <div class="alert alert-warning">
          <div class="fw-semibold">Are you sure?</div>
          <div class="small text-muted mt-1">You will leave the control panel session on this browser.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-outline-secondary" href="/cp/dashboard.php">Cancel</a>
          <a class="btn btn-dark" href="/cp/logout.php?perform=1">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
