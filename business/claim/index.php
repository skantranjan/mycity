<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';

$pageTitle = 'Claim this Business - My City Info';
$activePage = 'listings';

$isLoggedIn = !empty($_SESSION['mci_logged_in']) && !empty($_SESSION['mci_user_id']);

// Redirect guests to login, preserving return URL
if (!$isLoggedIn) {
    $returnUrl = '/business/claim/?' . http_build_query(['slug' => $_GET['slug'] ?? '']);
    header('Location: /login/?return=' . rawurlencode($returnUrl));
    exit;
}

$csrfAction = 'business_claim_submit';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$slug = trim((string) ($_GET['slug'] ?? ''));

$flash = '';
$submitted = false;

// Load listing from DB
require_once __DIR__ . '/../../api/v1/lib/db.php';
require_once __DIR__ . '/../../api/v1/lib/uuid.php';
$listing = null;
if ($slug !== '') {
    try {
        $pdo  = api_db();
        $stmt = $pdo->prepare("
            SELECT
                g.id,
                g.name AS title,
                g.claimed_by_user_id,
                c.name AS category,
                TRIM(CONCAT_WS(', ',
                    NULLIF(b.address_line1, ''),
                    NULLIF(b.city, ''),
                    NULLIF(b.state, '')
                )) AS address
            FROM mci_business_groups g
            LEFT JOIN mci_categories c ON c.id = g.parent_category_id
            LEFT JOIN mci_business_branches b ON b.business_group_id = g.id
                AND b.id = (
                    SELECT b2.id FROM mci_business_branches b2
                    WHERE b2.business_group_id = g.id
                    ORDER BY b2.is_primary DESC, b2.created_at ASC
                    LIMIT 1
                )
            WHERE g.slug = ? AND g.status != 'deleted'
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $listing = [
                'id'       => (string)$row['id'],
                'title'    => (string)$row['title'],
                'address'  => (string)$row['address'],
                'category' => (string)$row['category'],
                'claimed'  => !empty($row['claimed_by_user_id']),
            ];
        }
    } catch (Throwable $ignored) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flash = 'error:Invalid request token. Please refresh and try again.';
    } else {
        $relationship = trim((string) ($_POST['relationship'] ?? ''));
        $details      = trim((string) ($_POST['details'] ?? ''));
        $agreedTerms  = !empty($_POST['agree_terms']);
        $errors = [];
        if ($relationship === '') {
            $errors[] = 'Please select your relationship to the business.';
        }
        if (strlen($details) < 20) {
            $errors[] = 'Please provide at least 20 characters of supporting detail.';
        }
        if (!$agreedTerms) {
            $errors[] = 'You must agree to the terms to submit a claim.';
        }
        if ($listing === null) {
            $errors[] = 'Listing not found.';
        } elseif ($listing['claimed']) {
            $errors[] = 'This listing is already claimed.';
        }
        if ($errors) {
            $flash = 'error:' . implode(' ', $errors);
        } else {
            // Check for existing pending claim by this user
            try {
                $userId = (string)($_SESSION['mci_user_id'] ?? '');
                $pdo2   = api_db();
                $chk    = $pdo2->prepare(
                    "SELECT id FROM mci_business_claims WHERE business_group_id = ? AND claimant_user_id = ? AND status = 'pending' LIMIT 1"
                );
                $chk->execute([$listing['id'], $userId]);
                if ($chk->fetchColumn()) {
                    $flash = 'error:You already have a pending claim for this business.';
                } else {
                    $claimId = api_uuid_v4();
                    $pdo2->prepare(
                        "INSERT INTO mci_business_claims
                            (id, business_group_id, claimant_user_id, status, claim_message, created_by_user_id)
                         VALUES (?, ?, ?, 'pending', ?, ?)"
                    )->execute([
                        $claimId,
                        $listing['id'],
                        $userId,
                        $relationship . ' — ' . $details,
                        $userId,
                    ]);
                    $submitted = true;
                }
            } catch (Throwable $e) {
                $flash = 'error:Could not submit claim. Please try again.';
            }
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
      <li class="breadcrumb-item active" aria-current="page">Claim this business</li>
    </ol>
  </nav>

  <?php if ($submitted): ?>
    <!-- Success state -->
    <div class="card border-0 shadow-sm p-4 text-center">
      <div class="mb-3">
        <i class="bi bi-patch-check-fill text-success" style="font-size:3rem;" aria-hidden="true"></i>
      </div>
      <h1 class="h4 fw-bold mb-2">Claim submitted</h1>
      <p class="text-muted mb-4">
        Thanks! We've received your claim for
        <strong><?= htmlspecialchars($listing['title'] ?? $slug, ENT_QUOTES, 'UTF-8') ?></strong>.
        Our team will review it and notify you by email.
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

  <?php elseif ($listing['claimed']): ?>
    <!-- Already claimed -->
    <div class="card border-0 shadow-sm p-4">
      <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-shield-check text-success fs-4" aria-hidden="true"></i>
        <h1 class="h5 fw-bold mb-0">This listing is already claimed</h1>
      </div>
      <p class="text-muted small mb-3">
        <strong><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></strong>
        already has a verified owner. If you believe you have a right to this listing, you can raise a dispute.
      </p>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-dark" href="/business/<?= urlencode($slug) ?>/">Back to listing</a>
        <a class="btn btn-sm btn-dark" href="/business/dispute/?slug=<?= urlencode($slug) ?>">Raise a dispute</a>
      </div>
    </div>

  <?php else: ?>
    <!-- Claim form -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">

        <h1 class="h5 fw-bold mb-1">Claim this business</h1>
        <p class="text-muted small mb-4">
          Claiming gives you control to manage this listing, respond to enquiries, and keep information up to date.
          All claims are reviewed by our team before approval.
        </p>

        <!-- Business summary -->
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 mb-4">
          <i class="bi bi-building text-muted fs-4 mt-1" aria-hidden="true"></i>
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($listing['title'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($listing['address'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-muted small"><?= htmlspecialchars($listing['category'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>

        <?php if ($flash !== ''): ?>
          <?php [$flashType, $flashMsg] = explode(':', $flash, 2); ?>
          <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="alert">
            <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form method="post" action="" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

          <!-- Relationship -->
          <div class="mb-3">
            <label class="form-label fw-semibold small">Your relationship to this business <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 flex-wrap">
              <?php
              $relationships = ['Owner', 'Manager', 'Authorized Representative'];
              $selectedRel   = trim((string) ($_POST['relationship'] ?? ''));
              foreach ($relationships as $rel):
              ?>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="relationship"
                    id="rel_<?= htmlspecialchars(strtolower(str_replace(' ', '_', $rel))) ?>"
                    value="<?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $selectedRel === $rel ? 'checked' : '' ?> required />
                  <label class="form-check-label small"
                    for="rel_<?= htmlspecialchars(strtolower(str_replace(' ', '_', $rel))) ?>">
                    <?= htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Supporting details -->
          <div class="mb-3">
            <label for="claimDetails" class="form-label fw-semibold small">
              Supporting details <span class="text-danger">*</span>
            </label>
            <textarea class="form-control" id="claimDetails" name="details" rows="4"
              placeholder="Describe your connection to this business and any relevant information to support your claim…"
              minlength="20" required><?= htmlspecialchars((string) ($_POST['details'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-text small">Minimum 20 characters.</div>
          </div>

          <!-- Proof document (optional) -->
          <div class="mb-3">
            <label for="claimProof" class="form-label fw-semibold small">Proof document <span class="text-muted fw-normal">(optional)</span></label>
            <input class="form-control form-control-sm" type="file" id="claimProof" name="proof_document"
              accept="image/jpeg,image/png,image/webp" />
            <div class="form-text small">Images only (JPEG, PNG, WebP). Max 2 MB.</div>
          </div>

          <!-- Agree to terms -->
          <div class="mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" value="1"
                <?= !empty($_POST['agree_terms']) ? 'checked' : '' ?> required />
              <label class="form-check-label small" for="agreeTerms">
                I confirm that I am authorised to claim this listing and agree to the
                <a href="/terms-of-use/" target="_blank" rel="noopener noreferrer">Terms of Use</a>.
              </label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="/business/<?= urlencode($slug) ?>/">Cancel</a>
            <button type="submit" class="btn btn-dark btn-sm">
              <i class="bi bi-send me-1" aria-hidden="true"></i>Submit claim
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
