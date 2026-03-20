<?php
declare(strict_types=1);

$pageTitle = 'Delete listing - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

$slug = trim((string) ($_GET['slug'] ?? ''));
$title = trim((string) ($_GET['title'] ?? ''));

$stage = (int) ($_GET['stage'] ?? 1);
if ($stage < 1) {
    $stage = 1;
}
if ($stage > 2) {
    $stage = 2;
}
$error = '';
$ok = '';

$csrfAction = 'subscriber_listing_delete_action';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);
$csrfOk = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedStage = (int) ($_POST['stage'] ?? 1);
    $action = trim((string) ($_POST['action'] ?? ''));
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $error = 'Invalid request token. Please refresh and try again.';
        $csrfOk = false;
    } else {
        $csrfOk = true;

        if ($action === 'close') {
            $ok = 'Listing marked as permanently closed (demo).';
            $stage = 1;
        } elseif ($action === 'delete') {
            if ($postedStage === 2) {
                $confirm = trim((string) ($_POST['confirm_delete'] ?? ''));
                if (strtolower($confirm) !== 'delete') {
                    $error = 'Type `delete` exactly to confirm permanent deletion.';
                    $stage = 2;
                } else {
                    $ok = 'Listing deleted permanently (demo).';
                    $stage = 1;
                }
            } else {
                $stage = 2;
            }
        }
    }
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
          <div>
            <div class="fw-semibold">Listing actions</div>
            <div class="text-muted small">Confirm what you want to do with this listing.</div>
          </div>
          <span class="badge text-bg-light border"><?= htmlspecialchars('Step ' . $stage . ' of 2', ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <?php if ($ok !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-dark" href="/subscriber/listings/">Back to My listings</a>
          </div>
        <?php else: ?>
          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endif; ?>

          <div class="border rounded-3 p-3 bg-white">
            <div class="fw-semibold mb-1">
              <?= $title !== '' ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'This listing' ?>
            </div>
            <div class="text-muted small mb-3">
              <?php if ($stage === 1): ?>
                Are you sure you want to delete this listing? You will lose your business page content.
              <?php else: ?>
                Permanent deletion requires a final confirmation.
              <?php endif; ?>
            </div>

            <?php if ($stage === 1): ?>
              <form method="post" action="">
                <input type="hidden" name="stage" value="1" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                <div class="d-flex gap-2 flex-wrap">
                  <button type="submit" class="btn btn-outline-danger" name="action" value="delete">
                    <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete permanently
                  </button>
                  <button
                    type="submit"
                    class="btn btn-outline-secondary"
                    name="action"
                    value="close"
                  >
                    <i class="bi bi-eye-slash me-1" aria-hidden="true"></i>Mark as permanently closed
                  </button>
                </div>

                <p class="text-muted small mt-3 mb-0">
                  Or choose “Mark as permanently closed” to keep the listing but hide it from being claimed/edited publicly.
                </p>
              </form>
            <?php else: ?>
              <form method="post" action="">
                <input type="hidden" name="stage" value="2" />
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />

                <label class="form-label mci-field-label" for="confirm_delete">
                  Type <strong>delete</strong> to confirm
                </label>
                <input
                  class="form-control"
                  id="confirm_delete"
                  name="confirm_delete"
                  type="text"
                  placeholder="delete"
                  required
                />

                <div class="d-flex gap-2 flex-wrap mt-3">
                  <button type="submit" class="btn btn-dark">
                    <i class="bi bi-trash-fill me-1" aria-hidden="true"></i>Confirm deletion
                  </button>
                  <a class="btn btn-outline-dark" href="/subscriber/listing-delete/?slug=<?= urlencode($slug) ?>&title=<?= urlencode($title) ?>&stage=1">Back</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

