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
    $wizardContext  = 'subscriber';
    $postingType    = 'registered';
    $requesterLabel = 'Subscriber';
    $step7HeaderDesc = 'You\'re signed in — submit when everything looks right.';
    $step7AlertTitle = 'Preview & publish';
    $step7AlertBody  = 'Confirm the details below and submit your business for listing review.';
    $step7SubmitText = 'Submit listing';
    $submitRedirect  = '/subscriber/listings/';
} else {
    $wizardContext  = 'guest';
    $postingType    = 'anonymous';
    $requesterLabel = 'Guest';
    $step7HeaderDesc = 'Choose how to publish — then submit for review.';
    $step7AlertTitle = 'Account or anonymous';
    $step7AlertBody  = 'Create an account for faster updates and listing management, or submit without an account for admin review.';
    $step7SubmitText = 'Submit for review';
    $submitRedirect  = '/submit-business-listing/';
}

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

$wcJs = htmlspecialchars($wizardContext, ENT_QUOTES, 'UTF-8');
$rdJs = htmlspecialchars($submitRedirect, ENT_QUOTES, 'UTF-8');
$btnJs = htmlspecialchars($step7SubmitText, ENT_QUOTES, 'UTF-8');
$extraJS = <<<HTML
<script>
window._mciSubmitContext  = '{$wcJs}';
window._mciSubmitRedirect = '{$rdJs}';
window._mciSubmitBtnText  = '{$btnJs}';
</script>
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
