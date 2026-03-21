<?php
$pageTitle = 'Manage All Listings - My City Info';
$activePage = '';
$cpActive = 'listings';
$hideCta = true;
$appArea = 'cp';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Manage All Listings</div>
            <div class="text-muted small">Moderate, verify, and manage listing lifecycle (UI demo).</div>
          </div>
          <a class="btn btn-sm btn-outline-dark" href="/cp/anonymous-approvals/">Anonymous queue</a>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>Listing</th>
                <th>Category</th>
                <th>Submitted as</th>
                <th>Status</th>
                <th style="min-width: 260px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rows = [
                ['title' => 'Property 852', 'category' => 'Real Estate', 'by' => 'Registered', 'status' => 'Live'],
                ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'by' => 'Anonymous', 'status' => 'Pending approval'],
                ['title' => 'JXF Painting Service', 'category' => 'Painter', 'by' => 'Registered', 'status' => 'Live'],
                ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'by' => 'Anonymous', 'status' => 'Open for claim'],
              ];
              foreach ($rows as $r):
              ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($r['category']) ?></td>
                  <td><span class="badge text-bg-light border"><?= htmlspecialchars($r['by']) ?></span></td>
                  <td><span class="badge text-bg-light border"><?= htmlspecialchars($r['status']) ?></span></td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <a class="btn btn-sm btn-outline-dark" href="/business/?slug=<?= urlencode(strtolower(str_replace(' ', '-', $r['title']))) ?>">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                      </a>
                      <button class="btn btn-sm btn-outline-secondary" type="button" disabled>
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                      </button>
                      <button class="btn btn-sm btn-outline-danger" type="button" disabled>
                        <i class="bi bi-trash-fill me-1" aria-hidden="true"></i>Remove
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 text-muted small">Pagination and advanced filtering will be added later.</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

