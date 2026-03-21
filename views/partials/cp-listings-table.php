<?php
/**
 * Shared listings table partial for all CP listing sub-pages.
 *
 * Expects:
 *   $rows        array   — from api_business_list_cp()
 *   $total       int     — total matching records
 *   $pages       int     — total pages
 *   $curPage     int     — current page number
 *   $pageBase    string  — base URL for pagination + search, e.g. '/cp/listings/draft/'
 *   $flash       string  — 'type:message' or ''
 *   $csrfToken   string  — CSRF token (unused here, actions are JS fetch)
 *   $showStatus  bool    — whether to show the Status column (false on status-specific pages)
 *   $showRole    bool    — whether to show the Owner/Role column (false on role-specific pages)
 *   $q           string  — current search query
 */

$showStatus = $showStatus ?? true;
$showRole   = $showRole   ?? true;
$q          = $q          ?? '';
$pageBase   = rtrim($pageBase ?? '/cp/listings/', '/') . '/';

$statusBadgeMap = [
    'live'      => 'text-bg-success',
    'draft'     => 'text-bg-warning',
    'rejected'  => 'text-bg-danger',
    'suspended' => 'text-bg-secondary',
];
?>

<?php if ($flash !== ''): ?>
  <?php [$flashType, $flashMsg] = explode(':', $flash, 2); ?>
  <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="status">
    <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<!-- Search bar -->
<form method="get" action="" class="d-flex gap-2 mb-3">
  <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
    class="form-control form-control-sm" placeholder="Search by name or slug…" style="max-width:280px;" />
  <button class="btn btn-sm btn-outline-secondary" type="submit">
    <i class="bi bi-search" aria-hidden="true"></i>
  </button>
  <?php if ($q !== ''): ?>
    <a href="<?= htmlspecialchars($pageBase) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
  <?php endif; ?>
</form>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0" style="font-size:var(--mci-text-sm);">
    <thead class="table-light">
      <tr>
        <th>Business</th>
        <?php if ($showRole): ?><th>Posted by</th><?php endif; ?>
        <th>Category</th>
        <?php if ($showStatus): ?><th>Status</th><?php endif; ?>
        <th>Added</th>
        <th style="min-width:200px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($rows) === 0): ?>
        <tr>
          <td colspan="<?= 4 + ($showStatus ? 1 : 0) + ($showRole ? 1 : 0) ?>" class="text-center text-muted py-4 small">
            <?= $q !== '' ? 'No listings match your search.' : 'No listings in this view.' ?>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $r):
          $rowStatus  = strtolower((string)($r['status'] ?? ''));
          $statusBadge = $statusBadgeMap[$rowStatus] ?? 'text-bg-light border';
          $addedByRole = (string)($r['added_by_role'] ?? '');
          $bizId       = htmlspecialchars((string)$r['id'], ENT_QUOTES, 'UTF-8');
          $bizName     = htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8');
          $bizSlug     = (string)($r['slug'] ?? '');
        ?>
          <tr data-listing-id="<?= $bizId ?>">
            <td>
              <div class="fw-semibold"><?= htmlspecialchars((string)($r['name'] ?? '')) ?></div>
              <?php if ($bizSlug): ?>
                <div class="text-muted" style="font-size:var(--mci-text-micro);"><?= htmlspecialchars($bizSlug) ?></div>
              <?php endif; ?>
            </td>
            <?php if ($showRole): ?>
              <td>
                <?php if ($addedByRole === 'anonymous'): ?>
                  <span class="badge text-bg-light border"><i class="bi bi-incognito me-1" aria-hidden="true"></i>Anonymous</span>
                <?php elseif ($addedByRole === 'cp_admin'): ?>
                  <span class="badge text-bg-primary"><i class="bi bi-shield-fill me-1" aria-hidden="true"></i>Admin</span>
                <?php else: ?>
                  <span class="badge text-bg-info text-dark"><i class="bi bi-person-fill me-1" aria-hidden="true"></i>Subscriber</span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
            <td class="text-muted small"><?= htmlspecialchars((string)($r['category_name'] ?? '—')) ?></td>
            <?php if ($showStatus): ?>
              <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($rowStatus)) ?></span></td>
            <?php endif; ?>
            <td class="text-muted small text-nowrap">
              <?= $r['created_at'] ? htmlspecialchars(date('F j, Y \a\t g:i:s A', strtotime((string)$r['created_at']))) : '—' ?>
            </td>
            <td>
              <div class="d-flex gap-1 flex-wrap align-items-center">
                <?php if ($bizSlug): ?>
                  <a class="btn btn-sm btn-outline-dark py-0" href="/business/<?= urlencode($bizSlug) ?>/" target="_blank" rel="noopener noreferrer" title="View public page">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                  </a>
                <?php endif; ?>
                <?php if ($rowStatus === 'draft'): ?>
                  <button type="button" class="btn btn-sm btn-outline-success py-0 js-approve-btn"
                    data-id="<?= $bizId ?>" data-name="<?= $bizName ?>">
                    <i class="bi bi-check2" aria-hidden="true"></i> Approve
                  </button>
                <?php endif; ?>
                <?php if ($rowStatus === 'live'): ?>
                  <button type="button" class="btn btn-sm btn-outline-warning py-0 js-suspend-btn"
                    data-id="<?= $bizId ?>" data-name="<?= $bizName ?>">
                    <i class="bi bi-pause-fill" aria-hidden="true"></i> Suspend
                  </button>
                <?php endif; ?>
                <?php if (in_array($rowStatus, ['draft', 'live', 'suspended'], true)): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger py-0 js-reject-btn"
                    data-id="<?= $bizId ?>" data-name="<?= $bizName ?>">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Reject
                  </button>
                <?php endif; ?>
                <?php if (in_array($rowStatus, ['rejected', 'suspended'], true)): ?>
                  <button type="button" class="btn btn-sm btn-outline-success py-0 js-approve-btn"
                    data-id="<?= $bizId ?>" data-name="<?= $bizName ?>">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination + count -->
