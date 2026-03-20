<?php
$pageTitle = 'Explore Your City - My City Info';
$activePage = 'home';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="/assets/css/home.css" />
<script src="/assets/js/home-city.js" defer></script>
HTML;

$recentListings = [
  ['title' => 'Property 852', 'category' => 'Real Estate', 'location' => 'Hong Kong', 'address' => '88 Queens Rd Central, Central, Hong Kong', 'slug' => 'property-852', 'image' => 'https://picsum.photos/seed/mci-re-852/800/520'],
  ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'address' => '12 Brook St, Chester CH1 3DU, United Kingdom', 'slug' => 'locker-shop-uk', 'image' => 'https://picsum.photos/seed/mci-furn-locker/800/520'],
  ['title' => 'Shelving Store', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'address' => 'Unit 4, Industrial Estate, Saltney Rd, Chester CH4 8RQ, UK', 'slug' => 'shelving-store', 'image' => 'https://picsum.photos/seed/mci-furn-shelf/800/520'],
  ['title' => 'JXF Painting Service', 'category' => 'Painter', 'location' => 'Toronto, Ontario', 'address' => '2200 Yonge St Suite 1100, Toronto, ON M4S 2C6, Canada', 'slug' => 'jxf-painting', 'image' => 'https://picsum.photos/seed/mci-paint-jxf/800/520'],
  ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'location' => 'Hunters Hill NSW', 'address' => '46 Gladesville Rd, Hunters Hill NSW 2110, Australia', 'slug' => 'hunter-hill-physio', 'image' => 'https://picsum.photos/seed/mci-health-hh/800/520'],
  ['title' => 'Famous Veg Restaurant In Bhopal', 'category' => 'Restaurant', 'location' => 'Bhopal, MP', 'address' => '45 Zone-II, Maharana Pratap Nagar, Bhopal, MP 462011, India', 'slug' => 'famous-veg-restaurant-bhopal', 'image' => 'https://picsum.photos/seed/mci-rest-bhopal/800/520'],
  ['title' => 'City Auto Care', 'category' => 'Automotive', 'location' => 'Manchester', 'address' => '101 Great Ducie St, Manchester M3 1PT, United Kingdom', 'slug' => 'city-auto-care', 'image' => 'https://picsum.photos/seed/mci-auto-mcr/800/520'],
  ['title' => 'Riverside Bakery', 'category' => 'Bakery', 'location' => 'Portland, OR', 'address' => '1842 SE Water Ave, Portland, OR 97214, USA', 'slug' => 'riverside-bakery', 'image' => 'https://picsum.photos/seed/mci-bake-pdx/800/520'],
];

$popularListings = [
  ['title' => 'Harbour View Hotel', 'category' => 'Hotels', 'location' => 'Sydney', 'address' => '61 Macquarie St, Sydney NSW 2000, Australia', 'slug' => 'harbour-view-hotel', 'image' => 'https://picsum.photos/seed/mci-pop-hotel/800/520'],
  ['title' => 'Iron & Steel Gym', 'category' => 'Gym', 'location' => 'Austin, TX', 'address' => '1200 E 6th St, Austin, TX 78702, USA', 'slug' => 'iron-steel-gym', 'image' => 'https://picsum.photos/seed/mci-pop-gym/800/520'],
  ['title' => 'Bright Spark Electric', 'category' => 'Electrician', 'location' => 'Leeds', 'address' => '9 Wellington St, Leeds LS1 4AP, United Kingdom', 'slug' => 'bright-spark-electric', 'image' => 'https://picsum.photos/seed/mci-pop-elec/800/520'],
  ['title' => 'The Corner Café', 'category' => 'Restaurant', 'location' => 'Dublin', 'address' => '18 Dame St, Dublin 2, D02 XY31, Ireland', 'slug' => 'corner-cafe-dublin', 'image' => 'https://picsum.photos/seed/mci-pop-cafe/800/520'],
  ['title' => 'Metro Dental Clinic', 'category' => 'Health', 'location' => 'Calgary', 'address' => '250 6 Ave SW #100, Calgary, AB T2P 3H7, Canada', 'slug' => 'metro-dental-calgary', 'image' => 'https://picsum.photos/seed/mci-pop-dental/800/520'],
  ['title' => 'Green Leaf Landscaping', 'category' => 'Painter', 'location' => 'Seattle, WA', 'address' => '4100 Brooklyn Ave NE, Seattle, WA 98105, USA', 'slug' => 'green-leaf-landscaping', 'image' => 'https://picsum.photos/seed/mci-pop-land/800/520'],
  ['title' => 'Vintage Motors', 'category' => 'Automotive', 'location' => 'Birmingham', 'address' => '1 Great Charles St, Birmingham B3 3JY, United Kingdom', 'slug' => 'vintage-motors', 'image' => 'https://picsum.photos/seed/mci-pop-auto/800/520'],
  ['title' => 'Sunrise Co-working', 'category' => 'Real Estate', 'location' => 'Singapore', 'address' => '1 Raffles Place, #40-02, Singapore 048616', 'slug' => 'sunrise-coworking', 'image' => 'https://picsum.photos/seed/mci-pop-cowork/800/520'],
];

