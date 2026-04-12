<?php
$pageTitle = 'My Listings - My City Info';
$activePage = '';
$subActive = 'listings';
$hideCta = true;
$appArea = 'subscriber';

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_paths.php';

$userId = (string)($_SESSION['mci_user_id'] ?? '');

$rows = [];
if ($userId !== '') {
    require_once __DIR__ . '/../../api/v1/lib/db.php';
    require_once __DIR__ . '/../../api/v1/lib/business_service.php';
    try {
        $rows = api_business_list_owner(api_db(), $userId)['businesses'] ?? [];
    } catch (Throwable $e) {
        $rows = [];
    }
}

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">My Listings</div>
            <div class="text-muted small">See and modify your submitted listings.</div>
          </div>
          <a class="btn btn-sm btn-dark" href="/subscriber/list-business/">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add new listing
          </a>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle bg-white" style="font-size:var(--mci-text-sm);">
            <thead class="table-light">
              <tr>
                <th style="width:56px;"></th>
                <th>Business</th>
                <th>Category</th>
                <th>Status</th>
                <th style="min-width:100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="text-center text-muted small py-4">You haven't submitted any listings yet. <a href="/subscriber/list-business/">Add your first listing</a>.</td></tr>
              <?php endif; ?>
              <?php foreach ($rows as $r):
                $rowId     = htmlspecialchars((string)($r['id']            ?? ''), ENT_QUOTES, 'UTF-8');
                $rowSlug   = (string)($r['slug']          ?? '');
                $rowName   = (string)($r['name']          ?? '');
                $rowCat    = (string)($r['category_name'] ?? '');
                $rowStatus = strtolower((string)($r['status'] ?? ''));
                $statusBadge = match($rowStatus) {
                    'live'      => 'text-bg-success',
                    'draft'     => 'text-bg-warning',
                    'rejected'  => 'text-bg-danger',
                    'suspended' => 'text-bg-secondary',
                    default     => 'text-bg-light border',
                };
              ?>
                <tr data-listing-id="<?= $rowId ?>" data-listing-slug="<?= htmlspecialchars($rowSlug, ENT_QUOTES, 'UTF-8') ?>" data-listing-status="<?= htmlspecialchars($rowStatus, ENT_QUOTES, 'UTF-8') ?>">
                  <td>
                    <?php
                      $thumbLogo = !empty($r['logo_path'])
                        ? (string) $r['logo_path']
                        : mci_business_logo_placeholder_url();
                    ?>
                    <img src="<?= htmlspecialchars($thumbLogo, ENT_QUOTES, 'UTF-8') ?>"
                      alt="<?= htmlspecialchars($rowName !== '' ? $rowName . ' logo' : 'Business logo', ENT_QUOTES, 'UTF-8') ?>" width="48" height="48" class="rounded-2" style="object-fit:cover;" loading="lazy" decoding="async" />
                  </td>
                  <td>
                    <button type="button" class="btn btn-link p-0 text-start fw-semibold js-sub-view-btn"
                      data-id="<?= $rowId ?>" style="text-decoration:none;color:inherit;font-size:inherit;">
                      <?= htmlspecialchars($rowName) ?>
                    </button>
                    <?php if ($rowSlug): ?>
                      <div class="text-muted" style="font-size:var(--mci-text-micro);"><?= htmlspecialchars($rowSlug) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= htmlspecialchars($rowCat) ?></td>
                  <td>
                    <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($rowStatus)) ?></span>
                  </td>
                  <td>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 js-sub-view-btn" data-id="<?= $rowId ?>">
                      <i class="bi bi-eye" aria-hidden="true"></i> View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (count($rows) > 0): ?>
        <div class="mt-3 text-muted small text-end">Showing <?= count($rows) ?> listing<?= count($rows) !== 1 ? 's' : '' ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Subscriber listing detail flyout ──────────────────────────────────── -->
<style>
#subReviewOverlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.35);
  z-index: 1040;
}
#subReviewOverlay.active { display: block; }

#subReviewPanel {
  position: fixed;
  top: 0;
  right: 0;
  width: min(680px, 92vw);
  max-width: 680px;
  height: 100dvh;
  background: #fff;
  box-shadow: -4px 0 24px rgba(0,0,0,.15);
  z-index: 1050;
  display: flex;
  flex-direction: column;
  transform: translateX(100%);
  transition: transform .25s ease;
}
#subReviewPanel.active { transform: translateX(0); }

