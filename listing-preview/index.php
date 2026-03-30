<?php
declare(strict_types=1);

$pageTitle    = 'Listing Preview – My City Info';
$activePage   = '';
$hideCta      = true;

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="/assets/css/business.css" />
<style>
/* ── Preview-mode banner ─────────────────────────────── */
.mci-preview-banner {
  position: sticky;
  top: 0;
  z-index: 1050;
  background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
  color: #fff;
  padding: 0.6rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(249,115,22,.25);
}
.mci-preview-banner a {
  color: #fff;
  text-decoration: underline;
  text-underline-offset: 2px;
}
/* ── No-data state ───────────────────────────────────── */
.mci-preview-nodata {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  text-align: center;
  color: #64748b;
}
.mci-preview-nodata i { font-size: 3rem; opacity: .4; }
/* ── Placeholder elements (shown before JS fills them) ── */
[data-pv].pv-hide { display: none !important; }
</style>
HTML;

ob_start();
?>

<!-- Preview mode banner -->
<div class="mci-preview-banner" id="pvBanner" role="status">
  <div>
    <i class="bi bi-eye-fill me-2" aria-hidden="true"></i>
    Preview mode - this is exactly how your listing will appear once published.
  </div>
  <div class="d-flex align-items-center gap-3 flex-shrink-0">
    <a href="/submit-business-listing/">← Back to edit</a>
    <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Dismiss preview banner" id="pvBannerClose"></button>
  </div>
</div>

<!-- No-data fallback (shown if localStorage is empty) -->
<div class="mci-preview-nodata d-none" id="pvNoData">
  <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
  <div>
    <div class="fw-bold mb-1">No preview data found</div>
    <p class="small mb-3">Fill in your listing details on the submit form first, then click "Preview listing".</p>
    <a href="/submit-business-listing/" class="btn btn-dark btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Go to submit form
    </a>
  </div>
</div>

