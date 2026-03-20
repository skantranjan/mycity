<?php
$pageTitle = 'Change Password - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Change Password</div>
        <div class="text-muted small mb-4">Update your password (UI demo).</div>

        <form action="#" method="post">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Current password</label>
              <input class="form-control" type="password" name="current_password" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">New password</label>
              <input class="form-control" type="password" name="new_password" required />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Confirm new password</label>
              <input class="form-control" type="password" name="new_password_confirm" required />
            </div>
          </div>
          <button class="btn btn-dark mt-3" type="submit">Update password</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

