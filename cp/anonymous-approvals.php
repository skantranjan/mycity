<?php
$pageTitle = 'Anonymous Approvals - My City Info';
$activePage = '';
$cpActive = 'anonymous';

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
            <div class="fw-semibold">Approve Anonymous Listings</div>
            <div class="text-muted small">Approve items submitted anonymously and optionally mark them as open for claim.</div>
          </div>
          <div class="text-muted small">UI demo</div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>Listing</th>
                <th>Category</th>
                <th>Submitted</th>
                <th style="min-width: 320px;">Admin actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rows = [
                ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'submitted' => '2 hours ago'],
                ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'submitted' => 'Yesterday'],
                ['title' => 'City Park Walks', 'category' => 'Park', 'submitted' => '3 days ago'],
              ];
              foreach ($rows as $r):
              ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($r['title']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($r['category']) ?></td>
                  <td class="text-muted small"><?= htmlspecialchars($r['submitted']) ?></td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <button class="btn btn-sm btn-outline-success" type="button" disabled>
                        Approve & make live
                      </button>
                      <button class="btn btn-sm btn-outline-primary" type="button" disabled>
                        Approve & open for claim
                      </button>
                      <button class="btn btn-sm btn-outline-danger" type="button" disabled>
                        Reject
                      </button>
                      <a class="btn btn-sm btn-outline-dark" href="/business.php">Preview</a>
                    </div>
                    <div class="text-muted small mt-1">Backend approval/moderation wiring later.</div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="mt-3 text-muted small">When approved anonymously, listings should become claimable after verification (backend later).</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

