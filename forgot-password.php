<?php
$pageTitle = 'Forgot Password - My City Info';
$activePage = '';

ob_start();
?>

<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-5">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="text-center mb-4">
          <div class="fw-bold fs-4">Reset password</div>
          <div class="text-muted small">Enter your email address and we will send you a reset link (UI placeholder).</div>
        </div>

        <form action="#" method="post">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" placeholder="name@example.com" required />
          </div>
          <button class="btn btn-dark w-100" type="submit">Send reset link</button>
        </form>

        <div class="text-muted small mt-3 text-center">
          Remembered your password? <a href="/login.php" class="text-decoration-none">Back to login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

