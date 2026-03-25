<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/mci_require_session.php';
require_once __DIR__ . '/../../../includes/mci_session.php';
require_once __DIR__ . '/../../../api/v1/lib/config.php';
require_once __DIR__ . '/../../../api/v1/lib/db.php';
require_once __DIR__ . '/../../../api/v1/lib/jwt.php';
require_once __DIR__ . '/../../../includes/mci_cp_jwt.php';

mci_require_cp_session();

$jwtForJs = mci_cp_ensure_jwt();
$pageTitle = 'Scraper Review Queue — My City Info CP';
$cpActive  = 'scraper';
$hideCta   = true;
$appArea   = 'cp';

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">

        <!-- Header -->
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div>
            <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">
              <a href="/cp/scraper/" class="text-decoration-none text-muted me-1" aria-label="Back to scraper">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
              </a>
              Review queue
            </div>
            <div class="text-muted small mt-1">Review scraped businesses before importing them as anonymous listings.</div>
          </div>
          <a href="/cp/scraper/" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
            <i class="bi bi-cloud-download" aria-hidden="true"></i>
            <span>New scrape</span>
          </a>
        </div>

        <!-- Status tabs -->
        <ul class="nav nav-tabs mb-3" id="mciStatusTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-status="" type="button">
              All <span class="badge text-bg-secondary ms-1" id="tabCountAll">—</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-status="pending_review" type="button">
              Pending review <span class="badge text-bg-warning ms-1" id="tabCountPending">—</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-status="imported" type="button">
              Imported <span class="badge text-bg-success ms-1" id="tabCountImported">—</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-status="rejected" type="button">
              Rejected <span class="badge text-bg-danger ms-1" id="tabCountRejected">—</span>
            </button>
          </li>
        </ul>

        <!-- Filters -->
        <div class="row g-2 mb-3">
          <div class="col-12 col-sm-4">
            <input type="text" id="mciFilterQ" class="form-control form-control-sm"
                   placeholder="Search name, address…" autocomplete="off">
          </div>
          <div class="col-12 col-sm-3">
            <input type="text" id="mciFilterCity" class="form-control form-control-sm"
                   placeholder="City" autocomplete="off">
          </div>
          <div class="col-12 col-sm-3">
            <select id="mciFilterSource" class="form-select form-select-sm">
              <option value="">All sources</option>
              <option value="osm">OpenStreetMap</option>
              <option value="tomtom">TomTom</option>
              <option value="here">HERE</option>
              <option value="google_places">Google Places</option>
              <option value="foursquare">Foursquare</option>
              <option value="curl_scrape">cURL / HTML</option>
            </select>
          </div>
          <div class="col-12 col-sm-2">
            <button type="button" id="mciFilterBtn" class="btn btn-sm btn-outline-secondary w-100">
              <i class="bi bi-funnel" aria-hidden="true"></i> Filter
            </button>
          </div>
        </div>

        <!-- Table -->
        <div id="mciQueueLoading" class="text-center py-4 text-muted small">
          <span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading…
        </div>
        <div id="mciQueueEmpty" class="text-center py-4 text-muted small d-none">No records found.</div>
        <div class="table-responsive d-none" id="mciQueueTableWrap">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Source</th>
                <th>City</th>
                <th>Category</th>
                <th>Phone</th>
                <th>Scraped</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="mciQueueTbody"></tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div id="mciQueuePager" class="d-flex justify-content-between align-items-center mt-3 d-none">
          <div class="text-muted small" id="mciQueuePagerInfo"></div>
          <div class="d-flex gap-2">
            <button type="button" id="mciPagerPrev" class="btn btn-sm btn-outline-secondary" disabled>
              <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" id="mciPagerNext" class="btn btn-sm btn-outline-secondary" disabled>
              <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Reject modal ─────────────────────────────────────────────────── -->
    <div class="modal fade" id="mciRejectModal" tabindex="-1" aria-labelledby="mciRejectModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="mciRejectModalLabel">Reject record</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <label for="mciRejectReason" class="form-label small fw-semibold">Reason (optional)</label>
            <input type="text" id="mciRejectReason" class="form-control form-control-sm"
                   placeholder="e.g. duplicate, irrelevant…">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-sm btn-danger" id="mciRejectConfirm">Reject</button>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /col -->
