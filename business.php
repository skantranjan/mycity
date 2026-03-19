<?php
$pageTitle = 'Business Details - My City Info';
$activePage = 'listings';

$slug = trim((string)($_GET['slug'] ?? ''));

$catalog = [
  'property-852' => [
    'title' => 'Property 852',
    'category' => 'Real Estate',
    'location' => 'Units 1205-1208, Level 12, Cyberport 2, 100 Cyberport Road, Hong Kong',
    'phone' => '+852 0000 0000',
    'whatsapp' => '+852 0000 0000',
    'website' => 'https://example.com',
    'gallery' => ['1','2','3','4','5','6'],
  ],
  'locker-shop-uk' => [
    'title' => 'Locker Shop UK Ltd',
    'category' => 'Furniture Store',
    'location' => 'Regus - Chester Business Park, Heronsway, Chester, UK',
    'phone' => '+44 0000 0000',
    'whatsapp' => '+44 0000 0000',
    'website' => 'https://example.com',
    'gallery' => ['1','2','3','4','5','6'],
  ],
  'jxf-painting' => [
    'title' => 'JXF Painting Service',
    'category' => 'Painter',
    'location' => '33 Bond Ave. Toronto, Ontario M3B 3R3',
    'phone' => '+1 000-000-0000',
    'whatsapp' => '+1 000-000-0000',
    'website' => 'https://example.com',
    'gallery' => ['1','2','3','4','5','6'],
  ],
  'hunter-hill-physio' => [
    'title' => 'Hunter Hill Physiotherapy',
    'category' => 'Health',
    'location' => 'Hunters Hill NSW 2110, Australia',
    'phone' => '+61 0000 0000',
    'whatsapp' => '+61 0000 0000',
    'website' => 'https://example.com',
    'gallery' => ['1','2','3','4','5','6'],
  ],
  'famous-veg-restaurant-bhopal' => [
    'title' => 'Famous Veg Restaurant In Bhopal | Naveen',
    'category' => 'Restaurant',
    'location' => '123 jyoti shopping complex Zone 1 , M P Nagar 462023',
    'phone' => '+91 00000 00000',
    'whatsapp' => '+91 00000 00000',
    'website' => 'https://example.com',
    'gallery' => ['1','2','3','4','5','6'],
  ],
];

$listing = $catalog[$slug] ?? [
  'title' => 'Business Listing',
  'category' => 'Services',
  'location' => 'Address not available',
  'phone' => '',
  'whatsapp' => '',
  'website' => '',
  'gallery' => ['1','2','3','4'],
];

$pageTitle = $listing['title'] . ' - My City Info';

$isLoggedIn = false; // UI-only placeholder; later wire to session/auth.

ob_start();
?>

<div class="row g-4">
  <!-- Hero -->
  <div class="col-12">
    <div class="card border-0 shadow-sm overflow-hidden">
      <div class="p-4 bg-white">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div class="d-flex align-items-start gap-3">
            <div class="rounded-4 border bg-light d-flex align-items-center justify-content-center" style="width:84px;height:84px;">
              <div class="fw-bold text-muted">LOGO</div>
            </div>
            <div>
              <div class="text-uppercase small text-muted"><?= htmlspecialchars($listing['category']) ?></div>
              <div class="h4 fw-bold"><?= htmlspecialchars($listing['title']) ?></div>
              <div class="text-muted small mt-2"><?= htmlspecialchars($listing['location']) ?></div>
            </div>
          </div>

          <div class="d-flex flex-column gap-2" style="min-width: 240px;">
            <?php if ($isLoggedIn): ?>
              <button class="btn btn-dark w-100" type="button" data-bs-toggle="modal" data-bs-target="#claimModal">
                Claim this listing
              </button>
              <div class="text-muted small text-center">You are logged in (demo).</div>
            <?php else: ?>
              <button class="btn btn-dark w-100" type="button" data-bs-toggle="modal" data-bs-target="#claimModal">
                Claim this listing
              </button>
              <div class="text-muted small text-center">
                Login/register is required to claim.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="fw-semibold mb-3">About</div>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <div class="bg-light rounded-3 p-3">
              <div class="text-muted small">Phone</div>
              <div class="fw-semibold"><?= htmlspecialchars($listing['phone'] ?: 'Not provided') ?></div>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="bg-light rounded-3 p-3">
              <div class="text-muted small">WhatsApp</div>
              <div class="fw-semibold"><?= htmlspecialchars($listing['whatsapp'] ?: 'Not provided') ?></div>
            </div>
          </div>

          <div class="col-12">
            <div class="bg-light rounded-3 p-3">
              <div class="text-muted small">Website</div>
              <div class="fw-semibold">
                <?php if (!empty($listing['website'])): ?>
                  <a class="text-decoration-none" href="<?= htmlspecialchars($listing['website']) ?>" target="_blank" rel="noreferrer">
                    <?= htmlspecialchars($listing['website']) ?>
                  </a>
                <?php else: ?>
                  Not provided
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="fw-semibold mt-3 mb-2">Services</div>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $serviceChips = ['Quality service', 'Verified listing', 'Customer support', 'Affordable pricing'];
              foreach ($serviceChips as $chip):
              ?>
                <span class="badge text-bg-light border"><?= htmlspecialchars($chip) ?></span>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="col-12">
            <div class="fw-semibold mt-3 mb-2">Business hours (demo)</div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Day</th>
                    <th>Slot 1</th>
                    <th>Slot 2</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>Mon</td><td>09:00 - 17:00</td><td>-</td></tr>
                  <tr><td>Tue</td><td>09:00 - 17:00</td><td>-</td></tr>
                  <tr><td>Wed</td><td>09:00 - 17:00</td><td>-</td></tr>
                  <tr><td>Thu</td><td>09:00 - 17:00</td><td>-</td></tr>
                  <tr><td>Fri</td><td>09:00 - 17:00</td><td>-</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <div class="fw-semibold mb-2">Gallery</div>
          <div class="row g-2">
            <?php foreach (($listing['gallery'] ?? []) as $g): ?>
              <div class="col-6 col-md-4">
                <div class="rounded-3 border bg-light p-2">
                  <div class="text-center text-muted small">Image <?= htmlspecialchars((string)$g) ?></div>
                  <div class="rounded-2 bg-white border mt-2" style="height:110px;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="fw-semibold mb-3">Contact & actions</div>
        <div class="d-flex flex-column gap-2">
          <a class="btn btn-outline-dark" href="/contact.php">Send enquiry</a>
          <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#claimModal">
            Claim listing
          </button>
          <button class="btn btn-outline-secondary" type="button">
            Save to favourites (demo)
          </button>
        </div>
        <div class="text-muted small mt-3">
          Claiming requires registration. Anonymous submissions will be approved first by super admin.
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Claim modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Claim this listing</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($isLoggedIn): ?>
          <div class="alert alert-info mb-0">
            You’re logged in. In the backend phase, you’ll be able to request a claim and manage the listing.
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">
            Registration/login is required to claim this listing.
            <div class="mt-2">
              <a href="/login.php" class="btn btn-sm btn-dark me-2">Login</a>
              <a href="/register.php" class="btn btn-sm btn-outline-dark">Register</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-dark" disabled>Submit claim (backend later)</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

