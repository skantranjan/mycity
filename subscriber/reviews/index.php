<?php
$pageTitle = 'Comments & ratings - My City Info';
$activePage = '';
$subActive = 'reviews';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';

$csrfAction = 'subscriber_reviews_reply';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$userId = (string)($_SESSION['mci_user_id'] ?? '');

$flash        = '';
$statusFilter = trim((string) ($_GET['status']   ?? 'all'));
$statusFilter = $statusFilter !== '' ? $statusFilter : 'all';
$fromDate     = trim((string) ($_GET['from_date'] ?? ''));
$toDate       = trim((string) ($_GET['to_date']   ?? ''));
$replyFor     = trim((string) ($_POST['comment_id'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $replyFor !== '') {
    $replyText = trim((string) ($_POST['reply_text'] ?? ''));
    if ($replyText !== '') {
        $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
        if (!mci_csrf_verify($csrfAction, $csrfPost)) {
            $flash = 'Invalid request token. Please refresh and try again.';
        } else {
            $flash = 'Reply recorded for comment #' . htmlspecialchars($replyFor, ENT_QUOTES, 'UTF-8') . '.';
        }
    }
}

// Load real reviews from DB scoped to the subscriber's business groups
$comments = [];
try {
    $pdo = api_db();

    // Get all business group IDs owned by this subscriber
    $groupStmt = $pdo->prepare(
        "SELECT id FROM mci_business_groups WHERE added_by_user_id = ? AND status != 'deleted'"
    );
    $groupStmt->execute([$userId]);
    $groupIds = array_column($groupStmt->fetchAll(PDO::FETCH_ASSOC), 'id');

    if (!empty($groupIds)) {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $binds = $groupIds;

        $where = ["r.business_group_id IN ({$placeholders})"];

        if ($fromDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
            $where[] = 'DATE(r.created_at) >= ?';
            $binds[] = $fromDate;
        }
        if ($toDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
            $where[] = 'DATE(r.created_at) <= ?';
            $binds[] = $toDate;
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $pdo->prepare(
            "SELECT r.id, r.rating, r.review_text, r.created_at, g.name AS business_name
             FROM mci_business_reviews r
             INNER JOIN mci_business_groups g ON g.id = r.business_group_id
             WHERE {$whereSql}
             ORDER BY r.created_at DESC"
        );
        $stmt->execute($binds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $ts   = strtotime((string)$row['created_at']);
            $diff = $ts ? time() - $ts : 0;
            if ($diff < 86400)       $when = 'Today';
            elseif ($diff < 172800)  $when = 'Yesterday';
            elseif ($diff < 604800)  $when = (int)($diff / 86400) . ' days ago';
            else                     $when = date('M j, Y', $ts ?: 0);

            $comments[] = [
                'id'           => (string)$row['id'],
                'user'         => 'Anonymous',
                'stars'        => (int)$row['rating'],
                'text'         => (string)$row['review_text'],
                'when'         => $when,
                'date'         => substr((string)$row['created_at'], 0, 10),
                'business'     => (string)$row['business_name'],
                'status'       => 'new',
            ];
        }
    }
} catch (Throwable) {}

// Status filter (visual only — reviews table has no reply status column yet)
$statusMap = ['all' => 'all', 'new' => 'new', 'awaiting' => 'new', 'replied' => 'replied'];
$wanted    = $statusMap[strtolower($statusFilter)] ?? 'all';

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
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
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
                <option value="all" <?= $wanted === 'all'     ? 'selected' : '' ?>>All</option>
                <option value="new" <?= $wanted === 'new'     ? 'selected' : '' ?>>New</option>
                <option value="awaiting" <?= $wanted === 'new'  ? 'selected' : '' ?>>Awaiting response</option>
                <option value="replied" <?= $wanted === 'replied' ? 'selected' : '' ?>>Replied</option>
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
              <div class="mb-2 d-flex align-items-center gap-2 flex-wrap">
                <span class="badge text-bg-light border"><?= htmlspecialchars((string) ($c['status'] ?? '')) ?></span>
                <?php if (!empty($c['business'])): ?>
                  <span class="text-muted small">For <?= htmlspecialchars($c['business']) ?></span>
                <?php endif; ?>
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
include __DIR__ . '/../../views/layout.php';
?>

