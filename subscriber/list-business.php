<?php

declare(strict_types=1);

$pageTitle = 'List your business - My City Info';
$activePage = 'submit';
$subActive = 'list-business';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_submit_listing';
require_once __DIR__ . '/../includes/mci_csrf.php';
$mciSubmitCsrfToken = mci_csrf_token($csrfAction);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        http_response_code(403);
        throw new RuntimeException('Invalid CSRF token.');
    }
}

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

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <?php include __DIR__ . '/../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
