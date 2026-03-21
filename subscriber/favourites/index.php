<?php
$pageTitle = 'Favourite Listings - My City Info';
$activePage = '';
$subActive = 'favourites';
$hideCta = true;
$appArea = 'subscriber';

$csrfAction = 'subscriber_favourites_remove';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flash = '';
$removeSlug = trim((string) ($_POST['remove_slug'] ?? ''));

$rows = [
    ['slug' => 'property-852', 'title' => 'Property 852', 'address' => '12 Orchard Lane, Downtown', 'category' => 'Real Estate', 'saved_on' => '2026-03-18'],
    ['slug' => 'locker-shop-uk', 'title' => 'Locker Shop UK Ltd', 'address' => '88 Market Street, Central District', 'category' => 'Furniture Store', 'saved_on' => '2026-03-16'],
    ['slug' => 'jxf-painting', 'title' => 'JXF Painting Service', 'address' => '4 Riverside Avenue, West End', 'category' => 'Painter', 'saved_on' => '2026-03-12'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $removeSlug !== '') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flash = 'Invalid request token. Please refresh and try again.';
        $removeSlug = '';
    } else {
    $rows = array_values(array_filter($rows, static function (array $r) use ($removeSlug): bool {
        return (string) ($r['slug'] ?? '') !== $removeSlug;
    }));
    $flash = 'Listing removed from favourites (UI demo).';
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
            <div class="fw-semibold">Favourite listings</div>
            <div class="text-muted small">Manage businesses you have saved as favourite (UI demo).</div>
          </div>
          <span class="badge text-bg-light border px-3 py-2">Saved: <?= count($rows) ?></span>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (count($rows) === 0): ?>
          <div class="border rounded-3 p-4 text-center bg-light">
            <div class="fw-semibold mb-1">No favourites yet</div>
            <div class="text-muted small mb-3">Save businesses from listing details and they will appear here.</div>
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
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?= htmlspecialchars($r['title']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($r['address']) ?></div>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($r['category']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($r['saved_on']) ?></td>
                    <td>
                      <div class="d-flex gap-2 flex-wrap">
                        <a class="btn btn-sm btn-outline-dark" href="/business/?slug=<?= urlencode((string) $r['slug']) ?>" target="_blank" rel="noopener noreferrer" title="Open business page in a new window">
                          <i class="bi bi-eye me-1" aria-hidden="true"></i>View <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                        </a>
                        <form method="post" action="" class="d-inline">
                          <input type="hidden" name="remove_slug" value="<?= htmlspecialchars((string) $r['slug'], ENT_QUOTES, 'UTF-8') ?>" />
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
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
