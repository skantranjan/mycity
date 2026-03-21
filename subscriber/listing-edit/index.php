<?php

declare(strict_types=1);

$pageTitle = 'Edit Listing - My City Info';
$activePage = '';
$subActive = 'listings';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_edit_listing';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$mciSubmitCsrfToken = mci_csrf_token($csrfAction);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        http_response_code(403);
        throw new RuntimeException('Invalid CSRF token.');
    }
}

// UI demo: resolve listing from slug/id query param
$editSlug = trim((string) ($_GET['slug'] ?? ($_GET['id'] ?? '')));

// UI demo: hardcoded listing data keyed by slug
$demoListings = [
    'property-852' => [
        'slug'   => 'property-852',
        'title'  => 'Property 852',
        'status' => 'Live',
    ],
    'locker-shop-uk' => [
        'slug'   => 'locker-shop-uk',
        'title'  => 'Locker Shop UK Ltd',
        'status' => 'Pending',
    ],
    'jxf-painting' => [
        'slug'   => 'jxf-painting',
        'title'  => 'JXF Painting Service',
        'status' => 'Live',
    ],
    'hunter-hill-physio' => [
        'slug'   => 'hunter-hill-physio',
        'title'  => 'Hunter Hill Physiotherapy',
        'status' => 'Rejected',
    ],
];

$editListing = $demoListings[$editSlug] ?? null;
$listingTitle  = $editListing['title']  ?? 'Unknown Listing';
$listingStatus = $editListing['status'] ?? '';

$statusBadgeClass = match($listingStatus) {
    'Live'     => 'text-bg-success',
    'Pending'  => 'text-bg-warning',
    'Rejected' => 'text-bg-danger',
    'Draft'    => 'text-bg-secondary',
    default    => 'text-bg-light border',
};

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

$extraJS = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
HTML;

$categories = [
    'Airport', 'Amusement Park', 'Aquarium', 'Art Gallery', 'ATM', 'Automotive',
    'Bakery', 'Bank', 'Bar', 'Beauty Salon', 'Bicycle Store', 'Books & Stationary Store',
    'Bus Stations', 'Cafe', 'Car Dealer', 'Car Rental', 'Car Repair', 'Car Wash',
    'Cemetery', 'Church', 'City Attraction', 'Clothing Store', 'College',
    'Convenience Store', 'Courier Services', 'Dentist', 'Departmental Store',
    'Doctor', 'Electrician', 'Electronics Store', 'Fire Station', 'Florist',
    'Funeral Home', 'Furniture Store', 'Gift Shop', 'Government Office', 'Gym',
    'Hardware Store', 'Health', 'Hindu Temple', 'Home Appliances Products',
    'Hospital', 'Hotels', 'Industrial and Manufacturing Supplies', 'Insurance Agency',
    'Jewelry Store', 'Laundry', 'Lawyer', 'Library', 'Liquor Store', 'Locksmith',
    'Medical Store', 'Monuments', 'Mosque', 'Movie Theater', 'Museum',
    'NGO and Charitable Trusts', 'Night Club', 'Painter', 'Park', 'Pet Store',
    'Petrol Pump', 'Physiotherapist', 'Plumber', 'Police Station', 'Post Office',
    'Pre Schools and Day Care', 'Private Coaching Institutes', 'Real Estate',
    'Resorts', 'Restaurant', 'School', 'Services', 'Shoe Store', 'Shopping', 'Spa',
    'Stadium', 'Supermarket', 'Travel Agency', 'University', 'Veterinary Care',
];

// Override wizard labels for edit context
$submitKicker   = 'Edit listing';
$submitTitle    = 'Edit: ' . htmlspecialchars($listingTitle, ENT_QUOTES, 'UTF-8');
$submitLead     = 'Update your listing details. All changes will be reviewed before going live.';
$step7SubmitText = 'Save changes';
$step7HeaderDesc = 'Review your changes below, then save when everything looks right.';
$step7AlertTitle = 'Review &amp; save';
$step7AlertBody  = 'Check all steps before saving. Changes to a live listing may require re-approval.';
$formOrigin     = 'ui_subscriber_edit_listing';
$postingType    = 'registered';
$requesterLabel = 'Subscriber';

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Breadcrumb + status header -->
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
          <li class="breadcrumb-item"><a href="/subscriber/listings/">My Listings</a></li>
          <li class="breadcrumb-item active" aria-current="page">
            Edit: <?= htmlspecialchars($listingTitle, ENT_QUOTES, 'UTF-8') ?>
          </li>
        </ol>
      </nav>
      <?php if ($listingStatus !== ''): ?>
        <span class="badge <?= $statusBadgeClass ?>">
          <?= htmlspecialchars($listingStatus, ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($editListing === null && $editSlug !== ''): ?>
      <div class="alert alert-warning">
        Listing not found. <a href="/subscriber/listings/">Back to My Listings</a>.
      </div>
    <?php else: ?>
      <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
    <?php endif; ?>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
