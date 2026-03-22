<?php
$pageTitle = 'Favourite Listings - My City Info';
$activePage = '';
$subActive = 'favourites';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_csrf.php';
require_once __DIR__ . '/../../includes/mci_favourites.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';

mci_require_subscriber_session();

$userId    = (string)($_SESSION['mci_user_id'] ?? '');
$csrfAction = 'subscriber_favourites_remove';
$csrfToken  = mci_csrf_token($csrfAction);
$flash = '';

$pdo = api_db();

// ── Handle remove POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost    = trim((string)($_POST['csrf_token']   ?? ''));
    $removeGroup = trim((string)($_POST['remove_group'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flash = 'error:Invalid request token. Please refresh and try again.';
    } elseif ($removeGroup !== '') {
        try {
            $pdo->prepare(
                "DELETE FROM mci_user_favourites WHERE user_id = ? AND business_group_id = ?"
            )->execute([$userId, $removeGroup]);
            $flash = 'success:Listing removed from favourites.';
        } catch (Throwable $ignored) {
            $flash = 'error:Could not remove the listing. Please try again.';
        }
    }
}

// ── Load favourites ───────────────────────────────────────────────────────────
$rows = mci_favourites_list($pdo, $userId);

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Favourite listings</div>
            <div class="text-muted small">Businesses you have saved as favourites.</div>
          </div>
          <span class="badge text-bg-light border px-3 py-2">Saved: <?= count($rows) ?></span>
        </div>

        <?php if ($flash !== ''): ?>
          <?php [$flashType, $flashMsg] = explode(':', $flash, 2); ?>
          <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="status">
            <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <?php if (count($rows) === 0): ?>
          <div class="border rounded-3 p-4 text-center bg-light">
            <div class="fw-semibold mb-1">No favourites yet</div>
            <div class="text-muted small mb-3">Save businesses from their listing page and they will appear here.</div>
            <a class="btn btn-sm btn-dark" href="/business-listing/">Explore listings</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered align-middle bg-white">
              <thead class="table-light">
                <tr>
                  <th>Business</th>
                  <th>Category</th>
                  <th>Saved on</th>
                  <th style="min-width: 220px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r):
                  $rSlug    = (string)($r['slug']          ?? '');
                  $rName    = (string)($r['name']          ?? '');
                  $rCat     = (string)($r['category_name'] ?? '');
                  $rCity    = (string)($r['city']          ?? '');
                  $rAddr    = trim(implode(', ', array_filter([
                                (string)($r['address_line1'] ?? ''),
                                $rCity,
                                (string)($r['state']   ?? ''),
                                (string)($r['pincode'] ?? ''),
                              ])));
                  $rSavedAt = (string)($r['saved_at'] ?? '');
                  $rDate    = $rSavedAt !== '' ? date('M j, Y', strtotime($rSavedAt)) : '—';
                  $rGroupId = (string)($r['business_group_id'] ?? '');
                ?>
                  <tr>
                    <td>
                      <div class="fw-semibold small"><?= htmlspecialchars($rName) ?></div>
                      <?php if ($rAddr !== ''): ?>
                        <div class="text-muted" style="font-size:var(--mci-text-xs)"><?= htmlspecialchars($rAddr) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($rCat) ?></td>
                    <td class="text-muted small text-nowrap"><?= htmlspecialchars($rDate) ?></td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-dark"
                           href="/business/<?= urlencode($rSlug) ?>/"
                           target="_blank" rel="noopener noreferrer"
                           title="Open business page">
                          <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                          <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                        </a>
                        <form method="post" action="" class="d-inline">
                          <input type="hidden" name="remove_group" value="<?= htmlspecialchars($rGroupId, ENT_QUOTES, 'UTF-8') ?>" />
                          <input type="hidden" name="csrf_token"   value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-heartbreak me-1" aria-hidden="true"></i>Remove
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
