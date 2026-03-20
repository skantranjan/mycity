<?php
// Reusable listing card.
// Expected:
// - $listing associative array: ['title','category','location','image','slug']
//   Optional: 'address' (full street address; falls back to 'location')
// Optional:
// - $size: 'sm'|'md'
// - $variant: null|'home' — 4-column grid on large screens; stacked: name, category, address

$listing = $listing ?? [];
$size = $size ?? 'md';
$variant = $variant ?? null;
$isHome = $variant === 'home';
$isCompact = $size === 'compact';

$title = (string)($listing['title'] ?? 'Untitled');
$category = (string)($listing['category'] ?? '');
$location = (string)($listing['location'] ?? '');
$address = (string)($listing['address'] ?? '');
if ($address === '') {
  $address = $location;
}
$slug = (string)($listing['slug'] ?? '');

$image = $listing['image'] ?? '';
if (!$image) {
  $image = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="640" height="360" fill="#e2e8f0"/><text x="50%" y="50%" font-size="18" text-anchor="middle" fill="#64748b" dy=".35em">Photo</text></svg>'
  );
}

if ($isHome) {
  $cardClass = 'col-12 col-sm-6 col-lg-3';
} elseif ($isCompact) {
  $cardClass = '';
} else {
  $cardClass = $size === 'sm' ? 'col-12 col-md-6 col-lg-4' : 'col-12 col-md-6 col-lg-4';
}

$cardExtraClass = $isHome ? ' mci-listing-card--home' : '';
$cardExtraClass .= $isCompact ? ' mci-listing-card--compact' : '';
?>
<?php if (!$isCompact): ?>
<div class="<?= $cardClass ?>">
<?php endif; ?>
  <a href="/business/?slug=<?= urlencode($slug) ?>" class="text-decoration-none text-body d-block h-100">
    <div class="card h-100 border-0 shadow-sm mci-listing-card<?= $cardExtraClass ?>">
      <div class="card-img-wrap">
        <img
          src="<?= htmlspecialchars($image) ?>"
          class="card-img-top"
          alt="<?= htmlspecialchars($title) ?>"
          loading="lazy"
        />
      </div>
      <div class="card-body">
        <?php if ($isHome): ?>
          <div class="mci-home-card-meta">
            <div class="mci-home-card-name text-dark"><?= htmlspecialchars($title) ?></div>
            <?php if ($category !== ''): ?>
              <div class="mci-home-card-category text-muted small"><?= htmlspecialchars($category) ?></div>
            <?php endif; ?>
            <?php if ($address !== ''): ?>
              <div class="mci-home-card-address text-muted small d-flex align-items-start gap-1 mt-1">
                <span class="mci-home-card-address-icon flex-shrink-0" aria-hidden="true">📍</span>
                <span><?= htmlspecialchars($address) ?></span>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="d-flex align-items-start justify-content-between gap-2">
            <div class="pe-2">
              <?php if ($category !== ''): ?>
                <span class="category-pill d-inline-block mb-2"><?= htmlspecialchars($category) ?></span>
              <?php endif; ?>
              <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($title) ?></div>
              <?php if ($location !== ''): ?>
                <div class="text-muted small mt-1 d-flex align-items-start gap-1">
                  <span aria-hidden="true">📍</span>
                  <span><?= htmlspecialchars($location) ?></span>
                </div>
              <?php endif; ?>
              <?php
              $distKm = $listing['distance_km'] ?? null;
              if ($distKm !== null && $distKm !== ''):
              ?>
                <div class="small fw-bold mt-2 mb-0 mci-listing-card__distance">
                  ~<?= htmlspecialchars(number_format((float) $distKm, 1)) ?> km away
                </div>
              <?php endif; ?>
            </div>
            <span class="btn-view-pill flex-shrink-0 align-self-start">View</span>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </a>
<?php if (!$isCompact): ?>
</div>
<?php endif; ?>
