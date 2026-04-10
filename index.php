<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/mci_paths.php';
require_once __DIR__ . '/includes/mci_category_icons.php';
require_once __DIR__ . '/api/v1/lib/db.php';
require_once __DIR__ . '/api/v1/lib/business_service.php';

$pageTitle = 'Explore Your City - My City Info'; // updated below once city is known
$activePage = 'home';
$metaDescription = 'Discover local businesses, services, restaurants, and hidden gems in your city. My City Info is your local discovery platform.';
$canonicalUrl    = mci_site_base_url() . '/';
$ogImage         = mci_site_base_url() . '/assets/images/og-default.png';

$slugify = static function (string $value): string {
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
};

// ── Load categories from DB ───────────────────────────────
$categories = [];          // top-level only — for the grid
$categoryTree = [];        // [ {parent, children:[]} ] — for the accordion
$pdo = null;               // defined so later queries don't trigger "undefined variable"

try {
    $pdo = api_db();

    $stmt = $pdo->query(
        "SELECT id, parent_id, name, slug, icon, sort_order
         FROM mci_categories
         ORDER BY COALESCE(parent_id, 0), sort_order, name"
    );
    $allCats = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    // Index by id for quick lookup
    $byId = [];
    foreach ($allCats as $c) {
        $byId[$c['id']] = $c + ['children' => []];
    }

    // Build tree and top-level list
    foreach ($byId as $id => $c) {
        if ($c['parent_id'] === null) {
            $categoryTree[] = &$byId[$id];
        } else {
            if (isset($byId[$c['parent_id']])) {
                $byId[$c['parent_id']]['children'][] = &$byId[$id];
            }
        }
    }
    unset($c, $id);

    // Top-level only for the grid — pick 16 at random
    foreach ($categoryTree as $c) {
        $categories[] = [
            'name' => $c['name'],
            'slug' => $c['slug'],
            'icon' => $c['icon'] ?: mci_category_icon($c['slug']),
        ];
    }
    shuffle($categories);
    $categories = array_slice($categories, 0, 16);
} catch (Throwable $ignored) {
    // DB not ready — fall back to static list
}

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
HTML;

// ── Helper: map a DB listing row to the shape listing-card.php expects ───────
function mci_listing_row_to_card(array $row): array {
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
                              : mci_listing_placeholder_url()),
        'price_range' => $row['price_range'] ?? null,
    ];
}

// ── Active city filter ────────────────────────────────────────────────────────
// Priority: ?where= URL param > cookie set by city picker JS
$activeCity = trim((string)($_GET['where'] ?? ''));
if ($activeCity === '') {
    $activeCity = trim((string)(urldecode($_COOKIE['mci_active_city'] ?? '')));
}
if ($activeCity !== '') {
    $pageTitle = 'Explore ' . $activeCity . ' - My City Info';
}

// ── Load recent + popular listings from DB ────────────────────────────────────
$recentListings     = [];
$establishedListings = [];

try {
    $cityFilter  = $activeCity !== '' ? ['city' => $activeCity] : [];
    $recentRows  = [];
    $establishedRows = [];

    if ($pdo instanceof PDO) {
        $recentRows  = api_business_list_public($pdo, $cityFilter + ['per_page' => 8, 'sort' => 'newest'])['businesses'] ?? [];
        // Oldest-first adds variety vs “recent”; not a popularity metric — copy matches this.
        $establishedRows = api_business_list_public($pdo, $cityFilter + ['per_page' => 8, 'sort' => 'oldest'])['businesses'] ?? [];
    }

    foreach ($recentRows  as $row) { $recentListings[]  = mci_listing_row_to_card($row); }
    foreach ($establishedRows as $row) { $establishedListings[] = mci_listing_row_to_card($row); }
} catch (Throwable $ignored) {
    // Graceful degradation — sections will render empty
}


ob_start();
?>