<!-- Business detail layout — populated by JS from localStorage -->
<div class="mci-business-page pb-4" id="pvPage" style="display:none">

  <!-- Hero banner -->
  <div class="mci-business-hero mb-0">
    <img class="mci-business-hero__img" id="pvHeroImg" src="" alt="" />
    <div class="mci-business-hero__overlay" aria-hidden="true"></div>
    <div class="mci-business-hero__content">
      <div class="mci-business-hero__title-row w-100 min-w-0">
        <span class="mci-business-hero__badge" id="pvHeroCat">Category</span>
        <h1 class="mci-business-hero__title" id="pvHeroTitle">Business name</h1>
      </div>
    </div>
  </div>

  <!-- Profile image -->
  <div class="mci-business-profile-wrap">
    <img class="mci-business-profile" id="pvProfileImg" src="" alt="" width="132" height="132" />
  </div>

  <div class="px-1 px-sm-2">
    <div class="row g-4 align-items-start">

      <!-- ── Left column ───────────────────────────── -->
      <div class="col-12 col-lg-8">
        <div class="mci-business-title-block mb-3 pb-lg-1">
          <div class="mci-business-cat text-uppercase small mb-1" id="pvCat">—</div>
          <h2 class="mci-business-name mb-2" id="pvTitle">—</h2>
          <p class="text-muted mb-2" id="pvTagline"></p>
          <div class="mci-business-tags" id="pvTagsWrap" aria-label="Business tags" style="display:none">
            <span class="mci-business-tags__label text-muted small fw-semibold me-2 align-middle">Tags</span>
            <div class="d-inline-flex flex-wrap gap-2 align-middle" id="pvTagsInner"></div>
          </div>
        </div>

        <div class="card mci-business-card border-0 bg-white mb-4">
          <div class="card-body">

            <!-- About -->
            <div class="mci-business-section-title mb-3">
              <i class="bi bi-info-circle-fill" aria-hidden="true"></i> About
            </div>
            <div class="mci-business-about">
              <p id="pvAbout" class="mb-0"></p>
            </div>

            <!-- Services -->
            <div id="pvServicesSection">
              <div class="mci-business-section-title mt-4 mb-3">
                <i class="bi bi-stars" aria-hidden="true"></i> Services &amp; highlights
              </div>
              <div class="d-flex flex-wrap gap-2" id="pvServicesInner"></div>
            </div>

            <!-- Gallery -->
            <div id="pvGallerySection">
              <div class="mci-business-section-title mt-4 mb-3">
                <i class="bi bi-images" aria-hidden="true"></i> Gallery
              </div>
              <div class="row g-3" id="pvGalleryInner"></div>
            </div>

            <!-- FAQs -->
            <div id="pvFaqSection">
              <div class="mci-business-section-title mt-4 mb-3">
                <i class="bi bi-question-circle-fill" aria-hidden="true"></i> Frequently asked questions
              </div>
              <div class="accordion mci-business-faq" id="pvFaqAccordion"></div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Right sidebar ─────────────────────────── -->
      <div class="col-12 col-lg-4">
        <div class="card mci-business-card border-0 bg-white mb-3">
          <div class="card-body py-3">

            <div class="mci-business-section-title mb-3">
              <i class="bi bi-telephone-fill" aria-hidden="true"></i> Contact
            </div>

            <div id="pvContactRows"></div>

            <!-- Social links -->
            <div id="pvSocialSection" style="display:none">
              <div class="mci-business-section-title mt-3 mb-2">
                <i class="bi bi-share-fill" aria-hidden="true"></i> Follow us
              </div>
              <div class="d-flex flex-wrap gap-2" id="pvSocialInner"></div>
            </div>

          </div>
        </div>

        <!-- Preview note card -->
        <div class="card border-0 mb-3" style="background:linear-gradient(135deg,#fef3c7 0%,#fff 100%);border:1px solid #fde68a!important">
          <div class="card-body py-3">
            <div class="fw-semibold small mb-1">
              <i class="bi bi-info-circle me-1 text-warning" aria-hidden="true"></i>Preview only
            </div>
            <p class="text-muted small mb-2">Map, reviews, nearby businesses, and hours will appear once your listing is live.</p>
            <a href="/submit-business-listing/" class="btn btn-dark btn-sm w-100">
              <i class="bi bi-arrow-left me-1"></i>Back to edit
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  var LS_KEY = 'mci_listing_preview';

  // Dismiss banner
  document.getElementById('pvBannerClose').addEventListener('click', function () {
    document.getElementById('pvBanner').style.display = 'none';
  });

  // Load data
  var raw;
  try { raw = localStorage.getItem(LS_KEY); } catch(e) {}

  if (!raw) {
    document.getElementById('pvNoData').classList.remove('d-none');
    return;
  }

  var d;
  try { d = JSON.parse(raw); } catch(e) {
    document.getElementById('pvNoData').classList.remove('d-none');
    return;
  }

  document.getElementById('pvPage').style.display = '';

  function esc(s) {
    var div = document.createElement('div');
    div.textContent = s || '';
    return div.innerHTML;
  }
  function el(id) { return document.getElementById(id); }
  function setTxt(id, val) { var e = el(id); if (e) e.textContent = val || ''; }
  function setHTML(id, val) { var e = el(id); if (e) e.innerHTML = val || ''; }

  // Hero image: prefer banner, fallback logo, fallback placeholder gradient
  var heroSrc = d.img_banner || d.img_logo || '';
  if (heroSrc) {
    el('pvHeroImg').src = heroSrc;
    el('pvHeroImg').style.objectFit = 'cover';
  } else {
    el('pvHeroImg').style.background = 'linear-gradient(135deg,#6366f1 0%,#818cf8 100%)';
    el('pvHeroImg').style.minHeight = '220px';
  }

  // Profile image: prefer profile, fallback logo
  var profileSrc = d.img_profile || d.img_logo || '';
  if (profileSrc) {
    el('pvProfileImg').src = profileSrc;
  } else {
    el('pvProfileImg').style.display = 'none';
  }

  // Title & category
  var title = d.listing_title || 'Unnamed business';
  var cat   = d.category || '';
  setTxt('pvHeroCat',  cat  || 'Category');
  setTxt('pvHeroTitle', title);
  setTxt('pvCat',       cat.toUpperCase());
  setTxt('pvTitle',     title);
  setTxt('pvTagline',   d.tagline || '');

  // Tags
  var tagsRaw = (d.tags || '').split(',').map(function(t){ return t.trim(); }).filter(Boolean);
  if (tagsRaw.length) {
    el('pvTagsWrap').style.display = '';
    el('pvTagsInner').innerHTML = tagsRaw.map(function(t){
      return '<span class="mci-business-tag">' + esc(t) + '</span>';
    }).join('');
  }

  // About / description
  var desc = d.description || '';
  el('pvAbout').textContent = desc || 'No description provided.';

  // Services
  var svcs = d._services || [];
  svcs = svcs.filter(function(s){ return s.name && s.name.trim(); });
  if (svcs.length) {
    el('pvServicesInner').innerHTML = svcs.map(function(s){
      return '<span class="mci-business-chip">' + esc(s.name) + '</span>';
    }).join('');
  } else {
    el('pvServicesSection').style.display = 'none';
  }

  // Gallery (uploaded images)
  var galleryImgs = [];
  // Hidden inputs for gallery aren't base64 (they're file refs), so we just skip
  // Only show if we have something meaningful — placeholder note instead
  el('pvGallerySection').innerHTML =
    '<div class="mci-business-section-title mt-4 mb-3"><i class="bi bi-images" aria-hidden="true"></i> Gallery</div>' +
    '<p class="text-muted small mb-0">Gallery photos will appear here once your listing is published.</p>';

  // FAQs
  var faqs = d._faqs || [];
  faqs = faqs.filter(function(f){ return f.q && f.q.trim(); });
  if (faqs.length) {
    el('pvFaqAccordion').innerHTML = faqs.map(function(f, i){
      var id = 'pvFaq' + i;
      return '<div class="accordion-item mci-business-faq__item">' +
        '<h3 class="accordion-header">' +
        '<button class="accordion-button' + (i > 0 ? ' collapsed' : '') + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + id + '">' +
        esc(f.q) + '</button></h3>' +
        '<div id="' + id + '" class="accordion-collapse collapse' + (i === 0 ? ' show' : '') + '" data-bs-parent="#pvFaqAccordion">' +
        '<div class="accordion-body small">' + esc(f.a || '—') + '</div></div></div>';
    }).join('');
  } else {
    el('pvFaqSection').style.display = 'none';
  }

  // Contact rows
  var contacts = [
    { icon: 'bi-geo-alt-fill',   val: d.city,          label: 'City' },
    { icon: 'bi-map',            val: d.full_address,  label: 'Address' },
    { icon: 'bi-telephone-fill', val: d.phone,         label: 'Phone', href: 'tel:' },
    { icon: 'bi-whatsapp',       val: d.whatsapp,      label: 'WhatsApp', cls: 'text-success', href: 'https://wa.me/' },
    { icon: 'bi-envelope-fill',  val: d.email_contact, label: 'Email', href: 'mailto:' },
    { icon: 'bi-globe2',         val: d.website,       label: 'Website', href: '' },
  ];
  var contactHTML = '';
  contacts.forEach(function(c) {
    if (!c.val || !c.val.trim()) return;
    var inner = c.href !== undefined
      ? '<a href="' + esc((c.href || '') + c.val) + '" target="_blank" rel="noopener">' + esc(c.val) + '</a>'
      : esc(c.val);
    contactHTML += '<div class="mci-contact-row mb-2 d-flex align-items-start gap-2">' +
      '<i class="bi ' + c.icon + ' mt-1 flex-shrink-0' + (c.cls ? ' ' + c.cls : '') + '" aria-hidden="true"></i>' +
      '<div class="small">' + inner + '</div></div>';
  });
  el('pvContactRows').innerHTML = contactHTML || '<p class="text-muted small mb-0">No contact details provided.</p>';

  // Social links
  var socialDefs = [
    { key: 'social_facebook',  icon: 'bi-facebook',   label: 'Facebook',  cls: 'text-primary' },
    { key: 'social_instagram', icon: 'bi-instagram',  label: 'Instagram', cls: 'text-danger' },
    { key: 'social_x',        icon: 'bi-twitter-x', label: 'X' },
    { key: 'social_youtube',  icon: 'bi-youtube',   label: 'YouTube',   cls: 'text-danger' },
    { key: 'social_linkedin', icon: 'bi-linkedin',  label: 'LinkedIn',  cls: 'text-primary' },
    { key: 'social_tiktok',   icon: 'bi-tiktok',    label: 'TikTok' },
  ];
  var socialHTML = '';
  socialDefs.forEach(function(s) {
    var v = d[s.key];
    if (!v || !v.trim()) return;
    var href = v.startsWith('http') ? v : 'https://' + v;
    socialHTML += '<a href="' + esc(href) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mci-social-btn" aria-label="' + s.label + '">' +
      '<i class="bi ' + s.icon + (s.cls ? ' ' + s.cls : '') + '" aria-hidden="true"></i></a>';
  });
  if (socialHTML) {
    el('pvSocialSection').style.display = '';
    el('pvSocialInner').innerHTML = socialHTML;
  }

}());
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
