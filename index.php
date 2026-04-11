<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/mci_paths.php';
require_once __DIR__ . '/includes/mci_config.php';
require_once __DIR__ . '/includes/mci_cache.php';
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
$ttlHome = mci_cache_ttl_default();

try {
    $pdo = api_db();

    $allCats = mci_cache_remember(
        mci_cache_public_key('home:categories:v1'),
        $ttlHome,
        static function () use ($pdo): array {
            $stmt = $pdo->query(
                "SELECT id, parent_id, name, slug, icon, sort_order
                 FROM mci_categories
                 ORDER BY COALESCE(parent_id, 0), sort_order, name"
            );

            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    );

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
} catch (Throwable $e) {
    mci_log_error('index categories', $e);
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
        $cityKey = $activeCity !== '' ? hash('sha256', strtolower($activeCity)) : 'all';
        $recentRows = mci_cache_remember(
            mci_cache_public_key('home:list:newest:' . $cityKey . ':v1'),
            $ttlHome,
            static function () use ($pdo, $cityFilter): array {
                return api_business_list_public($pdo, $cityFilter + ['per_page' => 8, 'sort' => 'newest'])['businesses'] ?? [];
            }
        );
        // Oldest-first adds variety vs “recent”; not a popularity metric — copy matches this.
        $establishedRows = mci_cache_remember(
            mci_cache_public_key('home:list:oldest:' . $cityKey . ':v1'),
            $ttlHome,
            static function () use ($pdo, $cityFilter): array {
                return api_business_list_public($pdo, $cityFilter + ['per_page' => 8, 'sort' => 'oldest'])['businesses'] ?? [];
            }
        );
    }

    foreach ($recentRows  as $row) { $recentListings[]  = mci_listing_row_to_card($row); }
    foreach ($establishedRows as $row) { $establishedListings[] = mci_listing_row_to_card($row); }
} catch (Throwable $e) {
    mci_log_error('index listings', $e);
}


ob_start();
?>