</div><!-- /row -->

<script>
(function () {
  'use strict';

  const API  = '/api/v1/cp/scraper';
  const JWT  = <?= json_encode($jwtForJs) ?>;
  const AUTH = JWT ? { 'Authorization': 'Bearer ' + JWT } : {};
  let currentPage   = 1;
  let currentStatus = '';
  let totalPages    = 1;
  let rejectTargetId = null;

  // ── Tab click ───────────────────────────────────────────────────────────
  document.querySelectorAll('#mciStatusTabs .nav-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('#mciStatusTabs .nav-link').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentStatus = btn.dataset.status || '';
      currentPage   = 1;
      loadList();
    });
  });

  // ── Filter button ───────────────────────────────────────────────────────
  document.getElementById('mciFilterBtn').addEventListener('click', function () {
    currentPage = 1;
    loadList();
  });
  ['mciFilterQ','mciFilterCity','mciFilterSource'].forEach(function (id) {
    document.getElementById(id).addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { currentPage = 1; loadList(); }
    });
  });

  // ── Pagination ──────────────────────────────────────────────────────────
  document.getElementById('mciPagerPrev').addEventListener('click', function () {
    if (currentPage > 1) { currentPage--; loadList(); }
  });
  document.getElementById('mciPagerNext').addEventListener('click', function () {
    if (currentPage < totalPages) { currentPage++; loadList(); }
  });

  // ── Load list ───────────────────────────────────────────────────────────
  async function loadList() {
    const q      = document.getElementById('mciFilterQ').value.trim();
    const city   = document.getElementById('mciFilterCity').value.trim();
    const source = document.getElementById('mciFilterSource').value;

    const params = new URLSearchParams({ page: currentPage, per_page: 25 });
    if (currentStatus) params.set('status', currentStatus);
    if (q)             params.set('q', q);
    if (city)          params.set('city', city);
    if (source)        params.set('source', source);

    document.getElementById('mciQueueLoading').classList.remove('d-none');
    document.getElementById('mciQueueTableWrap').classList.add('d-none');
    document.getElementById('mciQueueEmpty').classList.add('d-none');
    document.getElementById('mciQueuePager').classList.add('d-none');

    try {
      const res  = await fetch(API + '/results?' + params, { credentials: 'include', headers: AUTH });
      const json = await res.json();

      document.getElementById('mciQueueLoading').classList.add('d-none');

      if (!json.items || json.items.length === 0) {
        document.getElementById('mciQueueEmpty').classList.remove('d-none');
        return;
      }

      totalPages = Math.ceil(json.total / json.per_page) || 1;
      renderTable(json.items);
      updatePager(json.total, json.page, json.per_page);

      document.getElementById('mciQueueTableWrap').classList.remove('d-none');
      document.getElementById('mciQueuePager').classList.remove('d-none');

      // Update tab badge counts
      loadCounts();
    } catch (e) {
      document.getElementById('mciQueueLoading').classList.add('d-none');
      document.getElementById('mciQueueEmpty').textContent = 'Failed to load. Please refresh.';
      document.getElementById('mciQueueEmpty').classList.remove('d-none');
    }
  }

  async function loadCounts() {
    try {
      const res  = await fetch(API + '/counts', { credentials: 'include', headers: AUTH });
      const json = await res.json();
      document.getElementById('tabCountAll').textContent     = json.total        || 0;
      document.getElementById('tabCountPending').textContent = json.pending_review || 0;
      document.getElementById('tabCountImported').textContent= json.imported      || 0;
      document.getElementById('tabCountRejected').textContent= json.rejected      || 0;
    } catch (_) {}
  }

  const SOURCE_BADGES = {
    osm:           '<span class="badge text-bg-info">OSM</span>',
    tomtom:        '<span class="badge text-bg-primary">TomTom</span>',
    here:          '<span class="badge" style="background:#0acc85;color:#fff;">HERE</span>',
    google_places: '<span class="badge text-bg-warning">Google</span>',
    foursquare:    '<span class="badge" style="background:#f94877;color:#fff;">4sq</span>',
    curl_scrape:   '<span class="badge text-bg-secondary">cURL</span>',
  };
  const STATUS_BADGES = {
    pending_review: '<span class="badge text-bg-warning">Pending</span>',
    imported:       '<span class="badge text-bg-success">Imported</span>',
    rejected:       '<span class="badge text-bg-danger">Rejected</span>',
  };

  function renderTable(items) {
    const tbody = document.getElementById('mciQueueTbody');
    tbody.innerHTML = items.map(function (r) {
      const srcBadge = SOURCE_BADGES[r.source] || `<span class="badge text-bg-secondary">${esc(r.source)}</span>`;
      const stBadge  = STATUS_BADGES[r.status] || `<span class="badge text-bg-secondary">${esc(r.status)}</span>`;
      const date     = r.created_at ? r.created_at.substring(0, 10) : '—';

      let actions = '';
      if (r.status === 'pending_review') {
        actions = `<a href="/cp/scraper/review/?id=${encodeURIComponent(r.id)}"
                     class="btn btn-xs btn-outline-primary btn-sm me-1">Review</a>
                   <button type="button" class="btn btn-xs btn-outline-danger btn-sm"
                     data-reject-id="${esc(r.id)}">Reject</button>`;
      } else if (r.status === 'imported') {
        actions = '<span class="text-muted small">Imported</span>';
      }

      return `<tr>
        <td><a href="/cp/scraper/review/?id=${encodeURIComponent(r.id)}" class="text-decoration-none fw-semibold">${esc(r.name)}</a></td>
        <td>${srcBadge}</td>
        <td class="text-muted small">${esc(r.city || '—')}</td>
        <td class="text-muted small">${esc(r.category_hint || '—')}</td>
        <td class="small">${esc(r.phone || '—')}</td>
        <td class="text-muted small">${date}</td>
        <td>${stBadge}</td>
        <td>${actions}</td>
      </tr>`;
    }).join('');

    // Reject button listeners
    tbody.querySelectorAll('[data-reject-id]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        rejectTargetId = btn.dataset.rejectId;
        document.getElementById('mciRejectReason').value = '';
        new bootstrap.Modal(document.getElementById('mciRejectModal')).show();
      });
    });
  }

  function updatePager(total, page, perPage) {
    totalPages = Math.ceil(total / perPage) || 1;
    const from = (page - 1) * perPage + 1;
    const to   = Math.min(page * perPage, total);
    document.getElementById('mciQueuePagerInfo').textContent = `${from}–${to} of ${total}`;
    document.getElementById('mciPagerPrev').disabled = page <= 1;
    document.getElementById('mciPagerNext').disabled = page >= totalPages;
  }

  // ── Reject confirm ──────────────────────────────────────────────────────
  document.getElementById('mciRejectConfirm').addEventListener('click', async function () {
    if (!rejectTargetId) return;
    const reason = document.getElementById('mciRejectReason').value.trim() || null;

    try {
      const res  = await fetch(`${API}/results/${encodeURIComponent(rejectTargetId)}/reject`, {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', ...AUTH },
        body:        JSON.stringify({ reason }),
      });
      const json = await res.json();
      bootstrap.Modal.getInstance(document.getElementById('mciRejectModal'))?.hide();
      if (json.ok) {
        loadList();
      }
    } catch (_) {}
  });

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Init ───────────────────────────────────────────────────────────────
  loadList();
  loadCounts();
}());
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../../views/layout.php';
?>
