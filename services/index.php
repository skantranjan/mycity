<?php

declare(strict_types=1);

require_once __DIR__ . '/../api/v1/lib/db.php';
require_once __DIR__ . '/../api/v1/lib/item_search_service.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';

$pageTitle       = 'Services - My City Info';
$activePage      = 'services';
$metaDescription = 'Find local services on My City Info. Filter by city, category and price to connect with service providers near you.';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/item-search.css" />
<script src="/assets/js/item-search.js" defer></script>
HTML;

// ── Read filter params ────────────────────────────────────────────────────────
$q        = trim((string)($_GET['q']        ?? ''));
$city     = trim((string)($_GET['city']     ?? ''));
if ($city === '') {
    $city = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
$category = trim((string)($_GET['category'] ?? ''));
$priceMin = trim((string)($_GET['price_min'] ?? ''));
$priceMax = trim((string)($_GET['price_max'] ?? ''));
$sort     = trim((string)($_GET['sort']     ?? 'relevance'));
$curPage  = max(1, (int)($_GET['page']      ?? 1));

// Derive active price preset label for the chip
$priceLabel = '';
if ($priceMin === '' && $priceMax === '500')    { $priceLabel = 'Under ₹500'; }
elseif ($priceMin === '500' && $priceMax === '2000')  { $priceLabel = '₹500 – ₹2,000'; }
elseif ($priceMin === '2000' && $priceMax === '10000') { $priceLabel = '₹2,000 – ₹10,000'; }
elseif ($priceMin === '10000' && $priceMax === '')   { $priceLabel = 'Above ₹10,000'; }

// ── Load categories for the filter dropdown (services-only) ───────────────────
$filterCategories = [];
try {
    $pdo      = api_db();
    $catStmt  = $pdo->query("
        SELECT DISTINCT c.name, c.slug
        FROM mci_categories c
        INNER JOIN mci_business_groups g   ON g.parent_category_id = c.id AND g.status = 'live'
        INNER JOIN mci_business_services p ON p.business_group_id = g.id AND p.is_active = 1
        WHERE c.parent_id IS NULL
        ORDER BY c.name
    ");
    $filterCategories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $ignored) {}

// ── Run search ────────────────────────────────────────────────────────────────
$searchResult = ['ok' => false, 'total' => 0, 'pages' => 1, 'items' => []];
try {
    $pdo          = isset($pdo) ? $pdo : api_db();
    $searchResult = api_items_search($pdo, [
        'type'      => 'services',
        'q'         => $q,
        'city'      => $city,
        'category'  => $category,
        'price_min' => $priceMin,
        'price_max' => $priceMax,
        'sort'      => $sort,
        'page'      => $curPage,
        'per_page'  => 12,
    ]);
} catch (Throwable $ignored) {}

$items      = $searchResult['items']  ?? [];
$total      = (int)($searchResult['total'] ?? 0);
$totalPages = (int)($searchResult['pages'] ?? 1);

// ── Pagination URL helper ─────────────────────────────────────────────────────
function mci_services_page_url(int $page, string $q, string $city, string $category, string $priceMin, string $priceMax, string $sort): string {
    $p = [];
    if ($q !== '')        $p['q']         = $q;
    if ($city !== '')     $p['city']       = $city;
    if ($category !== '') $p['category']   = $category;
    if ($priceMin !== '') $p['price_min']  = $priceMin;
    if ($priceMax !== '') $p['price_max']  = $priceMax;
    if ($sort !== 'relevance') $p['sort'] = $sort;
    if ($page > 1)        $p['page']       = $page;
    return '/services/?' . http_build_query($p);
}

ob_start();
?>

<!-- HERO -->
<div class="mci-items-hero">
  <h1>Find Services Near You</h1>
  <p class="mci-items-hero__sub">Discover services offered by local businesses across your city</p>
  <div class="mci-items-search-wrap">
    <form method="get" action="/services/" id="mciItemsSearchForm">
      <div class="mci-items-search-box">
        <i class="bi bi-search text-muted" aria-hidden="true"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search services…" aria-label="Search services" />
        <div class="mci-items-search-divider"></div>
        <div class="mci-items-city-wrap">
          <i class="bi bi-geo-alt-fill" style="color:var(--mci-color-primary);" aria-hidden="true"></i>
          <input type="text" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="City" aria-label="City" />
        </div>
        <button type="submit">Search</button>
      </div>
      <!-- Hidden filter state carried through the search box form -->
      <?php if ($category !== ''): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>" />
      <?php endif; ?>
      <?php if ($priceMin !== ''): ?>
        <input type="hidden" name="price_min" value="<?= htmlspecialchars($priceMin) ?>" />
      <?php endif; ?>
      <?php if ($priceMax !== ''): ?>
        <input type="hidden" name="price_max" value="<?= htmlspecialchars($priceMax) ?>" />
      <?php endif; ?>
      <?php if ($sort !== 'relevance'): ?>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />
      <?php endif; ?>
      <input type="hidden" name="page" value="1" />
    </form>
  </div>
</div>

<!-- FILTER BAR -->
<div class="mci-items-filter-bar">
  <div class="container">
    <!-- Mobile toggle (hidden on desktop) -->
    <button class="btn btn-sm btn-outline-dark d-lg-none mb-2" type="button"
      id="mciFilterToggle" aria-expanded="false" aria-controls="mciFiltersPanel">
      <i class="bi bi-sliders me-1" aria-hidden="true"></i>Filters
      <?php
        $activeFilterCount = ($city !== '' ? 1 : 0) + ($category !== '' ? 1 : 0) + ($priceLabel !== '' ? 1 : 0);
        if ($activeFilterCount > 0): ?>
        <span class="badge text-bg-dark ms-1"><?= $activeFilterCount ?></span>
      <?php endif; ?>
    </button>
    <div id="mciFiltersPanel">
    <form method="get" action="/services/" id="mciItemsFilterForm" style="display:contents;">

      <!-- Category pill -->
      <select name="category" class="mci-items-filter-pill <?= $category !== '' ? 'active' : '' ?>" aria-label="Filter by category">
        <option value="">🏷️ Category</option>
        <?php foreach ($filterCategories as $fc): ?>
          <option value="<?= htmlspecialchars($fc['slug']) ?>" <?= $fc['slug'] === $category ? 'selected' : '' ?>>
            <?= htmlspecialchars($fc['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Price pill -->
      <?php
      $priceValue = '';
      if ($priceMin === '' && $priceMax === '500')          $priceValue = 'under500';
      elseif ($priceMin === '500' && $priceMax === '2000')  $priceValue = '500-2000';
      elseif ($priceMin === '2000' && $priceMax === '10000') $priceValue = '2000-10000';
      elseif ($priceMin === '10000' && $priceMax === '')    $priceValue = 'above10000';
      ?>
      <select name="_price_preset" class="mci-items-filter-pill <?= $priceValue !== '' ? 'active' : '' ?>" aria-label="Filter by price" id="mciPricePreset">
        <option value="">💰 Price</option>
        <option value="under500"    <?= $priceValue === 'under500'    ? 'selected' : '' ?>>Under ₹500</option>
        <option value="500-2000"    <?= $priceValue === '500-2000'    ? 'selected' : '' ?>>₹500 – ₹2,000</option>
        <option value="2000-10000"  <?= $priceValue === '2000-10000'  ? 'selected' : '' ?>>₹2,000 – ₹10,000</option>
        <option value="above10000"  <?= $priceValue === 'above10000'  ? 'selected' : '' ?>>Above ₹10,000</option>
      </select>
      <!-- Hidden price_min / price_max resolved by JS -->
      <input type="hidden" name="price_min" id="mciPriceMin" value="<?= htmlspecialchars($priceMin) ?>" />
      <input type="hidden" name="price_max" id="mciPriceMax" value="<?= htmlspecialchars($priceMax) ?>" />

      <!-- Sort pill -->
      <select name="sort" class="mci-items-filter-pill <?= $sort !== 'relevance' ? 'active' : '' ?>" aria-label="Sort results">
        <option value="relevance"  <?= $sort === 'relevance'  ? 'selected' : '' ?>>↕️ Relevance</option>
        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>🕐 Newest</option>
        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>💰 Price: Low–High</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💰 Price: High–Low</option>
      </select>

      <!-- Carry q and city through pill form -->
      <input type="hidden" name="q"    value="<?= htmlspecialchars($q) ?>" />
      <input type="hidden" name="city" value="<?= htmlspecialchars($city) ?>" />
      <input type="hidden" name="page" value="1" />

    </form>

    <!-- Active filter chips -->
    <?php if ($city !== ''): ?>
      <span class="mci-items-active-tag">
        📍 <?= htmlspecialchars($city) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="city" aria-label="Remove city filter">✕</button>
      </span>
    <?php endif; ?>
    <?php if ($category !== ''): ?>
      <?php $catName = ''; foreach ($filterCategories as $fc) { if ($fc['slug'] === $category) { $catName = $fc['name']; break; } } ?>
      <span class="mci-items-active-tag">
        <?= htmlspecialchars($catName ?: $category) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="category" aria-label="Remove category filter">✕</button>
      </span>
    <?php endif; ?>
    <?php if ($priceLabel !== ''): ?>
      <span class="mci-items-active-tag">
        <?= htmlspecialchars($priceLabel) ?>
        <button class="mci-items-active-tag__x" data-dismiss-param="price" aria-label="Remove price filter">✕</button>
      </span>
    <?php endif; ?>

    <span class="mci-items-result-count"><?= number_format($total) ?> result<?= $total !== 1 ? 's' : '' ?></span>
    </div><!-- /#mciFiltersPanel -->
  </div>
</div>

<!-- RESULTS -->
<div class="container py-4">

  <?php if (empty($items)): ?>
    <div class="mci-items-empty">
      <div class="mci-items-empty__icon">🔍</div>
      <div class="mci-items-empty__title">No services found</div>
      <p class="small">Try different keywords or remove some filters.</p>
      <a href="/services/" class="btn btn-sm btn-dark mt-2">Clear all filters</a>
    </div>

  <?php else: ?>

    <!-- Results header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div class="fw-semibold">
        Services
        <?php if ($q !== ''): ?>
          <span class="fw-normal text-muted">— "<?= htmlspecialchars($q) ?>"</span>
        <?php endif; ?>
        <?php if ($city !== ''): ?>
          <span class="fw-normal text-muted"> in <?= htmlspecialchars($city) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Card grid -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4">
      <?php foreach ($items as $item):
        $bizUrl     = '/business/' . htmlspecialchars(urlencode($item['business_slug'])) . '/';
        $priceStr   = '';
        if ($item['price_min'] !== null && $item['price_max'] !== null) {
            $priceStr = '₹' . number_format((int)$item['price_min']) . ' – ₹' . number_format((int)$item['price_max']);
        } elseif ($item['price_min'] !== null) {
            $priceStr = 'From ₹' . number_format((int)$item['price_min']);
        } elseif ($item['price_max'] !== null) {
            $priceStr = 'Up to ₹' . number_format((int)$item['price_max']);
        }
      ?>
        <div class="col">
          <div class="mci-item-card h-100"
            data-item-card="1"
            data-name="<?= htmlspecialchars($item['name']) ?>"
            data-desc="<?= htmlspecialchars($item['description']) ?>"
            data-price="<?= htmlspecialchars($priceStr) ?>"
            data-unit="<?= htmlspecialchars($item['price_unit']) ?>"
            data-image="<?= htmlspecialchars($item['image_path']) ?>"
            data-biz-name="<?= htmlspecialchars($item['business_name']) ?>"
            data-biz-slug="<?= htmlspecialchars($item['business_slug']) ?>"
            data-biz-logo="<?= htmlspecialchars($item['business_logo']) ?>"
            data-biz-category="<?= htmlspecialchars($item['business_category']) ?>"
            data-city="<?= htmlspecialchars($item['city']) ?>"
          >
            <div class="mci-item-card__img">
              <?php if ($item['image_path'] !== ''): ?>
                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" />
              <?php else: ?>
                <i class="bi bi-stars" style="font-size:2rem; color:var(--mci-muted);" aria-hidden="true"></i>
              <?php endif; ?>
              <?php if ($item['business_category'] !== ''): ?>
                <span class="mci-item-card__cat-badge"><?= htmlspecialchars($item['business_category']) ?></span>
              <?php endif; ?>
            </div>
            <div class="mci-item-card__body">
              <div class="mci-item-card__name"><?= htmlspecialchars($item['name']) ?></div>
              <?php if ($item['description'] !== ''): ?>
                <div class="mci-item-card__desc"><?= htmlspecialchars($item['description']) ?></div>
              <?php endif; ?>
              <?php if ($priceStr !== ''): ?>
                <div class="mci-item-card__price"><?= $priceStr ?></div>
              <?php endif; ?>
              <div class="mci-item-card__biz-strip">
                <div class="mci-item-card__biz-logo">
                  <?php if ($item['business_logo'] !== ''): ?>
                    <img src="<?= htmlspecialchars($item['business_logo']) ?>" alt="" loading="lazy" />
                  <?php else: ?>
                    <i class="bi bi-shop" aria-hidden="true"></i>
                  <?php endif; ?>
                </div>
                <span class="mci-item-card__biz-name"><?= htmlspecialchars($item['business_name']) ?></span>
                <?php if ($item['city'] !== ''): ?>
                  <span class="mci-item-card__city">📍 <?= htmlspecialchars($item['city']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav class="d-flex justify-content-center mt-2 mb-4" aria-label="Service pages">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars(mci_services_page_url($curPage - 1, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>">
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
          </li>
          <?php for ($p = max(1, $curPage - 2); $p <= min($totalPages, $curPage + 2); $p++): ?>
            <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
              <a class="page-link" href="<?= htmlspecialchars(mci_services_page_url($p, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>"><?= $p ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $curPage >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars(mci_services_page_url($curPage + 1, $q, $city, $category, $priceMin, $priceMax, $sort)) ?>">
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  <?php endif; ?>
</div>

<!-- QUICK-VIEW MODAL -->
<div class="mci-item-modal" id="mciItemModal" role="dialog" aria-modal="true" aria-labelledby="mciModalName">
  <div class="mci-item-modal__box">
    <div class="mci-item-modal__img" id="mciModalImg">
      <img id="mciModalImgEl" src="" alt="" style="display:none;" />
      <button class="mci-item-modal__close" data-modal-close aria-label="Close">✕</button>
    </div>
    <div class="mci-item-modal__body">
      <div class="mci-item-modal__name" id="mciModalName"></div>
      <div>
        <span class="mci-item-modal__price" id="mciModalPrice"></span>
        <span class="mci-item-modal__unit" id="mciModalUnit"></span>
      </div>
      <p class="mci-item-modal__desc" id="mciModalDesc"></p>
      <div class="mci-item-modal__biz">
        <div class="mci-item-modal__biz-logo" id="mciModalBizLogo">
          <img id="mciModalBizLogoImg" src="" alt="" style="display:none;" />
          <i class="bi bi-shop" aria-hidden="true"></i>
        </div>
        <div>
          <span class="mci-item-modal__biz-name" id="mciModalBizName"></span>
          <span class="mci-item-modal__biz-meta" id="mciModalBizMeta"></span>
        </div>
        <a class="mci-item-modal__biz-link" id="mciModalBizLink" href="#">View profile →</a>
      </div>
      <div class="mci-item-modal__actions">
        <a class="mci-item-modal__cta" id="mciModalCta" href="#">View Business Profile</a>
        <a class="mci-item-modal__contact" id="mciModalContact" href="#">📞 Contact</a>
      </div>
    </div>
  </div>
</div>

<!-- Price preset → hidden inputs wiring (inline, minimal) -->
<script>
(function () {
  var preset = document.getElementById('mciPricePreset');
  var pMin   = document.getElementById('mciPriceMin');
  var pMax   = document.getElementById('mciPriceMax');
  if (!preset) return;
  var map = {
    'under500':   ['', '500'],
    '500-2000':   ['500', '2000'],
    '2000-10000': ['2000', '10000'],
    'above10000': ['10000', ''],
    '':           ['', '']
  };
  preset.addEventListener('change', function () {
    var vals = map[preset.value] || ['', ''];
    pMin.value = vals[0];
    pMax.value = vals[1];
    // form submit handled by item-search.js pill auto-submit
  });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
