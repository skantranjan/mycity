<?php
$pageTitle = 'Subscriber Dashboard - My City Info';
$activePage = '';
$subActive = 'dashboard';
$hideCta = true;
$appArea = 'subscriber';

// UI demo data
$demoStats = [
    'total_listings'   => 4,
    'pending_approval' => 1,
    'live'             => 2,
    'total_leads'      => 5,
];

$demoListings = [
    ['slug' => 'property-852',   'title' => 'Property 852',        'address' => '12 Orchard Lane, Downtown',         'status' => 'Live',    'updated' => '2 days ago'],
    ['slug' => 'locker-shop-uk', 'title' => 'Locker Shop UK Ltd',  'address' => '88 Market Street, Central District', 'status' => 'Pending', 'updated' => '1 day ago'],
    ['slug' => 'jxf-painting',   'title' => 'JXF Painting Service','address' => '4 Riverside Avenue, West End',       'status' => 'Live',    'updated' => '5 days ago'],
];

$demoLeads = [
    ['listing' => 'Property 852',        'from' => 'James Harrington', 'when' => '2 hours ago',  'status' => 'New'],
    ['listing' => 'JXF Painting Service','from' => 'Sara Mitchell',    'when' => 'Yesterday',     'status' => 'Contacted'],
    ['listing' => 'Locker Shop UK Ltd',  'from' => 'School Admin',     'when' => '2 days ago',    'status' => 'Converted'],
    ['listing' => 'Property 852',        'from' => 'Linda Farrow',     'when' => '5 days ago',    'status' => 'Closed'],
    ['listing' => 'JXF Painting Service','from' => 'Oliver Brooks',    'when' => '8 days ago',    'status' => 'New'],
];

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
      <div>
        <div class="fw-semibold fs-5">Dashboard</div>
        <div class="text-muted small">Overview of your listing activity.</div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-dark" href="/subscriber/listings/">
          <i class="bi bi-shop-window me-1" aria-hidden="true"></i>My listings
        </a>
        <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">
          <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add business
        </a>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4"><?= $demoStats['total_listings'] ?></div>
            <div class="text-muted small">Total Listings</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-success);"><i class="bi bi-arrow-up-short" aria-hidden="true"></i>1 this month</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/?status=pending" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-warning"><?= $demoStats['pending_approval'] ?></div>
            <div class="text-muted small">Pending Approval</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-warning);">Awaiting review</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/?status=live" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-success"><?= $demoStats['live'] ?></div>
            <div class="text-muted small">Live</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-success);"><i class="bi bi-arrow-up-short" aria-hidden="true"></i>Active &amp; visible</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/leads/" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-primary"><?= $demoStats['total_leads'] ?></div>
            <div class="text-muted small">Total Leads</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-success);"><i class="bi bi-arrow-up-short" aria-hidden="true"></i>2 new this week</div>
          </div>
        </a>
      </div>
    </div>

    <div class="row g-4">

      <!-- My Listings quick-access -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
              <div class="fw-semibold">My Listings</div>
              <a class="btn btn-sm btn-outline-dark" href="/subscriber/listings/">
                <i class="bi bi-shop-window me-1" aria-hidden="true"></i>View all
              </a>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Business</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="min-width:220px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($demoListings as $r): ?>
                    <?php
                    $badgeClass = match($r['status']) {
                        'Live'     => 'text-bg-success',
                        'Pending'  => 'text-bg-warning',
                        'Rejected' => 'text-bg-danger',
                        default    => 'text-bg-light border',
                    };
                    ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($r['address']) ?></div>
                      </td>
                      <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                      <td class="text-muted small"><?= htmlspecialchars($r['updated']) ?></td>
                      <td>
                        <div class="d-flex gap-2 flex-wrap">
                          <a class="btn btn-sm btn-outline-dark" href="/business/?slug=<?= urlencode((string) $r['slug']) ?>" target="_blank" rel="noopener noreferrer" title="View public listing">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i>View <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                          </a>
                          <a class="btn btn-sm btn-outline-secondary" href="/subscriber/listing-edit/?slug=<?= urlencode((string) $r['slug']) ?>">
                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Leads + Quick Actions -->
      <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
              <div class="fw-semibold">Recent Leads</div>
              <a class="btn btn-sm btn-outline-dark" href="/subscriber/leads/">
                <i class="bi bi-person-lines-fill me-1" aria-hidden="true"></i>View all
              </a>
            </div>
            <div class="d-flex flex-column gap-2">
              <?php foreach (array_slice($demoLeads, 0, 5) as $lead):
                $leadBadge = match($lead['status']) {
                    'New'       => 'text-bg-primary',
                    'Contacted' => 'text-bg-warning',
                    'Converted' => 'text-bg-success',
                    'Closed'    => 'text-bg-secondary',
                    default     => 'text-bg-light border',
                };
              ?>
                <div class="d-flex align-items-center justify-content-between gap-2 border rounded-2 p-2">
                  <div>
                    <div class="small fw-semibold"><?= htmlspecialchars($lead['from']) ?>
                      <span class="text-muted fw-normal">· <?= htmlspecialchars($lead['listing']) ?></span>
                    </div>
                    <div class="text-muted small"><?= htmlspecialchars($lead['when']) ?></div>
                  </div>
                  <span class="badge <?= $leadBadge ?> text-nowrap"><?= htmlspecialchars($lead['status']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body p-4">
            <div class="fw-semibold mb-3">Quick Actions</div>
            <div class="row g-2">
              <div class="col-6">
                <a href="/subscriber/list-business/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-deep);color:#fff;min-height:80px;">
                    <i class="bi bi-plus-circle fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">Add Business</span>
                  </div>
                </a>
              </div>
              <div class="col-6">
                <a href="/subscriber/listings/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);min-height:80px;">
                    <i class="bi bi-shop-window fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">My Listings</span>
                  </div>
                </a>
              </div>
              <div class="col-6">
                <a href="/subscriber/leads/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);min-height:80px;">
                    <i class="bi bi-person-lines-fill fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">Leads</span>
                  </div>
                </a>
              </div>
              <div class="col-6">
                <a href="/subscriber/enquiries/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);min-height:80px;">
                    <i class="bi bi-chat-left-text fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">Enquiries</span>
                  </div>
                </a>
              </div>
              <div class="col-6">
                <a href="/subscriber/favourites/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);min-height:80px;">
                    <i class="bi bi-heart-fill fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">Favourites</span>
                  </div>
                </a>
              </div>
              <div class="col-6">
                <a href="/subscriber/profile/" class="text-decoration-none">
                  <div class="d-flex flex-column align-items-center justify-content-center gap-1 p-3 rounded-3 text-center h-100" style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);min-height:80px;">
                    <i class="bi bi-person-circle fs-4" aria-hidden="true"></i>
                    <span class="small fw-semibold">Profile</span>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
