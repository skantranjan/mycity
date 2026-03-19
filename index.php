<?php
$pageTitle = 'Explore Your City - My City Info';
$activePage = 'home';

$recentListings = [
  ['title' => 'Property 852', 'category' => 'Real Estate', 'location' => 'Hong Kong', 'slug' => 'property-852'],
  ['title' => 'Locker Shop UK Ltd', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'slug' => 'locker-shop-uk'],
  ['title' => 'Shelving Store', 'category' => 'Furniture Store', 'location' => 'Chester, UK', 'slug' => 'shelving-store'],
  ['title' => 'JXF Painting Service', 'category' => 'Painter', 'location' => 'Toronto, Ontario', 'slug' => 'jxf-painting'],
  ['title' => 'Hunter Hill Physiotherapy', 'category' => 'Health', 'location' => 'Hunters Hill NSW', 'slug' => 'hunter-hill-physio'],
  ['title' => 'Famous Veg Restaurant In Bhopal', 'category' => 'Restaurant', 'location' => 'Bhopal, MP', 'slug' => 'famous-veg-restaurant-bhopal'],
];

ob_start();
?>

<div class="py-2">
  <section class="bg-white border rounded-4 shadow-sm p-4">
    <div class="row g-4 align-items-center">
      <div class="col-12 col-lg-5">
        <h1 class="h2 fw-bold mb-2">Explore Your City</h1>
        <p class="text-muted mb-0">Let's uncover the best places, business and services in your city.</p>
      </div>
      <div class="col-12 col-lg-7">
        <form class="row g-2" action="/listings.php" method="get">
          <div class="col-12 col-md-5">
            <label class="form-label">What</label>
            <input class="form-control" type="text" name="what" placeholder="e.g. Electrician, Bakery" />
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">Where</label>
            <input class="form-control" type="text" name="where" placeholder="e.g. Patna, Bangalore" />
          </div>
          <div class="col-12 col-md-2 d-flex align-items-end">
            <button class="btn btn-dark w-100" type="submit">Search</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <section class="mt-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="fw-semibold">Browse categories</div>
        <div class="text-muted small">Popular categories for quick discovery</div>
      </div>
      <a href="/listings.php" class="text-decoration-none small">See all</a>
    </div>

    <div class="row g-2">
      <?php
      $categories = [
        'Real Estate',
        'Furniture Store',
        'Painter',
        'Restaurant',
        'Health',
        'Automotive',
        'Hotels',
        'Gym',
        'Bakery',
        'Electrician',
      ];
      foreach ($categories as $c):
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
          <a class="btn btn-light w-100 text-start border" href="/listings.php?category=<?= urlencode($c) ?>">
            <?= htmlspecialchars($c) ?>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="mt-5">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="fw-semibold">Recent listings</div>
        <div class="text-muted small">New and recently updated businesses</div>
      </div>
      <a class="btn btn-sm btn-outline-dark" href="/submit-listing.php">Add your listing</a>
    </div>

    <div class="row g-3">
      <?php foreach ($recentListings as $listing): ?>
        <?php $size = 'md'; include __DIR__ . '/views/components/listing-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/views/layout.php';
?>

