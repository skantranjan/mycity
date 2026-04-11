<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_paths.php';
require_once __DIR__ . '/../includes/mci_config.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';
require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/business_service.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));

$pageTitle  = 'Category - My City Info';
$activePage = 'categories';
$extraHead  = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
<link rel="stylesheet" href="/assets/css/listings.css" />
<script src="/assets/js/listings-view.js" defer></script>
HTML;

// ── Load category from DB ─────────────────────────────────────────────────────
$pdo         = api_db();
$categoryRow = null;
try {
    $stmt = $pdo->prepare("SELECT id, name, slug, icon FROM mci_categories WHERE slug = ? AND parent_id IS NULL LIMIT 1");
    $stmt->execute([$slug]);
    $categoryRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $ignored) {}

$selectedCategoryName = $categoryRow ? (string)$categoryRow['name'] : '';
$categorySlug         = $categoryRow ? (string)$categoryRow['slug'] : $slug;
$categoryId           = $categoryRow ? (int)$categoryRow['id'] : 0;

if (!$categoryRow) {
    http_response_code(404);
    $pageTitle = 'Category Not Found - My City Info';
} else {
    $pageTitle    = $selectedCategoryName . ' - My City Info';
    $canonicalUrl = mci_site_base_url() . '/business-category/' . rawurlencode($categorySlug) . '/';
    $ogTitle      = $selectedCategoryName . ' Businesses - My City Info';
}

