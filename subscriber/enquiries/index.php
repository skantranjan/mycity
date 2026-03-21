<?php
$pageTitle = 'Enquiries - My City Info';
$activePage = '';
$subActive = 'enquiries';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_enquiries_reply';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);
$csrfOk = false;

$replyFlash = '';
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$statusFilter = $statusFilter !== '' ? $statusFilter : 'all';
$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));
$replyFor = trim((string) ($_POST['enquiry_id'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '') {
    $replyText = trim((string) ($_POST['reply_text'] ?? ''));
    if ($replyText !== '') {
        $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
        if (!mci_csrf_verify($csrfAction, $csrfPost)) {
            $replyFlash = 'Invalid request token. Please refresh and try again.';
            $csrfOk = false;
        } else {
            $replyFlash = 'Reply sent for enquiry #' . htmlspecialchars($replyFor, ENT_QUOTES, 'UTF-8') . ' (UI demo).';
            $csrfOk = true;
        }
    }
}

$enquiries = [
    [
        'id' => '4f2286e9-d99f-4337-97e6-2ee38f8b14d5',
        'listing' => 'JXF Painting Service',
        'from' => 'Ava Thompson',
        'email' => 'ava.t@example.com',
        'message' => 'Do you handle exterior repainting for small retail shops? Looking for work in next 2 weeks.',
        'when' => '2 hours ago',
        'date' => '2026-03-20',
        'status' => 'New',
    ],
    [
        'id' => '7f6a4ff8-08d0-47a8-8928-a89ffeb15b88',
        'listing' => 'Locker Shop UK Ltd',
        'from' => 'School Admin Team',
        'email' => 'ops@school-demo.org',
        'message' => 'Need quote for 120 student lockers with delivery timeline. Can you share options?',
        'when' => 'Yesterday',
        'date' => '2026-03-19',
        'status' => 'Waiting reply',
    ],
    [
        'id' => 'f0d42f4e-92fe-425d-9fd9-15c58c181e2a',
        'listing' => 'Property 852',
        'from' => 'Noah Lee',
        'email' => 'noahl@example.net',
        'message' => 'Please share if 800-1000 sq ft office spaces are available this month.',
        'when' => '2 days ago',
        'date' => '2026-03-18',
        'status' => 'Replied',
    ],
];

// UI demo: update status after reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '' && $replyFlash !== '' && $csrfOk) {
    foreach ($enquiries as &$q) {
        if ((string) $q['id'] === (string) $replyFor) {
            $q['status'] = 'Replied';
            break;
        }
    }
    unset($q);
}

// Filter by status (UI demo)
$statusMap = [
    'all' => 'all',
    'new' => 'New',
    'awaiting' => 'Waiting reply',
    'waiting' => 'Waiting reply',
    'waiting reply' => 'Waiting reply',
    'replied' => 'Replied',
];
$key = strtolower(trim($statusFilter));
$wanted = $statusMap[$key] ?? 'all';
if ($wanted !== 'all') {
    $enquiries = array_values(array_filter($enquiries, static function (array $q) use ($wanted): bool {
        return isset($q['status']) && (string) $q['status'] === (string) $wanted;
    }));
}

$fromDateObj = DateTime::createFromFormat('Y-m-d', $fromDate) ?: null;
$toDateObj = DateTime::createFromFormat('Y-m-d', $toDate) ?: null;
if ($fromDateObj || $toDateObj) {
    $enquiries = array_values(array_filter($enquiries, static function (array $q) use ($fromDateObj, $toDateObj): bool {
        $itemDate = DateTime::createFromFormat('Y-m-d', (string) ($q['date'] ?? '')) ?: null;
        if ($itemDate === null) {
            return false;
        }
        if ($fromDateObj && $itemDate < $fromDateObj) {
            return false;
        }
        if ($toDateObj && $itemDate > $toDateObj) {
            return false;
        }
        return true;
    }));
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
            <div class="fw-semibold">Enquiries</div>
            <div class="text-muted small">View incoming enquiries and reply to each one from here (UI demo).</div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
              <label class="form-label small mb-0">Status</label>
              <select name="status" class="form-select form-select-sm" aria-label="Filter enquiries by status">
                <option value="all" <?= $wanted === 'all' ? 'selected' : '' ?>>All</option>
                <option value="new" <?= $wanted === 'New' ? 'selected' : '' ?>>New</option>
                <option value="awaiting" <?= $wanted === 'Waiting reply' ? 'selected' : '' ?>>Awaiting response</option>
                <option value="replied" <?= $wanted === 'Replied' ? 'selected' : '' ?>>Replied</option>
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
                <span class="badge text-bg-light border"><?= htmlspecialchars($q['status']) ?></span>
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

