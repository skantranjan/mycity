<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_auth_messages.php';
require_once __DIR__ . '/../api/v1/lib/auth_direct.php';

$pageTitle = 'Login - My City Info';
$activePage = '';
$returnUrl = mci_safe_return_url();

$authError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $em = trim((string) ($_POST['email'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Same backend as POST /api/v1/auth/login with audience=subscriber
    $result = api_direct_auth_login($em, $pw, 'subscriber');
    if (!empty($result['ok'])) {
        $user = $result['user'] ?? [];
        $userId = (string)($user['id'] ?? '');
        $role = (string)($user['role'] ?? '');

        $_SESSION['mci_user_id'] = $userId !== '' ? $userId : bin2hex(random_bytes(16));
        $_SESSION['mci_logged_in'] = true;
        $_SESSION['mci_role'] = $role;

        if ($em !== '' && empty($_SESSION['mci_sub_profile_name'])) {
            $local = trim(explode('@', $em, 2)[0] ?? '');
            if ($local !== '') {
                $_SESSION['mci_sub_profile_name'] = $local;
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
        if ($postedReturn === '/index.php') {
            $postedReturn = '/subscriber/dashboard/';
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
  <!-- Left: why sign in — access & features -->
  <div class="col-12 col-lg-6 order-2 order-lg-1">
    <div class="mci-auth-benefits h-100">
      <p class="mci-auth-benefits__kicker mb-0">My City Info</p>
      <h1 class="mci-auth-benefits__title">Welcome back - sign in to pick up where you left off</h1>
      <p class="mci-auth-benefits__lead">
        Your account keeps your reviews, saved searches, and claimed listings in one place.
        Sign in to rate businesses, manage your profile, and discover what's new in your neighbourhood.
      </p>

      <figure class="mci-auth-benefits__figure" aria-hidden="true">
        <img
          src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&amp;fit=crop&amp;w=800&amp;h=600&amp;q=80"
          alt="People using laptop and mobile devices - easy access"
          width="800"
          height="600"
          loading="lazy"
        />
      </figure>

      <ul class="mci-auth-benefits__list">
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
          <div>
            <strong>Rate and review anonymously</strong>
            <p>Share your experience on any business page. Your reviews appear anonymously to the public, but you can track them in your dashboard.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-bookmark-star"></i></span>
          <div>
            <strong>Favourites & saved searches</strong>
            <p>Bookmark places you love or might try later. When we add watchlists, you'll get alerts when saved businesses update their hours or add photos.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
          <div>
            <strong>Manage your listing</strong>
            <p>If you've claimed a business, sign in to update details, reply to reviews, and upload fresh photos - all from your subscriber dashboard.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-lightning"></i></span>
          <div>
            <strong>One login, all devices</strong>
            <p>Use the same credentials on desktop, tablet, and mobile - your session stays secure and your data syncs across devices.</p>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <!-- Right: sign in form -->
  <div class="col-12 col-lg-6 order-1 order-lg-2">
    <div class="card border-0 shadow-sm bg-white mci-auth-form-card h-100">
      <div class="card-body d-flex flex-column">
            <?php if ($authError): ?>
              <div class="alert alert-danger small mb-3" role="alert">
                <?= htmlspecialchars($authError) ?>
              </div>
            <?php endif; ?>
        <div class="mb-4">
          <div class="fw-bold fs-4">Sign in</div>
        </div>

        <form action="/login/" method="post" class="flex-grow-1 d-flex flex-column">
          <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>" />
          <div class="mb-3">
            <label class="form-label" for="loginEmail">Email</label>
            <input class="form-control" id="loginEmail" type="email" name="email" placeholder="name@example.com" required autocomplete="email" />
          </div>
          <div class="mb-3">
            <label class="form-label" for="loginPassword">Password</label>
            <input class="form-control" id="loginPassword" type="password" name="password" placeholder="Your password" required autocomplete="current-password" />
          </div>

          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me" />
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a class="text-decoration-none small" href="/forgot-password/">Forgot password?</a>
          </div>

          <button class="btn btn-dark w-100" type="submit">Login</button>

          <div class="my-3 text-center text-muted small">or</div>

          <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-dark" aria-label="Login with Google">
              Continue with Google
            </button>
            <button type="button" class="btn btn-outline-dark" aria-label="Login with Facebook">
              Continue with Facebook
            </button>
          </div>

          <div class="text-center mt-3 mb-1">
            <span class="badge rounded-pill px-3 py-2" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);font-size:var(--mci-text-xs);font-weight:700;border:1px solid rgba(124,58,237,0.2);">
              <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Trusted by thousands of local businesses
            </span>
          </div>
          <div class="text-muted small text-center mt-auto pt-3">
            Don't have an account? <a href="/register/?return=<?= rawurlencode($returnUrl) ?>" class="text-decoration-none fw-semibold">Register</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
