<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';

$pageTitle = 'Control panel login - My City Info';
$activePage = '';
$cpActive = '';
$returnUrl = mci_safe_return_url();
$appArea = 'cp';

$authError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $em = trim((string) ($_POST['email'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    $result = api_direct_cp_login($em, $pw);
    if (!empty($result['ok'])) {
        $user = $result['user'] ?? [];
        $userId = (string)($user['id'] ?? '');
        $role = (string)($user['role'] ?? '');

        $_SESSION['mci_cp_user_id'] = $userId !== '' ? $userId : bin2hex(random_bytes(16));
        $_SESSION['mci_cp_logged_in'] = true;
        $_SESSION['mci_cp_role'] = $role;

        if ($em !== '' && empty($_SESSION['mci_cp_profile_name'])) {
            $local = trim(explode('@', $em, 2)[0] ?? '');
            if ($local !== '') {
                $_SESSION['mci_cp_profile_name'] = $local;
            }
        }

        $token = (string)$result['token'];
        $exp = (int)($result['exp'] ?? (time() + 900));
        setcookie('mci_api_token', $token, [
            'expires' => $exp,
            'path' => '/',
            'secure' => $scheme === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $postedReturn = mci_safe_return_url(trim((string) ($_POST['return'] ?? '')));
        if ($postedReturn === '/index.php' || $postedReturn === '/cp/login/' || $postedReturn === '') {
            $postedReturn = '/cp/dashboard/';
        }
        header('Location: ' . $postedReturn);
        exit;
    }

    $code = (string)($result['error'] ?? 'login_failed');
    $authError = mci_auth_error_message($code);
}

$hideCta = true;
$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/auth-pages.css" />
HTML;

ob_start();
?>

<div class="row g-4 g-lg-5 mci-auth-split align-items-stretch">
  <div class="col-12 col-lg-6 order-2 order-lg-1">
    <div class="mci-auth-benefits h-100">
      <p class="mci-auth-benefits__kicker mb-0">Control panel</p>
      <h1 class="mci-auth-benefits__title">Sign in to moderate listings and manage the directory</h1>
      <p class="mci-auth-benefits__lead">
        Super admin and co-admin accounts use this login. After signing in, your session uses the same API token cookie as other authenticated requests.
      </p>
      <figure class="mci-auth-benefits__figure" aria-hidden="true">
        <img
          src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&amp;fit=crop&amp;w=800&amp;h=600&amp;q=80"
          alt="Team reviewing dashboard on screen"
          width="800"
          height="600"
          loading="lazy"
        />
      </figure>
      <ul class="mci-auth-benefits__list">
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
          <div>
            <strong>Staff only</strong>
            <p>Access is restricted to configured super admin and co-admin roles.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-key"></i></span>
          <div>
            <strong>Secure session</strong>
            <p>Credentials are verified by the API; the browser stores a short-lived token cookie.</p>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <div class="col-12 col-lg-6 order-1 order-lg-2">
    <div class="card border-0 shadow-sm bg-white mci-auth-form-card h-100">
      <div class="card-body d-flex flex-column">
        <?php if ($authError): ?>
          <div class="alert alert-danger small mb-3" role="alert">
            <?= htmlspecialchars($authError) ?>
          </div>
        <?php endif; ?>
        <div class="mb-4">
          <div class="fw-bold fs-4">Control panel sign in</div>
          <div class="text-muted small mt-1">Super admin or co-admin email and password.</div>
          <div class="alert alert-info small mt-3 mb-0 text-start">
            <strong>Dev:</strong> Accounts are seeded by <code>001_create_core_tables.sql</code>; see <code>project_brain/DEV_TEST_ACCOUNTS.md</code>.
          </div>
        </div>

        <form action="/cp/login/" method="post" class="flex-grow-1 d-flex flex-column">
          <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>" />
          <div class="mb-3">
            <label class="form-label" for="cpLoginEmail">Email</label>
            <input class="form-control" id="cpLoginEmail" type="email" name="email" placeholder="admin@example.com" required autocomplete="email" />
          </div>
          <div class="mb-3">
            <label class="form-label" for="cpLoginPassword">Password</label>
            <input class="form-control" id="cpLoginPassword" type="password" name="password" placeholder="Your password" required autocomplete="current-password" />
          </div>

          <button class="btn btn-dark w-100" type="submit">Sign in</button>

          <div class="text-muted small text-center mt-auto pt-3">
            <a href="/" class="text-decoration-none">Back to site</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
