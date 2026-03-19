<?php
$pageTitle = 'Profile - My City Info';
$activePage = '';
$subActive = 'profile';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Profile Management</div>
        <div class="text-muted small mb-3">Update your profile details (UI demo).</div>

        <form action="#" method="post">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label">Full name</label>
              <input class="form-control" type="text" name="full_name" value="Demo User" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" value="demo@example.com" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Phone</label>
              <input class="form-control" type="text" name="phone" value="+1 000-000-0000" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">Whatsapp</label>
              <input class="form-control" type="text" name="whatsapp" value="+1 000-000-0000" />
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

