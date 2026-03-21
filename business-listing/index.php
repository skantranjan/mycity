<?php
$pageTitle = 'Listings - My City Info';
$activePage = 'listings';

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
  // On mobile: hide filters panel by default
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
</script>
HTML;

$what = trim((string)($_GET['what'] ?? ''));
$where = trim((string)($_GET['where'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$priceRange = trim((string)($_GET['price_range'] ?? ''));

/** Exact tag match (case-insensitive). */
function mci_listing_has_tag(array $listing, string $tag): bool
{
    $tag = strtolower(trim($tag));
    if ($tag === '') {
        return true;
    }
    foreach ($listing['tags'] ?? [] as $t) {
        if (strtolower(trim((string) $t)) === $tag) {
            return true;
        }
    }
    return false;
}

$categories = [
  'Airport',
  'Automotive',
  'Bakery',
  'Beauty Salon',
  'Cafe',
  'Dentist',
  'Electrician',
  'Furniture Store',
  'Gym',
  'Health',
  'Hotels',
  'Painter',
  'Park',
  'Real Estate',
  'Restaurant',
  'Shopping',
  'Spa',
  'Supermarket',
  'Travel Agency',
];

require_once __DIR__ . '/../includes/mci_directory_listings.php';
$allListings = $mciDirectoryListings;

// UI-only filtering (placeholder).
$filteredListings = array_filter($allListings, function ($l) use ($what, $where, $category, $priceRange, $tag) {
  $ok = true;
  if ($category !== '') {
      $ok = $ok && ($l['category'] === $category);
  }
  if ($what !== '') {
      $matchWhat = stripos($l['title'], $what) !== false || stripos($l['category'], $what) !== false;
      if (!$matchWhat) {
          foreach ($l['tags'] ?? [] as $t) {
              if (stripos((string) $t, $what) !== false) {
                  $matchWhat = true;
                  break;
              }
          }
      }
      $ok = $ok && $matchWhat;
  }
  if ($where !== '') {
      $ok = $ok && stripos($l['location'], $where) !== false;
  }
  if ($tag !== '') {
      $ok = $ok && mci_listing_has_tag($l, $tag);
  }
  if ($priceRange !== '') {
    $ok = $ok && (($l['price_range'] ?? '') === $priceRange);
  }
  return $ok;
});

ob_start();
?>

<?php
$activeFilterCount = ($what !== '' ? 1 : 0) + ($where !== '' ? 1 : 0) + ($category !== '' ? 1 : 0) + ($tag !== '' ? 1 : 0) + ($priceRange !== '' ? 1 : 0);
?>
<div class="row g-4">
  <div class="col-12">
    <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap">
      <div>
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

  <div class="col-12 col-lg-4" id="mciFiltersPanel">
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
            <input class="form-control" type="text" name="tag" value="<?= htmlspecialchars($tag) ?>" placeholder="e.g. Vegetarian, Toronto, B2B" autocomplete="off" />
            <div class="form-text">Matches a business tag (from detail pages). Case-insensitive.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
              <option value="">All categories</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $c === $category ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Price range</label>
            <select class="form-select" name="price_range">
              <option value="">Any</option>
              <option value="free" <?= $priceRange === 'free' ? 'selected' : '' ?>>₹ (Inexpensive)</option>
              <option value="moderate" <?= $priceRange === 'moderate' ? 'selected' : '' ?>>₹₹ (Moderate)</option>
              <option value="pricey" <?= $priceRange === 'pricey' ? 'selected' : '' ?>>₹₹₹ (Pricey)</option>
              <option value="ultra" <?= $priceRange === 'ultra' ? 'selected' : '' ?>>₹₹₹₹ (Ultra High)</option>
            </select>
          </div>

          <button class="btn btn-outline-dark w-100" type="submit">Apply filters</button>
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
            <button
              type="button"
              class="btn btn-outline-dark btn-sm listings-view-toggle__btn active"
              id="listingsViewGrid"
              aria-pressed="true"
              aria-label="Grid view"
              title="Grid view"
            >
              <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
            </button>
            <button
              type="button"
              class="btn btn-outline-dark btn-sm listings-view-toggle__btn"
              id="listingsViewList"
              aria-pressed="false"
              aria-label="List view"
              title="List view"
            >
              <i class="bi bi-list-ul" aria-hidden="true"></i>
            </button>
          </div>
          <div class="text-muted small">Showing <?= count($filteredListings) ?> of <?= count($allListings) ?> listings</div>
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

        <?php if (count($filteredListings) < count($allListings)): ?>
          <div class="mt-4 text-center">
            <button type="button" class="btn btn-outline-dark btn-sm px-4" disabled>
              Load more <span class="text-muted ms-1">(<?= count($filteredListings) ?> of <?= count($allListings) ?> shown)</span>
            </button>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>

