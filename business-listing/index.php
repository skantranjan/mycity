<?php
declare(strict_types=1);

$pageTitle = 'Listings - My City Info';
$activePage = 'listings';

require_once __DIR__ . '/../includes/mci_category_icons.php';
require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/business_service.php';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/listings.css" />
<script src="/assets/js/listings-view.js" defer></script>
HTML;

$extraJS = <<<'HTML'
<script>
(function () {
  var btn = document.getElementById('mciFilterToggle');
  var panel = document.getElementById('mciFiltersPanel');
  if (!btn || !panel) return;
  function applyMobileState() {
    if (window.innerWidth < 992) {
      if (btn.getAttribute('aria-expanded') !== 'true') {
        panel.style.display = 'none';
      }
    } else {
      panel.style.display = '';
    }
  }
  applyMobileState();
  window.addEventListener('resize', applyMobileState);
  btn.addEventListener('click', function () {
    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
    panel.style.display = expanded ? 'none' : '';
  });
})();
// Clear subcategory hidden input when category changes
(function () {
  var sel = document.getElementById('mciCategorySelect');
  if (!sel) return;
  sel.addEventListener('change', function () {
    var hidden = document.querySelector('input[name="subcategory"]');
    if (hidden) hidden.remove();
  });
})();
</script>
HTML;

