<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_config.php';
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_cp_session();

$pageTitle = 'Dashboard - My City Info';
$activePage = '';
$cpActive = 'dashboard';
$hideCta = true;
$appArea = 'cp';
$cpRole  = (string) ($_SESSION['mci_cp_role'] ?? 'co_admin');

$extraJS = '';
if ($cpRole === 'super_admin') {
    $extraJS = <<<'HTML'
<script>
(function () {
  var btn = document.getElementById('mciCpClearPublicCacheBtn');
  var st = document.getElementById('mciCpClearPublicCacheStatus');
  if (!btn) return;
  var api = typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; };
  btn.addEventListener('click', function () {
    if (!window.confirm('Clear cached public listings, categories, tags, and home page data? Visitors will see fresh data on the next request.')) return;
    btn.disabled = true;
    if (st) {
      st.textContent = 'Working…';
      st.className = 'small text-muted ms-2';
    }
    fetch(api('/cp/cache/clear-public'), {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: '{}'
    })
      .then(function (r) {
        return r.text().then(function (t) {
          var j;
          try {
            j = JSON.parse(t);
          } catch (e) {
            throw new Error(t || 'Invalid response');
          }
          if (!r.ok) throw new Error(String(j.error || j.message || 'Request failed'));
          return j;
        });
      })
      .then(function (d) {
        if (!st) return;
        if (d.cache_enabled === false) {
          st.textContent = 'Caching is disabled (MCI_CACHE_DISABLE); nothing to invalidate.';
          st.className = 'small text-warning ms-2';
        } else {
          st.textContent = 'Public cache cleared (version ' + (d.public_directory_version != null ? d.public_directory_version : '—') + ').';
          st.className = 'small text-success ms-2';
        }
      })
      .catch(function (e) {
        if (st) {
          st.textContent = e.message || 'Failed';
          st.className = 'small text-danger ms-2';
        }
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
</script>
HTML;
}

require_once __DIR__ . '/../../api/v1/lib/db.php';

// ── Stats from DB ─────────────────────────────────────────
$stats = [
    'subscribers'  => 0,
    'total'        => 0,
    'pending'      => 0,
    'errors_today' => 0,
];

$pendingListings = [];

try {
    $pdo = api_db();

    // Keep each metric isolated so one failing query doesn't blank the dashboard.
    try {
        $r = $pdo->query("
            SELECT COUNT(*)
            FROM mci_users u
            INNER JOIN mci_roles r ON r.id = u.role_id
            WHERE r.short_name = 'subscriber'
              AND u.deleted_at IS NULL
              AND u.status <> 'deleted'
        ");
        $stats['subscribers'] = (int) ($r ? $r->fetchColumn() : 0);
    } catch (Throwable $ignored) {}

    try {
        $r = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE status <> 'deleted'");
        $stats['total'] = (int) ($r ? $r->fetchColumn() : 0);
    } catch (Throwable $ignored) {}

    try {
        $r = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE status = 'draft'");
        $stats['pending'] = (int) ($r ? $r->fetchColumn() : 0);
    } catch (Throwable $ignored) {}

    try {
        $r = $pdo->query("SELECT COUNT(*) FROM mci_error_log WHERE created_at >= NOW() - INTERVAL 1 DAY");
        $stats['errors_today'] = (int) ($r ? $r->fetchColumn() : 0);
    } catch (Throwable $ignored) {}

    // Recent pending listings for quick moderation
    $stmt = $pdo->query(
        "SELECT g.id, g.name, g.slug, g.added_by_role, g.created_at
         FROM mci_business_groups g
         WHERE g.status = 'draft'
         ORDER BY g.created_at DESC
         LIMIT " . MCI_CP_DASHBOARD_PENDING_LIMIT . ""
    );
    $pendingListings = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

} catch (Throwable $ignored) {
    // DB not ready — stats stay at 0
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Page header -->
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
      <div>
        <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Dashboard</div>
        <div class="text-muted small mt-1">
          <?= date('l, F j, Y') ?> &mdash;
          <?= $cpRole === 'super_admin' ? 'Super admin' : 'Co-admin' ?>
        </div>
      </div>
    </div>

    <?php if (($_GET['notice'] ?? '') === 'forbidden'): ?>
      <div class="alert alert-warning py-2 small mb-3" role="status">That page is only available to super admins.</div>
    <?php endif; ?>

    <!-- ── Stat tiles ───────────────────────────────────── -->
    <div class="row g-3 mb-4">

      <div class="col-6 col-md-3">
        <a href="/cp/subscribers/" class="text-decoration-none">
          <div class="mci-stat-tile h-100">
            <div class="mci-stat-tile__icon text-primary"><i class="bi bi-people-fill" aria-hidden="true"></i></div>
            <div class="mci-stat-tile__value"><?= number_format($stats['subscribers']) ?></div>
            <div class="mci-stat-tile__label">Subscribers</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="/cp/listings/all/" class="text-decoration-none">
          <div class="mci-stat-tile h-100">
            <div class="mci-stat-tile__icon" style="color:var(--mci-color-primary-deep);"><i class="bi bi-collection-fill" aria-hidden="true"></i></div>
            <div class="mci-stat-tile__value"><?= number_format($stats['total']) ?></div>
            <div class="mci-stat-tile__label">Total businesses</div>
          </div>
        </a>
      </div>

      <div class="col-6 col-md-3">
        <a href="/cp/listings/awaiting-approval/" class="text-decoration-none">
          <div class="mci-stat-tile h-100 <?= $stats['pending'] > 0 ? 'mci-stat-tile--warn' : '' ?>">
            <div class="mci-stat-tile__icon text-warning"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
            <div class="mci-stat-tile__value <?= $stats['pending'] > 0 ? 'text-warning' : '' ?>"><?= $stats['pending'] ?></div>
            <div class="mci-stat-tile__label">Pending review</div>
          </div>
        </a>
      </div>

      <?php if ($cpRole === 'super_admin'): ?>
      <div class="col-6 col-md-3">
        <a href="/cp/error-log/" class="text-decoration-none">
          <div class="mci-stat-tile h-100 <?= $stats['errors_today'] > 0 ? 'mci-stat-tile--danger' : '' ?>">
            <div class="mci-stat-tile__icon text-danger"><i class="bi bi-bug-fill" aria-hidden="true"></i></div>
            <div class="mci-stat-tile__value <?= $stats['errors_today'] > 0 ? 'text-danger' : '' ?>"><?= $stats['errors_today'] ?></div>
            <div class="mci-stat-tile__label">Errors (24 h)</div>
          </div>
        </a>
      </div>
      <?php else: ?>
      <div class="col-6 col-md-3">
        <a href="/cp/categories/" class="text-decoration-none">
          <div class="mci-stat-tile h-100">
            <div class="mci-stat-tile__icon" style="color:#059669;"><i class="bi bi-tags-fill" aria-hidden="true"></i></div>
            <div class="mci-stat-tile__value">—</div>
            <div class="mci-stat-tile__label">Categories</div>
          </div>
        </a>
      </div>
      <?php endif; ?>

    </div>

    <!-- ── Quick actions strip ──────────────────────────── -->
    <div class="mci-responsive-actions mb-4">
      <a href="/cp/anonymous-business/" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add business
      </a>
      <a href="/cp/listings/awaiting-approval/" class="btn btn-sm btn-outline-warning">
        <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Review pending
      </a>
      <a href="/cp/categories/" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-tags me-1" aria-hidden="true"></i>Manage categories
      </a>
      <?php if ($cpRole === 'super_admin'): ?>
        <a href="/cp/error-log/" class="btn btn-sm btn-outline-danger">
          <i class="bi bi-bug me-1" aria-hidden="true"></i>Error log
        </a>
      <?php endif; ?>
    </div>

    <?php if ($cpRole === 'super_admin'): ?>
    <div class="card border-0 shadow-sm mb-4" id="mciCpPublicCacheCard">
      <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 flex-wrap">
          <div class="text-secondary" style="font-size:1.5rem;" aria-hidden="true"><i class="bi bi-arrow-clockwise"></i></div>
          <div class="flex-grow-1" style="min-width:220px;">
            <div class="fw-semibold mb-1">Public site cache</div>
            <p class="text-muted small mb-3 mb-md-0">
              Bumps the cache version for visitor-facing listings, category/tag API responses, and home page snapshots (APCu or <code class="small">storage/cache/app</code>). Use after bulk data fixes or if the public site looks out of date.
            </p>
          </div>
          <div class="d-flex flex-column align-items-stretch align-items-md-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-dark" id="mciCpClearPublicCacheBtn">
              <i class="bi bi-trash3 me-1" aria-hidden="true"></i>Clear public cache
            </button>
            <span class="small text-muted" id="mciCpClearPublicCacheStatus" aria-live="polite"></span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Pending listings queue ───────────────────────── -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="fw-semibold">Pending business listings</div>
          <a href="/cp/listings/awaiting-approval/" class="btn btn-sm btn-outline-secondary">
            View all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
          </a>
        </div>

        <?php if (count($pendingListings) === 0): ?>
          <div class="text-center py-4 rounded-3" style="background:var(--mci-color-success-soft,#f0fdf4);">
            <div style="font-size:var(--mci-text-2xl);color:var(--mci-color-success,#16a34a);" aria-hidden="true" class="mb-2">
              <i class="bi bi-check2-circle"></i>
            </div>
            <div class="fw-semibold" style="color:var(--mci-color-success,#16a34a);">Queue is clear</div>
            <div class="text-muted small mt-1">No pending listings to review.</div>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:var(--mci-text-sm);">
              <thead class="table-light">
                <tr>
                  <th>Business name</th>
                  <th>Submitted by</th>
                  <th>Submitted</th>
                  <th style="min-width:120px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($pendingListings as $r): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars((string) $r['name']) ?></td>
                    <td>
                      <span class="badge text-bg-light border"><?= htmlspecialchars((string) $r['added_by_role']) ?></span>
                    </td>
                    <td class="text-muted small text-nowrap">
                      <?= htmlspecialchars(date('F j, Y \a\t g:i:s A', strtotime((string) $r['created_at']))) ?>
                    </td>
                    <td>
                      <a href="/cp/listings/awaiting-approval/?review=<?= rawurlencode((string) $r['id']) ?>" class="btn btn-sm btn-outline-dark py-0">
                        <i class="bi bi-eye me-1" aria-hidden="true"></i>Review
                      </a>
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
