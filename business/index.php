<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_reviews.php';

$pageTitle = 'Business Details - My City Info';
$activePage = 'listings';

$slug = trim((string) ($_GET['slug'] ?? ''));

$isLoggedIn = !empty($_SESSION['mci_logged_in']) && !empty($_SESSION['mci_user_id']);
$userId = (string) ($_SESSION['mci_user_id'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mci_review_submit'])) {
    $targetSlug = trim((string) ($_POST['business_slug'] ?? ''));
    if ($targetSlug === '') {
        $targetSlug = $slug;
    }
    if ($targetSlug === '') {
        header('Location: /business-listing/');
        exit;
    }
    if (!$isLoggedIn) {
        header('Location: /login/?return=' . rawurlencode('/business/?slug=' . $targetSlug . '#reviews'));
        exit;
    }
    $rating = (int) ($_POST['rating'] ?? 0);
    $text = trim((string) ($_POST['review_text'] ?? ''));
    $result = mci_reviews_add($targetSlug, $rating, $text, $userId);
    if ($result['ok']) {
        header('Location: /business/?slug=' . rawurlencode($targetSlug) . '&review_ok=1#reviews');
    } else {
        $err = $result['error'] ?? 'Something went wrong.';
        header('Location: /business/?slug=' . rawurlencode($targetSlug) . '&review_err=' . rawurlencode($err) . '#reviews');
    }
    exit;
}

$reviewFlashOk = isset($_GET['review_ok']);
$reviewFlashErr = trim((string) ($_GET['review_err'] ?? ''));

/**
 * OpenStreetMap embed (no API key). $lat, $lon in WGS84.
 */
function mci_osm_embed_url(float $lat, float $lon): string
{
  $dlon = 0.02;
  $dlat = 0.014;
  $minLon = $lon - $dlon;
  $minLat = $lat - $dlat;
  $maxLon = $lon + $dlon;
  $maxLat = $lat + $dlat;
  return sprintf(
    'https://www.openstreetmap.org/export/embed.html?bbox=%F%%2C%F%%2C%F%%2C%F&layer=mapnik&marker=%F%%2C%F',
    $minLon,
    $minLat,
    $maxLon,
    $maxLat,
    $lat,
    $lon
  );
}

$catalog = [
  'property-852' => [
    'title' => 'Property 852',
    'category' => 'Real Estate',
    'tagline' => 'Premium commercial & residential spaces in Hong Kong.',
    'location' => 'Hong Kong',
    'address' => 'Units 1205–1208, Level 12, Cyberport 2, 100 Cyberport Road, Hong Kong',
    'phone' => '+852 2123 4567',
    'whatsapp' => '+852 9123 4567',
    'website' => 'https://example.com/property852',
    'image' => 'https://picsum.photos/seed/mci-dir-852/800/520',
    'map_lat' => 22.2623,
    'map_lon' => 114.0126,
    'about' => [
      'Property 852 helps businesses and families find the right space in Hong Kong—from waterfront offices to efficient studio layouts. Our team offers guided viewings, lease negotiation support, and after-move concierge introductions.',
      'Whether you are scaling a startup or relocating a branch, we combine market data with local insight so you can decide quickly and confidently.',
    ],
    'services' => ['Office leasing', 'Residential sales', 'Investment advisory', 'Virtual tours'],
    'tags' => ['Office space', 'Leasing', 'Hong Kong', 'Waterfront', 'Commercial'],
    'gallery_seeds' => ['mci-gal-852-a', 'mci-gal-852-b', 'mci-gal-852-c', 'mci-gal-852-d'],
    'faqs' => [
      ['q' => 'How do I schedule a viewing?', 'a' => 'Call or WhatsApp us with your preferred times. We usually confirm within one business day and can arrange after-hours slots for executive schedules (demo copy).'],
      ['q' => 'What lease terms are typical?', 'a' => 'Terms vary by building and landlord. We walk you through minimum commitment, fit-out contributions, and renewal options before you sign anything.'],
      ['q' => 'Do you help with corporate relocations?', 'a' => 'Yes—we coordinate shortlists, comparisons, and handover checklists so your team can move in with fewer surprises.'],
    ],
  ],
  'locker-shop-uk' => [
    'title' => 'Locker Shop UK Ltd',
    'category' => 'Furniture Store',
    'tagline' => 'Storage, shelving & locker solutions for home and workplace.',
    'location' => 'Chester, UK',
    'address' => 'Regus – Chester Business Park, Heronsway, Chester CH4 9QR, United Kingdom',
    'phone' => '+44 1244 000000',
    'whatsapp' => '+44 7700 900000',
    'website' => 'https://example.com/locker-shop',
    'image' => 'https://picsum.photos/seed/mci-dir-locker/800/520',
    'map_lat' => 53.1934,
    'map_lon' => -2.8931,
    'about' => [
      'Locker Shop UK Ltd supplies durable lockers, industrial shelving, and bespoke storage for schools, gyms, and warehouses across the North West.',
      'Visit our Chester showroom for samples, or book a site survey—we measure, recommend, and install with minimal disruption to your operations.',
    ],
    'services' => ['Steel lockers', 'Boltless shelving', 'Workplace fit-outs', 'Delivery & install'],
    'tags' => ['Lockers', 'Shelving', 'Storage', 'Chester', 'B2B'],
    'gallery_seeds' => ['mci-gal-lock-1', 'mci-gal-lock-2', 'mci-gal-lock-3'],
    'faqs' => [
      ['q' => 'Do you deliver outside Chester?', 'a' => 'We deliver across the UK for many product lines. Large or bespoke orders may use a scheduled pallet service—ask for a quote with your postcode.'],
      ['q' => 'Can lockers be customised?', 'a' => 'Yes—colours, locking systems, and compartment layouts can often be tailored. Lead times depend on the manufacturer (sample answer).'],
      ['q' => 'What warranty applies?', 'a' => 'Warranty varies by product family. We provide written warranty terms with every quote so you know what is covered.'],
    ],
  ],
  'jxf-painting' => [
    'title' => 'JXF Painting Service',
    'category' => 'Painter',
    'tagline' => 'Interior & exterior painting with clean lines and clear timelines.',
    'location' => 'Toronto, Ontario',
    'address' => '33 Bond Ave, North York, Toronto, ON M3B 3R3, Canada',
    'phone' => '+1 416-555-0142',
    'whatsapp' => '+1 647-555-0199',
    'website' => 'https://example.com/jxf-painting',
    'image' => 'https://picsum.photos/seed/mci-dir-jxf/800/520',
    'map_lat' => 43.6532,
    'map_lon' => -79.3832,
    'about' => [
      'JXF Painting Service has refreshed hundreds of condos, retail units, and family homes across the GTA. We use low-VOC paints where possible and protect floors and furniture before a single brush touches the wall.',
      'Every project includes a written scope, colour consult (optional), and a walkthrough on completion so you know exactly what was delivered.',
    ],
    'services' => ['Interior painting', 'Cabinet refinishing', 'Commercial repaint', 'Colour matching'],
    'tags' => ['Painting', 'Interior', 'Toronto', 'Condo', 'Low-VOC'],
    'gallery_seeds' => ['mci-gal-jxf-1', 'mci-gal-jxf-2', 'mci-gal-jxf-3', 'mci-gal-jxf-4'],
    'faqs' => [
      ['q' => 'How long does a typical condo repaint take?', 'a' => 'Most 2-bedroom units take 3–5 working days depending on prep, ceilings, and colour changes. We give a written schedule before we start.'],
      ['q' => 'Are your paints low-odour?', 'a' => 'We offer low-VOC and low-odour options suitable for families and pets. We can recommend finishes for kitchens and bathrooms too.'],
      ['q' => 'Do you move furniture?', 'a' => 'We shift and cover furniture as part of scope. Heavy items may need your help or a third party—flag anything fragile upfront.'],
    ],
  ],
  'hunter-hill-physio' => [
    'title' => 'Hunter Hill Physiotherapy',
    'category' => 'Health',
    'tagline' => 'Hands-on physio for sports, posture, and recovery.',
    'location' => 'Hunters Hill NSW',
    'address' => '46 Gladesville Rd, Hunters Hill NSW 2110, Australia',
    'phone' => '+61 2 9817 0000',
    'whatsapp' => '+61 400 000 000',
    'website' => 'https://example.com/hunter-hill-physio',
    'image' => 'https://picsum.photos/seed/mci-dir-hh/800/520',
    'map_lat' => -33.8368,
    'map_lon' => 151.1473,
    'about' => [
      'Our clinicians combine manual therapy, exercise prescription, and education so you understand your recovery—not just follow a generic sheet of stretches.',
      'Evening appointments and HICAPS are available (demo copy). New patients receive a 45-minute initial assessment with a clear treatment plan.',
    ],
    'services' => ['Sports injuries', 'Post-op rehab', 'Neck & back pain', 'Dry needling'],
    'tags' => ['Physiotherapy', 'Sports injury', 'Sydney', 'Rehab', 'Health'],
    'gallery_seeds' => ['mci-gal-hh-1', 'mci-gal-hh-2'],
    'faqs' => [
      ['q' => 'Do I need a referral to book?', 'a' => 'No referral is required for private appointments (demo). If you are using insurance, bring your insurer’s requirements and we’ll advise.'],
      ['q' => 'How long is the first visit?', 'a' => 'New patients typically get a 45-minute assessment: history, movement tests, and a first-step treatment or exercise plan.'],
      ['q' => 'Is parking available?', 'a' => 'Street parking is available nearby; paid options exist within a short walk. Check the map on this page for directions.'],
    ],
  ],
  'famous-veg-restaurant-bhopal' => [
    'title' => 'Famous Veg Restaurant In Bhopal | Naveen',
    'category' => 'Restaurant',
    'tagline' => 'North & South Indian vegetarian favourites since 2005 (demo).',
    'location' => 'Whitefield, Bangalore (demo branch)',
    'address' => 'TC-105, EPIP Area, Near Vydehi Campus, Whitefield, Bangalore 560066, India',
    'phone' => '+91 93530 49993',
    'whatsapp' => '+91 93530 49993',
    'website' => 'http://www.dti.rocks/',
    'image' => 'https://picsum.photos/seed/mci-dir-bhopal/800/520',
    'map_lat' => 12.9698,
    'map_lon' => 77.7499,
    'about' => [
      'Step into a relaxed dining room filled with the aroma of fresh spices and seasonal produce. Our kitchen focuses on thalis, tandoor breads, and regional specials—prepared without onion/garlic on request.',
      'We welcome families, office groups, and weekend brunch crowds. Reserve ahead for parties of 8+; outdoor catering menus are available (demo text).',
    ],
    'services' => ['Dine-in', 'Takeaway', 'Catering', 'Custom thali'],
    'tags' => ['Vegetarian', 'Thali', 'Indian food', 'Family friendly', 'Catering'],
    'gallery_seeds' => ['mci-gal-bpl-1', 'mci-gal-bpl-2', 'mci-gal-bpl-3', 'mci-gal-bpl-4', 'mci-gal-bpl-5'],
    'faqs' => [
      ['q' => 'Is everything vegetarian?', 'a' => 'Yes—our kitchen is vegetarian. Many dishes can be prepared without onion or garlic on request; tell us when you order.'],
      ['q' => 'Do you take reservations?', 'a' => 'Walk-ins welcome; for groups of 8+ we recommend booking ahead, especially on weekends (demo policy).'],
      ['q' => 'Do you offer office catering?', 'a' => 'Yes—boxed meals and buffet-style menus are available. Contact us with headcount, date, and dietary notes for a sample menu.'],
      ['q' => 'What payment methods do you accept?', 'a' => 'Cash, cards, and UPI are accepted here (placeholder—confirm with staff).'],
    ],
  ],
];

$defaultListing = [
  'title' => 'Sample Local Business',
  'category' => 'Services',
  'tagline' => 'Quality service in your neighbourhood—this is placeholder content.',
  'location' => 'Your city',
  'address' => '123 Main Street, Suite 100, Your City 560001',
  'phone' => '+91 98765 43210',
  'whatsapp' => '+91 98765 43210',
  'website' => 'https://example.com',
  'image' => 'https://picsum.photos/seed/mci-biz-default-' . rawurlencode($slug ?: 'x') . '/800/520',
  'map_lat' => 12.9716,
  'map_lon' => 77.5946,
  'about' => [
    'This is demo copy for a business that is not yet in our sample catalog. When your backend is connected, the real description, hours, and media will appear here.',
    'Use this page to review layout: hero image, profile photo, map, and contact rows should all feel bright and easy to scan on mobile and desktop.',
  ],
  'services' => ['Consultation', 'Local delivery', 'Support hotline', 'Custom quotes'],
  'tags' => ['Local business', 'Services', 'Demo'],
  'gallery_seeds' => ['mci-gal-def-1', 'mci-gal-def-2', 'mci-gal-def-3'],
  'faqs' => [
    ['q' => 'What are your opening hours?', 'a' => 'Hours shown on this page are demo data. When your listing is live, your real schedule will appear here and in search results.'],
    ['q' => 'How can I get a quote?', 'a' => 'Use the contact options on the right, or send an enquiry through our site. We’ll respond with next steps (sample text).'],
    ['q' => 'Do you serve my area?', 'a' => 'Coverage depends on the business. Add service areas and tags in your dashboard so customers can find you more easily.'],
  ],
];

$listing = array_merge($defaultListing, $catalog[$slug] ?? []);

$listingFaqs = $listing['faqs'] ?? [];
$listingFaqs = is_array($listingFaqs) ? $listingFaqs : [];
$faqAccordionId = 'mciBizFaq' . preg_replace('/[^a-zA-Z0-9]+/', '', $slug !== '' ? $slug : 'general');

$pageTitle = $listing['title'] . ' - My City Info';

$mapEmbedUrl = mci_osm_embed_url((float) $listing['map_lat'], (float) $listing['map_lon']);
$directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($listing['address']);
$mapsSearchUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($listing['address']);
$whatsappDigits = preg_replace('/\D+/', '', (string) ($listing['whatsapp'] ?? ''));
$whatsappUrl = $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits : '';

$businessPageUrl = $slug !== '' ? '/business/?slug=' . rawurlencode($slug) : '/business-listing/';

$reviewsDisplay = mci_reviews_merged_for_display($slug);
$reviewSummary = mci_reviews_summary($reviewsDisplay);
$userAlreadyReviewed = $slug !== '' && $isLoggedIn && $userId !== '' && mci_reviews_user_has_reviewed($slug, $userId);

require_once __DIR__ . '/../includes/mci_directory_listings.php';
require_once __DIR__ . '/../includes/mci_geo.php';

$nearbyKmAllowed = [1, 2, 5, 10];
$nearbyKm = (int) ($_GET['nearby_km'] ?? 5);
if (!in_array($nearbyKm, $nearbyKmAllowed, true)) {
    $nearbyKm = 5;
}

$nearbyResults = [];
$originLat = (float) ($listing['map_lat'] ?? 0);
$originLon = (float) ($listing['map_lon'] ?? 0);
$hasOriginCoords = ($originLat !== 0.0 || $originLon !== 0.0);

if ($hasOriginCoords) {
    foreach ($mciDirectoryListings as $row) {
        $otherSlug = (string) ($row['slug'] ?? '');
        if ($slug !== '' && $otherSlug === $slug) {
            continue;
        }
        $olat = (float) ($row['map_lat'] ?? 0);
        $olon = (float) ($row['map_lon'] ?? 0);
        if ($olat === 0.0 && $olon === 0.0) {
            continue;
        }
        $d = mci_distance_km($originLat, $originLon, $olat, $olon);
        if ($d <= (float) $nearbyKm) {
            $copy = $row;
            $copy['distance_km'] = $d;
            $nearbyResults[] = $copy;
        }
    }
    usort($nearbyResults, static function (array $a, array $b): int {
        return ($a['distance_km'] <=> $b['distance_km']);
    });
    $nearbyResults = array_slice($nearbyResults, 0, 8);
}

$nearbyUrlParams = ['nearby_km' => $nearbyKm];
if ($slug !== '') {
    $nearbyUrlParams['slug'] = $slug;
}

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/business.css" />
<script src="/assets/js/business-reviews.js" defer></script>
HTML;

ob_start();
?>

<div class="mci-business-page pb-4">
  <!-- Hero: same image as listing card -->
  <div class="mci-business-hero mb-0">
    <img
      class="mci-business-hero__img"
      src="<?= htmlspecialchars($listing['image']) ?>"
      alt=""
      loading="eager"
    />
    <div class="mci-business-hero__overlay" aria-hidden="true"></div>
    <div class="mci-business-hero__content">
      <div class="mci-business-hero__title-row w-100 min-w-0">
        <span class="mci-business-hero__badge"><?= htmlspecialchars($listing['category']) ?></span>
        <h1 class="mci-business-hero__title"><?= htmlspecialchars($listing['title']) ?></h1>
      </div>
    </div>
  </div>

  <!-- Profile image (listing photo again, like a storefront avatar) -->
  <div class="mci-business-profile-wrap">
    <img
      class="mci-business-profile"
      src="<?= htmlspecialchars($listing['image']) ?>"
      alt="<?= htmlspecialchars($listing['title']) ?>"
      width="132"
      height="132"
      loading="lazy"
    />
  </div>

  <div class="px-1 px-sm-2">
    <div class="row g-4 align-items-start">
      <div class="col-12 col-lg-8">
        <div class="mci-business-title-block mb-3 pb-lg-1">
          <div class="mci-business-cat text-uppercase small mb-1"><?= htmlspecialchars($listing['category']) ?></div>
          <h2 class="mci-business-name mb-2"><?= htmlspecialchars($listing['title']) ?></h2>
          <p class="text-muted mb-2"><?= htmlspecialchars($listing['tagline']) ?></p>
          <?php
          $bizTags = $listing['tags'] ?? [];
          $bizTags = is_array($bizTags) ? $bizTags : [];
          ?>
          <?php if (count($bizTags) > 0): ?>
            <div class="mci-business-tags" aria-label="Business tags">
              <span class="mci-business-tags__label text-muted small fw-semibold me-2 align-middle">Tags</span>
              <div class="d-inline-flex flex-wrap gap-2 align-middle">
                <?php foreach ($bizTags as $t): ?>
                  <?php $t = trim((string) $t); if ($t === '') {
                      continue;
                  } ?>
                  <a
                    class="mci-business-tag"
                    href="/business-listing/?tag=<?= rawurlencode($t) ?>"
                  ><?= htmlspecialchars($t) ?></a>
                <?php endforeach; ?>
              </div>
            </div>
            <p class="text-muted small mt-2 mb-0">Click a tag to open the directory filtered by that tag (also searchable via the “What” field).</p>
          <?php endif; ?>
        </div>

        <div class="card mci-business-card border-0 bg-white mb-4">
          <div class="card-body">
            <div class="mci-business-section-title mb-3">
              <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
              About
            </div>
            <div class="mci-business-about">
              <?php foreach ($listing['about'] as $para): ?>
                <p><?= htmlspecialchars($para) ?></p>
              <?php endforeach; ?>
            </div>

            <div class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-stars" aria-hidden="true"></i>
              Services &amp; highlights
            </div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($listing['services'] as $svc): ?>
                <span class="mci-business-chip"><?= htmlspecialchars($svc) ?></span>
              <?php endforeach; ?>
            </div>

            <div class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-clock-fill" aria-hidden="true"></i>
              Business hours <span class="text-muted fw-normal fs-6">(demo)</span>
            </div>
            <div class="mci-business-hours-wrap">
              <table class="table table-sm table-bordered align-middle mb-0">
                <thead>
                  <tr>
                    <th>Day</th>
                    <th>Morning</th>
                    <th>Evening</th>
                  </tr>
                </thead>
                <tbody class="small">
                  <tr><td class="fw-semibold">Mon – Fri</td><td>9:00 – 13:00</td><td>15:00 – 19:30</td></tr>
                  <tr><td class="fw-semibold">Saturday</td><td>10:00 – 16:00</td><td>—</td></tr>
                  <tr><td class="fw-semibold">Sunday</td><td colspan="2" class="text-muted">Closed (demo)</td></tr>
                </tbody>
              </table>
            </div>

            <div class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-images" aria-hidden="true"></i>
              Gallery <span class="text-muted fw-normal fs-6">(demo photos)</span>
            </div>
            <div class="row g-3">
              <?php foreach ($listing['gallery_seeds'] as $seed): ?>
                <div class="col-6 col-md-4">
                  <img
                    class="mci-business-gallery-img"
                    src="https://picsum.photos/seed/<?= htmlspecialchars($seed) ?>/640/480"
                    alt=""
                    loading="lazy"
                  />
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (count($listingFaqs) > 0): ?>
            <div class="mci-business-section-title mt-4 mb-3">
              <i class="bi bi-question-circle-fill" aria-hidden="true"></i>
              Frequently asked questions
            </div>
            <p class="text-muted small mb-3">Quick answers to common questions (sample content for layout preview).</p>
            <div class="accordion mci-business-faq" id="<?= htmlspecialchars($faqAccordionId) ?>">
              <?php
              $faqRenderIndex = 0;
              foreach ($listingFaqs as $faq):
                  if (!is_array($faq)) {
                      continue;
                  }
                  $fq = trim((string) ($faq['q'] ?? ''));
                  $fa = trim((string) ($faq['a'] ?? ''));
                  if ($fq === '' || $fa === '') {
                      continue;
                  }
                  $collapseId = $faqAccordionId . 'c' . $faqRenderIndex;
                  $isFirst = $faqRenderIndex === 0;
                  $faqRenderIndex++;
                  ?>
                <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden shadow-sm">
                  <h3 class="accordion-header m-0" id="<?= htmlspecialchars($collapseId) ?>-head">
                    <button
                      class="accordion-button <?= $isFirst ? '' : 'collapsed' ?> fw-semibold small py-3"
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                      aria-expanded="<?= $isFirst ? 'true' : 'false' ?>"
                      aria-controls="<?= htmlspecialchars($collapseId) ?>"
                    >
                      <?= htmlspecialchars($fq) ?>
                    </button>
                  </h3>
                  <div
                    id="<?= htmlspecialchars($collapseId) ?>"
                    class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>"
                    aria-labelledby="<?= htmlspecialchars($collapseId) ?>-head"
                    data-bs-parent="#<?= htmlspecialchars($faqAccordionId) ?>"
                  >
                    <div class="accordion-body small text-muted border-top pt-3 pb-3" style="line-height: 1.65;">
                      <?= nl2br(htmlspecialchars($fa)) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($slug !== ''): ?>
        <div class="card mci-business-card border-0 bg-white mb-4" id="reviews">
          <div class="card-body">
            <div class="mci-business-section-title mb-2">
              <i class="bi bi-chat-heart-fill" aria-hidden="true"></i>
              Ratings &amp; reviews
            </div>
            <p class="text-muted small mb-3">
              All published reviews appear as <strong>Anonymous</strong>—your name and account are never shown publicly.
              You must be signed in so we can prevent spam and duplicate reviews.
            </p>

            <?php if ($reviewFlashOk): ?>
              <div class="alert alert-success py-2 small mb-3" role="status">Thanks! Your review was posted anonymously.</div>
            <?php endif; ?>
            <?php if ($reviewFlashErr !== ''): ?>
              <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($reviewFlashErr) ?></div>
            <?php endif; ?>

            <div class="mci-reviews-summary d-flex flex-wrap align-items-center gap-3 mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #ecfeff 0%, #fff 100%); border: 1px solid var(--mci-border);">
              <div class="d-flex align-items-center gap-2">
                <span class="mci-reviews-summary__score display-6 fw-bold mb-0" style="color: var(--mci-color-primary-deep);">
                  <?= $reviewSummary['count'] > 0 ? htmlspecialchars((string) $reviewSummary['average']) : '—' ?>
                </span>
                <div>
                  <?= $reviewSummary['count'] > 0 ? mci_reviews_stars_html((int) round($reviewSummary['average'])) : '<span class="text-muted small">No ratings yet</span>' ?>
                  <div class="text-muted small"><?= (int) $reviewSummary['count'] ?> review<?= $reviewSummary['count'] === 1 ? '' : 's' ?></div>
                </div>
              </div>
            </div>

            <div class="mci-reviews-list mb-4">
              <?php if (count($reviewsDisplay) === 0): ?>
                <p class="text-muted small mb-0">No reviews yet. Be the first to share your experience.</p>
              <?php else: ?>
                <?php foreach ($reviewsDisplay as $rev): ?>
                  <?php
                  if (!is_array($rev)) {
                      continue;
                  }
                  $rStars = (int) ($rev['rating'] ?? 0);
                  $rText = (string) ($rev['text'] ?? '');
                  $rWhen = $rev['created_at'] ?? '';
                  $rDate = $rWhen !== '' ? date('M j, Y', strtotime($rWhen)) : '';
                  ?>
                  <div class="mci-review-item border rounded-3 p-3 mb-3 bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill text-bg-light border fw-semibold">Anonymous</span>
                        <?= mci_reviews_stars_html($rStars) ?>
                      </div>
                      <?php if ($rDate !== ''): ?>
                        <time class="text-muted small" datetime="<?= htmlspecialchars($rWhen) ?>"><?= htmlspecialchars($rDate) ?></time>
                      <?php endif; ?>
                    </div>
                    <div class="mci-review-item__text small mb-0" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($rText)) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <?php if ($isLoggedIn): ?>
              <?php if ($userAlreadyReviewed): ?>
                <div class="alert alert-info small mb-0">You’ve already submitted a review for this business. Thank you!</div>
                <div class="mt-2">
                  <a class="small text-muted" href="/logout/?return=<?= rawurlencode('/business/?slug=' . $slug . '#reviews') ?>">Sign out</a>
                </div>
              <?php else: ?>
                <div class="mci-business-section-title mb-3">
                  <i class="bi bi-pencil-square" aria-hidden="true"></i>
                  Write a review
                </div>
                <form id="mciReviewForm" method="post" action="/business/" class="mci-review-form">
                  <input type="hidden" name="business_slug" value="<?= htmlspecialchars($slug) ?>" />
                  <fieldset class="mb-3">
                    <legend class="form-label fw-semibold small mb-2">Your rating</legend>
                    <input type="hidden" name="rating" id="mciRatingValue" value="0" />
                    <div class="mci-star-picker d-flex gap-1" id="mciStarPicker" role="group" aria-label="Star rating 1 to 5">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <button type="button" class="btn btn-link mci-star-picker__btn p-0 border-0" data-star-value="<?= $s ?>" aria-label="<?= $s ?> star<?= $s === 1 ? '' : 's' ?>">
                          <i class="bi bi-star mci-reviews-star--off fs-3" aria-hidden="true"></i>
                        </button>
                      <?php endfor; ?>
                    </div>
                    <div class="invalid-feedback d-block visually-hidden" id="mciRatingHint">Select 1–5 stars.</div>
                  </fieldset>
                  <div class="mb-3">
                    <label class="form-label fw-semibold small" for="mciReviewText">Your review</label>
                    <textarea class="form-control" name="review_text" id="mciReviewText" rows="4" required minlength="10" maxlength="2000" placeholder="Share your experience (minimum 10 characters). Your name will not be shown."></textarea>
                    <div class="form-text">Posted publicly as Anonymous.</div>
                  </div>
                  <div class="d-flex flex-wrap align-items-center gap-3">
                    <button type="submit" name="mci_review_submit" value="1" class="btn btn-dark">Submit review</button>
                    <a class="small text-muted" href="/logout/?return=<?= rawurlencode('/business/?slug=' . $slug . '#reviews') ?>">Sign out</a>
                  </div>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <div class="border rounded-3 p-4 text-center" style="background: linear-gradient(180deg, #f8fafc 0%, #fff 100%); border-color: var(--mci-border) !important;">
                <div class="fw-bold mb-2">Sign in to rate &amp; review</div>
                <p class="text-muted small mb-3 mb-md-4">General users can rate any business. Reviews stay anonymous on the listing.</p>
                <a class="btn btn-dark me-2" href="/login/?return=<?= rawurlencode('/business/?slug=' . $slug . '#reviews') ?>">Login</a>
                <a class="btn btn-outline-dark" href="/register/?return=<?= rawurlencode('/business/?slug=' . $slug . '#reviews') ?>">Register</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right panel: map + all contact details -->
      <div class="col-12 col-lg-4 mci-business-sidebar-sticky">
        <div class="card mci-business-side-card border-0 bg-white mb-4">
          <div class="mci-business-map-wrap">
            <div class="mci-business-map-actions">
              <a class="btn btn-dark btn-sm" href="<?= htmlspecialchars($directionsUrl) ?>" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-sign-turn-right me-1" aria-hidden="true"></i> Get directions
              </a>
              <a class="btn btn-light btn-sm border-0" href="<?= htmlspecialchars($mapsSearchUrl) ?>" target="_blank" rel="noopener noreferrer" title="Open in maps">
                <i class="bi bi-arrows-fullscreen" aria-hidden="true"></i><span class="visually-hidden"> Open full map</span>
              </a>
            </div>
            <iframe
              title="Map — <?= htmlspecialchars($listing['title']) ?>"
              src="<?= htmlspecialchars($mapEmbedUrl) ?>"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>

          <div class="mci-business-side-contact-block">
            <div class="mci-business-side-contact-heading">Contact</div>

          <div class="mci-business-contact-row">
            <div class="mci-business-contact-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></div>
            <div>
              <div class="mci-business-contact-label">Address</div>
              <div class="mci-business-contact-value"><?= htmlspecialchars($listing['address']) ?></div>
            </div>
          </div>

          <div class="mci-business-contact-row">
            <div class="mci-business-contact-icon" aria-hidden="true"><i class="bi bi-telephone"></i></div>
            <div>
              <div class="mci-business-contact-label">Phone</div>
              <div class="mci-business-contact-value">
                <?php if ($listing['phone'] !== ''): ?>
                  <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $listing['phone'])) ?>"><?= htmlspecialchars($listing['phone']) ?></a>
                <?php else: ?>
                  Not provided
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="mci-business-contact-row">
            <div class="mci-business-contact-icon" aria-hidden="true"><i class="bi bi-whatsapp"></i></div>
            <div>
              <div class="mci-business-contact-label">WhatsApp</div>
              <div class="mci-business-contact-value">
                <?php if ($whatsappUrl !== ''): ?>
                  <a href="<?= htmlspecialchars($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($listing['whatsapp']) ?></a>
                <?php elseif (($listing['whatsapp'] ?? '') !== ''): ?>
                  <?= htmlspecialchars($listing['whatsapp']) ?>
                <?php else: ?>
                  Not provided
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="mci-business-contact-row">
            <div class="mci-business-contact-icon" aria-hidden="true"><i class="bi bi-globe2"></i></div>
            <div>
              <div class="mci-business-contact-label">Website</div>
              <div class="mci-business-contact-value">
                <?php if (!empty($listing['website'])): ?>
                  <a href="<?= htmlspecialchars($listing['website']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($listing['website']) ?></a>
                <?php else: ?>
                  Not provided
                <?php endif; ?>
              </div>
            </div>
          </div>
          </div>

          <div class="mci-business-side-actions d-flex flex-column gap-2">
            <?php if ($isLoggedIn): ?>
              <button class="btn btn-dark w-100" type="button" data-bs-toggle="modal" data-bs-target="#claimModal">
                Claim this listing
              </button>
              <div class="text-muted small text-center">You are logged in (demo).</div>
            <?php else: ?>
              <button class="btn btn-dark w-100" type="button" data-bs-toggle="modal" data-bs-target="#claimModal">
                Claim this listing
              </button>
              <div class="text-muted small text-center">Login or register to claim.</div>
            <?php endif; ?>
            <a class="btn btn-outline-dark w-100" href="/contact/">Send enquiry</a>
            <button class="btn btn-outline-secondary w-100" type="button" disabled title="Demo">Save to favourites</button>
            <p class="text-muted small mb-0">
              Claiming requires registration. Anonymous listings are reviewed by admin first.
            </p>
          </div>
        </div>

        <?php if ($hasOriginCoords): ?>
        <div class="card mci-business-side-card border-0 bg-white mb-4" id="nearby">
          <div class="card-body">
            <div class="mci-business-section-title mb-2">
              <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
              Nearby
            </div>
            <p class="text-muted small mb-3">
              Other listings within a straight-line distance. Choose a radius; sorted nearest first.
            </p>
            <div class="mci-nearby-radius btn-group flex-wrap mb-3" role="group" aria-label="Search radius">
              <?php foreach ($nearbyKmAllowed as $km): ?>
                <?php
                $params = array_merge($nearbyUrlParams, ['nearby_km' => $km]);
                $nearbyHref = '/business/?' . http_build_query($params) . '#nearby';
                ?>
                <a
                  class="btn btn-sm <?= $km === $nearbyKm ? 'btn-dark' : 'btn-outline-dark' ?>"
                  href="<?= htmlspecialchars($nearbyHref) ?>"
                ><?= (int) $km ?> km</a>
              <?php endforeach; ?>
            </div>

            <?php if (count($nearbyResults) === 0): ?>
              <p class="text-muted small mb-0">
                No listings within <strong><?= (int) $nearbyKm ?> km</strong>.
                Try a larger radius or <a href="/business-listing/">browse all</a>.
              </p>
            <?php else: ?>
              <div class="text-muted small mb-3">
                <strong><?= count($nearbyResults) ?></strong> within <strong><?= (int) $nearbyKm ?> km</strong> (max 8)
              </div>
              <?php $mciListingBackup = $listing; ?>
              <div class="d-flex flex-column gap-3">
                <?php foreach ($nearbyResults as $nearbyRow): ?>
                  <?php
                  $listing = $nearbyRow;
                  $size = 'compact';
                  include __DIR__ . '/../views/components/listing-card.php';
                  ?>
                <?php endforeach; ?>
              </div>
              <?php $listing = $mciListingBackup; unset($mciListingBackup, $nearbyRow); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Claim modal -->
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div class="fw-semibold">Claim this listing</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if ($isLoggedIn): ?>
          <div class="alert alert-info mb-0">
            You’re logged in. In the backend phase, you’ll be able to request a claim and manage the listing.
          </div>
        <?php else: ?>
          <div class="alert alert-warning mb-0">
            Registration/login is required to claim this listing.
            <div class="mt-2">
              <a href="/login/?return=<?= rawurlencode($businessPageUrl) ?>" class="btn btn-sm btn-dark me-2">Login</a>
              <a href="/register/?return=<?= rawurlencode($businessPageUrl) ?>" class="btn btn-sm btn-outline-dark">Register</a>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-dark" disabled>Submit claim (backend later)</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
