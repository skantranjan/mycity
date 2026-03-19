<?php
$pageTitle = 'Listings - My City Info';
$activePage = 'listings';

$what = trim((string)($_GET['what'] ?? ''));
$where = trim((string)($_GET['where'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$priceRange = trim((string)($_GET['price_range'] ?? ''));

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

$allListings = [
  ['title' => 'Property 852', 'category' => 'Real Estate', 'location' => 'Hong Kong', 'slug' => 'property-852'],
  ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'slug' => 'locker-shop-uk'],
  ['title' => 'JXF Painting Service', 'category' => 'Painter', 'location' => 'Toronto, Ontario', 'slug' => 'jxf-painting'],
  ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'location' => 'Hunters Hill NSW', 'slug' => 'hunter-hill-physio'],
  ['title' => 'Famous Veg Restaurant In Bhopal | Naveen', 'category' => 'Restaurant', 'location' => 'Bhopal, MP', 'slug' => 'famous-veg-restaurant-bhopal'],
  ['title' => 'Chester Gym & Fitness', 'category' => 'Gym', 'location' => 'Chester, UK', 'slug' => 'chester-gym'],
  ['title' => 'Spark Electricals', 'category' => 'Electrician', 'location' => 'Bangalore', 'slug' => 'spark-electricals'],
  ['title' => 'Sunrise Hotel Rooms', 'category' => 'Hotels', 'location' => 'Patna', 'slug' => 'sunrise-hotel-rooms'],
  ['title' => 'City Park Walks', 'category' => 'Park', 'location' => 'Guwahati', 'slug' => 'city-park-walks'],
  ['title' => 'Cafe Aroma', 'category' => 'Cafe', 'location' => 'Delhi', 'slug' => 'cafe-aroma'],
  ['title' => 'QuickCare Dentist', 'category' => 'Dentist', 'location' => 'Mumbai', 'slug' => 'quickcare-dentist'],
  ['title' => 'Urban Spa House', 'category' => 'Spa', 'location' => 'Jaipur', 'slug' => 'urban-spa-house'],
];

// UI-only filtering (placeholder).
$filteredListings = array_filter($allListings, function ($l) use ($what, $where, $category, $priceRange) {
  $ok = true;
  if ($category !== '') $ok = $ok && ($l['category'] === $category);
  if ($what !== '') $ok = $ok && (stripos($l['title'], $what) !== false || stripos($l['category'], $what) !== false);
  if ($where !== '') $ok = $ok && stripos($l['location'], $where) !== false;
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
            <?php if ($category !== '' || $where !== ''): ?> | <?php endif; ?>
            Search: <span class="text-dark fw-semibold"><?= htmlspecialchars($what) ?></span>
          <?php endif; ?>
          <?php if ($category === '' && $where === '' && $what === ''): ?>
            Browse businesses by category or city.
          <?php endif; ?>
        </div>
      </div>
      <a class="btn btn-sm btn-dark" href="/submit-listing.php">Submit listing</a>
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
        <div class="text-muted small"><?= count($filteredListings) ?> results (demo)</div>
      </div>

      <div class="row g-3">
        <?php foreach ($filteredListings as $listing): ?>
          <?php include __DIR__ . '/views/components/listing-card.php'; ?>
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

