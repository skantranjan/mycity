<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_csrf.php';

$pageTitle = 'Add business (anonymous) - CP - My City Info';
$activePage = '';
$cpActive = 'add-business';
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

// Overrides consumed by subscriber-list-business-inner.php.
$wizardContext   = 'cp_admin';
$submitKicker    = 'Super admin';
$submitTitle     = 'Add business';
$submitLead      = 'Eight guided steps — save anytime; published immediately after submit.';

$postingType     = 'anonymous';
$requesterLabel  = 'Super admin';

$step7HeaderDesc = 'Posted by super admin — goes live immediately.';
$step7AlertTitle = 'Ready to publish';
$step7AlertBody  = 'This listing will be published immediately and visible to all users.';
$step7SubmitText = 'Publish business';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

$extraJS = <<<'HTML'
<script>
window._mciSubmitContext  = 'cp_admin';
window._mciSubmitRedirect = '/cp/listings/';
window._mciSubmitBtnText  = 'Publish business';
</script>
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
      Only super admins / co-admins can submit anonymously. Switch role in <a href="/cp/coadmins/">Co-admins</a>.
    </div>

    <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