$what        = trim((string)($_GET['what']        ?? ''));
$where       = trim((string)($_GET['where']       ?? ''));
// Fall back to cookie city if no explicit where filter
if ($where === '') {
    $where = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
$category    = trim((string)($_GET['category']    ?? ''));
$subcategory = trim((string)($_GET['subcategory'] ?? ''));
$tag         = trim((string)($_GET['tag']         ?? ''));
$priceRange  = trim((string)($_GET['price_range'] ?? ''));
$curPage     = max(1, (int)($_GET['page']         ?? 1));

// ── Load categories from DB for the filter dropdown ──────────────────────────
$dbCategories = [];
$dbSubcategories = [];
try {
    $pdo = api_db();
    $catStmt = $pdo->query("SELECT name, slug FROM mci_categories WHERE parent_id IS NULL ORDER BY sort_order, name");
    $dbCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Load subcategories for the selected parent category
    if ($category !== '') {
        $subStmt = $pdo->prepare("
            SELECT sc.name, sc.slug
            FROM mci_categories sc
            INNER JOIN mci_categories pc ON pc.id = sc.parent_id
            WHERE pc.slug = ? AND sc.parent_id IS NOT NULL
            ORDER BY sc.sort_order, sc.name
        ");
        $subStmt->execute([$category]);
        $dbSubcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $ignored) {}

// Reset subcategory if it doesn't belong to the selected category
if ($subcategory !== '' && !in_array($subcategory, array_column($dbSubcategories, 'slug'), true)) {
    $subcategory = '';
}

// ── Load listings from DB with server-side filter ─────────────────────────────
$filters = ['page' => $curPage, 'per_page' => 12];
if ($what !== '')        { $filters['q']                = $what; }
if ($where !== '')       { $filters['city']             = $where; }
if ($category !== '')    { $filters['category_slug']    = $category; }
if ($subcategory !== '') { $filters['subcategory_slug'] = $subcategory; }
if ($tag !== '')         { $filters['tag_slug']         = $tag; }
if ($priceRange !== '')  { $filters['price_range']      = $priceRange; }

$result          = ['businesses' => [], 'total' => 0, 'pages' => 1];
$filteredListings = [];

try {
    $result = api_business_list_public(isset($pdo) ? $pdo : api_db(), $filters);
    foreach ($result['businesses'] as $row) {
        $filteredListings[] = [
            'title'       => (string)($row['name']          ?? ''),
            'category'    => (string)($row['category_name'] ?? ''),
            'location'    => (string)($row['city']          ?? ''),
            'address'     => (string)($row['city']          ?? ''),
            'slug'        => (string)($row['slug']          ?? ''),
            'image'       => !empty($row['logo_path'])
                               ? $row['logo_path']
                               : (!empty($row['banner_path'])
                                   ? $row['banner_path']
                                   : 'https://picsum.photos/seed/mci-' . ($row['slug'] ?? 'biz') . '/480/320'),
            'price_range' => $row['price_range'] ?? null,
            'tags'        => [],
        ];
    }
} catch (Throwable $ignored) {}

$total = (int)($result['total'] ?? 0);
$pages = (int)($result['pages'] ?? 1);

$activeFilterCount = ($what !== '' ? 1 : 0) + ($where !== '' ? 1 : 0) + ($category !== '' ? 1 : 0)
                   + ($subcategory !== '' ? 1 : 0) + ($tag !== '' ? 1 : 0) + ($priceRange !== '' ? 1 : 0);

ob_start();
?>

<div class="row g-4">
  <div class="col-12">
    <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap">
      <div>
        <!-- Breadcrumb -->
        <nav class="mci-breadcrumb mb-2" aria-label="Breadcrumb">
          <a href="/">Home</a>
          <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
          <span class="mci-breadcrumb__current">Listings<?php if ($category !== ''): ?> — <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $category))) ?><?php endif; ?></span>
        </nav>
        <h1 class="h4 fw-bold mb-1">Listings</h1>
        <div class="text-muted small">
          <?php if ($category !== ''): ?>
            Category: <span class="text-dark fw-semibold"><?= htmlspecialchars($category) ?></span>
          <?php endif; ?>
          <?php if ($where !== ''): ?>
            <?php if ($category !== ''): ?> | <?php endif; ?>
            Location: <span class="text-dark fw-semibold"><?= htmlspecialchars($where) ?></span>
          <?php endif; ?>
          <?php if ($what !== ''): ?>
            <?php if ($category !== '' || $where !== '' || $tag !== ''): ?> | <?php endif; ?>
            Search: <span class="text-dark fw-semibold"><?= htmlspecialchars($what) ?></span>
          <?php endif; ?>
          <?php if ($tag !== ''): ?>
            <?php if ($category !== '' || $where !== '' || $what !== ''): ?> | <?php endif; ?>
            Tag: <span class="text-dark fw-semibold"><?= htmlspecialchars($tag) ?></span>
          <?php endif; ?>
          <?php if ($category === '' && $where === '' && $what === '' && $tag === ''): ?>
            Browse businesses by category, city, or tag.
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-dark d-lg-none" type="button" id="mciFilterToggle" aria-expanded="false" aria-controls="mciFiltersPanel">
          <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filters<?php if ($activeFilterCount > 0): ?> <span class="badge text-bg-dark ms-1"><?= $activeFilterCount ?></span><?php endif; ?>
        </button>
        <a class="btn btn-sm btn-dark" href="/submit-business-listing/">List your business</a>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4 mci-listings-filter-sticky" id="mciFiltersPanel">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="fw-semibold mb-3">Filters</div>
        <form method="get" action="/business-listing/">
          <div class="mb-3">
            <label class="form-label">What</label>
            <input class="form-control" type="text" name="what" value="<?= htmlspecialchars($what) ?>" placeholder="Search by business name or category" />
          </div>
          <div class="mb-3">
            <label class="form-label">Where</label>
            <input class="form-control" type="text" id="mciListingWhere" name="where" value="<?= htmlspecialchars($where) ?>" placeholder="City or area" />
          </div>

          <div class="mb-3">
            <label class="form-label">Tag</label>
            <input class="form-control" type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" placeholder="e.g. eco-friendly, walk-ins-welcome" autocomplete="off" />
            <div class="form-text">Matches a business tag slug. Case-insensitive.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category" id="mciCategorySelect">
              <option value="">All categories</option>
              <?php foreach ($dbCategories as $c): ?>
                <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $c['slug'] === $category ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if (!empty($dbSubcategories)): ?>
          <div class="mb-3">
            <label class="form-label">Subcategory</label>
            <div class="d-flex flex-wrap gap-2">
              <?php
              // Build base params without subcategory for "All" link
              $subBaseParams = [];
              if ($what !== '')       $subBaseParams['what']        = $what;
              if ($where !== '')      $subBaseParams['where']       = $where;
              if ($category !== '')   $subBaseParams['category']    = $category;
              if ($tag !== '')        $subBaseParams['tag']         = $tag;
              if ($priceRange !== '') $subBaseParams['price_range'] = $priceRange;
              ?>
              <a href="/business-listing/?<?= htmlspecialchars(http_build_query($subBaseParams)) ?>"
                 class="btn btn-sm <?= $subcategory === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
                All
              </a>
              <?php foreach ($dbSubcategories as $sc):
                $scParams = $subBaseParams + ['subcategory' => $sc['slug']];
              ?>
                <a href="/business-listing/?<?= htmlspecialchars(http_build_query($scParams)) ?>"
                   class="btn btn-sm <?= $subcategory === $sc['slug'] ? 'btn-dark' : 'btn-outline-secondary' ?>">
                  <?= htmlspecialchars($sc['name']) ?>
                </a>
              <?php endforeach; ?>
            </div>
            <?php if ($subcategory !== ''): ?>
              <input type="hidden" name="subcategory" value="<?= htmlspecialchars($subcategory) ?>" />
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Price range</label>
            <select class="form-select" name="price_range">
              <option value="">Any</option>
              <option value="free"     <?= $priceRange === 'free'     ? 'selected' : '' ?>>₹ (Inexpensive)</option>
              <option value="moderate" <?= $priceRange === 'moderate' ? 'selected' : '' ?>>₹₹ (Moderate)</option>
              <option value="pricey"   <?= $priceRange === 'pricey'   ? 'selected' : '' ?>>₹₹₹ (Pricey)</option>
              <option value="ultra"    <?= $priceRange === 'ultra'    ? 'selected' : '' ?>>₹₹₹₹ (Ultra High)</option>
            </select>
          </div>

          <button class="btn btn-outline-dark w-100" type="submit">Apply filters</button>
          <?php if ($activeFilterCount > 0): ?>
            <a href="/business-listing/" class="btn btn-outline-secondary w-100 mt-2">Clear filters</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3">
      <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
        <div class="fw-semibold">Business listings</div>
        <div class="d-flex align-items-center gap-3 flex-wrap">
          <div class="btn-group listings-view-toggle" role="group" aria-label="Choose layout">
            <button type="button" class="btn btn-outline-dark btn-sm listings-view-toggle__btn active"
              id="listingsViewGrid" aria-pressed="true" aria-label="Grid view" title="Grid view">
              <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn btn-outline-dark btn-sm listings-view-toggle__btn"
              id="listingsViewList" aria-pressed="false" aria-label="List view" title="List view">
              <i class="bi bi-list-ul" aria-hidden="true"></i>
            </button>
          </div>
          <div class="text-muted small">Showing <?= count($filteredListings) ?> of <?= number_format($total) ?> listings</div>
        </div>
      </div>

      <?php if (count($filteredListings) === 0): ?>
        <div class="text-center py-5">
          <div class="mb-3" style="font-size:3rem;" aria-hidden="true">🔍</div>
          <div class="fw-semibold mb-1">No businesses found</div>
          <div class="text-muted small mb-3">Try adjusting or clearing your filters to see more results.</div>
          <a href="/business-listing/" class="btn btn-sm btn-dark">Clear all filters</a>
        </div>
      <?php else: ?>
        <div id="listingsGridView" class="row g-3">
          <?php foreach ($filteredListings as $listing): ?>
            <?php $size = 'md'; include __DIR__ . '/../views/components/listing-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <div id="listingsListView" class="row g-3 d-none">
          <?php foreach ($filteredListings as $listing): ?>
            <?php include __DIR__ . '/../views/components/listing-row.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
          <?php
          $baseParams = [];
          if ($what !== '')        $baseParams['what']        = $what;
          if ($where !== '')       $baseParams['where']       = $where;
          if ($category !== '')    $baseParams['category']    = $category;
          if ($subcategory !== '') $baseParams['subcategory'] = $subcategory;
          if ($tag !== '')         $baseParams['tag']         = $tag;
          if ($priceRange !== '')  $baseParams['price_range'] = $priceRange;
          function mci_listing_page_url(array $base, int $page): string {
              $p = $base;
              if ($page > 1) $p['page'] = $page;
              return '/business-listing/?' . http_build_query($p);
          }
          ?>
          <nav class="mt-4 d-flex justify-content-center" aria-label="Listings pages">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars(mci_listing_page_url($baseParams, $curPage - 1)) ?>">
                  <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </a>
              </li>
              <?php for ($p = max(1, $curPage - 2); $p <= min($pages, $curPage + 2); $p++): ?>
                <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
                  <a class="page-link" href="<?= htmlspecialchars(mci_listing_page_url($baseParams, $p)) ?>"><?= $p ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= $curPage >= $pages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars(mci_listing_page_url($baseParams, $curPage + 1)) ?>">
                  <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
