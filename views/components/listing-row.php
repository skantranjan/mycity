<?php
// Horizontal listing row (list view).
// Expected: $listing with title, category, location, slug, optional image

$listing = $listing ?? [];

$title = (string)($listing['title'] ?? 'Untitled');
$category = (string)($listing['category'] ?? '');
$location = (string)($listing['location'] ?? '');
$slug = (string)($listing['slug'] ?? '');

$image = $listing['image'] ?? '';
if (!$image) {
  $image = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="640" height="360" fill="#e2e8f0"/><text x="50%" y="50%" font-size="18" text-anchor="middle" fill="#64748b" dy=".35em">Photo</text></svg>'
  );
}
?>
<div class="col-12">
  <a href="/business.php?slug=<?= urlencode($slug) ?>" class="text-decoration-none text-body d-block mci-listing-row-link">
    <div class="card border-0 shadow-sm mci-listing-row-card h-100 overflow-hidden">
      <div class="row g-0 align-items-stretch">
        <div class="col-4 col-sm-3 col-md-3 col-lg-4">
          <div class="mci-listing-row-img-wrap h-100">
            <img
              src="<?= htmlspecialchars($image) ?>"
              class="mci-listing-row-img"
              alt="<?= htmlspecialchars($title) ?>"
              loading="lazy"
            />
          </div>
        </div>
        <div class="col-8 col-sm-9 col-md-9 col-lg-8">
          <div class="card-body py-3 px-3 d-flex flex-column h-100 justify-content-center">
            <div class="d-flex align-items-start justify-content-between gap-2">
              <div class="min-w-0 pe-2">
                <?php if ($category !== ''): ?>
                  <span class="category-pill d-inline-block mb-2"><?= htmlspecialchars($category) ?></span>
                <?php endif; ?>
                <div class="fw-bold text-dark mci-listing-row-title"><?= htmlspecialchars($title) ?></div>
                <?php if ($location !== ''): ?>
                  <div class="text-muted small mt-1 d-flex align-items-start gap-1">
                    <span aria-hidden="true">📍</span>
                    <span><?= htmlspecialchars($location) ?></span>
                  </div>
                <?php endif; ?>
              </div>
              <span class="btn btn-sm btn-dark rounded-pill flex-shrink-0 align-self-center d-none d-sm-inline-block">View</span>
            </div>
            <div class="d-sm-none mt-2">
              <span class="btn btn-sm btn-outline-dark rounded-pill w-100">View details</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </a>
</div>
