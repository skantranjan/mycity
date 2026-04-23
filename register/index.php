<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_auth_messages.php';
require_once __DIR__ . '/../api/v1/lib/auth_direct.php';

$pageTitle = 'Register - My City Info';
$activePage = '';
$returnUrl = mci_safe_return_url();

$authError = null;

// ── Math captcha generation ───────────────────────────────────────────────────
$captchaA = random_int(2, 15);
$captchaB = random_int(1, 10);
$captchaAnswer = $captchaA + $captchaB;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $em = trim((string) ($_POST['email'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password_confirm'] ?? ($_POST['password_confirmation'] ?? ''));
    $captchaInput = (int)($_POST['captcha_answer'] ?? -1);
    $captchaExpected = (int)($_POST['captcha_expected'] ?? -2);

    if ($captchaInput !== $captchaExpected) {
        $authError = 'Incorrect answer to the security question. Please try again.';
    } elseif ($pw === '' || $pw2 === '' || $pw !== $pw2) {
        $authError = 'Passwords do not match.';
    } elseif (empty($_POST['accept_terms']) || empty($_POST['accept_privacy'])) {
        $authError = 'You must accept the Terms of Use and Privacy Policy.';
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        $regData = [
            'email' => $em,
            'password' => $pw,
            'display_name' => trim((string)($_POST['display_name'] ?? '')),
            'accept_terms' => !empty($_POST['accept_terms']),
            'accept_privacy' => !empty($_POST['accept_privacy']),
        ];

        $result = api_direct_subscriber_register($regData);
        if (!empty($result['ok'])) {
            $user = $result['user'] ?? [];
            $userId = (string)($user['id'] ?? '');
            $role = (string)($user['role'] ?? 'subscriber');

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

        $err = (string)($result['error'] ?? 'register_failed');
        $authError = mci_auth_error_message($err);
    }
}

$hideCta = true;
$extraHead = <<<'HTML'
<meta name="robots" content="noindex, follow" />
<link rel="stylesheet" href="/assets/css/auth-pages.css" />
HTML;

ob_start();
?>

<div class="row g-4 g-lg-5 mci-auth-split align-items-stretch">
  <!-- Left: why register — trust & benefits -->
  <div class="col-12 col-lg-6 order-2 order-lg-1">
    <div class="mci-auth-benefits h-100">
      <p class="mci-auth-benefits__kicker mb-0">My City Info</p>
      <h1 class="mci-auth-benefits__title">A free account that works for you - not marketers</h1>
      <p class="mci-auth-benefits__lead">
        Join locals who discover businesses, leave honest feedback, and optionally manage a listing.
        We built registration around respect for your inbox and your time.
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
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
          <div>
            <strong>No spam, no sell-offs</strong>
            <p>We don’t blast promotions or share your email with random “partners.” Account mail is for security and things you asked for - like listing updates or password resets.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-person-fill-slash"></i></span>
          <div>
            <strong>Reviews stay anonymous on the page</strong>
            <p>Your public reviews show as anonymous so you can share feedback without broadcasting your identity to the world.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
          <div>
            <strong>Your data, plainly explained</strong>
            <p>We follow our <a href="/privacy-policy/">Privacy Policy</a> - what we collect, why we use it, and how you can ask questions.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
          <div>
            <strong>List or claim when you’re ready</strong>
            <p>One account can power reviews today and <a href="/submit-business-listing/">listing your business</a> or claiming a page when you choose - no pressure.</p>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <!-- Right: create account -->
  <div class="col-12 col-lg-6 order-1 order-lg-2">
    <div class="card border-0 shadow-sm bg-white mci-auth-form-card h-100">
      <div class="card-body d-flex flex-column">

        <div class="fw-bold fs-4 mb-1">Create account</div>
        <p class="text-muted small mb-3">Create an account with your email and password.</p>

        <div class="alert alert-success small mb-3" role="note">
          <div class="fw-semibold mb-1">FREE plan (default on registration)</div>
          <div>You get full access to all premium business features under the FREE plan till March 31, 2028.</div>
          <div class="mt-1 fw-semibold">Enjoy full features for free till March 2028</div>
        </div>

        <div class="card border mb-3">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div class="fw-semibold">PAID plan</div>
              <span class="badge text-bg-warning">Coming Soon</span>
            </div>
            <div class="text-muted small mt-1">Activation date: April 01, 2028</div>
            <ul class="small mb-0 mt-2 ps-3">
              <li>Unlimited business listings</li>
              <li>Dedicated business profile page</li>
              <li>Accept business enquiries</li>
              <li>Manage products and services</li>
            </ul>
          </div>
        </div>

        <?php if ($authError): ?>
          <div class="alert alert-danger small mb-3" role="alert">
            <?= htmlspecialchars($authError) ?>
          </div>
        <?php endif; ?>

        <form action="/register/" method="post" class="flex-grow-1 d-flex flex-column">
          <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>" />
          <input type="hidden" name="captcha_expected" value="<?= $captchaAnswer ?>" />

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="regDisplayName">Display Name</label>
              <input class="form-control" id="regDisplayName" type="text" name="display_name" placeholder="How you'll appear publicly" required autocomplete="nickname" maxlength="60" />
            </div>
            <div class="col-12">
              <label class="form-label" for="regEmail">Email Address</label>
              <input class="form-control" id="regEmail" type="email" name="email" placeholder="name@example.com" required autocomplete="email" />
            </div>
            <div class="col-12">
              <label class="form-label" for="regPassword">Password</label>
              <input class="form-control" id="regPassword" type="password" name="password" placeholder="Create a password" required autocomplete="new-password" />
            </div>
            <div class="col-12">
              <label class="form-label" for="regPasswordConfirm">Confirm Password</label>
              <input class="form-control" id="regPasswordConfirm" type="password" name="password_confirm" placeholder="Re-enter your password" required autocomplete="new-password" />
            </div>
            <div class="col-12">
              <label class="form-label" for="regCaptcha">
                Security check: what is <?= $captchaA ?> + <?= $captchaB ?>?
              </label>
              <input class="form-control" id="regCaptcha" type="number" name="captcha_answer" placeholder="Your answer" required autocomplete="off" />
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="accept_terms" id="regTerms" required />
                <label class="form-check-label small" for="regTerms">
                  I agree to the <a href="/terms-of-use/" target="_blank" class="text-decoration-none fw-semibold">Terms of Use</a>
                </label>
              </div>
              <div class="form-check mt-1">
                <input class="form-check-input" type="checkbox" name="accept_privacy" id="regPrivacy" required />
                <label class="form-check-label small" for="regPrivacy">
                  I have read the <a href="/privacy-policy/" target="_blank" class="text-decoration-none fw-semibold">Privacy Policy</a>
                </label>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <button class="btn btn-dark w-100" type="submit">Register</button>
          </div>

          <div class="text-center mt-3 pt-2 border-top">
            <span class="text-muted small">Already have an account?</span>
            <a href="/login/?return=<?= rawurlencode($returnUrl) ?>" class="text-decoration-none fw-semibold small ms-1">Login</a>
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
