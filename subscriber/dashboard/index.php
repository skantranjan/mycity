<?php
$pageTitle = 'Subscriber Dashboard - My City Info';
$activePage = '';
$subActive = 'dashboard';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_config.php';
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';

$userId = (string)($_SESSION['mci_user_id'] ?? '');

// ── Real stats from DB ────────────────────────────────────────────────────────
$stats = ['live' => 0, 'draft' => 0, 'suspended' => 0, 'total' => 0];

if ($userId !== '') {
    try {
        $pdo = api_db();

        // Status counts
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) AS cnt FROM mci_business_groups
             WHERE added_by_user_id = ? AND status != 'deleted'
             GROUP BY status"
        );
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $stats[(string)$row['status']] = (int)$row['cnt'];
        }
        $stats['total'] = array_sum($stats);
    } catch (Throwable $e) {
        // Graceful degradation — stats stay 0
    }
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
      <div>
        <div class="fw-semibold fs-5">Dashboard</div>
        <div class="text-muted small">Overview of your listing activity.</div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-dark" href="/subscriber/listings/">
          <i class="bi bi-shop-window me-1" aria-hidden="true"></i>My listings
        </a>
        <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">
          <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add business
        </a>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4"><?= $stats['total'] ?></div>
            <div class="text-muted small">Total Listings</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/?status=pending" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-warning"><?= $stats['draft'] ?></div>
            <div class="text-muted small">Pending Approval</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-warning);">Awaiting review</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/listings/?status=live" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-success"><?= $stats['live'] ?></div>
            <div class="text-muted small">Live</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-color-success);">Active &amp; visible</div>
          </div>
        </a>
      </div>
      <div class="col-6 col-md-3">
        <a href="/subscriber/leads/" class="text-decoration-none">
          <div class="card border-0 shadow-sm text-center p-3 h-100">
            <div class="fw-bold fs-4 text-primary">—</div>
            <div class="text-muted small">Total Leads</div>
            <div class="mt-1" style="font-size:var(--mci-text-micro);color:var(--mci-text-muted);">Coming soon</div>
          </div>
        </a>
      </div>
    </div>

    <!-- Recent Leads -->
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
          <div class="fw-semibold">Recent Leads</div>
          <a class="btn btn-sm btn-outline-dark" href="/subscriber/leads/">
            <i class="bi bi-person-lines-fill me-1" aria-hidden="true"></i>View all
          </a>
        </div>
        <div class="text-muted small py-3 text-center">
          <i class="bi bi-hourglass-split fs-4 d-block mb-2 opacity-50" aria-hidden="true"></i>
          Leads will appear here once the feature is live.
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
