<?php
$pageTitle = 'Subscriber Dashboard - My City Info';
$activePage = '';
$subActive = 'dashboard';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Dashboard</div>
            <div class="text-muted small">Overview of your listing activity (UI demo).</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/submit-listing.php">Add new listing</a>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Total listings</div>
              <div class="fs-4 fw-bold">12</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Pending approval</div>
              <div class="fs-4 fw-bold">2</div>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="bg-light border rounded-3 p-3 h-100">
              <div class="text-muted small">Live listings</div>
              <div class="fs-4 fw-bold">9</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="fw-semibold mb-2">Recent activity</div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white">
              <thead class="table-light">
                <tr>
                  <th>Listing</th>
                  <th>Status</th>
                  <th>Last updated</th>
                  <th style="min-width: 220px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $rows = [
                  ['title' => 'Property 852', 'status' => 'Live', 'updated' => '2 days ago'],
                  ['title' => 'Locker Shop UK Ltd', 'status' => 'Pending', 'updated' => '1 day ago'],
                  ['title' => 'JXF Painting Service', 'status' => 'Live', 'updated' => '5 days ago'],
                ];
                foreach ($rows as $r):
                ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                    <td>
                      <span class="badge text-bg-light border"><?= htmlspecialchars($r['status']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($r['updated']) ?></td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-dark" href="/subscriber/listings.php">View</a>
                        <a class="btn btn-sm btn-outline-secondary" href="/subscriber/listings.php">Edit</a>
                        <button class="btn btn-sm btn-outline-danger" type="button" disabled>Delete</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="text-muted small mt-2">Backend moderation/listing CRUD will be added later.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

