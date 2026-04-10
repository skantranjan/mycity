<?php
/**
 * Shared listings table partial for all CP listing sub-pages.
 *
 * Expects:
 *   $rows        array   — from api_business_list_cp()
 *   $total       int     — total matching records
 *   $pages       int     — total pages
 *   $curPage     int     — current page number
 *   $pageBase    string  — base URL for pagination + search, e.g. '/cp/listings/draft/'
 *   $flash       string  — 'type:message' or ''
 *   $csrfToken   string  — CSRF token (unused here, actions are JS fetch)
 *   $showStatus  bool    — whether to show the Status column (false on status-specific pages)
 *   $showRole    bool    — whether to show the Owner/Role column (false on role-specific pages)
 *   $q           string  — current search query
 */

$showStatus = $showStatus ?? true;
$showRole   = $showRole   ?? true;
$q          = $q          ?? '';
$pageBase   = rtrim($pageBase ?? '/cp/listings/', '/') . '/';

$statusBadgeMap = [
    'live'      => 'text-bg-success',
    'draft'     => 'text-bg-warning',
    'rejected'  => 'text-bg-danger',
    'suspended' => 'text-bg-secondary',
];
?>

<?php if ($flash !== ''): ?>
  <?php [$flashType, $flashMsg] = explode(':', $flash, 2); ?>
  <div class="alert alert-<?= $flashType === 'error' ? 'danger' : 'success' ?> py-2 small mb-3" role="status">
    <?= htmlspecialchars($flashMsg, ENT_QUOTES, 'UTF-8') ?>
  </div>
<?php endif; ?>

<!-- Search bar -->
<form method="get" action="" class="d-flex gap-2 mb-3">
  <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
    class="form-control form-control-sm" placeholder="Search by name or slug…" style="max-width:280px;" />
  <button class="btn btn-sm btn-outline-secondary" type="submit">
    <i class="bi bi-search" aria-hidden="true"></i>
  </button>
  <?php if ($q !== ''): ?>
    <a href="<?= htmlspecialchars($pageBase) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
  <?php endif; ?>
</form>

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0" style="font-size:var(--mci-text-sm);">
    <thead class="table-light">
      <tr>
        <th>Business</th>
        <?php if ($showRole): ?><th>Posted by</th><?php endif; ?>
        <th>Category</th>
        <?php if ($showStatus): ?><th>Status</th><?php endif; ?>
        <th>Added</th>
        <th style="min-width:100px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($rows) === 0): ?>
        <tr>
          <td colspan="<?= 4 + ($showStatus ? 1 : 0) + ($showRole ? 1 : 0) ?>" class="text-center text-muted py-4 small">
            <?= $q !== '' ? 'No listings match your search.' : 'No listings in this view.' ?>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $r):
          $rowStatus   = strtolower((string)($r['status'] ?? ''));
          $statusBadge = $statusBadgeMap[$rowStatus] ?? 'text-bg-light border';
          $addedByRole = (string)($r['added_by_role'] ?? '');
          $bizId       = htmlspecialchars((string)$r['id'], ENT_QUOTES, 'UTF-8');
          $bizName     = htmlspecialchars((string)($r['name'] ?? ''), ENT_QUOTES, 'UTF-8');
          $bizSlug     = (string)($r['slug'] ?? '');
        ?>
          <tr data-listing-id="<?= $bizId ?>" data-listing-status="<?= htmlspecialchars($rowStatus) ?>">
            <td>
              <button type="button" class="btn btn-link p-0 text-start fw-semibold js-review-btn"
                data-id="<?= $bizId ?>" style="text-decoration:none;color:inherit;">
                <?= htmlspecialchars((string)($r['name'] ?? '')) ?>
              </button>
              <?php if ($bizSlug): ?>
                <div class="text-muted" style="font-size:var(--mci-text-micro);"><?= htmlspecialchars($bizSlug) ?></div>
              <?php endif; ?>
            </td>
            <?php if ($showRole): ?>
              <td>
                <?php if ($addedByRole === 'anonymous'): ?>
                  <span class="badge text-bg-light border"><i class="bi bi-incognito me-1" aria-hidden="true"></i>Anonymous</span>
                <?php elseif ($addedByRole === 'cp_admin'): ?>
                  <span class="badge text-bg-primary"><i class="bi bi-shield-fill me-1" aria-hidden="true"></i>Admin</span>
                <?php else: ?>
                  <span class="badge text-bg-info text-dark"><i class="bi bi-person-fill me-1" aria-hidden="true"></i>Subscriber</span>
                <?php endif; ?>
              </td>
            <?php endif; ?>
            <td class="text-muted small"><?= htmlspecialchars((string)($r['category_name'] ?? '—')) ?></td>
            <?php if ($showStatus): ?>
              <td><span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($rowStatus)) ?></span></td>
            <?php endif; ?>
            <td class="text-muted small text-nowrap">
              <?= $r['created_at'] ? htmlspecialchars(date('M j, Y', strtotime((string)$r['created_at']))) : '—' ?>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-outline-secondary py-0 js-review-btn"
                data-id="<?= $bizId ?>">
                <i class="bi bi-eye" aria-hidden="true"></i> Review
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination + count -->
<div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
  <div class="text-muted small">
    Showing <?= number_format(count($rows)) ?> of <?= number_format((int)$total) ?> listings
  </div>
  <?php if ($pages > 1): ?>
    <nav aria-label="Listings pages">
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $curPage <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $curPage - 1 ?>">
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
          </a>
        </li>
        <?php for ($p = max(1, $curPage - 2); $p <= min($pages, $curPage + 2); $p++): ?>
          <li class="page-item <?= $p === $curPage ? 'active' : '' ?>">
            <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $p ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $curPage >= $pages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= htmlspecialchars($pageBase) ?>?<?= $q ? 'q=' . urlencode($q) . '&' : '' ?>page=<?= $curPage + 1 ?>">
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
          </a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<!-- ── Right flyout panel ──────────────────────────────────────────────── -->
