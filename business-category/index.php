<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_category_icons.php';
require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/business_service.php';

$pageTitle = 'All Categories - My City Info';
$activePage = 'categories';
$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

// ── Load data from DB ─────────────────────────────────────────────────────────
$pdo           = api_db();
$locations     = [];
$categories    = [];
$newlyAdded    = [];
$established   = [];

try {
    // Distinct cities from live business branches
    $locStmt = $pdo->query("
        SELECT DISTINCT b.city
        FROM mci_business_branches b
        INNER JOIN mci_business_groups g ON g.id = b.business_group_id
        WHERE g.status = 'live' AND b.city != ''
        ORDER BY b.city
    ");
    $locations = $locStmt ? array_column($locStmt->fetchAll(PDO::FETCH_ASSOC), 'city') : [];
} catch (Throwable $ignored) {}

$selectedLocation = trim((string)($_GET['location'] ?? ''));
if ($selectedLocation === '') {
    $selectedLocation = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
// Only keep if it matches a known city; otherwise show all
if ($selectedLocation !== '' && !in_array($selectedLocation, $locations, true)) {
    $selectedLocation = '';
}

// ── Categories with business count (filtered by location if set) ───────────
try {
    $catSql = "
        SELECT c.name, c.slug, c.icon, COUNT(DISTINCT g.id) AS cnt
        FROM mci_categories c
        INNER JOIN mci_business_groups g ON g.parent_category_id = c.id AND g.status = 'live'
        LEFT JOIN mci_business_branches b ON b.business_group_id = g.id
        WHERE c.parent_id IS NULL
    ";
    $catParams = [];
    if ($selectedLocation !== '') {
        $catSql .= ' AND b.city = :city';
        $catParams[':city'] = $selectedLocation;
    }
    $catSql .= ' GROUP BY c.id, c.name, c.slug, c.icon ORDER BY c.sort_order, c.name';
    $catStmt = $pdo->prepare($catSql);
    $catStmt->execute($catParams);
    foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ((int)$row['cnt'] > 0) {
            $categories[] = [
                'name'  => $row['name'],
                'slug'  => $row['slug'],
                'icon'  => $row['icon'] ?: mci_category_icon($row['slug']),
                'count' => (int)$row['cnt'],
            ];
        }
    }
} catch (Throwable $ignored) {}

// ── Recently added + established (8 each) ───────────────────────────────────
try {
    $listFilters = ['per_page' => 8];
    if ($selectedLocation !== '') {
        $listFilters['city'] = $selectedLocation;
    }

    $recentRows = api_business_list_public($pdo, array_merge($listFilters, ['sort' => 'newest']))['businesses'] ?? [];
    $oldestRows = api_business_list_public($pdo, array_merge($listFilters, ['sort' => 'oldest']))['businesses'] ?? [];

    $rowToCard = static function (array $row): array {
        return [
            'title'      => (string)($row['name']          ?? ''),
            'category'   => (string)($row['category_name'] ?? ''),
            'location'   => (string)($row['city']          ?? ''),
            'address'    => (string)($row['city']          ?? ''),
            'slug'       => (string)($row['slug']          ?? ''),
            'image'      => !empty($row['logo_path'])
                              ? $row['logo_path']
                              : (!empty($row['banner_path'])
                                  ? $row['banner_path']
                                  : 'https://picsum.photos/seed/mci-' . ($row['slug'] ?? 'biz') . '/480/320'),
            'price_range' => $row['price_range'] ?? null,
        ];
    };

    foreach ($recentRows  as $r) { $newlyAdded[]  = $rowToCard($r); }
    foreach ($oldestRows  as $r) { $established[] = $rowToCard($r); }
} catch (Throwable $ignored) {}

$extraJS = <<<'HTML'
<script>
(function () {
  var STORAGE_KEY = 'mci_detected_city';
  var LOCATION_KEY = 'mci_selected_location';
  var select = document.getElementById('categoryLocation');
  var form = document.getElementById('categoryLocationForm');
  var editBtn = document.getElementById('categoryLocationEdit');
  var editor = document.getElementById('categoryLocationEditor');
  if (!select || !form) return;

  var params = new URLSearchParams(window.location.search);
  var urlLocation = (params.get('location') || '').trim();

  if (!urlLocation) {
    var preferred = '';
    try { preferred = (sessionStorage.getItem(LOCATION_KEY) || '').trim(); } catch (e) {}
    if (!preferred) { try { preferred = (localStorage.getItem(LOCATION_KEY) || '').trim(); } catch (e) {} }
    if (!preferred) { try { preferred = (sessionStorage.getItem(STORAGE_KEY) || '').trim(); } catch (e) {} }
    if (!preferred) { try { preferred = (localStorage.getItem(STORAGE_KEY) || '').trim(); } catch (e) {} }

    if (preferred) {
      var option = Array.prototype.find.call(select.options, function (o) {
        return (o.value || '').toLowerCase() === preferred.toLowerCase();
      });
      if (option) {
        select.value = option.value;
        var next = new URL(window.location.href);
        next.searchParams.set('location', option.value);
        window.location.replace(next.toString());
        return;
      }
    }
  }

  if (urlLocation) {
    try { sessionStorage.setItem(LOCATION_KEY, urlLocation); } catch (e) {}
    try { localStorage.setItem(LOCATION_KEY, urlLocation); } catch (e) {}
  }

  if (editBtn && editor) {
    editBtn.addEventListener('click', function () {
      editor.classList.toggle('d-none');
      if (!editor.classList.contains('d-none')) { select.focus(); }
    });
  }

  select.addEventListener('change', function () {
    var value = (select.value || '').trim();
    try { sessionStorage.setItem(LOCATION_KEY, value); } catch (e) {}
    try { localStorage.setItem(LOCATION_KEY, value); } catch (e) {}
    form.submit();
  });
})();
</script>
HTML;

ob_start();
?>

<div class="py-4 py-lg-5">
  <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
      <h1 class="h3 mb-1">All categories</h1>
      <div class="text-muted small">
        Browse categories and business snapshots
        <?php if ($selectedLocation !== ''): ?>
          for <strong><?= htmlspecialchars($selectedLocation) ?></strong>.
        <?php else: ?>
          across all locations.
        <?php endif; ?>
      </div>
      <div class="small mt-1">
        <span class="text-muted">Location:</span>
        <strong id="categoryLocationLabel"><?= $selectedLocation !== '' ? htmlspecialchars($selectedLocation) : 'All locations' ?></strong>
        <button type="button" class="btn btn-link btn-sm p-0 ms-2 align-baseline text-decoration-none" id="categoryLocationEdit" aria-label="Edit location">
          <i class="bi bi-pencil-square" aria-hidden="true"></i>
        </button>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="get" class="d-flex align-items-center gap-2 flex-wrap" id="categoryLocationForm">
        <div id="categoryLocationEditor" class="d-flex align-items-center gap-2 flex-wrap d-none">
          <label for="categoryLocation" class="form-label small mb-0">Location</label>
          <select id="categoryLocation" name="location" class="form-select form-select-sm" aria-label="Choose location">
            <option value="" <?= $selectedLocation === '' ? 'selected' : '' ?>>All locations</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedLocation === $loc ? 'selected' : '' ?>>
                <?= htmlspecialchars($loc) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-outline-dark">Apply</button>
        </div>
      </form>
      <a class="btn btn-sm btn-outline-dark" href="/business-listing/">
        <i class="bi bi-grid me-1" aria-hidden="true"></i>See all listings
      </a>
    </div>
  </div>

  <!-- Category grid -->
  <?php if (empty($categories)): ?>
    <div class="text-muted small py-4 text-center">No categories with live businesses yet.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <a class="text-decoration-none" href="/business-category/<?= urlencode((string)$cat['slug']) ?>/">
            <div class="card border-0 shadow-sm bg-white h-100">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                  <?= mci_render_category_icon((string)$cat['icon'], 'fs-4') ?>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars((string)$cat['name']) ?></div>
                    <div class="text-muted small"><?= (int)$cat['count'] ?> business<?= (int)$cat['count'] === 1 ? '' : 'es' ?></div>
                  </div>
                </div>
                <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Newly added section -->
  <?php if (!empty($newlyAdded)): ?>
    <section class="mt-5">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
        <h2 class="h5 mb-0 home-section-title">Newly added</h2>
        <a class="small text-decoration-none fw-semibold" href="/business-listing/">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="home-section-accent mb-3"></div>
      <div class="row g-3">
        <?php foreach ($newlyAdded as $listing): ?>
          <?php $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Established / longest-running section -->
  <?php if (!empty($established)): ?>
    <section class="mt-5">
      <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
        <h2 class="h5 mb-0 home-section-title">Established businesses</h2>
        <a class="small text-decoration-none fw-semibold" href="/business-listing/">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="home-section-accent mb-3"></div>
      <div class="row g-3">
        <?php foreach ($established as $listing): ?>
          <?php $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
