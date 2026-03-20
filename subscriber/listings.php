<?php
$pageTitle = 'My Listings - My City Info';
$activePage = '';
$subActive = 'listings';
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
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">My Listings</div>
            <div class="text-muted small">See and modify your submitted listings (UI demo).</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">List your business</a>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>Business</th>
                <th>Category</th>
                <th>Status</th>
                <th style="min-width: 240px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $rows = [
                ['slug' => 'property-852', 'title' => 'Property 852', 'address' => '12 Orchard Lane, Downtown', 'category' => 'Real Estate', 'status' => 'Live'],
                ['slug' => 'locker-shop-uk', 'title' => 'Locker Shop UK Ltd', 'address' => '88 Market Street, Central District', 'category' => 'Furniture Store', 'status' => 'Pending'],
                ['slug' => 'jxf-painting', 'title' => 'JXF Painting Service', 'address' => '4 Riverside Avenue, West End', 'category' => 'Painter', 'status' => 'Live'],
                ['slug' => 'hunter-hill-physio', 'title' => 'Hunter Hill Physiotherapy', 'address' => '19 Hillcrest Road, Northside', 'category' => 'Health', 'status' => 'Rejected'],
              ];
              foreach ($rows as $r):
              ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($r['address']) ?></div>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($r['category']) ?></td>
                  <td>
                    <span class="badge text-bg-light border"><?= htmlspecialchars($r['status']) ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <a class="btn btn-sm btn-outline-dark" href="/business/?slug=<?= urlencode((string) $r['slug']) ?>" target="_blank" rel="noopener noreferrer" title="Open business page in a new window">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                      </a>
                      <a class="btn btn-sm btn-outline-secondary" href="/subscriber/list-business/?edit=1&slug=<?= urlencode((string) $r['slug']) ?>">
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                      </a>
                      <a class="btn btn-sm btn-outline-danger" href="/subscriber/listing-delete/?slug=<?= urlencode((string) $r['slug']) ?>&title=<?= urlencode((string) $r['title']) ?>">
                        <i class="bi bi-trash-fill me-1" aria-hidden="true"></i>Delete
                      </a>
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

