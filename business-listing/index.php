<?php
declare(strict_types=1);

$pageTitle = 'Listings - My City Info';
$activePage = 'listings';

require_once __DIR__ . '/../includes/mci_paths.php';
require_once __DIR__ . '/../includes/mci_seo.php';
require_once __DIR__ . '/../includes/mci_config.php';
require_once __DIR__ . '/../includes/mci_cache.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';
require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/business_service.php';

$extraHead = <<<'HTML'
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

// Subcategory dynamic loader
(function () {
  var sel   = document.getElementById('mciCategorySelect');
  var wrap  = document.getElementById('mciSubcategoryWrap');
  var pills = document.getElementById('mciSubcategoryPills');
  var input = document.getElementById('mciSubcategoryInput');
  if (!sel || !wrap || !pills || !input) return;

  // Cache fetched subcategories per category slug
  var cache = {};

  // Preloaded from server for the initial category (avoids a round-trip on page load)
  var preloaded = JSON.parse(wrap.dataset.preload || '[]');
  var initCat = sel.value;
  if (initCat && preloaded.length) {
    cache[initCat] = preloaded;
    renderPills(preloaded, input.value);
  }

  sel.addEventListener('change', function () {
    var slug = sel.value;
    input.value = '';
    wrap.style.display = 'none';
    pills.innerHTML = '';
    if (!slug) return;
    if (cache[slug]) {
      renderPills(cache[slug], '');
      return;
    }
    // Fetch from public categories endpoint
    fetch('/api/v1/public/categories')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var cats = data.categories || [];
        var match = cats.find(function (c) { return c.slug === slug; });
        var subs = (match && match.children) ? match.children : [];
        cache[slug] = subs;
        renderPills(subs, '');
      })
      .catch(function () { /* silently ignore */ });
  });

  function renderPills(subs, activeSub) {
    pills.innerHTML = '';
    if (!subs.length) { wrap.style.display = 'none'; return; }
    // "All" pill
    pills.appendChild(makePill('All', '', activeSub === ''));
    subs.forEach(function (sc) {
      pills.appendChild(makePill(sc.name, sc.slug, sc.slug === activeSub));
    });
    wrap.style.display = '';
  }

  function makePill(label, slug, active) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm ' + (active ? 'btn-dark' : 'btn-outline-secondary');
    btn.textContent = label;
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.addEventListener('click', function () {
      input.value = slug;
      pills.querySelectorAll('.btn').forEach(function (b) {
        b.className = 'btn btn-sm btn-outline-secondary';
        b.setAttribute('aria-pressed', 'false');
      });
      btn.className = 'btn btn-sm btn-dark';
      btn.setAttribute('aria-pressed', 'true');
    });
    return btn;
  }
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

// ── SEO meta for filter combinations ─────────────────────────────────────────
$canonicalUrl = mci_seo_listings_public_url();
if ($what !== '' || $where !== '' || $category !== '') {
    $_ogTitleParts = ['Listings'];
    if ($what !== '')     $_ogTitleParts[] = $what;
    if ($category !== '') $_ogTitleParts[] = ucwords(str_replace('-', ' ', $category));
    if ($where !== '')    $_ogTitleParts[] = 'in ' . $where;
    $ogTitle         = implode(' — ', $_ogTitleParts) . ' - My City Info';
    $metaDescription = 'Browse local businesses'
        . ($what !== ''     ? ' for ' . $what : '')
        . ($category !== '' ? ' in ' . ucwords(str_replace('-', ' ', $category)) : '')
        . ($where !== ''    ? ' in ' . $where : '')
        . ' on My City Info.';
}

// ── Load categories from DB for the filter dropdown ──────────────────────────
$dbCategories = [];
$dbSubcategories = [];
$pdo = null;
$ttlList = mci_cache_ttl_default();
try {
    $pdo = api_db();
    $dbCategories = mci_cache_remember(
        mci_cache_public_key('page:listing:parentCats:v1'),
        $ttlList,
        static function () use ($pdo): array {
            $catStmt = $pdo->query("SELECT name, slug FROM mci_categories WHERE parent_id IS NULL ORDER BY sort_order, name");

            return $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    );

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
} catch (Throwable $e) {
    mci_log_error('business-listing categories', $e);
}

// Reset subcategory if it doesn't belong to the selected category
if ($subcategory !== '' && !in_array($subcategory, array_column($dbSubcategories, 'slug'), true)) {
    $subcategory = '';
}

// ── Load listings from DB with server-side filter ─────────────────────────────
$filters = ['page' => $curPage, 'per_page' => MCI_LISTING_PER_PAGE];
if ($what !== '')        { $filters['q']                = $what; }
if ($where !== '')       { $filters['city']             = $where; }
if ($category !== '')    { $filters['category_slug']    = $category; }
if ($subcategory !== '') { $filters['subcategory_slug'] = $subcategory; }
if ($tag !== '')         { $filters['tag_slug']         = $tag; }
if ($priceRange !== '')  { $filters['price_range']      = $priceRange; }

$result          = ['businesses' => [], 'total' => 0, 'pages' => 1];
$filteredListings = [];

try {
    if ($pdo instanceof PDO) {
        $listKey = mci_cache_key_public_filters('page:listing:pub:', $filters);
        $result  = mci_cache_remember(
            $listKey,
            $ttlList,
            static function () use ($pdo, $filters): array {
                return api_business_list_public($pdo, $filters);
            }
        );
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
                                       : mci_listing_placeholder_url()),
                'price_range' => $row['price_range'] ?? null,
                'tags'        => [],
            ];
        }
    }
} catch (Throwable $e) {
    mci_log_error('business-listing listings', $e);
}

