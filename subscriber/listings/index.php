<?php
$pageTitle = 'My Listings - My City Info';
$activePage = '';
$subActive = 'listings';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_session.php';

$userId = (string)($_SESSION['mci_user_id'] ?? '');

$rows = [];
if ($userId !== '') {
    require_once __DIR__ . '/../../api/v1/lib/db.php';
    require_once __DIR__ . '/../../api/v1/lib/business_service.php';
    try {
        $rows = api_business_list_owner(api_db(), $userId)['businesses'] ?? [];
    } catch (Throwable $e) {
        $rows = [];
    }
}

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
            <div class="fw-semibold">My Listings</div>
            <div class="text-muted small">See and modify your submitted listings.</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add new listing
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th style="width:56px;"></th>
                <th>Business</th>
                <th>Category</th>
                <th>Status</th>
                <th style="min-width: 240px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted small py-4">You haven't submitted any listings yet. <a href="/subscriber/list-business/">Add your first listing</a>.</td></tr>
              <?php endif; ?>
              <?php foreach ($rows as $r):
                $rowSlug   = (string)($r['slug']          ?? '');
                $rowName   = (string)($r['name']          ?? '');
                $rowCat    = (string)($r['category_name'] ?? '');
                $rowStatus = strtolower((string)($r['status'] ?? ''));
              ?>
                <tr>
                  <td>
                    <?php if (!empty($r['logo_path'])): ?>
                      <img src="<?= htmlspecialchars((string)$r['logo_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="" width="48" height="48" class="rounded-2" style="object-fit:cover;" loading="lazy" decoding="async" />
                    <?php else: ?>
                      <div class="rounded-2 bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                        <i class="bi bi-building text-muted" aria-hidden="true"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-semibold small"><?= htmlspecialchars($rowName) ?></div>
                    <div class="text-muted" style="font-size:var(--mci-text-xs)"><?= htmlspecialchars($rowCat) ?></div>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($rowCat) ?></td>
                  <td>
                    <?php
                    $statusBadge = match($rowStatus) {
                        'live'      => 'text-bg-success',
                        'draft'     => 'text-bg-warning',
                        'rejected'  => 'text-bg-danger',
                        'suspended' => 'text-bg-secondary',
                        default     => 'text-bg-light border',
                    };
                    ?>
                    <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($rowStatus)) ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <a class="btn btn-sm btn-outline-dark" href="/business/<?= urlencode($rowSlug) ?>/" target="_blank" rel="noopener noreferrer" title="View listing">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (count($rows) > 0): ?>
        <div class="mt-3 text-muted small text-end">Showing <?= count($rows) ?> listing<?= count($rows) !== 1 ? 's' : '' ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

