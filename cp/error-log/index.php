<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_super_admin_session();

$pageTitle = 'Error Log - My City Info';
$activePage = '';
$cpActive = 'error-log';
$hideCta = true;
$appArea = 'cp';

require_once __DIR__ . '/../../api/v1/lib/db.php';

// ── Filters ──────────────────────────────────────────────
$levelFilter = trim((string) ($_GET['level'] ?? 'all'));
$validLevels = ['all', 'exception', 'fatal', 'error', 'warning'];
if (!in_array($levelFilter, $validLevels, true)) {
    $levelFilter = 'all';
}

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

// ── Query ─────────────────────────────────────────────────
$rows  = [];
$total = 0;
$levelCounts = ['exception' => 0, 'fatal' => 0, 'error' => 0, 'warning' => 0];
$dbReady = false;

try {
    $pdo = api_db();
    $dbReady = true;

    // Level counts
    $countRows = $pdo->query("SELECT level, COUNT(*) AS cnt FROM mci_error_log GROUP BY level");
    if ($countRows) {
        foreach ($countRows->fetchAll(PDO::FETCH_ASSOC) as $cr) {
            if (isset($levelCounts[$cr['level']])) {
                $levelCounts[$cr['level']] = (int) $cr['cnt'];
            }
        }
    }

    // Total for current filter
    if ($levelFilter === 'all') {
        $totalRow = $pdo->query("SELECT COUNT(*) FROM mci_error_log");
        $total = (int) ($totalRow ? $totalRow->fetchColumn() : 0);

        $stmt = $pdo->prepare("SELECT * FROM mci_error_log ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    } else {
        $totalRow = $pdo->prepare("SELECT COUNT(*) FROM mci_error_log WHERE level = :level");
        $totalRow->execute([':level' => $levelFilter]);
        $total = (int) ($totalRow->fetchColumn() ?? 0);

        $stmt = $pdo->prepare("SELECT * FROM mci_error_log WHERE level = :level ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':level', $levelFilter, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $dbReady = false;
}

$totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

$levelBadge = [
    'exception' => 'text-bg-danger',
    'fatal'     => 'text-bg-dark',
    'error'     => 'text-bg-warning',
    'warning'   => 'text-bg-secondary',
];

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
      <div>
        <div class="fw-semibold" style="font-size:var(--mci-text-xl);">Error log</div>
        <div class="text-muted small">Application exceptions, fatal errors, and warnings captured by the system.</div>
      </div>
      <?php if ($total > 0): ?>
        <a href="?clear=1" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Clear ALL error log entries? This cannot be undone.')">
          <i class="bi bi-trash me-1" aria-hidden="true"></i>Clear all
        </a>
      <?php endif; ?>
    </div>

    <?php
    // Handle clear action
    if (isset($_GET['clear']) && $dbReady) {
        try {
            api_db()->exec("TRUNCATE TABLE mci_error_log");
            header('Location: /cp/error-log/');
            exit;
        } catch (Throwable $ignored) {}
    }
    ?>

    <?php if (!$dbReady): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
        Could not connect to the database. Run migration <code>010_system_error_log.sql</code> first.
      </div>
    <?php else: ?>

    <!-- Level filter pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
      <?php
      $allCount = array_sum($levelCounts);
      $filters = ['all' => $allCount] + $levelCounts;
      $filterLabels = ['all' => 'All', 'exception' => 'Exceptions', 'fatal' => 'Fatals', 'error' => 'Errors', 'warning' => 'Warnings'];
      foreach ($filters as $fKey => $fCnt):
        $isActive = $levelFilter === $fKey;
      ?>
        <a href="?level=<?= urlencode($fKey) ?>"
           class="btn btn-sm <?= $isActive ? 'btn-secondary' : 'btn-outline-secondary' ?> d-flex align-items-center gap-1">
          <?= htmlspecialchars($filterLabels[$fKey]) ?>
          <?php if ($fCnt > 0): ?>
            <span class="badge rounded-pill <?= $isActive ? 'bg-white text-dark' : 'text-bg-secondary' ?>"><?= $fCnt ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Log table -->
    <?php if (count($rows) === 0): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <div class="mb-3" style="font-size:2.5rem;" aria-hidden="true"><i class="bi bi-check2-circle text-success"></i></div>
          <div class="fw-semibold">No errors logged</div>
          <div class="text-muted small mt-1">
            <?= $levelFilter === 'all' ? 'The error log is empty — everything looks healthy.' : 'No ' . htmlspecialchars($levelFilter) . ' entries found.' ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="card border-0 shadow-sm mb-4">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:var(--mci-text-xs);">
            <thead class="table-light">
              <tr>
                <th style="width:120px;">Level</th>
                <th>Message</th>
                <th style="width:220px;">File : line</th>
                <th style="width:160px;">When</th>
                <th style="width:48px;"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row):
                $badge = $levelBadge[$row['level']] ?? 'text-bg-secondary';
                $msgShort = mb_strimwidth((string) $row['message'], 0, 120, '…');
                $fileShort = $row['file'] ? basename((string) $row['file']) . ':' . $row['line'] : '—';
                $ctx = $row['context'] ? json_decode((string) $row['context'], true) : null;
                $trace = is_array($ctx) ? ($ctx['trace'] ?? null) : null;
              ?>
                <tr>
                  <td>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars(strtoupper($row['level'])) ?></span>
                  </td>
                  <td class="text-break fw-medium" style="max-width:340px;">
                    <?= htmlspecialchars($msgShort, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($row['uri']): ?>
                      <div class="text-muted mt-1" style="font-size:var(--mci-text-micro);">
                        <i class="bi bi-link-45deg" aria-hidden="true"></i> <?= htmlspecialchars((string) $row['uri']) ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted text-break font-monospace" style="font-size:var(--mci-text-micro);">
                    <?= htmlspecialchars($fileShort) ?>
                  </td>
                  <td class="text-muted text-nowrap">
                    <?= htmlspecialchars(date('F j, Y \a\t g:i:s A', strtotime((string) $row['created_at']))) ?>
                  </td>
                  <td>
                    <?php if ($trace): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-1"
                        data-bs-toggle="modal"
                        data-bs-target="#traceModal"
                        data-trace="<?= htmlspecialchars($trace, ENT_QUOTES, 'UTF-8') ?>"
                        data-msg="<?= htmlspecialchars((string) $row['message'], ENT_QUOTES, 'UTF-8') ?>"
                        title="View stack trace">
                        <i class="bi bi-code-square" aria-hidden="true"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Error log pages">
          <ul class="pagination pagination-sm justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?level=<?= urlencode($levelFilter) ?>&page=<?= $page - 1 ?>">
                  <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
              </li>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="?level=<?= urlencode($levelFilter) ?>&page=<?= $p ?>"><?= $p ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?level=<?= urlencode($levelFilter) ?>&page=<?= $page + 1 ?>">
                  <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
        <div class="text-center text-muted small">
          Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $perPage, $total)) ?> of <?= number_format($total) ?> entries
        </div>
      <?php endif; ?>

    <?php endif; // count($rows) ?>
    <?php endif; // $dbReady ?>

  </div>
</div>

<!-- Stack trace modal -->
<div class="modal fade" id="traceModal" tabindex="-1" aria-labelledby="traceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="traceModalLabel">Stack trace</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3" id="traceModalMsg"></p>
        <pre class="bg-dark text-white rounded-2 p-3 small" style="white-space:pre-wrap;word-break:break-all;max-height:60vh;overflow-y:auto;" id="traceModalPre"></pre>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('traceModal');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function (e) {
    var btn = e.relatedTarget;
    document.getElementById('traceModalMsg').textContent = btn.dataset.msg || '';
    document.getElementById('traceModalPre').textContent = btn.dataset.trace || '(no trace available)';
  });
}());
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
