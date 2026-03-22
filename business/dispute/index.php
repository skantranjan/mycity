<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';

$pageTitle = 'Dispute Ownership - My City Info';
$activePage = 'listings';

$isLoggedIn = !empty($_SESSION['mci_logged_in']) && !empty($_SESSION['mci_user_id']);

// Redirect guests to login
if (!$isLoggedIn) {
    $returnUrl = '/business/dispute/?' . http_build_query(['slug' => $_GET['slug'] ?? '']);
    header('Location: /login/?return=' . rawurlencode($returnUrl));
    exit;
}

$csrfAction = 'business_dispute_submit';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$slug  = trim((string) ($_GET['slug'] ?? ''));
$flash = '';
$submitted = false;

// UI demo: hardcoded listing lookup
$demoListings = [
    'property-852' => [
        'title'        => 'Property 852',
        'address'      => '12 Orchard Lane, Downtown',
        'category'     => 'Real Estate',
        'claimed'      => false,
        'owner_display'=> null,
    ],
    'locker-shop-uk' => [
        'title'        => 'Locker Shop UK Ltd',
        'address'      => '88 Market Street, Central District',
        'category'     => 'Furniture Store',
        'claimed'      => false,
        'owner_display'=> null,
    ],
    'jxf-painting' => [
        'title'        => 'JXF Painting Service',
        'address'      => '4 Riverside Avenue, West End',
        'category'     => 'Painter',
        'claimed'      => true,
        'owner_display'=> 'J. Fletcher',
    ],
    'hunter-hill-physio' => [
        'title'        => 'Hunter Hill Physiotherapy',
        'address'      => '19 Hillcrest Road, Northside',
        'category'     => 'Health',
        'claimed'      => false,
        'owner_display'=> null,
    ],
];

$listing = $slug !== '' ? ($demoListings[$slug] ?? null) : null;

// Pre-fill email from session
$userEmail = (string) ($_SESSION['mci_user_email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flash = 'error:Invalid request token. Please refresh and try again.';
    } else {
        $reason     = trim((string) ($_POST['reason'] ?? ''));
        $details    = trim((string) ($_POST['details'] ?? ''));
        $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
        $errors = [];
        if ($reason === '') {
            $errors[] = 'Please select a reason for the dispute.';
        }
        if (strlen($details) < 20) {
            $errors[] = 'Please provide at least 20 characters of evidence or detail.';
        }
        if ($contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid contact email address.';
        }
        if ($errors) {
            $flash = 'error:' . implode(' ', $errors);
        } else {
            $submitted = true;
        }
    }
}

ob_start();
?>

