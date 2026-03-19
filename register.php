<?php
$pageTitle = 'Register - My City Info';
$activePage = '';

ob_start();
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-6">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="fw-bold fs-4">Create account</div>
          <div class="text-muted small">Registration is fast. You can also sign up using Google or Facebook.</div>
        </div>

        <form action="#" method="post">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" placeholder="name@example.com" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Password</label>
              <input class="form-control" type="password" name="password" placeholder="Create a password" required />
            </div>
            <div class="col-12">
              <label class="form-label">Confirm password</label>
              <input class="form-control" type="password" name="password_confirm" placeholder="Re-enter password" required />
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

          <div class="text-muted small mt-2 text-center">
            Already have an account? <a href="/login.php" class="text-decoration-none">Login</a>
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

