<?php
$pageTitle = 'Subscriber Dashboard - My City Info';
$activePage = '';
$subActive = 'dashboard';
$hideCta = true;
$appArea = 'subscriber';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Dashboard</div>
            <div class="text-muted small">Overview of your listing activity (UI demo).</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">List your business</a>
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

        <div class="row g-3 mt-1">
          <div class="col-12 col-lg-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
              <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <div class="fw-semibold">Enquiries snapshot</div>
                <a class="btn btn-sm btn-outline-dark" href="/subscriber/enquiries/">
                  <i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Open enquiries
                </a>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <span class="badge text-bg-light border">New: 2</span>
                <span class="badge text-bg-light border">Awaiting response: 3</span>
                <span class="badge text-bg-light border">Replied: 9</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <div class="border rounded-2 p-2">
                  <div class="small fw-semibold">Ava Thompson <span class="text-muted fw-normal">for JXF Painting Service</span></div>
                  <div class="small text-muted">Do you handle exterior repainting for small shops?</div>
                  <a class="small text-decoration-none" href="/subscriber/enquiries/">Reply now</a>
                </div>
                <div class="border rounded-2 p-2">
                  <div class="small fw-semibold">School Admin Team <span class="text-muted fw-normal">for Locker Shop UK Ltd</span></div>
                  <div class="small text-muted">Need quote for 120 student lockers with delivery timeline.</div>
                  <a class="small text-decoration-none" href="/subscriber/enquiries/">Open and respond</a>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-6">
            <div class="border rounded-3 p-3 h-100 bg-white">
              <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <div class="fw-semibold">Comments &amp; ratings snapshot</div>
                <a class="btn btn-sm btn-outline-dark" href="/subscriber/reviews/">
                  <i class="bi bi-star-half me-1" aria-hidden="true"></i>Open comments
                </a>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
                <span class="badge text-bg-light border">New: 1</span>
                <span class="badge text-bg-light border">Awaiting response: 2</span>
                <span class="badge text-bg-light border">Replied: 7</span>
              </div>
              <div class="d-flex flex-column gap-2">
                <div class="border rounded-2 p-2">
                  <div class="small fw-semibold">Anonymous <span class="text-muted fw-normal">rated 4/5</span></div>
                  <div class="small text-muted">Great service — punctual and professional.</div>
                  <a class="small text-decoration-none" href="/subscriber/reviews/">Reply to comment</a>
                </div>
                <div class="border rounded-2 p-2">
                  <div class="small fw-semibold">Anonymous <span class="text-muted fw-normal">rated 2/5</span></div>
                  <div class="small text-muted">Good result but communication was slow.</div>
                  <a class="small text-decoration-none" href="/subscriber/reviews/">Take action</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="fw-semibold mb-2">Recent activity</div>
          <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white">
              <thead class="table-light">
                <tr>
                  <th>Business</th>
                  <th>Status</th>
                  <th>Last updated</th>
                  <th style="min-width: 220px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $rows = [
                  ['slug' => 'property-852', 'title' => 'Property 852', 'address' => '12 Orchard Lane, Downtown', 'status' => 'Live', 'updated' => '2 days ago'],
                  ['slug' => 'locker-shop-uk', 'title' => 'Locker Shop UK Ltd', 'address' => '88 Market Street, Central District', 'status' => 'Pending', 'updated' => '1 day ago'],
                  ['slug' => 'jxf-painting', 'title' => 'JXF Painting Service', 'address' => '4 Riverside Avenue, West End', 'status' => 'Live', 'updated' => '5 days ago'],
                ];
                foreach ($rows as $r):
                ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($r['address']) ?></div>
                    </td>
                    <td>
                      <span class="badge text-bg-light border"><?= htmlspecialchars($r['status']) ?></span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($r['updated']) ?></td>
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
          <div class="text-muted small mt-2">Backend moderation/listing CRUD will be added later.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

