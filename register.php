<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/mci_session.php';

$pageTitle = 'Register - My City Info';
$activePage = '';
$returnUrl = mci_safe_return_url();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['mci_user_id'] = bin2hex(random_bytes(16));
    $_SESSION['mci_logged_in'] = true;
    $em = trim((string) ($_POST['email'] ?? ''));
    if ($em !== '' && empty($_SESSION['mci_sub_profile_name'])) {
        $local = trim(explode('@', $em, 2)[0] ?? '');
        if ($local !== '') {
            $_SESSION['mci_sub_profile_name'] = $local;
        }
    }
    $postedReturn = mci_safe_return_url(trim((string) ($_POST['return'] ?? '')));
    // Default to subscriber dashboard if no specific return URL
    if ($postedReturn === '/index.php') {
        $postedReturn = '/subscriber/dashboard.php';
    }
    header('Location: ' . $postedReturn);
    exit;
}

$hideCta = true;
$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/auth-pages.css" />
HTML;

ob_start();
?>

<div class="row g-4 g-lg-5 mci-auth-split align-items-stretch">
  <!-- Left: why register — trust & benefits -->
  <div class="col-12 col-lg-6 order-2 order-lg-1">
    <div class="mci-auth-benefits h-100">
      <p class="mci-auth-benefits__kicker mb-0">My City Info</p>
      <h1 class="mci-auth-benefits__title">A free account that works for you—not marketers</h1>
      <p class="mci-auth-benefits__lead">
        Join locals who discover businesses, leave honest feedback, and optionally manage a listing.
        We built registration around respect for your inbox and your time.
      </p>

      <figure class="mci-auth-benefits__figure" aria-hidden="true">
        <img
          src="https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&amp;fit=crop&amp;w=800&amp;h=600&amp;q=80"
          alt="People collaborating — local community and trust"
          width="800"
          height="600"
          loading="lazy"
        />
      </figure>

      <ul class="mci-auth-benefits__list">
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-envelope-check"></i></span>
          <div>
            <strong>No spam, no sell-offs</strong>
            <p>We don’t blast promotions or share your email with random “partners.” Account mail is for security and things you asked for—like listing updates or password resets.</p>
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
            <p>We follow our <a href="/privacy-policy.php">Privacy Policy</a>—what we collect, why we use it, and how you can ask questions.</p>
          </div>
        </li>
        <li class="mci-auth-benefits__item">
          <span class="mci-auth-benefits__icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
          <div>
            <strong>List or claim when you’re ready</strong>
            <p>One account can power reviews today and <a href="/submit-listing.php">listing your business</a> or claiming a page when you choose—no pressure.</p>
          </div>
        </li>
      </ul>
    </div>
  </div>

  <!-- Right: create account -->
  <div class="col-12 col-lg-6 order-1 order-lg-2">
    <div class="card border-0 shadow-sm bg-white mci-auth-form-card h-100">
      <div class="card-body d-flex flex-column">
        <div class="mb-4">
          <div class="fw-bold fs-4">Create account</div>
          <div class="text-muted small mt-1">Quick signup. You can also continue with Google or Facebook when we wire them up.</div>
          <div class="alert alert-info small mt-3 mb-0 text-start">
            <strong>Demo:</strong> Submitting creates a session so you can rate and review businesses anonymously on listings.
          </div>
        </div>

        <form action="/register.php" method="post" class="flex-grow-1 d-flex flex-column">
          <input type="hidden" name="return" value="<?= htmlspecialchars($returnUrl) ?>" />
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="regEmail">Email</label>
              <input class="form-control" id="regEmail" type="email" name="email" placeholder="name@example.com" required autocomplete="email" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="regPassword">Password</label>
              <input class="form-control" id="regPassword" type="password" name="password" placeholder="Create a password" required autocomplete="new-password" />
            </div>
            <div class="col-12">
              <label class="form-label" for="regPasswordConfirm">Confirm password</label>
              <input class="form-control" id="regPasswordConfirm" type="password" name="password_confirm" placeholder="Re-enter password" required autocomplete="new-password" />
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-dark w-100" type="submit">Register</button>
          </div>

          <div class="my-3 text-center text-muted small">or</div>

          <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-dark" aria-label="Register with Google">
              Continue with Google
            </button>
            <button type="button" class="btn btn-outline-dark" aria-label="Register with Facebook">
              Continue with Facebook
            </button>
          </div>

          <div class="text-muted small mt-4">
            By creating an account, you agree to our <a href="/terms.php">Terms</a> and <a href="/privacy-policy.php">Privacy Policy</a>.
          </div>

          <div class="text-muted small text-center mt-auto pt-3">
            Already have an account? <a href="/login.php?return=<?= rawurlencode($returnUrl) ?>" class="text-decoration-none fw-semibold">Login</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