<div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div class="text-muted small">
    Showing <?= number_format(count($rows)) ?> of <?= number_format((int)$total) ?> listings
  </div>
  <?php if ($pages > 1): ?>
    <nav aria-label="Listings pages">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $curPage - 1 ?>">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </a>
        </li>
        <?php for ($p = max(1, $curPage - 2); $p <= min($pages, $curPage + 2); $p++): ?>
          <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $p ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $curPage >= $pages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $curPage + 1 ?>">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<!-- Confirm modal (reject / suspend) -->
<div class="modal fade" id="cpActionModal" tabindex="-1" aria-labelledby="cpActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="cpActionModalLabel">Confirm action</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Business: <strong id="cpActionBizName">—</strong></p>
        <label for="cpActionNotes" class="form-label small fw-semibold">
          Notes <span class="text-muted fw-normal">(optional)</span>
        </label>
        <textarea class="form-control form-control-sm" id="cpActionNotes" rows="3"
          placeholder="Optional reason or note…"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-sm btn-danger" id="cpActionConfirmBtn">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var pendingAction = null;
  var modalEl = document.getElementById('cpActionModal');
  var actionModal = modalEl ? new bootstrap.Modal(modalEl) : null;

  function cpApiAction(id, action, notes) {
    return fetch('/api/v1/cp/businesses/' + id + '/' + action, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notes: notes || '' })
    }).then(function (r) { return r.json(); });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-approve-btn, .js-reject-btn, .js-suspend-btn');
    if (!btn) return;

    var id   = btn.dataset.id;
    var name = btn.dataset.name || id;

    if (btn.classList.contains('js-approve-btn')) {
      var label = btn.querySelector('i.bi-arrow-counterclockwise') ? 'Restore' : 'Approve';
      if (!confirm(label + ' "' + name + '"?')) return;
      btn.disabled = true;
      cpApiAction(id, 'approve', '')
        .then(function (d) {
          if (d.ok) { location.reload(); }
          else { alert('Error: ' + (d.error || 'unknown')); btn.disabled = false; }
        })
        .catch(function () { alert('Network error.'); btn.disabled = false; });
      return;
    }

    var action = btn.classList.contains('js-reject-btn') ? 'reject' : 'suspend';
    pendingAction = { id: id, action: action };
    document.getElementById('cpActionBizName').textContent = name;
    document.getElementById('cpActionNotes').value = '';
    document.getElementById('cpActionModalLabel').textContent =
      action === 'reject' ? 'Reject listing' : 'Suspend listing';
    var confirmBtn = document.getElementById('cpActionConfirmBtn');
    confirmBtn.className = 'btn btn-sm ' + (action === 'reject' ? 'btn-danger' : 'btn-warning');
    confirmBtn.textContent = action === 'reject' ? 'Reject' : 'Suspend';
    if (actionModal) actionModal.show();
  });

  var confirmBtn = document.getElementById('cpActionConfirmBtn');
  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (!pendingAction) return;
      var notes = document.getElementById('cpActionNotes').value.trim();
      if (actionModal) actionModal.hide();
      cpApiAction(pendingAction.id, pendingAction.action, notes)
        .then(function (d) {
          if (d.ok) { location.reload(); }
          else { alert('Error: ' + (d.error || 'unknown')); }
        })
        .catch(function () { alert('Network error.'); });
      pendingAction = null;
    });
  }
}());
</script>
