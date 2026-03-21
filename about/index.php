<?php

declare(strict_types=1);

$pageTitle = 'About Us - My City Info';
$activePage = 'about';
$metaDescription = 'Learn about My City Info — the local discovery platform helping people find trusted businesses, services, and hidden gems in their city.';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white mb-4">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-1">About My City Info</h1>
    <p class="text-muted mb-4">
      My City Info is your local discovery platform — helping people find trusted businesses, services, and hidden gems right in their city. We believe great local businesses deserve to be found, and every neighbourhood has something worth celebrating.
    </p>

    <!-- Stats strip -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold"  style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);">1,200+</div>
          <div class="text-muted small mt-1">Listings</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold"  style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);">40+</div>
          <div class="text-muted small mt-1">Categories</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold"  style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);">8</div>
          <div class="text-muted small mt-1">Cities</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="text-center p-3 rounded-3" style="background:var(--mci-color-primary-soft);">
          <div class="fw-bold"  style="font-size:var(--mci-text-xl);color:var(--mci-color-primary-deep);">Free</div>
          <div class="text-muted small mt-1">To list</div>
        </div>
      </div>
    </div>

    <!-- Mission + Story -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2"><i class="bi bi-bullseye me-2 text-primary" aria-hidden="true"></i>Our mission</div>
          <div class="text-muted small">
            Make it effortless to discover, connect with, and support local businesses — whether you're looking for a dentist, a cosy café, or a reliable plumber. We put trust and community at the heart of every listing.
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="bg-light border rounded-3 p-3 h-100">
          <div class="fw-semibold mb-2"><i class="bi bi-journal-text me-2 text-primary" aria-hidden="true"></i>Our story</div>
          <div class="text-muted small">
            Started in 2024 as a simple directory, My City Info grew as we saw how hard it was for local businesses to be found online. We built a platform that gives every neighbourhood business a fair shot — no paid rankings, no algorithms burying small operators.
          </div>
        </div>
      </div>
    </div>

    <!-- How listings work -->
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
            <div class="fw-semibold small mb-1">Claim & grow</div>
            <p class="text-muted small mb-0">Business owners can claim their listing, update details, respond to reviews, and receive direct enquiries.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Meet the team -->
    <div class="fw-semibold mb-3">Meet the team</div>
    <div class="row g-3">
      <div class="col-12 col-sm-4">
        <div class="text-center p-3 border rounded-3 h-100">
          <img src="https://picsum.photos/seed/team-ravi/80/80" alt="" class="rounded-circle mb-2" width="64" height="64" style="object-fit:cover;" />
          <div class="fw-semibold small">Ravi S.</div>
          <div class="text-muted small">Founder &amp; Product</div>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="text-center p-3 border rounded-3 h-100">
          <img src="https://picsum.photos/seed/team-priya/80/80" alt="" class="rounded-circle mb-2" width="64" height="64" style="object-fit:cover;" />
          <div class="fw-semibold small">Priya M.</div>
          <div class="text-muted small">City Operations</div>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="text-center p-3 border rounded-3 h-100">
          <img src="https://picsum.photos/seed/team-alex/80/80" alt="" class="rounded-circle mb-2" width="64" height="64" style="object-fit:cover;" />
          <div class="fw-semibold small">Alex T.</div>
          <div class="text-muted small">Engineering</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
