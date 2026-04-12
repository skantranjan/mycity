<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_auth_messages.php';
require_once __DIR__ . '/../api/v1/lib/auth_direct.php';

$pageTitle = 'Login - My City Info';
$activePage = '';
$returnUrl = mci_safe_return_url();

$authError = null;

// OAuth error passed back via redirect
if (!empty($_GET['oauth_error'])) {
    $oauthErrCode = (string)$_GET['oauth_error'];
    $authError = match ($oauthErrCode) {
        'provider_not_configured' => 'Social login is not yet enabled on this server.',
        'invalid_state'           => 'Security check failed. Please try again.',
        'token_exchange_failed'   => 'Could not complete sign-in with the provider. Please try again.',
        'profile_fetch_failed'    => 'Could not retrieve your profile from the provider. Please try again.',
        'email_not_provided'      => 'Your social account did not share an email address. Please register with email instead.',
        'oauth_missing_email'     => 'No email address was returned by the provider. Please register with email.',
        default                   => 'Social login failed. Please try again or use email.',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $em = trim((string) ($_POST['email'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');
    $rememberMe = !empty($_POST['remember_me']);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Same backend as POST /api/v1/auth/login with audience=subscriber
    $result = api_direct_auth_login($em, $pw, 'subscriber', $rememberMe);
    if (!empty($result['ok'])) {
        session_regenerate_id(true);
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

        if ($rememberMe) {
            $sessParams = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + (86400 * 30),
                'path' => $sessParams['path'] !== '' ? $sessParams['path'] : '/',
                'domain' => $sessParams['domain'] !== '' ? $sessParams['domain'] : '',
                'secure' => $scheme === 'https',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

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
<meta name="robots" content="noindex, follow" />
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
          src="/assets/images/hero-illustration.svg"
          alt="Local discovery — stylised city map and buildings illustration for My City Info"
          width="640"
          height="420"
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
            <strong>Favourites</strong>
            <p>Bookmark places you love or might try later from any business page while you are signed in.</p>
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

        <div class="fw-bold fs-4 mb-1">Sign in</div>
        <p class="text-muted small mb-3">Sign in with your email and password.</p>

        <?php if ($authError): ?>
          <div class="alert alert-danger small mb-3" role="alert">
            <?= htmlspecialchars($authError) ?>
          </div>
        <?php endif; ?>

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
              <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me" aria-describedby="rememberMeHint" />
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a class="text-decoration-none small" href="/forgot-password/">Forgot password?</a>
          </div>
          <p class="text-muted small mb-3" id="rememberMeHint">When checked, stays signed in for up to 30 days on this device.</p>

          <button class="btn btn-dark w-100" type="submit">Login</button>

          <div class="text-center mt-3 pt-2 border-top">
            <span class="text-muted small">Don't have an account?</span>
            <a href="/register/?return=<?= rawurlencode($returnUrl) ?>" class="text-decoration-none fw-semibold small ms-1">Register</a>
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