// ── Subcategories for this parent ─────────────────────────────────────────────
$subcategories = [];
if ($categoryRow) {
    try {
        $subStmt = $pdo->prepare("
            SELECT sc.name, sc.slug, sc.icon,
                   COUNT(DISTINCT bsc.business_group_id) AS biz_count
            FROM mci_categories sc
            LEFT JOIN mci_business_subcategories bsc ON bsc.category_id = sc.id
            LEFT JOIN mci_business_groups g ON g.id = bsc.business_group_id AND g.status = 'live'
            WHERE sc.parent_id = ?
            GROUP BY sc.id, sc.name, sc.slug, sc.icon
            ORDER BY sc.sort_order, sc.name
        ");
        $subStmt->execute([$categoryId]);
        $subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $ignored) {}
}

// ── Filter params ─────────────────────────────────────────────────────────────
// If no ?location= in URL, fall back to the cookie set by the city picker JS
$selectedLocation  = trim((string)($_GET['location']    ?? ''));
if ($selectedLocation === '') {
    $selectedLocation = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
if ($categoryRow) {
    $metaDescription = 'Browse ' . $selectedCategoryName . ' businesses'
        . ($selectedLocation !== '' ? ' in ' . $selectedLocation : ' across India')
        . ' on My City Info.';
}
$selectedSub       = trim((string)($_GET['subcategory'] ?? ''));
$tag               = trim((string)($_GET['tag']         ?? ''));
$priceRange        = trim((string)($_GET['price_range'] ?? ''));
$sort              = trim((string)($_GET['sort']        ?? 'newest'));
$curPage           = max(1, (int)($_GET['page']         ?? 1));

if (!in_array($sort, ['newest', 'oldest'], true)) {
    $sort = 'newest';
}

// Validate subcategory belongs to this parent
$subSlugs = array_column($subcategories, 'slug');
if ($selectedSub !== '' && !in_array($selectedSub, $subSlugs, true)) {
    $selectedSub = '';
}

// ── Location options from live businesses in this category ────────────────────
$locations = [];
if ($categoryRow) {
    try {
        $locStmt = $pdo->prepare("
            SELECT DISTINCT b.city
            FROM mci_business_branches b
            INNER JOIN mci_business_groups g ON g.id = b.business_group_id AND g.status = 'live'
            INNER JOIN mci_categories c ON c.id = g.parent_category_id AND c.slug = ?
            WHERE b.city != ''
            ORDER BY b.city
        ");
        $locStmt->execute([$categorySlug]);
        $locations = array_column($locStmt->fetchAll(PDO::FETCH_ASSOC), 'city');
    } catch (Throwable $ignored) {}
}
if ($selectedLocation !== '' && !in_array($selectedLocation, $locations, true)) {
    $selectedLocation = '';
}

// ── Tags used in this category (for the tag filter hint) ─────────────────────
$categoryTags = [];
if ($categoryRow) {
    try {
        $tagStmt = $pdo->prepare("
            SELECT DISTINCT t.name, t.slug
            FROM mci_tags t
            INNER JOIN mci_business_tags bt ON bt.tag_id = t.id
            INNER JOIN mci_business_groups g ON g.id = bt.business_group_id AND g.status = 'live'
            INNER JOIN mci_categories c ON c.id = g.parent_category_id AND c.slug = ?
            ORDER BY t.name
            LIMIT 30
        ");
        $tagStmt->execute([$categorySlug]);
        $categoryTags = $tagStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $ignored) {}
}

// ── Load businesses ───────────────────────────────────────────────────────────
$listings = [];
$total    = 0;
$pages    = 1;

if ($categoryRow) {
    try {
        $filters = [
            'category_slug' => $categorySlug,
            'per_page'      => MCI_LISTING_PER_PAGE,
            'page'          => $curPage,
            'sort'          => $sort,
        ];
        if ($selectedLocation !== '') { $filters['city']             = $selectedLocation; }
        if ($selectedSub       !== '') { $filters['subcategory_slug'] = $selectedSub; }
        if ($tag               !== '') { $filters['tag_slug']         = $tag; }
        if ($priceRange        !== '') { $filters['price_range']      = $priceRange; }

        $result = api_business_list_public($pdo, $filters);
        $total  = (int)($result['total'] ?? 0);
        $pages  = (int)($result['pages'] ?? 1);

        foreach ($result['businesses'] as $row) {
            $listings[] = [
                'title'       => (string)($row['name']          ?? ''),
                'category'    => (string)($row['category_name'] ?? ''),
                'location'    => (string)($row['city']          ?? ''),
                'address'     => (string)($row['city']          ?? ''),
                'slug'        => (string)($row['slug']          ?? ''),
                'image'       => mci_listing_card_image_url($row['logo_path'] ?? null, $row['banner_path'] ?? null),
                'price_range' => $row['price_range'] ?? null,
                'tags'        => [],
            ];
        }
    } catch (Throwable $ignored) {}
}

$activeFilterCount = ($selectedLocation !== '' ? 1 : 0) + ($selectedSub !== '' ? 1 : 0)
                   + ($tag !== '' ? 1 : 0) + ($priceRange !== '' ? 1 : 0);

// ── Base params helper for pagination / clear links ───────────────────────────
$baseParams = ['slug' => $categorySlug];
if ($selectedLocation !== '') { $baseParams['location']    = $selectedLocation; }
if ($selectedSub       !== '') { $baseParams['subcategory'] = $selectedSub; }
if ($tag               !== '') { $baseParams['tag']         = $tag; }
if ($priceRange        !== '') { $baseParams['price_range'] = $priceRange; }
if ($sort !== 'newest')        { $baseParams['sort']        = $sort; }

function mci_cat_page_url(array $base, int $page): string {
    $p = $base;
    if ($page > 1) $p['page'] = $page;
    return '/business-category/?' . http_build_query($p);
}

$extraJS = <<<'HTML'
<script>
(function () {
  // Mobile filter toggle
  var btn = document.getElementById('mciFilterToggle');
  var panel = document.getElementById('mciFiltersPanel');
  if (!btn || !panel) return;
  function applyMobileState() {
    if (window.innerWidth < 992) {
      if (btn.getAttribute('aria-expanded') !== 'true') panel.style.display = 'none';
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
</script>
HTML;
$extraJS .= '<script>window.MCI_LISTING_CONFIG=' . json_encode([
    'endpoint' => '/api/v1/public/businesses',
    'filters' => [
        'category_slug' => $categorySlug,
        'city' => $selectedLocation,
        'subcategory_slug' => $selectedSub,
        'tag_slug' => $tag,
        'price_range' => $priceRange,
        'sort' => $sort,
    ],
    'page' => $curPage,
    'pages' => $pages,
    'perPage' => MCI_LISTING_PER_PAGE,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';

ob_start();
?>

<!-- Breadcrumb -->
<nav class="mci-breadcrumb mb-3" aria-label="Breadcrumb">
  <a href="/">Home</a>
  <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
  <a href="/business-category/">Categories</a>
  <span class="mci-breadcrumb__sep" aria-hidden="true">›</span>
  <span class="mci-breadcrumb__current"><?= htmlspecialchars($categoryRow['name'] ?? '') ?></span>
</nav>

<div class="py-4">

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
    <div>
      <?php if ($categoryRow): ?>
        <div class="d-flex align-items-center gap-2 mb-1">
          <?= mci_render_category_icon((string)($categoryRow['icon'] ?: mci_category_icon($categorySlug)), 'fs-4') ?>
          <h1 class="h3 mb-0"><?= htmlspecialchars($selectedCategoryName) ?></h1>
        </div>
        <div class="text-muted small">
          <?= number_format($total) ?> business<?= $total === 1 ? '' : 'es' ?>
          <?= $selectedLocation !== '' ? ' in <strong>' . htmlspecialchars($selectedLocation) . '</strong>' : ' across all locations' ?>
        </div>
        <?php if ($curPage === 1): ?>
        <p class="text-muted small mt-2 mb-0">
          Browse <?= htmlspecialchars($selectedCategoryName) ?> businesses
          <?= $selectedLocation !== '' ? 'in ' . htmlspecialchars($selectedLocation) : 'across India' ?>
          on My City Info. Find verified contact details, addresses, reviews, and opening hours for
          trusted <?= htmlspecialchars($selectedCategoryName) ?> providers near you.
        </p>
        <?php endif; ?>
      <?php else: ?>
        <h1 class="h3 mb-0">Category not found</h1>
      <?php endif; ?>
    </div>
    <a class="btn btn-sm btn-outline-dark" href="/business-category/">
      <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>All categories
    </a>
  </div>

  <?php if (!$categoryRow): ?>
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="text-muted mb-3">This category was not found.</div>
        <a class="btn btn-dark btn-sm" href="/business-category/">Browse categories</a>
      </div>
    </div>
  <?php else: ?>

    <!-- ── Subcategory cards ─────────────────────────────────────────────── -->
    <?php if (!empty($subcategories)): ?>
    <div class="mb-4">
      <div class="fw-semibold small text-uppercase mb-2" style="letter-spacing:.06em;color:var(--mci-color-primary-deep)">
        Subcategories
      </div>
      <div class="row g-2">
        <?php foreach ($subcategories as $sc):
          $scActive = ($selectedSub === (string)$sc['slug']);
          $scParams = ['slug' => $categorySlug, 'subcategory' => $sc['slug']];
          if ($selectedLocation !== '') $scParams['location'] = $selectedLocation;
          if ($tag !== '')              $scParams['tag']      = $tag;
          if ($priceRange !== '')       $scParams['price_range'] = $priceRange;
        ?>
          <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <a href="/business-category/?<?= htmlspecialchars(http_build_query($scActive
                  ? array_diff_key($baseParams, ['subcategory' => ''])
                  : $scParams)) ?>"
               class="text-decoration-none">
              <div class="home-category-tile w-100 text-center flex-column gap-1 py-3 <?= $scActive ? 'home-category-tile--active' : '' ?>"
                   style="<?= $scActive ? 'border-color:var(--mci-color-primary);background:var(--mci-color-primary-soft);' : '' ?>">
                <span class="home-category-icon">
                  <?= mci_render_category_icon((string)($sc['icon'] ?: mci_category_icon((string)$sc['slug'])), '') ?>
                </span>
                <span class="small fw-semibold"><?= htmlspecialchars((string)$sc['name']) ?></span>
                <?php if ((int)$sc['biz_count'] > 0): ?>
                  <span class="text-muted" style="font-size:.7rem"><?= (int)$sc['biz_count'] ?></span>
                <?php endif; ?>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Listings (sidebar + main) ────────────────────────────────────── -->
    <div class="row g-4">

      <!-- Filter toggle (mobile) -->
      <div class="col-12 d-lg-none">
        <div class="d-flex align-items-center justify-content-between gap-2">
          <button class="btn btn-sm btn-outline-dark" type="button"
                  id="mciFilterToggle" aria-expanded="false" aria-controls="mciFiltersPanel">
            <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filters
            <?php if ($activeFilterCount > 0): ?>
              <span class="badge text-bg-dark ms-1"><?= $activeFilterCount ?></span>
            <?php endif; ?>
          </button>
          <div class="text-muted small">
            Showing <span id="mciShownCount"><?= count($listings) ?></span> of <span id="mciTotalCount"><?= number_format($total) ?></span>
          </div>
        </div>
      </div>

      <!-- Sidebar filters -->
      <div class="col-12 col-lg-3" id="mciFiltersPanel">
        <div class="card border-0 shadow-sm bg-white">
          <div class="card-body">
            <div class="fw-semibold mb-3">Filters</div>
            <form method="get" action="/business-category/">
              <input type="hidden" name="slug" value="<?= htmlspecialchars($categorySlug) ?>" />

              <!-- Location -->
              <div class="mb-3">
                <label class="form-label">Location</label>
                <?php if (!empty($locations)): ?>
                  <select class="form-select" name="location">
                    <option value="">All locations</option>
                    <?php foreach ($locations as $loc): ?>
                      <option value="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>"
                              <?= $selectedLocation === $loc ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <input class="form-control" type="text" name="location"
                         value="<?= htmlspecialchars($selectedLocation) ?>" placeholder="City or area" />
                <?php endif; ?>
              </div>

              <!-- Subcategory chips -->
              <?php if (!empty($subcategories)): ?>
              <div class="mb-3">
                <label class="form-label">Subcategory</label>
                <div class="d-flex flex-wrap gap-2">
                  <?php foreach ($subcategories as $sc): ?>
                    <label class="d-flex align-items-center gap-1 btn btn-sm <?= $selectedSub === (string)$sc['slug'] ? 'btn-dark' : 'btn-outline-secondary' ?> py-1 px-2" style="cursor:pointer;font-size:.78rem;">
                      <input type="radio" name="subcategory"
                             value="<?= htmlspecialchars((string)$sc['slug'], ENT_QUOTES) ?>"
                             <?= $selectedSub === (string)$sc['slug'] ? 'checked' : '' ?>
                             style="display:none" onchange="this.form.submit()" />
                      <?= htmlspecialchars((string)$sc['name']) ?>
                    </label>
                  <?php endforeach; ?>
                  <?php if ($selectedSub !== ''): ?>
                    <a href="/business-category/?<?= htmlspecialchars(http_build_query(array_diff_key($baseParams, ['subcategory' => '']))) ?>"
                       class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:.78rem;">
                      <i class="bi bi-x me-1" aria-hidden="true"></i>All
                    </a>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

              <!-- Tag -->
              <div class="mb-3">
                <label class="form-label">Tag</label>
                <input class="form-control" type="text" name="tag"
                       value="<?= htmlspecialchars($tag) ?>"
                       placeholder="e.g. vegetarian, walk-ins" autocomplete="off" list="mciCatTagList" />
                <?php if (!empty($categoryTags)): ?>
                  <datalist id="mciCatTagList">
                    <?php foreach ($categoryTags as $ct): ?>
                      <option value="<?= htmlspecialchars((string)$ct['slug'], ENT_QUOTES) ?>">
                        <?= htmlspecialchars((string)$ct['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </datalist>
                <?php endif; ?>
              </div>

              <!-- Price range -->
              <div class="mb-3">
                <label class="form-label">Price range</label>
                <select class="form-select" name="price_range">
                  <option value="">Any</option>
                  <option value="free"     <?= $priceRange === 'free'     ? 'selected' : '' ?>>₹ - Inexpensive</option>
                  <option value="moderate" <?= $priceRange === 'moderate' ? 'selected' : '' ?>>₹₹ - Moderate</option>
                  <option value="pricey"   <?= $priceRange === 'pricey'   ? 'selected' : '' ?>>₹₹₹ - Pricey</option>
                  <option value="ultra"    <?= $priceRange === 'ultra'    ? 'selected' : '' ?>>₹₹₹₹ - Ultra High</option>
                </select>
              </div>

              <!-- Sort -->
              <div class="mb-3">
                <label class="form-label">Sort by</label>
                <select class="form-select" name="sort">
                  <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newly added</option>
                  <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Established first</option>
                </select>
              </div>

              <button class="btn btn-outline-dark w-100" type="submit">
                <i class="bi bi-sliders me-1" aria-hidden="true"></i>Apply filters
              </button>
              <?php if ($activeFilterCount > 0): ?>
                <a href="/business-category/?slug=<?= urlencode($categorySlug) ?>"
                   class="btn btn-outline-secondary w-100 mt-2">Clear filters</a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>

      <!-- Main listing area -->
      <div class="col-12 col-lg-9">
        <div class="bg-white border-0 shadow-sm rounded-4 p-3">
          <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div class="fw-semibold">
              <?= htmlspecialchars($selectedCategoryName) ?> businesses
              <?php if ($selectedSub !== ''): ?>
                <span class="text-muted fw-normal">
                  &rsaquo; <?= htmlspecialchars(
                    (string)(array_column($subcategories, 'name', 'slug')[$selectedSub] ?? $selectedSub)
                  ) ?>
                </span>
              <?php endif; ?>
            </div>
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
              <div class="text-muted small d-none d-lg-block">
                Showing <span id="mciShownCountDesktop"><?= count($listings) ?></span> of <span id="mciTotalCountDesktop"><?= number_format($total) ?></span> listings
              </div>
            </div>
          </div>

          <?php if (count($listings) === 0): ?>
            <div class="text-center py-5">
              <div class="mb-3" style="font-size:3rem;" aria-hidden="true">🏙️</div>
              <div class="fw-semibold mb-1">No businesses found</div>
              <div class="text-muted small mb-3">Try adjusting or clearing your filters.</div>
              <a href="/business-category/?slug=<?= urlencode($categorySlug) ?>"
                 class="btn btn-sm btn-dark">Clear filters</a>
            </div>
          <?php else: ?>
            <div id="listingsGridView" class="row g-3" data-infinite-scroll="1">
              <?php foreach ($listings as $listing): ?>
                <?php $size = 'md'; include __DIR__ . '/../views/components/listing-card.php'; ?>
              <?php endforeach; ?>
            </div>

            <div id="listingsListView" class="row g-3 d-none" data-infinite-scroll="1">
              <?php foreach ($listings as $listing): ?>
                <?php include __DIR__ . '/../views/components/listing-row.php'; ?>
              <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
              <nav id="mciPagination" class="mt-4 d-flex justify-content-center" aria-label="Category listing pages">
                <ul class="pagination pagination-sm mb-0">
                  <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars(mci_cat_page_url($baseParams, $curPage - 1)) ?>">
                      <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </a>
                  </li>
                  <?php for ($p = max(1, $curPage - 2); $p <= min($pages, $curPage + 2); $p++): ?>
                    <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
                      <a class="page-link" href="<?= htmlspecialchars(mci_cat_page_url($baseParams, $p)) ?>"><?= $p ?></a>
                    </li>
                  <?php endfor; ?>
                  <li class="page-item <?= $curPage >= $pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars(mci_cat_page_url($baseParams, $curPage + 1)) ?>">
                      <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                  </li>
                </ul>
              </nav>
            <?php endif; ?>
            <div id="mciInfiniteStatus" class="text-center small text-muted mt-3" style="display:none;"></div>
          <?php endif; ?>
        </div>
      </div>
    </div><!-- /row -->

  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
