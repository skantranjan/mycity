<?php
$pageTitle = 'CP Logout - My City Info';
$activePage = '';
$cpActive = 'logout';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Logout</div>
        <div class="text-muted small mb-3">UI placeholder for clearing admin session.</div>

        <div class="alert alert-warning">
          <div class="fw-semibold">Are you sure?</div>
          <div class="small text-muted mt-1">Backend logout will redirect to the login page.</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-outline-secondary" href="/cp/dashboard.php">Cancel</a>
          <a class="btn btn-dark" href="/">Logout</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

