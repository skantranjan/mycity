<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_directory_listings.php';
require_once __DIR__ . '/../includes/mci_category_icons.php';

$pageTitle = 'All Categories - My City Info';
$activePage = 'categories';
$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

$slugify = static function (string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
};

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

$locations = [];
foreach ($mciDirectoryListings as $row) {
    $loc = trim((string) ($row['location'] ?? ''));
    if ($loc !== '') {
        $city = trim((string) explode(',', $loc)[0]);
        if ($city !== '') {
            $locations[$city] = true;
        }
    }
}
$locations = array_keys($locations);
sort($locations, SORT_NATURAL | SORT_FLAG_CASE);

$selectedLocation = trim((string) ($_GET['location'] ?? ''));
if ($selectedLocation !== '' && !in_array($selectedLocation, $locations, true)) {
    $selectedLocation = '';
}

$locationScopedListings = $mciDirectoryListings;
if ($selectedLocation !== '') {
    $locationScopedListings = array_values(array_filter($mciDirectoryListings, static function (array $row) use ($selectedLocation): bool {
        $loc = strtolower((string) ($row['location'] ?? ''));
        return strpos($loc, strtolower($selectedLocation)) !== false;
    }));
}

$categories = [];
foreach ($locationScopedListings as $row) {
    $name = trim((string) ($row['category'] ?? ''));
    if ($name === '') {
        continue;
    }
    $slug = $slugify($name);
    if ($slug === '') {
        continue;
    }
    if (!isset($categories[$slug])) {
        $categories[$slug] = [
            'name' => $name,
            'slug' => $slug,
            'count' => 0,
            'icon' => mci_category_icon($slug),
        ];
    }
    $categories[$slug]['count']++;
}

usort($categories, static function (array $a, array $b): int {
    return strcasecmp((string) $a['name'], (string) $b['name']);
});

$enriched = array_map(static function (array $row) use ($listingStats): array {
    $slug = (string) ($row['slug'] ?? '');
    $stats = $listingStats[$slug] ?? ['rating' => 4.0, 'reviews' => 10, 'popular_score' => 60, 'added_on' => '2026-03-01'];
    return array_merge($row, $stats);
}, $locationScopedListings);

$mostRated = $enriched;
usort($mostRated, static function (array $a, array $b): int {
    $r = ((float) $b['rating']) <=> ((float) $a['rating']);
    if ($r !== 0) {
        return $r;
    }
    return ((int) $b['reviews']) <=> ((int) $a['reviews']);
});
$mostRated = array_slice($mostRated, 0, 4);

$popular = $enriched;
usort($popular, static function (array $a, array $b): int {
    return ((int) $b['popular_score']) <=> ((int) $a['popular_score']);
});
$popular = array_slice($popular, 0, 4);

$newlyAdded = $enriched;
usort($newlyAdded, static function (array $a, array $b): int {
    return strcmp((string) $b['added_on'], (string) $a['added_on']);
});
$newlyAdded = array_slice($newlyAdded, 0, 8);
$mostRated = array_slice($mostRated, 0, 8);
$popular = array_slice($popular, 0, 8);

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
    try {
      preferred = (sessionStorage.getItem(LOCATION_KEY) || '').trim();
    } catch (e) {}
    if (!preferred) {
      try {
        preferred = (localStorage.getItem(LOCATION_KEY) || '').trim();
      } catch (e) {}
    }
    if (!preferred) {
      try {
        preferred = (sessionStorage.getItem(STORAGE_KEY) || '').trim();
      } catch (e) {}
    }
    if (!preferred) {
      try {
        preferred = (localStorage.getItem(STORAGE_KEY) || '').trim();
      } catch (e) {}
    }

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
      if (!editor.classList.contains('d-none')) {
        select.focus();
      }
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

  <div class="row g-3">
    <?php foreach ($categories as $cat): ?>
      <div class="col-12 col-sm-6 col-lg-3">
        <a class="text-decoration-none" href="/business-category/<?= urlencode((string) $cat['slug']) ?>">
          <div class="card border-0 shadow-sm bg-white h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <?= mci_render_category_icon((string) $cat['icon'], 'fs-4') ?>
                <div>
                <div class="fw-semibold"><?= htmlspecialchars((string) $cat['name']) ?></div>
                <div class="text-muted small"><?= (int) $cat['count'] ?> business<?= (int) $cat['count'] === 1 ? '' : 'es' ?></div>
                </div>
              </div>
              <i class="bi bi-chevron-right text-muted" aria-hidden="true"></i>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <section class="mt-5">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
      <h2 class="h5 mb-0 home-section-title">Most rated</h2>
      <a class="small text-decoration-none fw-semibold" href="/business-listing/?sort=rating">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="home-section-accent mb-3"></div>
    <div class="row g-3">
      <?php foreach ($mostRated as $row): ?>
        <?php $listing = $row; $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mt-5">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
      <h2 class="h5 mb-0 home-section-title">Popular</h2>
      <a class="small text-decoration-none fw-semibold" href="/business-listing/?sort=popular">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="home-section-accent mb-3"></div>
    <div class="row g-3">
      <?php foreach ($popular as $row): ?>
        <?php $listing = $row; $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mt-5">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-1 flex-wrap">
      <h2 class="h5 mb-0 home-section-title">Newly added</h2>
      <a class="small text-decoration-none fw-semibold" href="/business-listing/?sort=newest">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="home-section-accent mb-3"></div>
    <div class="row g-3">
      <?php foreach ($newlyAdded as $row): ?>
        <?php $listing = $row; $variant = 'home'; include __DIR__ . '/../views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