<div class="home-page pb-5">
  <!-- Hero -->
  <section class="home-hero text-white mb-5">
    <div class="home-hero-blob home-hero-blob--1" aria-hidden="true"></div>
    <div class="home-hero-blob home-hero-blob--2" aria-hidden="true"></div>

    <div class="row g-4 align-items-center position-relative mci-z-content">
      <div class="col-12 col-lg-6">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="home-stat-pill">✨ Local discovery</span>
          <span class="home-stat-pill">📍 City-wide</span>
          <span class="home-stat-pill">🆓 List for free</span>
        </div>
        <h1 class="display-5 fw-bold mb-3 lh-sm home-hero-title">
          <span class="home-hero-explore-line">
            <span class="home-hero-explore-prefix">Explore</span>
            <span id="heroCityName" class="home-hero-city-name home-hero-highlight"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>
          </span>
        </h1>
        <p class="lead text-white-50 mb-4 mb-lg-5 home-hero-lead home-hero-tagline">
          Let’s uncover the best places, businesses and services in
          <span id="heroTaglineCity" class="text-white fw-semibold"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>.
        </p>

        <div class="d-none d-lg-flex flex-wrap align-items-center home-hero-cta-row gap-3">
          <a href="/submit-business-listing/" class="btn btn-home-primary btn-home-cta-primary">List your business</a>
          <a href="/business-listing/" class="btn btn-home-ghost btn-home-cta-secondary">Browse all</a>
        </div>
      </div>

      <div class="col-12 col-lg-6 text-center text-lg-end">
        <div class="position-relative d-inline-block">
          <img
            src="/assets/images/hero-illustration.svg"
            alt=""
            class="img-fluid rounded-4 shadow-lg border border-light border-opacity-25 home-hero-image"
            width="640"
            height="420"
            loading="eager"
          />
          <div class="position-absolute bottom-0 start-0 m-2 m-md-3 px-2 px-md-3 py-2 rounded-3 small fw-semibold text-dark bg-white bg-opacity-90 shadow home-hero-badge d-inline-flex align-items-center gap-1">
            <i class="bi bi-map" aria-hidden="true"></i>
            <span id="homeHeroBadgeLabel"><?= $activeCity !== '' ? 'Discover ' . htmlspecialchars($activeCity, ENT_QUOTES, 'UTF-8') : 'Discover nearby' ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="home-search-card mt-4 mt-lg-5 p-3 p-md-4 position-relative mci-z-content">
      <form action="/business-listing/" method="get">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label" for="homeWhat">What</label>
            <input id="homeWhat" class="form-control form-control-lg" type="text" name="what" placeholder="Ex: food, service, barber, hotel" />
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label" for="homeWhere">Where</label>
            <input id="homeWhere" class="form-control form-control-lg" type="text" name="where" placeholder="City or area" autocomplete="address-level2" />
          </div>
          <div class="col-12 col-md-2">
            <button class="btn btn-home-primary w-100 py-2 py-md-3" type="submit">Search</button>
          </div>
        </div>
      </form>
    </div>

    <div class="d-flex d-lg-none flex-wrap align-items-stretch home-hero-cta-row gap-3 mt-3 position-relative mci-z-content">
      <a href="/submit-business-listing/" class="btn btn-home-primary btn-home-cta-primary flex-grow-1 text-center">List your business</a>
      <a href="/business-listing/" class="btn btn-home-ghost btn-home-cta-secondary flex-grow-1 text-center">Browse</a>
    </div>
  </section>

  <!-- Quick value strip -->
  <section class="row g-3 mb-5">
    <div class="col-12 col-md-4">
      <div class="bg-white rounded-4 border p-4 h-100 shadow-sm">
        <div class="fs-3 mb-2 text-primary" aria-hidden="true"><i class="bi bi-card-checklist"></i></div>
        <div class="fw-bold mb-1">List or claim</div>
        <div class="text-muted small mb-0">List your business or claim an existing page.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bg-white rounded-4 border p-4 h-100 shadow-sm">
        <div class="fs-3 mb-2 text-primary" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
        <div class="fw-bold mb-1">Reach locals</div>
        <div class="text-muted small mb-0">Show up when people search your city.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bg-white rounded-4 border p-4 h-100 shadow-sm">
        <div class="fs-3 mb-2 text-primary" aria-hidden="true"><i class="bi bi-chat-dots"></i></div>
        <div class="fw-bold mb-1">Get enquiries</div>
        <div class="text-muted small mb-0">Let customers contact you from your listing.</div>
      </div>
    </div>
  </section>

  <!-- Categories -->
  <section class="mb-5">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-sm-between gap-3 mb-4">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">Browse categories</h2>
        <p class="text-muted small mb-0">Jump into popular services and places</p>
      </div>
      <div class="d-flex flex-column flex-sm-row gap-2 align-self-stretch align-self-sm-auto">
        <a href="/business-listing/" class="btn btn-home-outline btn-sm text-center mci-touch-target mci-touch-target--sm">See all listings →</a>
        <a href="/business-category/" class="btn btn-home-outline btn-sm text-center mci-touch-target mci-touch-target--sm">See all categories →</a>
      </div>
    </div>

    <div class="row g-2 g-md-3">
      <?php if (empty($categories)): ?>
        <div class="col-12">
          <p class="text-muted small mb-0">Categories will appear here once they are published on the directory.</p>
        </div>
      <?php else: ?>
        <?php foreach ($categories as $cat): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <a class="home-category-tile w-100" href="/business-category/<?= urlencode($cat['slug']) ?>/">
              <span class="home-category-icon"><?= mci_render_category_icon((string) $cat['icon'], '') ?></span>
              <span><?= htmlspecialchars($cat['name']) ?></span>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- Recent listings -->
  <section class="mb-5 pb-lg-2">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-sm-between gap-3 mb-4">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">Recent listings</h2>
        <p class="text-muted small mb-0">Newly added businesses — name, category, and area at a glance</p>
      </div>
      <a class="btn btn-home-primary btn-sm text-center mci-touch-target mci-touch-target--sm" href="/submit-business-listing/">+ List your business</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($recentListings as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Established listings (oldest first — complements “recent” above) -->
  <section class="mb-2">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-sm-between gap-3 mb-4">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">
          Established in
          <span id="homePopularCity" class="home-popular-city-name"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>
        </h2>
        <p class="text-muted small mb-0">Businesses that joined the directory earlier — a different slice than new listings above</p>
      </div>
      <a href="/business-listing/" class="btn btn-home-outline btn-sm align-self-stretch align-self-sm-auto text-center mci-touch-target mci-touch-target--sm">See all in directory →</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($establishedListings as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php
// ── Site index accordion — built from DB category tree ─────────────────────

$siteIndexLocations = [];
try {
    if ($pdo instanceof PDO) {
        $locRows = $pdo->query(
            "SELECT country, state, city FROM mci_locations ORDER BY country, state, city"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($locRows as $locRow) {
            $siteIndexLocations[$locRow['country']][$locRow['state']][] = $locRow['city'];
        }
    }
} catch (Throwable $ignored) {
    // Graceful degradation — accordion renders with no locations
}
?>

<div class="container px-3 px-sm-4 py-4 py-md-5" id="mciSiteIndex">
  <div class="accordion accordion-flush border rounded-3 overflow-hidden shadow-sm" id="mciSiteIndexAccordion">

    <!-- Categories accordion item -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header" id="mciSiteIndexCatHead">
        <button
          class="accordion-button collapsed fw-semibold"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mciSiteIndexCatBody"
          aria-expanded="false"
          aria-controls="mciSiteIndexCatBody"
          style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);"
        >
          <i class="bi bi-tags me-2" aria-hidden="true"></i>
          Browse all categories &amp; subcategories
          <?php
            $totalSubCount = 0;
            foreach ($categoryTree as $c) { $totalSubCount += count($c['children']); }
          ?>
          <?php if ($totalSubCount > 0): ?>
            <span class="ms-2 badge text-bg-secondary fw-normal" style="font-size:var(--mci-text-micro)">
              <?= $totalSubCount ?> subcategories
            </span>
          <?php endif; ?>
        </button>
      </h2>
      <div id="mciSiteIndexCatBody" class="accordion-collapse collapse" aria-labelledby="mciSiteIndexCatHead" data-bs-parent="#mciSiteIndexAccordion">
        <div class="accordion-body pt-3 pb-4 px-3 px-md-4">
          <?php if (empty($categoryTree)): ?>
            <p class="text-muted small mb-0">No categories yet — <a href="/cp/categories/">add some in the control panel</a>.</p>
          <?php else: ?>
            <?php foreach ($categoryTree as $parent): ?>
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <?php if ($parent['icon']): ?>
                    <span style="font-size:var(--mci-text-base);"><?= mci_render_category_icon($parent['icon'], '') ?></span>
                  <?php endif; ?>
                  <a href="/business-category/<?= urlencode($parent['slug']) ?>/"
                     class="fw-semibold text-uppercase text-decoration-none"
                     style="font-size:var(--mci-text-micro);letter-spacing:0.08em;color:var(--mci-color-primary-deep);">
                    <?= htmlspecialchars($parent['name']) ?>
                  </a>
                </div>
                <?php if (!empty($parent['children'])): ?>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($parent['children'] as $sub): ?>
                      <a href="/business-category/<?= urlencode($sub['slug']) ?>/" class="mci-site-index-tag">
                        <?= htmlspecialchars($sub['name']) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted" style="font-size:var(--mci-text-xs);">No subcategories yet</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="border-top" aria-hidden="true"></div>

    <!-- Locations accordion item -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header" id="mciSiteIndexLocHead">
        <button
          class="accordion-button collapsed fw-semibold"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mciSiteIndexLocBody"
          aria-expanded="false"
          aria-controls="mciSiteIndexLocBody"
          style="background:var(--mci-color-primary-soft);color:var(--mci-color-primary-deep);"
        >
          <i class="bi bi-geo-alt me-2" aria-hidden="true"></i>
          Browse by city &amp; location
          <span class="ms-2 badge text-bg-secondary fw-normal" style="font-size:var(--mci-text-micro)">
            <?php
            $totalCityCount = 0;
            foreach ($siteIndexLocations as $_states) {
                foreach ($_states as $_cities) {
                    $totalCityCount += count($_cities);
                }
            }
            ?>
            <?= $totalCityCount ?> cities
          </span>
        </button>
      </h2>
      <div id="mciSiteIndexLocBody" class="accordion-collapse collapse" aria-labelledby="mciSiteIndexLocHead" data-bs-parent="#mciSiteIndexAccordion">
        <div class="accordion-body pt-3 pb-4 px-3 px-md-4">
          <?php if (empty($siteIndexLocations)): ?>
            <p class="text-muted small mb-0">No locations yet — they'll appear here as listings are added.</p>
          <?php else: ?>
            <?php foreach ($siteIndexLocations as $country => $stateMap): ?>
              <div class="mb-4">
                <div class="fw-semibold small mb-2 text-uppercase"
                     style="font-size:var(--mci-text-micro);letter-spacing:0.08em;color:var(--mci-color-primary-deep);">
                  <?= htmlspecialchars($country) ?>
                </div>
                <?php foreach ($stateMap as $state => $cities): ?>
                  <?php if ($state !== ''): ?>
                    <div class="text-muted mb-1 mt-2" style="font-size:var(--mci-text-xs);">
                      <?= htmlspecialchars($state) ?>
                    </div>
                  <?php endif; ?>
                  <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php foreach ($cities as $city): ?>
                      <a href="/business-listing/?where=<?= urlencode($city) ?>" class="mci-site-index-tag">
                        <?= htmlspecialchars($city) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