$total = (int)($result['total'] ?? 0);
$pages = (int)($result['pages'] ?? 1);

$extraJS .= '<script>window.MCI_LISTING_CONFIG=' . json_encode([
    'endpoint' => '/api/v1/public/businesses',
    'filters' => [
        'q' => $what,
        'city' => $where,
        'category_slug' => $category,
        'subcategory_slug' => $subcategory,
        'tag_slug' => $tag,
        'price_range' => $priceRange,
    ],
    'page' => $curPage,
    'pages' => $pages,
    'perPage' => MCI_LISTING_PER_PAGE,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';

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
          <span class="mci-breadcrumb__current">Listings<?php if ($category !== ''): ?> - <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $category))) ?><?php endif; ?></span>
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
        <?php if ($category === '' && $where === '' && $what === '' && $tag === '' && $curPage === 1): ?>
        <p class="text-muted small mt-2 mb-0">
          My City Info is India's free local business directory — covering restaurants, services,
          shops, and more across cities nationwide. Use the filters to narrow down by category,
          location, or price range, and discover trusted businesses with verified reviews and
          up-to-date contact details.
        </p>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-sm btn-outline-dark d-lg-none" type="button" id="mciFilterToggle" aria-expanded="false" aria-controls="mciFiltersPanel">
          <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filters<?php if ($activeFilterCount > 0): ?> <span class="badge text-bg-dark ms-1"><?= $activeFilterCount ?></span><?php endif; ?>
        </button>
        <a class="btn btn-sm mci-btn-list-biz fw-semibold" href="/submit-business-listing/">
          <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>List your business
        </a>
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

          <!-- Subcategory pills — populated by JS on category change -->
          <?php
          $preloadSubs = json_encode(
              array_map(fn($sc) => ['name' => $sc['name'], 'slug' => $sc['slug']], $dbSubcategories),
              JSON_HEX_TAG | JSON_HEX_QUOT
          );
          ?>
          <div class="mb-3" id="mciSubcategoryWrap"
            data-preload="<?= $preloadSubs ?>"
            style="<?= empty($dbSubcategories) ? 'display:none;' : '' ?>">
            <label class="form-label">Subcategory</label>
            <div class="d-flex flex-wrap gap-2" id="mciSubcategoryPills" role="group" aria-label="Subcategory"></div>
            <input type="hidden" name="subcategory" id="mciSubcategoryInput" value="<?= htmlspecialchars($subcategory) ?>" />
          </div>

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
          <div class="text-muted small">Showing <span id="mciShownCount"><?= count($filteredListings) ?></span> of <span id="mciTotalCount"><?= number_format($total) ?></span> listings</div>
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
        <div id="listingsGridView" class="row g-3" data-infinite-scroll="1">
          <?php foreach ($filteredListings as $listing): ?>
            <?php $size = 'md'; include __DIR__ . '/../views/components/listing-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <div id="listingsListView" class="row g-3 d-none" data-infinite-scroll="1">
          <?php foreach ($filteredListings as $listing): ?>
            <?php include __DIR__ . '/../views/components/listing-row.php'; ?>
          <?php endforeach; ?>
        </div>

        <?php
        // Pagination: only for users without JS (infinite scroll handles paging in-browser).
        $baseParams = [];
        if ($what !== '') {
            $baseParams['what'] = $what;
        }
        if ($where !== '') {
            $baseParams['where'] = $where;
        }
        if ($category !== '') {
            $baseParams['category'] = $category;
        }
        if ($subcategory !== '') {
            $baseParams['subcategory'] = $subcategory;
        }
        if ($tag !== '') {
            $baseParams['tag'] = $tag;
        }
        if ($priceRange !== '') {
            $baseParams['price_range'] = $priceRange;
        }
        if (!function_exists('mci_listing_page_url')) {
            function mci_listing_page_url(array $base, int $page): string
            {
                $p = $base;
                if ($page > 1) {
                    $p['page'] = $page;
                }

                return '/business-listing/?' . http_build_query($p);
            }
        }
        ?>
        <?php if ($pages > 1): ?>
        <noscript>
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
        </noscript>
        <?php endif; ?>
        <div id="mciInfiniteSentinel" class="mci-infinite-sentinel" aria-hidden="true"></div>
        <div id="mciInfiniteStatus" class="text-center small text-muted mt-3" style="display:none;"></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
