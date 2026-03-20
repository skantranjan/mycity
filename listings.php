<?php
$pageTitle = 'Listings - My City Info';
$activePage = 'listings';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/listings.css" />
<script src="/assets/js/listings-view.js" defer></script>
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

require_once __DIR__ . '/includes/mci_directory_listings.php';
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
    // No price data yet. Keep it as UI placeholder.
    $ok = $ok && true;
  }
  return $ok;
});

ob_start();
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
      <a class="btn btn-sm btn-dark" href="/submit-listing.php">List your business</a>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="fw-semibold mb-3">Filters</div>
        <form method="get" action="/listings.php">
          <div class="mb-3">
            <label class="form-label">What</label>
            <input class="form-control" type="text" name="what" value="<?= htmlspecialchars($what) ?>" placeholder="Search by business name or category" />
          </div>
          <div class="mb-3">
            <label class="form-label">Where</label>
            <input class="form-control" type="text" name="where" value="<?= htmlspecialchars($where) ?>" placeholder="City or area" />
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
            <label class="form-label">Price range (UI placeholder)</label>
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
          <div class="text-muted small"><?= count($filteredListings) ?> results (demo)</div>
        </div>
      </div>

      <div id="listingsGridView" class="row g-3">
        <?php foreach ($filteredListings as $listing): ?>
          <?php $size = 'md'; include __DIR__ . '/views/components/listing-card.php'; ?>
        <?php endforeach; ?>
      </div>

      <div id="listingsListView" class="row g-3 d-none">
        <?php foreach ($filteredListings as $listing): ?>
          <?php include __DIR__ . '/views/components/listing-row.php'; ?>
        <?php endforeach; ?>
      </div>

      <div class="mt-4 d-flex align-items-center justify-content-center gap-2">
        <a class="btn btn-sm btn-outline-secondary disabled" href="#">Prev</a>
        <div class="btn btn-sm btn-outline-dark disabled">1</div>
        <a class="btn btn-sm btn-outline-secondary disabled" href="#">2</a>
        <a class="btn btn-sm btn-outline-secondary disabled" href="#">Next</a>
      </div>
      <div class="text-muted small text-center mt-2">Pagination wiring will be added when backend is integrated.</div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

