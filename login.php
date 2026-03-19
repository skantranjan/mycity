<?php
$pageTitle = 'Login - My City Info';
$activePage = '';

ob_start();
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-5">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="fw-bold fs-4">Sign in</div>
          <div class="text-muted small">Use your email and password or continue with social login.</div>
        </div>

        <form action="#" method="post">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" placeholder="name@example.com" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" placeholder="Your password" required />
          </div>

          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="rememberMe" name="remember_me" />
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a class="text-decoration-none small" href="/forgot-password.php">Forgot password?</a>
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

          <div class="text-muted small mt-4 text-center">
            Don't have an account? <a href="/register.php" class="text-decoration-none">Register</a>
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

