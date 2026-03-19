<?php
// Reusable listing card.
// Expected:
// - $listing associative array: ['title','category','location','image','slug']
// Optional:
// - $size: 'sm'|'md'

$listing = $listing ?? [];
$size = $size ?? 'md';

$title = (string)($listing['title'] ?? 'Untitled');
$category = (string)($listing['category'] ?? '');
$location = (string)($listing['location'] ?? '');
$slug = (string)($listing['slug'] ?? '');

$image = $listing['image'] ?? '';
if (!$image) {
  // Simple inline placeholder (avoids adding static assets yet).
  $image = 'data:image/svg+xml;charset=utf-8,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360"><rect width="640" height="360" fill="#f3f4f6"/><text x="50%" y="50%" font-size="20" text-anchor="middle" fill="#6b7280" dy=".35em">MyCityInfo</text></svg>'
  );
}

$cardClass = $size === 'sm' ? 'col-12 col-md-6 col-lg-4' : 'col-12 col-md-6 col-lg-4';
?>
<div class="<?= $cardClass ?>">
  <div class="card h-100 border-0 shadow-sm hover:shadow-md" style="background: #fff;">
    <a href="/business.php?slug=<?= urlencode($slug) ?>" class="text-decoration-none text-body">
      <img
        src="<?= htmlspecialchars($image) ?>"
        class="card-img-top"
        alt="<?= htmlspecialchars($title) ?>"
        style="height: 160px; object-fit: cover;"
      />
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-2">
          <div>
            <div class="text-uppercase small text-muted"><?= htmlspecialchars($category) ?></div>
            <div class="fw-semibold mt-1"><?= htmlspecialchars($title) ?></div>
            <?php if ($location !== ''): ?>
              <div class="text-muted small mt-1"><?= htmlspecialchars($location) ?></div>
            <?php endif; ?>
          </div>
          <div class="text-muted small">View</div>
        </div>
      </div>
    </a>
  </div>
</div>