#subReviewPanelHead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #dee2e6;
  flex-shrink: 0;
}
#subReviewPanelBody {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}
#subReviewPanelFoot {
  display: flex;
  gap: .5rem;
  padding: .875rem 1.25rem;
  border-top: 1px solid #dee2e6;
  flex-shrink: 0;
  flex-wrap: wrap;
  align-items: center;
}
@media (max-width: 767px) {
  #subReviewPanel { width: 100%; max-width: 100%; }
  #subReviewPanelBody { padding: 1rem; }
  #subReviewPanelFoot .btn { flex: 1 1 100%; min-height: 2.75rem; }
  #subReviewPanelCloseBtn { order: 3; }
}
.sub-review-section-head {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #6c757d;
  margin-bottom: .4rem;
}
</style>

<div id="subReviewOverlay"></div>
<div id="subReviewPanel" role="dialog" aria-labelledby="subReviewPanelTitle" aria-modal="true">
  <div id="subReviewPanelHead">
    <span id="subReviewPanelTitle" class="fw-semibold" style="font-size:1rem;">Listing details</span>
    <button type="button" id="subReviewPanelClose" class="btn-close" aria-label="Close"></button>
  </div>
  <div id="subReviewPanelBody">
    <div class="text-center py-5 text-muted small">Loading…</div>
  </div>
  <div id="subReviewPanelFoot">
    <button type="button" id="subReviewPanelCloseBtn" class="btn btn-sm btn-outline-secondary me-auto">Close</button>
    <a id="subReviewPublicBtn" href="#" target="_blank" rel="noopener noreferrer"
      class="btn btn-sm btn-outline-dark d-none">
      <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>View live
    </a>
    <a id="subReviewEditBtn" href="#" class="btn btn-sm btn-dark">
      <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit listing
    </a>
  </div>
</div>