<style>
#cpReviewOverlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.35);
  z-index: 1040;
}
#cpReviewOverlay.active { display: block; }

#cpReviewPanel {
  position: fixed;
  top: 0;
  right: 0;
  width: 42%;
  min-width: 340px;
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
#cpReviewPanel.active { transform: translateX(0); }

#cpReviewPanelHead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #dee2e6;
  flex-shrink: 0;
}
#cpReviewPanelBody {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}
#cpReviewPanelFoot {
  display: flex;
  gap: .5rem;
  padding: .875rem 1.25rem;
  border-top: 1px solid #dee2e6;
  flex-shrink: 0;
  flex-wrap: wrap;
}
@media (max-width: 767px) {
  #cpReviewPanel { width: 100%; max-width: 100%; }
}
.cp-review-section-head {
  font-size: .7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #6c757d;
  margin-bottom: .4rem;
}
</style>

<div id="cpReviewOverlay"></div>
<div id="cpReviewPanel" role="dialog" aria-labelledby="cpReviewPanelTitle" aria-modal="true">
  <div id="cpReviewPanelHead">
    <span id="cpReviewPanelTitle" class="fw-semibold" style="font-size:1rem;">Review listing</span>
    <button type="button" id="cpReviewPanelClose" class="btn-close" aria-label="Close"></button>
  </div>
  <div id="cpReviewPanelBody">
    <div class="text-center py-5 text-muted small">Loading…</div>
  </div>
  <div id="cpReviewPanelFoot">
    <button type="button" id="cpReviewPanelCloseBtn" class="btn btn-sm btn-outline-secondary me-auto">Close</button>
    <button type="button" id="cpReviewSuspendBtn" class="btn btn-sm btn-outline-warning d-none">
      <i class="bi bi-pause-fill" aria-hidden="true"></i> Suspend
    </button>
    <button type="button" id="cpReviewRejectBtn" class="btn btn-sm btn-outline-danger d-none">
      <i class="bi bi-x-lg" aria-hidden="true"></i> Reject
    </button>
    <button type="button" id="cpReviewApproveBtn" class="btn btn-sm btn-success d-none">
      <i class="bi bi-check2" aria-hidden="true"></i> Approve
    </button>
  </div>
