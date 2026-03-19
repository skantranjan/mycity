<?php
$pageTitle = 'My Listings - My City Info';
$activePage = '';
$subActive = 'listings';

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
            <div class="fw-semibold">My Listings</div>
            <div class="text-muted small">See and modify your submitted listings (UI demo).</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/submit-listing.php">Add new</a>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th style="min-width: 240px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rows = [
                ['title' => 'Property 852', 'category' => 'Real Estate', 'status' => 'Live'],
                ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'status' => 'Pending'],
                ['title' => 'JXF Painting Service', 'category' => 'Painter', 'status' => 'Live'],
                ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'status' => 'Rejected'],
              ];
              foreach ($rows as $r):
              ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($r['category']) ?></td>
                  <td>
                    <span class="badge text-bg-light border"><?= htmlspecialchars($r['status']) ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <a class="btn btn-sm btn-outline-dark" href="/business.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $r['title']))) ?>">View</a>
                      <a class="btn btn-sm btn-outline-secondary" href="/subscriber/listings.php">Edit</a>
                      <button class="btn btn-sm btn-outline-danger" type="button" disabled>Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex align-items-center justify-content-center gap-2">
          <a class="btn btn-sm btn-outline-secondary disabled" href="#">Prev</a>
          <div class="btn btn-sm btn-outline-dark disabled">1</div>
          <a class="btn btn-sm btn-outline-secondary disabled" href="#">Next</a>
        </div>
        <div class="text-muted small text-center mt-2">Pagination will be wired later.</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