<script>
(function () {
  'use strict';

  /* ── helpers ── */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function row(label, val) {
    if (val === null || val === undefined || val === '') return '';
    return '<tr><th class="text-muted fw-normal pe-3 align-top" style="width:150px;white-space:nowrap;font-weight:400;">'
      + esc(label) + '</th><td>' + esc(val) + '</td></tr>';
  }
  function section(title) {
    return '<div class="sub-review-section-head">' + title + '</div>';
  }
  function priceStr(mn, mx, unit) {
    if (!mn && !mx) return null;
    var s = mn ? mn : '';
    if (mx && mx !== mn) s += (s ? ' \u2013 ' : '') + mx;
    if (unit) s += ' ' + unit;
    return s || null;
  }

  /* ── panel elements ── */
  var overlay    = document.getElementById('subReviewOverlay');
  var panel      = document.getElementById('subReviewPanel');
  var panelTitle = document.getElementById('subReviewPanelTitle');
  var panelBody  = document.getElementById('subReviewPanelBody');
  var editBtn    = document.getElementById('subReviewEditBtn');
  var publicBtn  = document.getElementById('subReviewPublicBtn');

  function openPanel() {
    overlay.classList.add('active');
    panel.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closePanel() {
    overlay.classList.remove('active');
    panel.classList.remove('active');
    document.body.style.overflow = '';
  }

  document.getElementById('subReviewPanelClose').addEventListener('click', closePanel);
  document.getElementById('subReviewPanelCloseBtn').addEventListener('click', closePanel);
  overlay.addEventListener('click', closePanel);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && panel.classList.contains('active')) { closePanel(); }
  });

  /* ── render business details ── */
  function renderBusiness(b) {
    var html = '';

    /* Logo / banner (placeholders when missing) */
    var phLogo   = '/assets/images/business-logo-placeholder.svg';
    var phBanner = '/assets/images/business-banner-placeholder.svg';
    var phProfile = '/assets/images/business-profile-placeholder.svg';
    var bizLabel = esc((b.name || 'Listing').trim() || 'Listing');
    html += '<div class="d-flex gap-2 mb-3 flex-wrap">';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Logo</div><img src="' + esc(b.logo_path || phLogo) + '" alt="' + bizLabel + ' logo" style="height:56px;max-width:160px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;background:#f8f9fa;padding:4px;"></div>';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Banner</div><img src="' + esc(b.banner_path || phBanner) + '" alt="' + bizLabel + ' banner" style="height:56px;max-width:200px;object-fit:cover;border-radius:4px;"></div>';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Profile</div><img src="' + esc(b.profile_path || phProfile) + '" alt="' + bizLabel + ' profile photo" style="height:56px;width:56px;object-fit:cover;border:1px solid #dee2e6;border-radius:50%;background:#f8f9fa;"></div>';
    html += '</div>';

    /* Status badge */
    var statusColors = { live: 'text-bg-success', draft: 'text-bg-warning', rejected: 'text-bg-danger', suspended: 'text-bg-secondary' };
    var statusCls = statusColors[String(b.status).toLowerCase()] || 'text-bg-light border';
    html += '<div class="mb-3"><span class="badge ' + statusCls + '">' + esc(b.status ? b.status.charAt(0).toUpperCase() + b.status.slice(1) : '') + '</span></div>';

    /* Business overview */
    html += section('Business details')
      + '<table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">'
      + row('Name',         b.name)
      + row('Tagline',      b.tagline)
      + row('Category',     b.category_name)
      + row('Subcategories',(b.subcategories || []).map(function(c){ return c.name; }).join(', ') || null)
      + row('Price range',  b.price_range)
      + row('Est. year',    b.established_year)
      + row('Website',      b.website_url)
      + row('Email',        b.email)
      + row('Video URL',    b.video_url)
      + row('Submitted',    b.created_at)
      + '</table>';

    /* Description */
    if (b.description) {
      html += '<div class="mt-2 mb-1"><span class="text-muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Description</span></div>'
        + '<p class="small mb-0" style="white-space:pre-wrap;line-height:1.5;">' + esc(b.description) + '</p>';
    }

    /* Tags */
    if ((b.tags || []).length) {
      html += '<div class="mt-2"><span class="text-muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Tags</span></div>'
        + '<div class="mt-1">'
        + b.tags.map(function(t){ return '<span class="badge text-bg-light border me-1 mb-1">' + esc(t.name) + '</span>'; }).join('')
        + '</div>';
    }

    /* Branches */
    var branches = b.branches || [];
    if (branches.length) {
      html += '<hr class="my-3">' + section('Location & contact' + (branches.length > 1 ? ' (' + branches.length + ' branches)' : ''));
      branches.forEach(function (br, i) {
        if (branches.length > 1) {
          html += '<div class="fw-semibold small mb-1 mt-2">'
            + (br.is_primary == 1 ? '<span class="badge text-bg-primary me-1" style="font-size:.7rem;">Primary</span>' : '')
            + 'Branch ' + (i + 1) + (br.branch_label ? ' \u2014 ' + esc(br.branch_label) : '')
            + '</div>';
        }
        var addrParts = [br.address_line1, br.address_line2].filter(Boolean);
        html += '<table class="table table-sm table-borderless mb-1" style="font-size:.85rem;">'
          + (addrParts.length ? row('Address', addrParts.join(', ')) : '')
          + row('City',     br.city)
          + row('State',    br.state)
          + row('Country',  br.country)
          + row('Pincode',  br.pincode)
          + row('Lat / Lon',(br.latitude && br.longitude) ? br.latitude + ', ' + br.longitude : null)
          + row('Phone',    br.phone_primary)
          + row('Phone 2',  br.phone_secondary)
          + row('WhatsApp', br.whatsapp_number)
          + row('Email',    br.email)
          + row('Website',  br.website)
          + '</table>';
        if (i < branches.length - 1) html += '<hr class="my-2">';
      });
    }

    /* Social links */
    if ((b.social_links || []).length) {
      html += '<hr class="my-3">' + section('Social media');
      html += '<table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">';
      b.social_links.forEach(function(s) {
        html += '<tr><th class="text-muted fw-normal pe-3 align-top" style="width:110px;text-transform:capitalize;">'
          + esc(s.platform) + '</th><td>'
          + '<a href="' + esc(s.url) + '" target="_blank" rel="noopener" style="word-break:break-all;">' + esc(s.url) + '</a>'
          + (s.label ? ' <span class="text-muted">(' + esc(s.label) + ')</span>' : '')
          + '</td></tr>';
      });
      html += '</table>';
    }

    /* Products */
    if ((b.products || []).length) {
      html += '<hr class="my-3">' + section('Products (' + b.products.length + ')');
      html += '<ul class="list-unstyled mb-0">';
      b.products.forEach(function(p) {
        var price = priceStr(p.price_min, p.price_max, p.price_unit);
        html += '<li class="py-2 border-bottom"><div class="d-flex gap-2 align-items-start">';
        if (p.image_path) html += '<img src="' + esc(p.image_path) + '" alt="' + esc(p.name) + ' thumbnail" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
        html += '<div><div class="fw-semibold small">' + esc(p.name) + '</div>'
          + (price ? '<div class="text-muted small">' + esc(price) + '</div>' : '')
          + (p.description ? '<div class="text-muted small mt-1">' + esc(p.description) + '</div>' : '')
          + '</div></div></li>';
      });
      html += '</ul>';
    }

    /* Services */
    if ((b.services || []).length) {
      html += '<hr class="my-3">' + section('Services (' + b.services.length + ')');
      html += '<ul class="list-unstyled mb-0">';
      b.services.forEach(function(s) {
        var price = priceStr(s.price_min, s.price_max, s.price_unit);
        html += '<li class="py-2 border-bottom"><div class="d-flex gap-2 align-items-start">';
        if (s.image_path) html += '<img src="' + esc(s.image_path) + '" alt="' + esc(s.name) + ' thumbnail" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
        html += '<div><div class="fw-semibold small">' + esc(s.name) + '</div>'
          + (price ? '<div class="text-muted small">' + esc(price) + '</div>' : '')
          + (s.description ? '<div class="text-muted small mt-1">' + esc(s.description) + '</div>' : '')
          + '</div></div></li>';
      });
      html += '</ul>';
    }

    /* FAQs */
    if ((b.faqs || []).length) {
      html += '<hr class="my-3">' + section('FAQs (' + b.faqs.length + ')');
      b.faqs.forEach(function(f) {
        html += '<div class="mb-2 small"><strong>' + esc(f.question) + '</strong>'
          + '<div class="text-muted mt-1">' + esc(f.answer) + '</div></div>';
      });
    }

    /* Gallery */
    if ((b.images || []).length) {
      html += '<hr class="my-3">' + section('Gallery (' + b.images.length + ')');
      html += '<div class="d-flex flex-wrap gap-2 mt-1">';
      b.images.forEach(function(img) {
        html += '<div style="position:relative;">'
          + '<img src="' + esc(img.file_path) + '" alt="' + esc(img.alt_text || img.caption || '') + '" '
          + 'style="height:80px;width:80px;object-fit:cover;border-radius:4px;" loading="lazy">';
        if (img.is_cover == 1) html += '<span style="position:absolute;top:3px;left:3px;background:rgba(0,0,0,.6);color:#fff;font-size:.65rem;padding:1px 4px;border-radius:3px;">Cover</span>';
        html += '</div>';
      });
      html += '</div>';
    }

    /* SEO */
    if (b.page_title || b.meta_description || b.meta_keywords) {
      html += '<hr class="my-3">' + section('SEO / meta')
        + '<table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">'
        + row('Page title',       b.page_title)
        + row('Meta description', b.meta_description)
        + row('Keywords',         b.meta_keywords)
        + '</table>';
    }

    return html;
  }

  /* ── open flyout on View / name click ── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-sub-view-btn');
    if (!btn) return;

    var id  = btn.dataset.id;
    var tr  = document.querySelector('tr[data-listing-id="' + id + '"]');
    var slug   = tr ? (tr.dataset.listingSlug   || '') : '';
    var status = tr ? (tr.dataset.listingStatus || '') : '';
    var name = tr ? (tr.querySelector('.js-sub-view-btn')?.textContent.trim() || id) : id;

    panelTitle.textContent = name;
    panelBody.innerHTML = '<div class="text-center py-5 text-muted small">Loading\u2026</div>';

    /* Edit always available */
    editBtn.href = '/subscriber/listing-edit/?id=' + encodeURIComponent(id);

    /* "View live" only for live listings */
    if (status === 'live' && slug) {
      publicBtn.href = '/business/' + encodeURIComponent(slug) + '/';
      publicBtn.classList.remove('d-none');
    } else {
      publicBtn.classList.add('d-none');
      publicBtn.href = '#';
    }

    openPanel();

    fetch('/api/v1/subscriber/businesses/' + encodeURIComponent(id), { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok && d.business) {
          panelTitle.textContent = d.business.name || id;
          panelBody.innerHTML = renderBusiness(d.business);
        } else {
          panelBody.innerHTML = '<div class="alert alert-danger small m-0">Could not load listing details: ' + esc(d.error || 'unknown') + '</div>';
        }
      })
      .catch(function (err) {
        panelBody.innerHTML = '<div class="alert alert-danger small m-0">Network error: ' + esc(String(err)) + '</div>';
      });
  });

}());
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
