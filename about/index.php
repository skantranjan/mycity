<?php

declare(strict_types=1);

$pageTitle = 'About Us - My City Info';
$activePage = 'about';
$metaDescription = 'My City Info is India\'s community-first local business directory - helping people discover trusted businesses, services, and neighbourhood gems in their city.';

require_once __DIR__ . '/../api/v1/lib/db.php';

// Live stats
$statListings   = '1,200+';
$statCategories = '40+';
$statCities     = '8';
try {
    $pdo = api_db();

    $r = $pdo->query("SELECT COUNT(*) FROM mci_business_groups WHERE status = 'live'");
    $n = (int)$r->fetchColumn();
    $statListings = number_format((int)(floor($n / 100) * 100)) . '+';

    $r = $pdo->query("SELECT COUNT(*) FROM mci_categories WHERE parent_id IS NULL");
    $statCategories = (int)$r->fetchColumn() . '+';

    $r = $pdo->query("SELECT COUNT(DISTINCT LOWER(city)) FROM mci_business_branches WHERE status = 'active'");
    $statCities = (string)(int)$r->fetchColumn();
} catch (Throwable) {}

ob_start();
?>

<div class="card border-0 shadow-sm bg-white mb-4">
  <div class="card-body p-4">

    <h1 class="h4 fw-bold mb-1">About My City Info</h1>
    <p class="text-muted mb-4">
      My City Info is a community-first local discovery platform built for India. We make it easy for anyone to find trusted businesses, services, products, and neighbourhood gems - right in their own city. No paid rankings. No buried small operators. Just honest, community-verified listings.
    </p>

    <!-- Stats strip -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold" style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);"><?= htmlspecialchars($statListings) ?></div>
          <div class="text-muted small mt-1">Listings</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold" style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);"><?= htmlspecialchars($statCategories) ?></div>
          <div class="text-muted small mt-1">Categories</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold" style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);"><?= htmlspecialchars($statCities) ?></div>
          <div class="text-muted small mt-1">Cities</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold" style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);">Free</div>
          <div class="text-muted small mt-1">To list</div>
        </div>
      </div>
    </div>

    <!-- What we believe -->
    <div class="row g-3 mb-4">
      <div class="col-12">
        <div class="p-3 rounded-3 border" style="background:var(--mci-color-primary-soft);">
          <div class="fw-semibold mb-2" style="color:var(--mci-color-primary-deep);">
            <i class="bi bi-heart me-2" aria-hidden="true"></i>What we believe
          </div>
          <div class="text-muted small" style="line-height:1.7;">
            Every city is full of remarkable local businesses - talented craftspeople, great restaurants, reliable service providers - that never get the visibility they deserve. We believe the internet should work for them, not against them. My City Info exists to level the playing field: a free, moderated, community-powered directory where quality and trust matter more than advertising budgets.
          </div>
        </div>
      </div>
    </div>

    <!-- Mission + Story -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2"><i class="bi bi-bullseye me-2 text-primary" aria-hidden="true"></i>Our mission</div>
          <div class="text-muted small" style="line-height:1.7;">
            Make it effortless to discover, connect with, and support local businesses - whether you're looking for a dentist, a cosy cafe, or a reliable plumber. We put trust and community at the heart of every listing.
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2"><i class="bi bi-journal-text me-2 text-primary" aria-hidden="true"></i>Our story</div>
          <div class="text-muted small" style="line-height:1.7;">
            Started in 2024 as a simple directory, My City Info grew as we saw how hard it was for local businesses to be found online. We built a platform that gives every neighbourhood business a fair shot - no paid rankings, no algorithms burying small operators.
          </div>
        </div>
      </div>
    </div>

    <!-- How it works -->
    <div class="fw-semibold mb-3">How listings work</div>
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div style="font-size:var(--mci-text-xl);" aria-hidden="true">📋</div>
          <div>
            <div class="fw-semibold small mb-1">Submit</div>
            <p class="text-muted small mb-0">Anyone can submit a business for free. Anonymous submissions go through moderation before going live.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div style="font-size:var(--mci-text-xl);" aria-hidden="true">✅</div>
          <div>
            <div class="fw-semibold small mb-1">Verify</div>
            <p class="text-muted small mb-0">Our moderation team reviews each submission to ensure quality and accuracy before publication.</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="d-flex align-items-start gap-3 p-3 bg-light rounded-3 h-100">
          <div style="font-size:var(--mci-text-xl);" aria-hidden="true">🏆</div>
          <div>
            <div class="fw-semibold small mb-1">Claim &amp; grow</div>
            <p class="text-muted small mb-0">Business owners can claim their listing, update details, respond to reviews, and receive direct enquiries.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact nudge -->
    <div class="border-top pt-4 mt-2">
      <div class="text-muted small">
        Have a question or want to get in touch?
        <a href="/contact/" class="text-decoration-none fw-semibold">Contact us</a> - we'd love to hear from you.
      </div>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
