<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_csrf.php';

$pageTitle = 'List your business - My City Info';
$activePage = 'submit';
$hideCta = true;

$csrfAction = 'public_submit_listing';
$mciSubmitCsrfToken = mci_csrf_token($csrfAction);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        http_response_code(403);
        throw new RuntimeException('Invalid CSRF token.');
    }
}

$isSubscriberLoggedIn = !empty($_SESSION['mci_logged_in']) && (($_SESSION['mci_role'] ?? '') === 'subscriber');

$formOrigin = 'ui_public_submit_listing';
$submitKicker = 'My City Info';
$submitTitle = 'List your business';
$submitLead = 'Seven guided steps — save anytime; submit when you\'re ready.';

$mciPublicSiteOrigin = 'https://mycityinfo.com';

/** Same 7-step wizard as subscriber / CP; public guests choose account vs anonymous on Publish. */
$submitPublicGuest = !$isSubscriberLoggedIn;
/** Hide the step-7 mini preview card on this public page (use “Preview listing” + subscriber wizard for full preview). */
$submitHideStep7InlinePreview = true;

if ($isSubscriberLoggedIn) {
    $postingType = 'registered';
    $requesterLabel = 'Subscriber';
    $step7HeaderDesc = 'You\'re signed in — submit when everything looks right.';
    $step7AlertTitle = 'Preview & publish';
    $step7AlertBody = 'Confirm the details below and submit your business for listing review.';
    $step7SubmitText = 'Submit listing';
} else {
    $postingType = 'anonymous';
    $requesterLabel = 'Guest';
    $step7HeaderDesc = 'Choose how to publish — then submit for review.';
    $step7AlertTitle = 'Account or anonymous';
    $step7AlertBody = 'Create an account for faster updates and listing management, or submit without an account for admin review.';
    $step7SubmitText = 'Submit for review';
}

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

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

$extraJS = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
HTML;

ob_start();
?>

<div class="row g-4 justify-content-center">
  <div class="col-12 col-md-11 col-lg-10">
    <?php include __DIR__ . '/../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
