<?php

declare(strict_types=1);

$pageTitle = 'Disclaimer - My City Info';
$activePage = '';
$metaDescription = 'Read the My City Info Disclaimer - information about listing accuracy, advertising disclosures, affiliate links, and limitations of our service.';

ob_start();
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-4">

    <h1 class="h4 fw-bold mb-1">Disclaimer</h1>
    <p class="text-muted small mb-4">Last updated: 30 March 2026</p>

    <p class="text-muted small mb-4" style="line-height:1.7;">
      Please read this Disclaimer carefully before using My City Info ("we", "us", "our") at <strong>mycityinfo.com</strong>. By accessing or using the Service, you acknowledge that you have read and understood this Disclaimer. Nothing on this website constitutes professional, legal, medical, financial, or investment advice.
    </p>

    <!-- 1 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">General information only</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      My City Info is a local business discovery platform. The information published on this site - including business names, addresses, phone numbers, opening hours, products, services, and descriptions - is provided for general informational and discovery purposes only. It does not constitute advice of any kind. You should independently verify any information before relying on it for any purpose, including before visiting, purchasing from, or engaging with any listed business.
    </p>

    <!-- 2 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Listing accuracy</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      Listings on My City Info are submitted by business owners, registered subscribers, or community members. While we moderate submissions before they go live and make reasonable efforts to ensure quality, we cannot guarantee the accuracy, completeness, timeliness, or reliability of any listing or associated content. Business details - including addresses, phone numbers, pricing, and operating hours - may change without notice and may not be reflected immediately on this site.
    </p>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      My City Info is not responsible for any errors or omissions in listing content, or for any loss, damage, or inconvenience caused by reliance on information found on this site. We strongly recommend contacting the business directly to confirm details before making any plans or purchases.
    </p>

    <!-- 3 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">No professional advice</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      Nothing on this website should be construed as legal, medical, financial, tax, or any other type of professional advice. If you require professional guidance, please consult a qualified professional in the relevant field. My City Info does not endorse, recommend, or take responsibility for any specific business, product, or service listed on the platform.
    </p>

    <!-- 4 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Advertising &amp; affiliate disclosure</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      My City Info may display advertisements through <strong>Google AdSense</strong> and may include <strong>affiliate or promotional links</strong> within listing pages, blog content, or other areas of the site. This means:
    </p>
    <ul class="text-muted small mb-3" style="line-height:1.7;">
      <li class="mb-1">We may earn a commission or referral fee if you click on an affiliate link and make a purchase or take a qualifying action. This is at <strong>no additional cost to you</strong>.</li>
      <li class="mb-1">Google AdSense may serve personalised ads based on your browsing history. We earn revenue from ad impressions and clicks.</li>
      <li class="mb-1">Advertising relationships and affiliate arrangements do <strong>not</strong> influence which businesses are listed in our directory, how listings are ranked, or the accuracy of listing content.</li>
      <li class="mb-1">Sponsored or promoted content, where it appears, will be clearly identified.</li>
    </ul>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      We only feature affiliate products or promotions that we believe are relevant and useful to our audience. Our primary obligation is to our users, not to advertisers.
    </p>

    <!-- 5 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">External links</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      My City Info may contain links to external websites or resources. These links are provided for convenience only. We have no control over the content, policies, or practices of external sites, and linking to a site does not constitute endorsement, recommendation, or approval of its content. We are not responsible for any loss or damage arising from your use of any external site.
    </p>

    <!-- 6 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">18+ business listings</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      The directory may include listings for businesses that operate in industries intended for adults, such as liquor retailers, tobacco shops, or adult entertainment venues. These listings are individually marked with an age-advisory notice. My City Info is not responsible for the nature, legality, quality, or conduct of any listed business, including those operating in age-restricted industries. Users are responsible for their own decisions regarding which businesses they choose to engage with.
    </p>

    <!-- 7 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Service availability</h2>
    <p class="text-muted small mb-3" style="line-height:1.7;">
      We make no guarantee that the Service will be available at all times, error-free, or uninterrupted. We reserve the right to modify, suspend, or discontinue any part of the Service at any time without notice. We shall not be liable for any inconvenience, loss, or damage arising from service interruptions or unavailability.
    </p>

    <!-- 8 -->
    <h2 class="h5 fw-semibold mt-4 mb-2">Contact us</h2>
    <div class="bg-light border rounded-3 p-3">
      <p class="text-muted small mb-1" style="line-height:1.7;">If you have a question about this Disclaimer or wish to report inaccurate listing content, please contact us:</p>
      <p class="small mb-0"><strong>My City Info</strong><br>
      Email: <a href="mailto:hello@mycityinfo.com" class="text-decoration-none fw-semibold">hello@mycityinfo.com</a></p>
    </div>

  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
