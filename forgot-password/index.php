<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_account_bridge.php';
require_once __DIR__ . '/../includes/mci_env_public.php';

$pageTitle = 'Forgot Password - My City Info';
$activePage = '';

$csrfAction = 'forgot_password';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flashOk = '';
$flashErr = '';
$debugToken = '';
$debugResetUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $out = mci_account_request_password_reset($email);
        $flashOk = (string) ($out['message'] ?? 'If an account exists for that email, password reset instructions will follow.');
        if (mci_env_flag('MCI_DEBUG_PASSWORD_RESET')) {
            $debugToken = (string) ($out['debug_token'] ?? '');
            $debugResetUrl = (string) ($out['debug_reset_url'] ?? '');
        }
    }
}

$hideCta = true;
$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/auth-pages.css" />
HTML;

ob_start();
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-5">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="fw-bold fs-4">Reset password</div>
          <div class="text-muted small">Enter your email address. If an account exists, you can set a new password using the link we provide.</div>
        </div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($debugToken !== '' && $debugResetUrl !== ''): ?>
          <div class="alert alert-warning small mb-3" role="status">
            <strong>Debug only</strong> (<code>MCI_DEBUG_PASSWORD_RESET</code>):<br />
            <a href="<?= htmlspecialchars($debugResetUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($debugResetUrl, ENT_QUOTES, 'UTF-8') ?></a>
            <div class="mt-2 font-monospace text-break"><?= htmlspecialchars($debugToken, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        <?php endif; ?>

        <form action="" method="post">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" type="email" name="email" placeholder="name@example.com" required autocomplete="email" />
          </div>
          <button class="btn btn-dark w-100" type="submit">Send reset link</button>
        </form>

        <div class="text-muted small mt-3 text-center">
          Remembered your password? <a href="/login/" class="text-decoration-none">Back to login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
