<?php
$pageTitle = 'Enquiries - My City Info';
$activePage = '';
$subActive = 'enquiries';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';
require_once __DIR__ . '/../../api/v1/lib/leads_service.php';

$csrfAction = 'subscriber_enquiries_reply';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$userId = (string)($_SESSION['mci_user_id'] ?? '');

$replyFlash   = '';
$statusFilter = trim((string) ($_GET['status']   ?? 'all'));
$statusFilter = $statusFilter !== '' ? $statusFilter : 'all';
$fromDate     = trim((string) ($_GET['from_date'] ?? ''));
$toDate       = trim((string) ($_GET['to_date']   ?? ''));
$replyFor     = trim((string) ($_POST['enquiry_id'] ?? ''));

// Handle reply POST — marks enquiry as 'replied'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '') {
    $replyText = trim((string) ($_POST['reply_text'] ?? ''));
    if ($replyText !== '') {
        $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
        if (!mci_csrf_verify($csrfAction, $csrfPost)) {
            $replyFlash = 'Invalid request token. Please refresh and try again.';
        } else {
            try {
                $pdo = api_db();
                leads_update_status($pdo, $replyFor, 'replied', $userId);
                $replyFlash = 'Reply recorded.';
            } catch (Throwable) {
                $replyFlash = 'Could not save reply. Please try again.';
            }
        }
    }
}

// Map UI filter value to DB status
$statusMap = [
    'all'      => 'all',
    'new'      => 'new',
    'awaiting' => 'new',
    'replied'  => 'replied',
];
$wanted = $statusMap[strtolower($statusFilter)] ?? 'all';

// Load from DB
$enquiries = [];
try {
    $pdo       = api_db();
    $result    = leads_list($pdo, $userId, 'enquiry', $wanted, '', $fromDate, $toDate);
    $rawItems  = $result['items'];
    // Map DB fields to the template's expected keys
    $enquiries = array_map(static function (array $item): array {
        return [
            'id'      => $item['id'],
            'listing' => $item['listing'],
            'from'    => $item['name'],
            'email'   => $item['email'],
            'message' => $item['message'],
            'when'    => $item['when'],
            'date'    => $item['date'],
            'status'  => $item['status'],
        ];
    }, $rawItems);
} catch (Throwable) {}

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
            <div class="fw-semibold">Enquiries</div>
            <div class="text-muted small">View incoming enquiries and reply to each one from here.</div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
              <label class="form-label small mb-0">Status</label>
              <select name="status" class="form-select form-select-sm" aria-label="Filter enquiries by status">
                <option value="all" <?= $wanted === 'all'     ? 'selected' : '' ?>>All</option>
                <option value="new" <?= $wanted === 'new'     ? 'selected' : '' ?>>New</option>
                <option value="awaiting" <?= $wanted === 'new'  ? 'selected' : '' ?>>Awaiting response</option>
                <option value="replied" <?= $wanted === 'replied' ? 'selected' : '' ?>>Replied</option>
              </select>
              <label class="form-label small mb-0">From</label>
              <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="Filter enquiries from date" />
              <label class="form-label small mb-0">To</label>
              <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="Filter enquiries to date" />
              <button type="submit" class="btn btn-sm btn-outline-dark">Filter</button>
            </form>
            <span class="badge text-bg-light border px-3 py-2">Total: <?= count($enquiries) ?></span>
          </div>
        </div>

        <?php if ($replyFlash !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= $replyFlash ?></div>
        <?php endif; ?>

        <div class="d-flex flex-column gap-3">
          <?php foreach ($enquiries as $q): ?>
            <div class="border rounded-3 p-3 bg-white">
              <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-2">
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($q['from']) ?> <span class="text-muted fw-normal">· <?= htmlspecialchars($q['email']) ?></span></div>
                  <div class="text-muted small">For <?= htmlspecialchars($q['listing']) ?> · <?= htmlspecialchars($q['when']) ?></div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php
                  $enqBadgeClass = match($q['status']) {
                      'new'     => 'text-bg-primary',
                      'replied' => 'text-bg-success',
                      default   => 'text-bg-light border',
                  };
                  ?>
                  <span class="badge <?= $enqBadgeClass ?>"><?= htmlspecialchars($q['status']) ?></span>
                  <?php if ($q['status'] === 'new'): ?>
                    <form method="post" action="" class="d-inline">
                      <input type="hidden" name="enquiry_id" value="<?= htmlspecialchars($q['id']) ?>" />
                      <input type="hidden" name="reply_text" value="(marked as read)" />
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                      <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Mark as read" style="font-size:var(--mci-text-xs)">
                        <i class="bi bi-check2-all" aria-hidden="true"></i> Mark read
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
              <p class="small mb-3"><?= htmlspecialchars($q['message']) ?></p>

              <form method="post" action="" class="row g-2 align-items-end">
                <input type="hidden" name="enquiry_id" value="<?= htmlspecialchars($q['id']) ?>" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="col-12 col-md-9">
                  <label class="form-label small mb-1">Reply to this enquiry</label>
                  <textarea class="form-control form-control-sm" name="reply_text" rows="2" placeholder="Type your response…" required></textarea>
                </div>
                <div class="col-12 col-md-3 d-grid">
                  <button type="submit" class="btn btn-sm btn-dark">
                    <i class="bi bi-send me-1" aria-hidden="true"></i>Send reply
                  </button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

