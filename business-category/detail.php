<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_directory_listings.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$slug = strtolower($slug);

$slugify = static function (string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
};

$pageTitle = 'Category - My City Info';
$activePage = 'categories';
$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

$categoryIcons = [
    'real-estate' => '🏠',
    'furniture-store' => '🛋️',
    'painter' => '🎨',
    'restaurant' => '🍽️',
    'health' => '⚕️',
    'automotive' => '🚗',
    'hotels' => '🏨',
    'gym' => '💪',
    'bakery' => '🥐',
    'electrician' => '⚡',
    'park' => '🌳',
    'cafe' => '☕',
    'dentist' => '🦷',
    'spa' => '🧖',
];

$listingStats = [
    'property-852' => ['rating' => 4.8, 'reviews' => 68, 'popular_score' => 96, 'added_on' => '2026-03-16'],
    'locker-shop-uk' => ['rating' => 4.5, 'reviews' => 37, 'popular_score' => 82, 'added_on' => '2026-03-11'],
    'jxf-painting' => ['rating' => 4.7, 'reviews' => 55, 'popular_score' => 90, 'added_on' => '2026-03-18'],
    'hunter-hill-physio' => ['rating' => 4.6, 'reviews' => 44, 'popular_score' => 84, 'added_on' => '2026-03-09'],
    'famous-veg-restaurant-bhopal' => ['rating' => 4.9, 'reviews' => 122, 'popular_score' => 98, 'added_on' => '2026-03-20'],
    'chester-gym' => ['rating' => 4.4, 'reviews' => 31, 'popular_score' => 79, 'added_on' => '2026-03-08'],
    'spark-electricals' => ['rating' => 4.3, 'reviews' => 28, 'popular_score' => 76, 'added_on' => '2026-03-12'],
    'sunrise-hotel-rooms' => ['rating' => 4.2, 'reviews' => 19, 'popular_score' => 74, 'added_on' => '2026-03-15'],
    'city-park-walks' => ['rating' => 4.1, 'reviews' => 16, 'popular_score' => 71, 'added_on' => '2026-03-10'],
    'cafe-aroma' => ['rating' => 4.6, 'reviews' => 49, 'popular_score' => 86, 'added_on' => '2026-03-14'],
    'quickcare-dentist' => ['rating' => 4.5, 'reviews' => 41, 'popular_score' => 81, 'added_on' => '2026-03-17'],
    'urban-spa-house' => ['rating' => 4.7, 'reviews' => 52, 'popular_score' => 88, 'added_on' => '2026-03-19'],
];

$selectedCategoryName = '';
$rows = [];
foreach ($mciDirectoryListings as $row) {
    $category = trim((string) ($row['category'] ?? ''));
    if ($category === '') {
        continue;
    }
    if ($slugify($category) !== $slug) {
        continue;
    }
    $selectedCategoryName = $category;
    $rows[] = $row;
}

if ($selectedCategoryName === '') {
    http_response_code(404);
    $pageTitle = 'Category Not Found - My City Info';
} else {
    $pageTitle = $selectedCategoryName . ' - Categories - My City Info';
}

$locations = [];
foreach ($mciDirectoryListings as $r) {
    $loc = trim((string) ($r['location'] ?? ''));
    if ($loc === '') {
        continue;
    }
    $city = trim((string) explode(',', $loc)[0]);
    if ($city !== '') {
        $locations[$city] = true;
    }
}
$locations = array_keys($locations);
sort($locations, SORT_NATURAL | SORT_FLAG_CASE);

$selectedLocation = trim((string) ($_GET['location'] ?? ''));
if ($selectedLocation !== '' && !in_array($selectedLocation, $locations, true)) {
    $selectedLocation = '';
}

$sort = trim((string) ($_GET['sort'] ?? 'popular'));
$sortAllowed = ['rating', 'popular', 'newest'];
if (!in_array($sort, $sortAllowed, true)) {
    $sort = 'popular';
}

$filteredRows = $rows;
if ($selectedLocation !== '') {
    $filteredRows = array_values(array_filter($filteredRows, static function (array $r) use ($selectedLocation): bool {
        $loc = strtolower(trim((string) ($r['location'] ?? '')));
        $needle = strtolower(trim($selectedLocation));
        return $needle !== '' && strpos($loc, $needle) !== false;
    }));
}

$enrichedRows = array_map(static function (array $r) use ($listingStats): array {
    $slugItem = (string) ($r['slug'] ?? '');
    $stats = $listingStats[$slugItem] ?? ['rating' => 0.0, 'reviews' => 0, 'popular_score' => 0, 'added_on' => '2026-01-01'];
    return array_merge($r, $stats);
}, $filteredRows);

