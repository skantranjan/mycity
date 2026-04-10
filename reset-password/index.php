<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_account_bridge.php';
require_once __DIR__ . '/../includes/mci_auth_messages.php';

$pageTitle = 'Set new password - My City Info';
$activePage = '';

$csrfAction = 'reset_password';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$tokenFromQuery = trim((string) ($_GET['token'] ?? ''));

$flashOk = '';
$flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $token = trim((string) ($_POST['token'] ?? ''));
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');
        if ($new !== $confirm) {
            $flashErr = mci_auth_error_message('password_mismatch');
        } else {
            $res = mci_account_reset_password_with_token($token, $new);
            if ($res['ok']) {
                $flashOk = 'Your password was updated. You can sign in now.';
            } else {
                $flashErr = mci_auth_error_message((string) ($res['error'] ?? 'reset_failed'));
            }
        }
    }
}

$hideCta = true;
$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/auth-pages.css" />
HTML;

ob_start();
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-5">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="fw-bold fs-4">Set a new password</div>
          <div class="text-muted small">Choose a new password for your account.</div>
        </div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
          <p class="text-center mb-0"><a href="/login/" class="btn btn-dark">Go to sign in</a></p>
        <?php else: ?>
          <?php if ($flashErr !== ''): ?>
            <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>

          <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
            <input type="hidden" name="token" value="<?= htmlspecialchars($tokenFromQuery !== '' ? $tokenFromQuery : (string) ($_POST['token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
            <div class="mb-3">
              <label class="form-label" for="new_password">New password</label>
              <input class="form-control" id="new_password" type="password" name="new_password" required minlength="8" autocomplete="new-password" />
              <div class="form-text">At least 8 characters.</div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="new_password_confirm">Confirm new password</label>
              <input class="form-control" id="new_password_confirm" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password" />
            </div>
            <button class="btn btn-dark w-100" type="submit">Update password</button>
          </form>

          <div class="text-muted small mt-3 text-center">
            <a href="/login/" class="text-decoration-none">Back to login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