$categories = [
  ['name' => 'Real Estate', 'emoji' => '🏠'],
  ['name' => 'Furniture Store', 'emoji' => '🛋️'],
  ['name' => 'Painter', 'emoji' => '🎨'],
  ['name' => 'Restaurant', 'emoji' => '🍽️'],
  ['name' => 'Health', 'emoji' => '⚕️'],
  ['name' => 'Automotive', 'emoji' => '🚗'],
  ['name' => 'Hotels', 'emoji' => '🏨'],
  ['name' => 'Gym', 'emoji' => '💪'],
  ['name' => 'Bakery', 'emoji' => '🥐'],
  ['name' => 'Electrician', 'emoji' => '⚡'],
];

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
        <h1
          class="display-5 fw-bold mb-3 lh-sm home-hero-title"
          aria-live="polite"
          aria-atomic="true"
        >
          <span class="home-hero-explore-line">
            <span class="home-hero-explore-prefix">Explore</span>
            <span id="heroCityName" class="home-hero-city-name home-hero-highlight">your city</span>
          </span>
        </h1>
        <p class="lead text-white-50 mb-4 mb-lg-5 home-hero-lead home-hero-tagline">
          Let’s uncover the best places, business and services in
          <span id="heroTaglineCity" class="text-white fw-semibold">your city</span>.
        </p>

        <div class="d-none d-lg-flex flex-wrap align-items-center home-hero-cta-row gap-3">
          <a href="/submit-business-listing/" class="btn btn-home-primary btn-home-cta-primary">List your business</a>
          <a href="/business-listing/" class="btn btn-home-ghost btn-home-cta-secondary">Browse all</a>
        </div>
      </div>

      <div class="col-12 col-lg-6 text-center text-lg-end">
        <div class="position-relative d-inline-block">
          <img
            src="https://picsum.photos/seed/mci-hero-city/640/420"
            alt=""
            class="img-fluid rounded-4 shadow-lg border border-light border-opacity-25 home-hero-image"
            loading="eager"
          />
          <div class="position-absolute bottom-0 start-0 m-2 m-md-3 px-2 px-md-3 py-2 rounded-3 small fw-semibold text-dark bg-white bg-opacity-90 shadow home-hero-badge">
            🗺️ Discover nearby
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
        <div class="fs-3 mb-2">📋</div>
        <div class="fw-bold mb-1">List or claim</div>
        <div class="text-muted small mb-0">List your business or claim an existing page.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bg-white rounded-4 border p-4 h-100 shadow-sm">
        <div class="fs-3 mb-2">🎯</div>
        <div class="fw-bold mb-1">Reach locals</div>
        <div class="text-muted small mb-0">Show up when people search your city.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bg-white rounded-4 border p-4 h-100 shadow-sm">
        <div class="fs-3 mb-2">💬</div>
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
        <a href="/category/" class="btn btn-home-outline btn-sm text-center mci-touch-target mci-touch-target--sm">See all categories →</a>
      </div>
    </div>

    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <a class="home-category-tile w-100" href="/business-listing/?category=<?= urlencode($cat['name']) ?>">
            <span class="home-category-icon" aria-hidden="true"><?= htmlspecialchars($cat['emoji']) ?></span>
            <span><?= htmlspecialchars($cat['name']) ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Recent listings -->
  <section class="mb-5 pb-lg-2">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-sm-between gap-3 mb-4">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">Recent listings</h2>
        <p class="text-muted small mb-0">Newly added businesses—name, category, and full address at a glance</p>
      </div>
      <a class="btn btn-home-primary btn-sm text-center mci-touch-target mci-touch-target--sm" href="/submit-business-listing/">+ List your business</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($recentListings as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Popular in [city] -->
  <section class="mb-2">
    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end justify-content-sm-between gap-3 mb-4">
      <div class="flex-grow-1 min-w-0">
        <div class="home-section-accent mb-2"></div>
        <h2 class="home-section-title h3 mb-1">
          Popular in
          <span id="homePopularCity" class="home-popular-city-name">your city</span>
        </h2>
        <p class="text-muted small mb-0">Highly viewed listings near you—updated when we detect your area</p>
      </div>
      <a href="/business-listing/" class="btn btn-home-outline btn-sm align-self-stretch align-self-sm-auto text-center mci-touch-target mci-touch-target--sm">See all in directory →</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($popularListings as $listing): ?>
        <?php $variant = 'home'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>
