<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_account_bridge.php';
require_once __DIR__ . '/../../includes/mci_auth_messages.php';

mci_require_subscriber_session();

$pageTitle = 'Change Password - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

$userId = (string) $_SESSION['mci_user_id'];

$csrfAction = 'subscriber_change_password';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flashOk = '';
$flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        if ($new !== $confirm) {
            $flashErr = mci_auth_error_message('password_mismatch');
        } else {
            $res = mci_account_change_password($userId, $current, $new);
            if ($res['ok']) {
                $flashOk = 'Password updated.';
            } else {
                $flashErr = mci_auth_error_message((string) ($res['error'] ?? 'login_failed'));
            }
        }
    }
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Change Password</div>
        <div class="text-muted small mb-4">Update your account password.</div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="" method="post" autocomplete="off">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="current_password">Current password</label>
              <input class="form-control" id="current_password" type="password" name="current_password" required autocomplete="current-password" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="new_password">New password</label>
              <input class="form-control" id="new_password" type="password" name="new_password" required minlength="8" autocomplete="new-password" />
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="new_password_confirm">Confirm new password</label>
              <input class="form-control" id="new_password_confirm" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password" />
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
include __DIR__ . '/../../views/layout.php';
