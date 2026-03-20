<?php
$pageTitle = 'Comments & ratings - My City Info';
$activePage = '';
$subActive = 'reviews';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_reviews_reply';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);
$csrfOk = false;

$flash = '';
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$statusFilter = $statusFilter !== '' ? $statusFilter : 'all';
$fromDate = trim((string) ($_GET['from_date'] ?? ''));
$toDate = trim((string) ($_GET['to_date'] ?? ''));
$replyFor = trim((string) ($_POST['comment_id'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '') {
    $replyText = trim((string) ($_POST['reply_text'] ?? ''));
    if ($replyText !== '') {
        $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
        if (!mci_csrf_verify($csrfAction, $csrfPost)) {
            $flash = 'Invalid request token. Please refresh and try again.';
            $csrfOk = false;
        } else {
            $flash = 'Reply sent for comment #' . htmlspecialchars($replyFor, ENT_QUOTES, 'UTF-8') . ' (UI demo).';
            $csrfOk = true;
        }
    }
}

$comments = [
    [
        'id' => '74d77c14-9956-4d2e-b4f1-bf96e34f0d5e',
        'user' => 'Anonymous',
        'stars' => 4,
        'text' => 'Great service — punctual and professional. Would recommend to friends.',
        'when' => 'Yesterday',
        'date' => '2026-03-19',
        'status' => 'New',
    ],
    [
        'id' => '2f9a5134-1754-4d8d-93cf-d2747fa2a5ec',
        'user' => 'Anonymous',
        'stars' => 2,
        'text' => 'Good result but communication was slow. Took longer than expected.',
        'when' => '3 days ago',
        'date' => '2026-03-17',
        'status' => 'Waiting reply',
    ],
    [
        'id' => 'b08b95bc-77af-4ce7-b082-e8d9eb0a62f8',
        'user' => 'Anonymous',
        'stars' => 5,
        'text' => 'Excellent! Everything was handled smoothly and on time.',
        'when' => '1 week ago',
        'date' => '2026-03-13',
        'status' => 'Replied',
    ],
];

// UI demo: update status after reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '' && $flash !== '' && $csrfOk) {
    foreach ($comments as &$c) {
        if ((string) $c['id'] === (string) $replyFor) {
            $c['status'] = 'Replied';
            break;
        }
    }
    unset($c);
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
    $comments = array_values(array_filter($comments, static function (array $c) use ($wanted): bool {
        return isset($c['status']) && (string) $c['status'] === (string) $wanted;
    }));
}

$fromDateObj = DateTime::createFromFormat('Y-m-d', $fromDate) ?: null;
$toDateObj = DateTime::createFromFormat('Y-m-d', $toDate) ?: null;
if ($fromDateObj || $toDateObj) {
    $comments = array_values(array_filter($comments, static function (array $c) use ($fromDateObj, $toDateObj): bool {
        $itemDate = DateTime::createFromFormat('Y-m-d', (string) ($c['date'] ?? '')) ?: null;
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

function starsHtml(int $n): string
{
    $n = max(0, min(5, $n));
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $on = $i <= $n;
        $cls = $on ? 'bi bi-star-fill' : 'bi bi-star';
        $style = $on ? 'color: #f59e0b;' : 'color: rgba(148,163,184,0.85);';
        $out .= '<i class="' . $cls . '" aria-hidden="true" style="margin-right:2px;' . $style . '"></i>';
    }
    return $out;
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
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Comments &amp; ratings</div>
            <div class="text-muted small">Reply to comments only — subscribers can’t edit ratings or comments.</div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex align-items-center gap-2 flex-wrap">
              <label class="form-label small mb-0">Status</label>
              <select name="status" class="form-select form-select-sm" aria-label="Filter comments by status">
                <option value="all" <?= $wanted === 'all' ? 'selected' : '' ?>>All</option>
                <option value="new" <?= $wanted === 'New' ? 'selected' : '' ?>>New</option>
                <option value="awaiting" <?= $wanted === 'Waiting reply' ? 'selected' : '' ?>>Awaiting response</option>
                <option value="replied" <?= $wanted === 'Replied' ? 'selected' : '' ?>>Replied</option>
              </select>
              <label class="form-label small mb-0">From</label>
              <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="Filter comments from date" />
              <label class="form-label small mb-0">To</label>
              <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="Filter comments to date" />
              <button type="submit" class="btn btn-sm btn-outline-dark">Filter</button>
            </form>
            <span class="badge text-bg-light border px-3 py-2">Total: <?= count($comments) ?></span>
          </div>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= $flash ?></div>
        <?php endif; ?>

        <div class="d-flex flex-column gap-3">
          <?php foreach ($comments as $c): ?>
            <div class="border rounded-3 p-3 bg-white">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge rounded-pill text-bg-light border">Anonymous</span>
                  <div class="small" role="img" aria-label="Rating: <?= htmlspecialchars((string) (int) $c['stars'], ENT_QUOTES, 'UTF-8') ?> out of 5">
                    <?= starsHtml((int) $c['stars']) ?>
                  </div>
                </div>
                <div class="text-muted small"><?= htmlspecialchars($c['when']) ?></div>
              </div>
              <div class="mb-2">
                <span class="badge text-bg-light border"><?= htmlspecialchars((string) ($c['status'] ?? '')) ?></span>
              </div>

              <div class="small mb-3" style="line-height:1.6;">
                <?= nl2br(htmlspecialchars($c['text'])) ?>
              </div>

              <form method="post" action="" class="row g-2 align-items-end">
                <input type="hidden" name="comment_id" value="<?= htmlspecialchars($c['id']) ?>" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                <div class="col-12 col-md-9">
                  <label class="form-label small mb-1">Reply (visible to user, doesn’t change rating)</label>
                  <textarea class="form-control form-control-sm" name="reply_text" rows="2" placeholder="Write your response…" required></textarea>
                </div>
                <div class="col-12 col-md-3 d-grid">
                  <button type="submit" class="btn btn-sm btn-dark">
                    <i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Reply
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
include __DIR__ . '/../views/layout.php';
?>

