<?php
$pageTitle = 'CP Profile - My City Info';
$activePage = '';
$cpActive = 'profile';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Profile Management</div>
        <div class="text-muted small mb-4">Update your admin profile (UI demo).</div>

        <form action="#" method="post">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Full name</label>
              <input class="form-control" type="text" name="full_name" value="Admin User" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" value="admin@example.com" />
            </div>
            <div class="col-12">
              <label class="form-label">Role</label>
              <input class="form-control" type="text" value="Super Admin" disabled />
            </div>
          </div>

          <button class="btn btn-dark mt-3" type="submit">Save changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

