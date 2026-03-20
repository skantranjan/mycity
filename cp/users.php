<?php
$pageTitle = 'Manage Registered Users - My City Info';
$activePage = '';
$cpActive = 'users';
$hideCta = true;
$appArea = 'cp';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Manage Registered Users</div>
            <div class="text-muted small">UI placeholder for user administration.</div>
          </div>
          <button class="btn btn-sm btn-outline-dark" type="button">Add user (demo)</button>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th style="min-width: 240px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $users = [
                ['name' => 'Demo User', 'email' => 'demo@example.com', 'role' => 'Subscriber', 'status' => 'Active'],
                ['name' => 'Jane Admin', 'email' => 'jane@example.com', 'role' => 'CP', 'status' => 'Active'],
                ['name' => 'Sam Pending', 'email' => 'sam@example.com', 'role' => 'Subscriber', 'status' => 'Pending'],
              ];
              foreach ($users as $u):
              ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($u['name']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
                  <td><span class="badge text-bg-light border"><?= htmlspecialchars($u['role']) ?></span></td>
                  <td><span class="badge text-bg-light border"><?= htmlspecialchars($u['status']) ?></span></td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <button class="btn btn-sm btn-outline-dark" type="button" disabled>Edit</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" disabled>Disable</button>
                      <button class="btn btn-sm btn-outline-danger" type="button" disabled>Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 text-muted small">CRUD + pagination wiring comes later.</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

