<?php
// Shared header/nav.
// Expects $activePage (optional).
$activePage = $activePage ?? '';

function navActive(string $key, string $activePage): string {
  return $key === $activePage ? 'active' : '';
}
?>
<header class="bg-white border-b position-sticky top-0 z-50">
  <div class="container py-2 d-flex align-items-center justify-content-between gap-3">
    <a href="/index.php" class="text-decoration-none fw-bold fs-4 text-gray-900">
      My City Info
    </a>

    <nav class="nav nav-pills gap-2">
      <a class="nav-link <?= navActive('home', $activePage) ?>" href="/index.php">Home</a>
      <a class="nav-link <?= navActive('submit', $activePage) ?>" href="/submit-listing.php">Submit</a>
      <a class="nav-link <?= navActive('listings', $activePage) ?>" href="/listings.php">Listings</a>
      <a class="nav-link <?= navActive('about', $activePage) ?>" href="/about-us.php">About</a>
      <a class="nav-link <?= navActive('contact', $activePage) ?>" href="/contact.php">Contact</a>
    </nav>

    <div class="d-flex align-items-center gap-2">
      <a class="btn btn-outline-dark btn-sm" href="/login.php">Login</a>
    </div>
  </div>
</header>

