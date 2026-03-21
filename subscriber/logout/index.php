<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';

if (isset($_GET['perform']) && $_GET['perform'] === '1') {
    unset(
        $_SESSION['mci_user_id'],
        $_SESSION['mci_logged_in'],
        $_SESSION['mci_sub_profile_name'],
        $_SESSION['mci_sub_profile_avatar']
    );
    header('Location: /');
    exit;
}

$pageTitle = 'Logout - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Logout</div>
        <div class="text-muted small mb-3">
          You can also sign out anytime from the profile menu in the top bar.
        </div>

        <div class="alert alert-warning mb-0">
          <div class="fw-semibold">Are you sure?</div>
          <div class="small text-muted mt-1">You will be logged out of your subscriber account.</div>
        </div>

        <div class="d-flex gap-2 mt-3 flex-wrap">
          <a class="btn btn-outline-secondary" href="/subscriber/dashboard/">Cancel</a>
          <a class="btn btn-dark" href="/subscriber/logout/?perform=1">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
