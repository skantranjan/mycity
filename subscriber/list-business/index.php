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
$submitRedirect  = '/subscriber/listings/';

$extraJS = <<<'HTML'
<script>
window._mciSubmitContext  = 'subscriber';
window._mciSubmitRedirect = '/subscriber/listings/';
window._mciSubmitBtnText  = 'Submit listing';
</script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
HTML;

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
