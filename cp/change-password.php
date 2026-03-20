<?php
$pageTitle = 'CP Change Password - My City Info';
$activePage = '';
$cpActive = '';
$hideCta = true;
$appArea = 'cp';

$csrfAction = 'cp_change_password';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flashOk = '';
$flashErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $flashOk = 'Password updated (UI demo).';
    }
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Change Password</div>
        <div class="text-muted small mb-4">Update your password (UI demo).</div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="#" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Current password</label>
              <input class="form-control" type="password" name="current_password" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">New password</label>
              <input class="form-control" type="password" name="new_password" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Confirm new password</label>
              <input class="form-control" type="password" name="new_password_confirm" required />
            </div>
          </div>
          <button class="btn btn-dark mt-3" type="submit">Update password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