<div class="container py-5" style="max-width:680px;">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb small">
      <?php if ($listing !== null): ?>
        <li class="breadcrumb-item">
          <a href="/business/<?= urlencode($slug) ?>/">
            <?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php else: ?>
        <li class="breadcrumb-item"><a href="/business-listing/">Listings</a></li>
      <?php endif; ?>
      <li class="breadcrumb-item active" aria-current="page">Dispute ownership</li>
    </ol>
  </nav>

  <?php if ($submitted): ?>
    <!-- Success state -->
    <div class="card border-0 shadow-sm p-4 text-center">
      <div class="mb-3">
        <i class="bi bi-shield-exclamation text-warning" style="font-size:3rem;" aria-hidden="true"></i>
      </div>
      <h1 class="h4 fw-bold mb-2">Dispute submitted</h1>
      <p class="text-muted mb-4">
        We've received your dispute for
        <strong><?= htmlspecialchars($listing['title'] ?? $slug, ENT_QUOTES, 'UTF-8') ?></strong>.
        Our team will investigate and contact you at the email you provided.
      </p>
      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <?php if ($listing !== null): ?>
          <a class="btn btn-outline-dark btn-sm" href="/business/<?= urlencode($slug) ?>/">Back to listing</a>
        <?php endif; ?>
        <a class="btn btn-dark btn-sm" href="/subscriber/dashboard/">Go to dashboard</a>
      </div>
    </div>

  <?php elseif ($listing === null): ?>
    <!-- Listing not found -->
    <div class="alert alert-warning">
      <strong>Listing not found.</strong> Please go back and try again.
      <a href="/business-listing/" class="alert-link ms-2">Browse listings</a>
    </div>

  <?php elseif (!$listing['claimed']): ?>
    <!-- Not claimed — dispute not applicable -->
    <div class="card border-0 shadow-sm p-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle text-primary fs-4" aria-hidden="true"></i>
        <h1 class="h5 fw-bold mb-0">This listing is unclaimed</h1>
      </div>
      <p class="text-muted small mb-3">
        <strong><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></strong>
        has no current owner, so there is nothing to dispute. You can claim it instead.
      </p>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-dark" href="/business/<?= urlencode($slug) ?>/">Back to listing</a>
        <a class="btn btn-sm btn-dark" href="/business/claim/?slug=<?= urlencode($slug) ?>">Claim this business</a>
      </div>
    </div>

  <?php else: ?>
    <!-- Dispute form -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">

        <h1 class="h5 fw-bold mb-1">Dispute ownership</h1>
        <p class="text-muted small mb-4">
          If you believe the ownership of this listing is incorrect, submit a dispute below.
          Our team will review the evidence and contact both parties.
        </p>

        <!-- Business summary -->
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 mb-4">
          <i class="bi bi-building text-muted fs-4 mt-1" aria-hidden="true"></i>
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($listing['address'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($listing['category'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($listing['owner_display'] !== null): ?>
              <div class="text-muted small mt-1">
                <i class="bi bi-person-check me-1" aria-hidden="true"></i>
                Current owner: <?= htmlspecialchars($listing['owner_display'], ENT_QUOTES, 'UTF-8') ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($flash !== ''): ?>
          <?php [$flashType, $flashMsg] = explode(':', $flash, 2); ?>
          <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="alert">
            <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="post" action="" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

          <!-- Reason -->
          <div class="mb-3">
            <label class="form-label fw-semibold small">Reason for dispute <span class="text-danger">*</span></label>
            <?php
            $reasons = [
                'real_owner'   => 'I am the real owner of this business',
                'incorrect'    => 'The listing contains incorrect information',
                'fraudulent'   => 'This is a fraudulent or fake listing',
            ];
            $selectedReason = trim((string) ($_POST['reason'] ?? ''));
            foreach ($reasons as $val => $label):
            ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="reason"
                  id="reason_<?= htmlspecialchars($val) ?>"
                  value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                  <?= $selectedReason === $val ? 'checked' : '' ?> required />
                <label class="form-check-label small" for="reason_<?= htmlspecialchars($val) ?>">
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Evidence / details -->
          <div class="mb-3">
            <label for="disputeDetails" class="form-label fw-semibold small">
              Evidence or details <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="disputeDetails" name="details" rows="4"
              placeholder="Provide any relevant evidence, context, or supporting information for your dispute…"
              minlength="20" required><?= htmlspecialchars((string) ($_POST['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-text small">Minimum 20 characters.</div>
          </div>

          <!-- Contact email -->
          <div class="mb-4">
            <label for="contactEmail" class="form-label fw-semibold small">
              Contact email <span class="text-danger">*</span>
            </label>
            <input type="email" class="form-control" id="contactEmail" name="contact_email"
              value="<?= htmlspecialchars((string) ($_POST['contact_email'] ?? $userEmail), ENT_QUOTES, 'UTF-8') ?>"
              placeholder="your@email.com" required />
            <div class="form-text small">We'll contact you at this address with our decision.</div>
          </div>

          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/business/<?= urlencode($slug) ?>/">Cancel</a>
            <button type="submit" class="btn btn-dark btn-sm">
              <i class="bi bi-flag me-1" aria-hidden="true"></i>Submit dispute
            </button>
          </div>
        </form>

      </div>
    </div>

  <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