<div class="home-page pb-4 pb-md-5">
  <!-- Hero -->
  <section class="home-hero home-hero--compact text-white mb-3">
    <div class="home-hero-blob home-hero-blob--1" aria-hidden="true"></div>
    <div class="home-hero-blob home-hero-blob--2" aria-hidden="true"></div>

    <div class="row g-3 align-items-start position-relative mci-z-content">
      <div class="col-12">
        <p class="home-hero-kicker mb-2">Find local favourites — fast.</p>
        <div class="d-flex flex-wrap gap-2 mb-2 home-hero-pills">
          <span class="home-stat-pill">✨ Local discovery</span>
          <span class="home-stat-pill">📍 City-wide</span>
          <span class="home-stat-pill">🆓 List for free</span>
        </div>
        <h1 class="fw-bold mb-2 lh-sm home-hero-title">
          <span class="home-hero-explore-line">
            <span class="home-hero-explore-prefix">Explore</span>
            <span id="heroCityName" class="home-hero-city-name home-hero-highlight"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>
          </span>
        </h1>
        <p class="text-white-50 mb-2 mb-md-3 home-hero-lead home-hero-tagline">
          Top places, real businesses — zero guesswork in
          <span id="heroTaglineCity" class="text-white fw-semibold"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>.
        </p>

        <div class="home-search-card home-search-card--compact home-search-card--shine position-relative mci-z-content mb-0">
          <form action="/business-listing/" method="get">
            <div class="row g-2 align-items-end">
              <div class="col-12 col-md">
                <label class="form-label home-search-card__label" for="homeWhat">What</label>
                <div class="home-search-field">
                  <span class="home-search-field__icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                  <input id="homeWhat" class="form-control home-search-card__input home-search-card__input--icon" type="text" name="what" placeholder="Food, salon, hotel…" />
                </div>
              </div>
              <div class="col-12 col-md">
                <label class="form-label home-search-card__label" for="homeWhere">Where</label>
                <div class="home-search-field">
                  <span class="home-search-field__icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                  <input id="homeWhere" class="form-control home-search-card__input home-search-card__input--icon" type="text" name="where" placeholder="City or area" autocomplete="address-level2" />
                </div>
              </div>
              <div class="col-12 col-md-auto">
                <label class="form-label d-none d-md-block invisible user-select-none" for="homeSearchSubmit">Search</label>
                <button id="homeSearchSubmit" class="btn btn-home-primary home-search-card__btn w-100" type="submit">
                  <span class="home-search-card__btn-text">Search</span>
                  <i class="bi bi-arrow-right-short home-search-card__btn-ico" aria-hidden="true"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Quick value strip -->
  <section class="row g-2 g-md-3 mb-4 home-value-strip">
    <div class="col-12 col-md-4">
      <div class="home-value-card bg-white rounded-3 border p-3 h-100 shadow-sm">
        <div class="home-value-card__icon text-primary mb-2" aria-hidden="true"><i class="bi bi-card-checklist"></i></div>
        <div class="home-value-card__title fw-bold mb-1">List or claim</div>
        <div class="home-value-card__text text-muted mb-0">List your business or claim an existing page.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="home-value-card bg-white rounded-3 border p-3 h-100 shadow-sm">
        <div class="home-value-card__icon text-primary mb-2" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
        <div class="home-value-card__title fw-bold mb-1">Reach locals</div>
        <div class="home-value-card__text text-muted mb-0">Show up when people search your city.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="home-value-card bg-white rounded-3 border p-3 h-100 shadow-sm">
        <div class="home-value-card__icon text-primary mb-2" aria-hidden="true"><i class="bi bi-chat-dots"></i></div>
        <div class="home-value-card__title fw-bold mb-1">Get enquiries</div>
        <div class="home-value-card__text text-muted mb-0">Let customers contact you from your listing.</div>
      </div>
    </div>
  </section>

  <!-- Categories -->
  <section class="mb-4 mb-md-5">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-sm-between gap-3 mb-3">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">Browse categories</h2>
        <p class="text-muted home-section-sub mb-0">Jump into popular services and places</p>
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
  <section class="mb-4 mb-md-5 pb-lg-2">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-sm-between gap-3 mb-3">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">Recent listings</h2>
        <p class="text-muted home-section-sub mb-0">Newly added businesses — name, category, and area at a glance</p>
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
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-sm-between gap-3 mb-3">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">
          Established in
          <span id="homePopularCity" class="home-popular-city-name"><?= $activeCity !== '' ? htmlspecialchars($activeCity) : 'your city' ?></span>
        </h2>
        <p class="text-muted home-section-sub mb-0">Businesses that joined the directory earlier — a different slice than new listings above</p>
      </div>
      <a href="/business-listing/" class="btn btn-home-outline btn-sm align-self-stretch align-self-sm-auto text-center mci-touch-target mci-touch-target--sm">See all in directory →</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($establishedListings as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Substantive crawlable copy: directory purpose, how to use it, and for business owners -->
  <section class="mb-5" aria-labelledby="homeEditorialHeading">
    <div class="rounded-4 border bg-white p-3 p-md-4 shadow-sm home-editorial">
      <h2 id="homeEditorialHeading" class="home-editorial__title fw-bold mb-2 text-dark">Find local businesses across India</h2>
      <div class="text-body-secondary home-editorial__body">
        <p class="mb-3">
          My City Info is a free online business directory for people who want to discover
          <strong class="text-dark">trusted local services</strong>, shops, restaurants, gyms, clinics,
          and skilled professionals in their own city or neighbourhood. Instead of piecing together hints from
          scattered social posts, you can search by <strong class="text-dark">what you need</strong> and
          <strong class="text-dark">where you are</strong>, then browse listings organised by category and location.
        </p>
        <p class="mb-3">
          Each listing is meant to show the essentials—business name, category, city or area, and practical ways to
          get in touch—so you can compare options and decide who to call or visit. You can
          <a href="/business-category/">explore all categories</a>, choose a segment from the grid above, or open the
          <a href="/business-listing/">full listings index</a> when you want to combine keywords, filters, and place names
          in one view.
        </p>
        <p class="mb-3">
          New businesses join the directory regularly. We also surface listings that have been in the index longer,
          because many customers still rely on <strong class="text-dark">established local names</strong> they heard about
          from friends, neighbours, or colleagues. Seeing both helps you spot new openings and familiar options in the
          same visit.
        </p>
        <p class="mb-3">
          <strong class="text-dark">If you own or manage a business</strong>, adding or claiming your listing helps you
          show up when nearby buyers are already looking for your type of product or service. It is free to create a page;
          you can describe what you offer, clarify your location, and make enquiries easier without sending people through
          outdated snippets on other sites. Accurate phone numbers, addresses, and categories mean fewer missed calls and
          wrong directions—especially for walk-in retail and trades that serve a local area.
        </p>
        <p class="mb-3">
          You can also start from dedicated hubs such as our <a href="/products/">products</a> and
          <a href="/services/">services</a> sections when you want a narrower entry point before you refine by city or
          search terms.
        </p>
        <p class="mb-0">
          Whether you are planning a meal out, comparing tutors, booking home repairs, or sourcing a supplier, use the
          search and categories above, then explore live listings. Read more on our <a href="/about/">about</a> page; when
          you are ready to be discovered, use <a href="/submit-business-listing/">list your business</a> to get started.
        </p>
      </div>
    </div>
  </section>
</div>

<?php
// ── Site index accordion — built from DB category tree ─────────────────────

$siteIndexLocations = [];
try {
    if ($pdo instanceof PDO) {
        $locRows = mci_cache_remember(
            mci_cache_public_key('home:locations:v1'),
            $ttlHome,
            static function () use ($pdo): array {
                $stmt = $pdo->query(
                    "SELECT country, state, city FROM mci_locations ORDER BY country, state, city"
                );

                return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            }
        );
        foreach ($locRows as $locRow) {
            $siteIndexLocations[$locRow['country']][$locRow['state']][] = $locRow['city'];
        }
    }
} catch (Throwable $e) {
    mci_log_error('index locations index', $e);
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
