<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_directory_listings.php';
require_once __DIR__ . '/../../includes/mci_csrf.php';

$pageTitle = 'Add business (anonymous) - CP - My City Info';
$activePage = '';
$cpActive = 'anonymous-business';
$hideCta = true;
$appArea = 'cp';

$csrfAction = 'cp_anonymous_submit_listing';
$mciSubmitCsrfToken = mci_csrf_token($csrfAction);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string)($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        http_response_code(403);
        throw new RuntimeException('Invalid CSRF token.');
    }
}

$categories = [];
foreach ($mciDirectoryListings as $row) {
    $c = trim((string)($row['category'] ?? ''));
    if ($c !== '') $categories[$c] = true;
}
$categories = array_keys($categories);
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);

// Overrides consumed by subscriber-list-business-inner.php.
$submitKicker = 'Super admin';
$submitTitle = 'Add business (anonymous)';
$submitLead = 'Seven guided steps — save anytime; this listing will be reviewed before going live.';

$postingType = 'anonymous';
$requesterLabel = 'Super admin';

$step7HeaderDesc = 'Posted anonymously by super admin — submit when ready.';
$step7AlertTitle = 'Preview & submit anonymously';
$step7AlertBody = 'We’ll send this listing to the anonymous approvals queue. (Demo/localStorage)';
$step7SubmitText = 'Submit anonymously';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

$extraJS = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
<script src="/assets/js/cp-anonymous-business.js"></script>
HTML;

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="alert alert-warning d-none" id="mciCpAnonGate" role="status">
      Only super admins / co-admins can submit anonymously in this demo. Switch role in <a href="/cp/coadmins/">Co-admins</a>.
    </div>
    <div class="alert alert-success d-none" id="mciAnonSuccess" role="status">
      Submitted anonymously. Redirecting to the queue...
    </div>

    <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

