<?php

declare(strict_types=1);

$pageTitle = 'List your business - My City Info';
$activePage = 'submit';
$subActive = 'list-business';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_submit_listing';
require_once __DIR__ . '/../../includes/mci_csrf.php';
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

$wizardContext  = 'subscriber';
$step7SubmitText = 'Submit listing';
$submitRedirect  = '/subscriber/list-business/?submitted=1';

$extraJS = <<<'HTML'
<script>
window._mciSubmitContext  = 'subscriber';
window._mciSubmitRedirect = '/subscriber/list-business/?submitted=1';
window._mciSubmitBtnText  = 'Submit listing';
</script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
HTML;

ob_start();
?>

<?php if (!empty($_GET['submitted'])): ?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm text-center p-4 p-md-5">
      <div style="font-size:3rem;" aria-hidden="true">&#x1F389;</div>
      <h1 class="h4 fw-bold mt-3 mb-2">Congratulations!</h1>
      <p class="text-muted mb-3">
        Your business listing has been submitted and is <strong>now live</strong>.<br>
        Verification will be completed within <strong>7&ndash;10 business days</strong>, after which
        you can start receiving leads and enquiries directly.
      </p>
      <p class="text-muted small mb-4">
        Manage your listing, update details, and track enquiries from your
        <a href="/subscriber/listings/" class="fw-semibold">subscriber dashboard</a>.
      </p>
      <a href="/subscriber/listings/" class="btn btn-dark">Go to my listings</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
