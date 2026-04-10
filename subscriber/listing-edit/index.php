<?php

declare(strict_types=1);

$pageTitle = 'Edit Listing - My City Info';
$activePage = '';
$subActive = 'listings';
$hideCta = true;
$appArea = 'subscriber';

// Session + auth
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';
mci_require_subscriber_session();

$sessionUserId = (string)($_SESSION['mci_user_id'] ?? '');

// CSRF
$csrfAction = 'subscriber_edit_listing';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$mciSubmitCsrfToken = mci_csrf_token($csrfAction);

// Resolve listing id from query param
$editId = trim((string)($_GET['id'] ?? ''));

// Fetch real listing data
$listing = null;
if ($editId !== '') {
    require_once __DIR__ . '/../../api/v1/lib/db.php';
    require_once __DIR__ . '/../../api/v1/lib/business_service.php';
    try {
        $fetched = api_business_fetch(api_db(), $editId);
        // Ownership check — only the owner (or admins via session) may edit
        if ($fetched && (string)($fetched['added_by_user_id'] ?? '') === $sessionUserId) {
            $listing = $fetched;
        }
    } catch (Throwable $e) {
        $listing = null;
    }
}

$listingTitle  = $listing ? (string)($listing['name']   ?? 'Unknown Listing') : 'Unknown Listing';
$listingStatus = $listing ? (string)($listing['status'] ?? '')                 : '';

$statusBadgeClass = match($listingStatus) {
    'live'      => 'text-bg-success',
    'pending'   => 'text-bg-warning',
    'rejected'  => 'text-bg-danger',
    'draft'     => 'text-bg-secondary',
    'suspended' => 'text-bg-danger',
    default     => 'text-bg-light border',
};

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" />
<link rel="stylesheet" href="/assets/css/submit-listing.css" />
HTML;

// Build the inline JS block that seeds the edit data
$listingJson  = $listing ? json_encode($listing, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) : 'null';
$editIdSafe   = htmlspecialchars($editId, ENT_QUOTES, 'UTF-8');

$extraJS = <<<HTML
<script>
window._mciSubmitContext  = 'subscriber';
window._mciSubmitRedirect = '/subscriber/listings/';
window._mciSubmitBtnText  = 'Save changes';
window._mciEditListing    = {$listingJson};
window._mciEditListingId  = '{$editIdSafe}';
</script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="/assets/js/subscriber-list-business.js"></script>
<script src="/assets/js/subscriber-listing-edit.js"></script>
HTML;

// Override wizard labels for edit context
$submitKicker    = 'Edit listing';
$submitTitle     = 'Edit: ' . htmlspecialchars($listingTitle, ENT_QUOTES, 'UTF-8');
$submitLead      = 'Update your listing details. All changes will be reviewed before going live.';
$step7SubmitText = 'Save changes';
$step7HeaderDesc = 'Review your changes below, then save when everything looks right.';
$step7AlertTitle = 'Review &amp; save';
$step7AlertBody  = 'Check all steps before saving. Changes to a live listing may require re-approval.';
$formOrigin      = 'ui_subscriber_edit_listing';
$postingType     = 'registered';
$requesterLabel  = 'Subscriber';

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
          <?= htmlspecialchars(ucfirst($listingStatus), ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
    </div>

    <?php if ($listing === null): ?>
      <div class="alert alert-warning">
        Listing not found or you do not have permission to edit it.
        <a href="/subscriber/listings/">Back to My Listings</a>.
      </div>
    <?php else: ?>
      <?php include __DIR__ . '/../../views/partials/subscriber-list-business-inner.php'; ?>
    <?php endif; ?>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