</div>

<!-- Styled confirm dialog (approve / reject / suspend) -->
<div id="cpConfirmWrap" style="display:none;position:fixed;inset:0;z-index:1060;background:rgba(15,23,42,.5);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:1rem;width:100%;max-width:440px;margin:1rem;box-shadow:0 20px 60px rgba(15,23,42,.18);overflow:hidden;">
    <!-- Header -->
    <div id="cpConfirmHeader" style="padding:1.1rem 1.4rem .9rem;display:flex;align-items:center;gap:.75rem;border-bottom:1px solid #e2e8f0;">
      <span id="cpConfirmIcon" style="width:2.25rem;height:2.25rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;"></span>
      <div>
        <div id="cpConfirmTitle" style="font-weight:700;font-size:1rem;color:#0f172a;line-height:1.3;">Confirm action</div>
        <div id="cpConfirmBizName" style="font-size:.8rem;color:#64748b;margin-top:.15rem;"></div>
      </div>
      <button type="button" id="cpConfirmClose" class="btn-close ms-auto" aria-label="Close"></button>
    </div>
    <!-- Body -->
    <div style="padding:1.1rem 1.4rem;">
      <p id="cpConfirmDesc" class="small text-muted mb-3"></p>
      <div id="cpConfirmNotesWrap">
        <label for="cpConfirmNotes" class="form-label small fw-semibold mb-1">Notes <span class="text-muted fw-normal">(optional)</span></label>
        <textarea class="form-control form-control-sm" id="cpConfirmNotes" rows="2" placeholder="Add a reason or note…" style="resize:none;"></textarea>
      </div>
    </div>
    <!-- Footer -->
    <div style="padding:.85rem 1.4rem;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;gap:.5rem;justify-content:flex-end;align-items:center;">
      <button type="button" id="cpConfirmCancel" class="btn btn-sm btn-outline-secondary px-3">Cancel</button>
      <button type="button" id="cpConfirmOk" class="btn btn-sm px-4 fw-semibold">Confirm</button>
    </div>
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

  /* ── API ── */
  function cpApi(id, action, notes) {
    return fetch('/api/v1/cp/businesses/' + encodeURIComponent(id) + '/' + action, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notes: notes || '' })
    }).then(function (r) { return r.json(); });
  }

  /* ── flyout panel ── */
  var overlay     = document.getElementById('cpReviewOverlay');
  var panel       = document.getElementById('cpReviewPanel');
  var panelTitle  = document.getElementById('cpReviewPanelTitle');
  var panelBody   = document.getElementById('cpReviewPanelBody');
  var approveBtn  = document.getElementById('cpReviewApproveBtn');
  var suspendBtn  = document.getElementById('cpReviewSuspendBtn');
  var rejectBtn   = document.getElementById('cpReviewRejectBtn');
  var activeId    = null;
  var activeName  = null;

  function openPanel() {
    overlay.classList.add('active');
    panel.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closePanel() {
    overlay.classList.remove('active');
    panel.classList.remove('active');
    document.body.style.overflow = '';
    activeId = null;
  }

  document.getElementById('cpReviewPanelClose').addEventListener('click', closePanel);
  document.getElementById('cpReviewPanelCloseBtn').addEventListener('click', closePanel);
  overlay.addEventListener('click', closePanel);

  /* ── render ── */
  function section(title) {
    return '<div class="cp-review-section-head">' + title + '</div>';
  }
  function priceStr(mn, mx, unit) {
    if (!mn && !mx) return null;
    var s = mn ? mn : '';
    if (mx && mx !== mn) s += (s ? ' – ' : '') + mx;
    if (unit) s += ' ' + unit;
    return s || null;
  }

  function renderBusiness(b) {
    var html = '';

    /* ── Logo / banner / profile (placeholders when missing) ── */
    var phLogo    = '/assets/images/business-logo-placeholder.svg';
    var phBanner  = '/assets/images/business-banner-placeholder.svg';
    var phProfile = '/assets/images/business-profile-placeholder.svg';
    html += '<div class="d-flex gap-2 mb-3 flex-wrap">';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Logo</div><img src="' + esc(b.logo_path || phLogo) + '" alt="logo" style="height:56px;max-width:160px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;background:#f8f9fa;padding:4px;"></div>';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Banner</div><img src="' + esc(b.banner_path || phBanner) + '" alt="banner" style="height:56px;max-width:200px;object-fit:cover;border-radius:4px;"></div>';
    html += '<div><div class="text-muted mb-1" style="font-size:.75rem;">Profile</div><img src="' + esc(b.profile_path || phProfile) + '" alt="profile" style="height:56px;width:56px;object-fit:cover;border:1px solid #dee2e6;border-radius:50%;background:#f8f9fa;"></div>';
    html += '</div>';

    /* ── Business overview ── */
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
      + row('Status',       b.status)
      + row('Added by',     b.added_by_role)
      + row('Submitted',    b.created_at)
      + '</table>';

    /* ── Description ── */
    if (b.description) {
      html += '<div class="mt-2 mb-1"><span class="text-muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Description</span></div>'
        + '<p class="small mb-0" style="white-space:pre-wrap;line-height:1.5;">' + esc(b.description) + '</p>';
    }

    /* ── Tags ── */
    if ((b.tags || []).length) {
      html += '<div class="mt-2"><span class="text-muted" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;">Tags</span></div>'
        + '<div class="mt-1">'
        + b.tags.map(function(t){ return '<span class="badge text-bg-light border me-1 mb-1">' + esc(t.name) + '</span>'; }).join('')
        + '</div>';
    }

    /* ── Branches ── */
    var branches = b.branches || [];
    if (branches.length) {
      html += '<hr class="my-3">' + section('Location & contact' + (branches.length > 1 ? ' (' + branches.length + ' branches)' : ''));
      branches.forEach(function (br, i) {
        if (branches.length > 1) {
          html += '<div class="fw-semibold small mb-1 mt-2">'
            + (br.is_primary == 1 ? '<span class="badge text-bg-primary me-1" style="font-size:.7rem;">Primary</span>' : '')
            + 'Branch ' + (i + 1) + (br.branch_label ? ' — ' + esc(br.branch_label) : '')
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

    /* ── Social links ── */
    if ((b.social_links || []).length) {
      html += '<hr class="my-3">' + section('Social media');
      html += '<table class="table table-sm table-borderless mb-0" style="font-size:.85rem;">';
      b.social_links.forEach(function(s) {
        var label = s.label ? esc(s.label) : esc(s.platform);
        html += '<tr><th class="text-muted fw-normal pe-3 align-top" style="width:110px;text-transform:capitalize;">'
          + esc(s.platform) + '</th><td>'
          + '<a href="' + esc(s.url) + '" target="_blank" rel="noopener" style="word-break:break-all;">' + esc(s.url) + '</a>'
          + (s.label ? ' <span class="text-muted">(' + esc(s.label) + ')</span>' : '')
          + '</td></tr>';
      });
      html += '</table>';
    }

    /* ── Products ── */
    if ((b.products || []).length) {
      html += '<hr class="my-3">' + section('Products (' + b.products.length + ')');
      html += '<ul class="list-unstyled mb-0">';
      b.products.forEach(function(p) {
        var price = priceStr(p.price_min, p.price_max, p.price_unit);
        html += '<li class="py-2 border-bottom">'
          + '<div class="d-flex gap-2 align-items-start">';
        if (p.image_path) html += '<img src="' + esc(p.image_path) + '" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
        html += '<div><div class="fw-semibold small">' + esc(p.name) + '</div>'
          + (price ? '<div class="text-muted small">' + esc(price) + '</div>' : '')
          + (p.description ? '<div class="text-muted small mt-1">' + esc(p.description) + '</div>' : '')
          + '</div></div></li>';
      });
      html += '</ul>';
    }

    /* ── Services ── */
    if ((b.services || []).length) {
      html += '<hr class="my-3">' + section('Services (' + b.services.length + ')');
      html += '<ul class="list-unstyled mb-0">';
      b.services.forEach(function(s) {
        var price = priceStr(s.price_min, s.price_max, s.price_unit);
        html += '<li class="py-2 border-bottom">'
          + '<div class="d-flex gap-2 align-items-start">';
        if (s.image_path) html += '<img src="' + esc(s.image_path) + '" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:4px;flex-shrink:0;">';
        html += '<div><div class="fw-semibold small">' + esc(s.name) + '</div>'
          + (price ? '<div class="text-muted small">' + esc(price) + '</div>' : '')
          + (s.description ? '<div class="text-muted small mt-1">' + esc(s.description) + '</div>' : '')
          + '</div></div></li>';
      });
      html += '</ul>';
    }

    /* ── FAQs ── */
    if ((b.faqs || []).length) {
      html += '<hr class="my-3">' + section('FAQs (' + b.faqs.length + ')');
      b.faqs.forEach(function(f) {
        html += '<div class="mb-2 small"><strong>' + esc(f.question) + '</strong>'
          + '<div class="text-muted mt-1">' + esc(f.answer) + '</div></div>';
      });
    }

    /* ── Gallery ── */
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
      var captions = b.images.filter(function(img){ return img.caption; });
      if (captions.length) {
        html += '<ul class="list-unstyled mt-2 small text-muted mb-0">';
        captions.forEach(function(img){ html += '<li>' + esc(img.caption) + '</li>'; });
        html += '</ul>';
      }
    }

    /* ── SEO / meta ── */
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

  /* ── open on Review button or business name click ── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-review-btn');
    if (!btn) return;

    var id  = btn.dataset.id;
    var tr  = document.querySelector('tr[data-listing-id="' + id + '"]');
    var status = tr ? (tr.dataset.listingStatus || '') : '';
    var name = tr ? (tr.querySelector('.js-review-btn')?.textContent.trim() || id) : id;

    activeId   = id;
    activeName = name;
    panelTitle.textContent = name;
    panelBody.innerHTML = '<div class="text-center py-5 text-muted small">Loading…</div>';

    var isDraft = status === 'draft';
    var isLive = status === 'live';
    var isSuspended = status === 'suspended' || status === 'rejected';
    approveBtn.classList.toggle('d-none', !(isDraft || isSuspended));
    suspendBtn.classList.toggle('d-none', !isLive);
    rejectBtn.classList.toggle('d-none', !isDraft);
    approveBtn.textContent = isSuspended ? 'Restore' : 'Approve';

    openPanel();

    fetch('/api/v1/cp/businesses/' + encodeURIComponent(id), { credentials: 'include' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.ok && d.business) {
          panelTitle.textContent = d.business.name || id;
          activeName = d.business.name || id;
          panelBody.innerHTML = renderBusiness(d.business);
        } else {
          panelBody.innerHTML = '<div class="alert alert-danger small m-0">Could not load listing details: ' + esc(d.error || 'unknown') + '</div>';
        }
      })
      .catch(function (err) {
        panelBody.innerHTML = '<div class="alert alert-danger small m-0">Network error: ' + esc(String(err)) + '</div>';
      });
  });

  /* ── approve from panel ── */
  approveBtn.addEventListener('click', function () {
    if (!activeId) return;
    var selectedId = activeId;
    var selectedName = activeName;
    var isRestore = approveBtn.textContent.trim() === 'Restore';
    closePanel();
    openConfirm('approve', selectedId, selectedName, isRestore);
  });

  /* ── confirm dialog ── */
  var confirmWrap  = document.getElementById('cpConfirmWrap');
  var confirmTitle = document.getElementById('cpConfirmTitle');
  var confirmBiz   = document.getElementById('cpConfirmBizName');
  var confirmNotes = document.getElementById('cpConfirmNotes');
  var confirmOk    = document.getElementById('cpConfirmOk');
  var pendingConfirm = null;

  function openConfirm(action, id, name, isRestore) {
    pendingConfirm = { action: action, id: id };
    confirmNotes.value = '';

    var icon    = document.getElementById('cpConfirmIcon');
    var header  = document.getElementById('cpConfirmHeader');
    var desc    = document.getElementById('cpConfirmDesc');
    var notesWrap = document.getElementById('cpConfirmNotesWrap');

    if (action === 'approve') {
      var label = isRestore ? 'Restore listing' : 'Approve listing';
      confirmTitle.textContent = label;
      confirmBiz.textContent   = name;
      desc.textContent         = isRestore
        ? 'This will restore the listing and make it live again.'
        : 'This will publish the listing and make it visible to the public.';
      icon.innerHTML    = '<i class="bi bi-check-lg" style="color:#16a34a;"></i>';
      icon.style.background = 'rgba(22,163,74,.1)';
      confirmOk.textContent = isRestore ? 'Restore' : 'Approve';
      confirmOk.className   = 'btn btn-sm px-4 fw-semibold btn-success';
      notesWrap.style.display = 'none';
    } else if (action === 'reject') {
      confirmTitle.textContent = 'Reject listing';
      confirmBiz.textContent   = name;
      desc.textContent         = 'The listing will be rejected and the submitter will be notified.';
      icon.innerHTML    = '<i class="bi bi-x-lg" style="color:#dc2626;"></i>';
      icon.style.background = 'rgba(220,38,38,.1)';
      confirmOk.textContent = 'Reject';
      confirmOk.className   = 'btn btn-sm px-4 fw-semibold btn-danger';
      notesWrap.style.display = '';
    } else {
      confirmTitle.textContent = 'Suspend listing';
      confirmBiz.textContent   = name;
      desc.textContent         = 'The listing will be hidden from public view until restored.';
      icon.innerHTML    = '<i class="bi bi-pause-fill" style="color:#d97706;"></i>';
      icon.style.background = 'rgba(217,119,6,.1)';
      confirmOk.textContent = 'Suspend';
      confirmOk.className   = 'btn btn-sm px-4 fw-semibold btn-warning';
      notesWrap.style.display = '';
    }
    confirmWrap.style.display = 'flex';
  }
  function closeConfirm() {
    confirmWrap.style.display = 'none';
    pendingConfirm = null;
  }

  document.getElementById('cpConfirmClose').addEventListener('click', closeConfirm);
  document.getElementById('cpConfirmCancel').addEventListener('click', closeConfirm);
  confirmWrap.addEventListener('click', function (e) {
    if (e.target === confirmWrap) closeConfirm();
  });

  rejectBtn.addEventListener('click', function () {
    if (!activeId) return;
    var selectedId = activeId;
    var selectedName = activeName;
    closePanel();
    openConfirm('reject', selectedId, selectedName);
  });

  suspendBtn.addEventListener('click', function () {
    if (!activeId) return;
    var selectedId = activeId;
    var selectedName = activeName;
    closePanel();
    openConfirm('suspend', selectedId, selectedName);
  });

  confirmOk.addEventListener('click', function () {
    if (!pendingConfirm) return;
    var notes = confirmNotes.value.trim();
    confirmOk.disabled = true;
    cpApi(pendingConfirm.id, pendingConfirm.action, notes)
      .then(function (d) {
        if (d.ok) { closeConfirm(); location.reload(); }
        else { alert('Error: ' + (d.error || 'unknown')); confirmOk.disabled = false; }
      })
      .catch(function () { alert('Network error.'); confirmOk.disabled = false; });
  });

  // Deep-link support: /cp/listings/awaiting-approval/?review=<business_id>
  (function autoOpenReviewFromQuery() {
    try {
      var params = new URLSearchParams(window.location.search || '');
      var reviewId = params.get('review') || params.get('review_id');
      if (!reviewId) return;
      var targetBtn = document.querySelector('.js-review-btn[data-id="' + reviewId + '"]');
      if (!targetBtn) return;
      targetBtn.click();
    } catch (e) {
      // Ignore URL parsing errors; normal manual review still works.
    }
  }());

  /* ── close panel on Escape ── */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (confirmWrap.style.display === 'flex') { closeConfirm(); return; }
      if (panel.classList.contains('active')) { closePanel(); }
    }
  });

}());
</script>