usort($enrichedRows, static function (array $a, array $b) use ($sort): int {
    if ($sort === 'rating') {
        $r = ((float) $b['rating']) <=> ((float) $a['rating']);
        if ($r !== 0) return $r;
        return ((int) $b['reviews']) <=> ((int) $a['reviews']);
    }
    if ($sort === 'newest') {
        return strcmp((string) $b['added_on'], (string) $a['added_on']);
    }
    $p = ((int) $b['popular_score']) <=> ((int) $a['popular_score']);
    if ($p !== 0) return $p;
    return ((float) $b['rating']) <=> ((float) $a['rating']);
});

$extraJS = <<<'HTML'
<script>
(function () {
  var STORAGE_KEY = 'mci_detected_city';
  var LOCATION_KEY = 'mci_selected_location';
  var url = new URL(window.location.href);

  if (url.searchParams.has('location') && (url.searchParams.get('location') || '').trim() !== '') {
    var loc = (url.searchParams.get('location') || '').trim();
    try { sessionStorage.setItem(LOCATION_KEY, loc); } catch (e) {}
    try { localStorage.setItem(LOCATION_KEY, loc); } catch (e) {}
    return;
  }

  var preferred = '';
  try { preferred = (sessionStorage.getItem(LOCATION_KEY) || '').trim(); } catch (e) {}
  if (!preferred) {
    try { preferred = (localStorage.getItem(LOCATION_KEY) || '').trim(); } catch (e) {}
  }
  if (!preferred) {
    try { preferred = (sessionStorage.getItem(STORAGE_KEY) || '').trim(); } catch (e) {}
  }
  if (!preferred) {
    try { preferred = (localStorage.getItem(STORAGE_KEY) || '').trim(); } catch (e) {}
  }
  if (!preferred) return;

  url.searchParams.set('location', preferred);
  window.location.replace(url.toString());
})();
</script>
HTML;

ob_start();
?>

<div class="py-4 py-lg-5">
  <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
    <div>
      <h1 class="h3 mb-1"><?= htmlspecialchars((string) $selectedCategoryName) ?></h1>
      <?php if ($selectedCategoryName !== ''): ?>
        <div class="text-muted small">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fs-4" aria-hidden="true"><?= htmlspecialchars((string) ($categoryIcons[$slug] ?? '📁')) ?></span>
            <?php if ($selectedLocation !== ''): ?>
              <?= count($enrichedRows) ?> business<?= count($enrichedRows) === 1 ? '' : 'es' ?> in <strong><?= htmlspecialchars((string) $selectedLocation) ?></strong>.
            <?php else: ?>
              <?= count($enrichedRows) ?> business<?= count($enrichedRows) === 1 ? '' : 'es' ?> across all locations.
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <a class="btn btn-sm btn-outline-dark" href="/business-category/">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>All categories
      </a>
      <a
        class="btn btn-sm btn-outline-dark"
        href="/business-listing/?category=<?= urlencode((string) $selectedCategoryName) ?>"
        title="See all listings for this category"
      >
        <i class="bi bi-grid me-1" aria-hidden="true"></i>See all listings
      </a>
    </div>
  </div>

  <?php if ($selectedCategoryName === ''): ?>
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body">
        <div class="text-muted mb-3">Try browsing from the category list.</div>
        <a class="btn btn-dark btn-sm" href="/business-category/">Browse categories</a>
      </div>
    </div>
  <?php else: ?>
    <div class="card border-0 shadow-sm bg-white mb-4">
      <div class="card-body">
        <div class="d-flex align-items-end justify-content-between gap-3 flex-wrap">
          <form method="get" action="" class="d-flex align-items-end gap-3 flex-wrap" aria-label="Category filters">
            <div class="d-flex flex-column">
              <label class="form-label small mb-1" for="categoryLocationDetail">Location</label>
              <select id="categoryLocationDetail" name="location" class="form-select form-select-sm" aria-label="Filter by location">
                <option value="" <?= $selectedLocation === '' ? 'selected' : '' ?>>All locations</option>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedLocation === $loc ? 'selected' : '' ?>>
                    <?= htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="d-flex flex-column">
              <label class="form-label small mb-1" for="categorySortDetail">Sort by</label>
              <select id="categorySortDetail" name="sort" class="form-select form-select-sm" aria-label="Sort listings">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popular first</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newly added</option>
              </select>
            </div>

            <button type="submit" class="btn btn-sm btn-dark">
              <i class="bi bi-sliders me-1" aria-hidden="true"></i>Apply
            </button>
          </form>

          <div class="text-muted small">
            Showing <strong><?= count($enrichedRows) ?></strong> business<?= count($enrichedRows) === 1 ? '' : 'es' ?>.
          </div>
        </div>
      </div>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($enrichedRows as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
