<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/config.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';
require_once __DIR__ . '/../../api/v1/lib/scraper_service.php';
require_once __DIR__ . '/../../api/v1/lib/jwt.php';
require_once __DIR__ . '/../../includes/mci_cp_jwt.php';

mci_require_cp_session();

$pdo            = api_db();
$adapterStatus  = scraper_adapter_status($pdo);
$scraperCounts  = scraper_counts($pdo);
$adapterStatusJson = json_encode($adapterStatus, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Ensure a valid JWT exists (re-issues from session if cookie is missing/expired)
$jwtForJs = mci_cp_ensure_jwt();

$pageTitle = 'Business Scraper — My City Info CP';
$cpActive  = 'scraper';
$hideCta   = true;
$appArea   = 'cp';

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- ── Usage alerts (JS-rendered) ─────────────────────────────────── -->
    <div id="mciScraperAlerts"></div>

    <!-- ── Page header ─────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div>
            <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Business Scraper</div>
            <div class="text-muted small mt-1">Search for businesses from external sources and import them as anonymous listings.</div>
          </div>
          <a href="/cp/scraper/results/" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span>Review queue</span>
            <?php if (($scraperCounts['pending_review'] ?? 0) > 0): ?>
              <span class="badge text-bg-warning ms-1"><?= (int)$scraperCounts['pending_review'] ?></span>
            <?php endif; ?>
          </a>
        </div>

        <!-- Source status cards (collapsible accordion) -->
        <div class="mb-4">
          <button class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-2 w-100 collapsed px-3 py-2 rounded"
                  type="button" data-bs-toggle="collapse" data-bs-target="#mciSourceCardsCollapse"
                  aria-expanded="false" aria-controls="mciSourceCardsCollapse"
                  style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--bs-secondary-color);background:var(--bs-tertiary-bg);">
            <span>Data sources — this month's usage</span>
            <i class="bi bi-chevron-down ms-auto" style="font-size:0.7rem;transition:transform .2s;" id="mciSourceCardsChevron"></i>
          </button>
          <div class="collapse" id="mciSourceCardsCollapse">
            <div id="mciSourceCards" class="row g-2 mt-2">
              <div class="col-12 text-muted small py-3 text-center">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span> Loading usage…
              </div>
            </div>
          </div>
        </div>

        <!-- ── Search form ─────────────────────────────────────────────── -->
        <form id="mciScraperForm" novalidate>
          <div class="row g-3">
            <div class="col-12 col-md-5">
              <label for="mciScraperQ" class="form-label fw-semibold">Search query</label>
              <input type="text" id="mciScraperQ" name="q" class="form-control"
                     placeholder="e.g. IT companies, restaurants, hotels" autocomplete="off">
            </div>
            <div class="col-12 col-md-4">
              <label for="mciScraperCity" class="form-label fw-semibold">City / area</label>
              <input type="text" id="mciScraperCity" name="city" class="form-control"
                     placeholder="e.g. Pune, Mumbai, Bangalore" autocomplete="off">
            </div>
            <div class="col-12 col-md-3">
              <label for="mciScraperSource" class="form-label fw-semibold">Data source</label>
              <select id="mciScraperSource" name="source" class="form-select">
                <!-- Populated by JS after usage data loads -->
                <option value="auto">Auto (best available)</option>
                <option value="osm">OpenStreetMap (Free)</option>
              </select>
            </div>
          </div>

          <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
            <button type="submit" id="mciScraperBtn" class="btn btn-primary d-flex align-items-center gap-2">
              <i class="bi bi-cloud-download" aria-hidden="true"></i>
              <span>Scrape</span>
            </button>
            <div id="mciScraperSpinner" class="d-none">
              <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span>
              <span class="text-muted small">Searching…</span>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- ── Search results preview ──────────────────────────────────────── -->
    <div id="mciScraperResults" class="d-none">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
            <div class="fw-semibold" id="mciResultsHeading">Results</div>
            <a href="/cp/scraper/results/" class="btn btn-sm btn-outline-primary">
              Go to review queue <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
          <div id="mciResultsSummary" class="alert alert-success py-2 small mb-3 d-none"></div>
          <div id="mciResultsError"   class="alert alert-danger  py-2 small mb-3 d-none"></div>
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>City</th>
                  <th>Category hint</th>
                  <th>Phone</th>
                  <th>Hours</th>
                </tr>
              </thead>
              <tbody id="mciResultsTbody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /col -->
</div><!-- /row -->

<script>
(function () {
  'use strict';

  const API = '/api/v1/cp/scraper';
  const JWT = <?= json_encode($jwtForJs) ?>;
  const AUTH = JWT ? { 'Authorization': 'Bearer ' + JWT } : {};

  // ── Adapter data injected server-side ──────────────────────────────────
  const ADAPTER_DATA = <?= $adapterStatusJson ?>;

  // ── Chevron rotation for source cards accordion ────────────────────────
  document.getElementById('mciSourceCardsCollapse').addEventListener('show.bs.collapse', function () {
    document.getElementById('mciSourceCardsChevron').style.transform = 'rotate(180deg)';
  });
  document.getElementById('mciSourceCardsCollapse').addEventListener('hide.bs.collapse', function () {
    document.getElementById('mciSourceCardsChevron').style.transform = 'rotate(0deg)';
  });

  function loadUsage() {
    renderSourceCards(ADAPTER_DATA);
    populateSourceDropdown(ADAPTER_DATA);
    renderAlerts(ADAPTER_DATA);
  }

  // Refresh usage after a search (re-fetch from API)
  async function reloadUsage() {
    try {
      const res  = await fetch(API + '/usage', { credentials: 'include', headers: AUTH });
      const json = await res.json();
      renderSourceCards(json.adapters || []);
      populateSourceDropdown(json.adapters || []);
      renderAlerts(json.adapters || []);
    } catch (_) {}
  }

  function renderSourceCards(adapters) {
    const container = document.getElementById('mciSourceCards');
    if (!adapters.length) {
      container.innerHTML = '<div class="col-12 text-muted small">No adapters found.</div>';
      return;
    }

    const SOURCE_LABELS = {
      osm:           { label: 'OpenStreetMap', tier: 'Free · Unlimited', icon: 'bi-geo-alt' },
      tomtom:        { label: 'TomTom Places', tier: 'Free · 75,000/mo', icon: 'bi-map' },
      here:          { label: 'HERE Places',   tier: 'Free · 250,000/mo', icon: 'bi-pin-map' },
      google_places: { label: 'Google Places', tier: 'Paid · 28,500/mo',  icon: 'bi-google' },
      foursquare:    { label: 'Foursquare',    tier: 'Free · 30,000/mo', icon: 'bi-geo-alt-fill' },
      curl_scrape:   { label: 'cURL / HTML',   tier: 'Custom target',    icon: 'bi-code-slash' },
    };

    container.innerHTML = adapters.map(function (a) {
      const meta    = SOURCE_LABELS[a.source] || { label: a.source, tier: '', icon: 'bi-plug' };
      const pct     = a.usage_pct;
      const isUnlim = a.monthly_limit === null;
      let statusDot, cardClass = '';

      if (!a.available) {
        statusDot = '<span class="mci-scraper-dot dot-grey" title="Not configured"></span>';
      } else if (a.at_limit) {
        statusDot = '<span class="mci-scraper-dot dot-red" title="Limit reached"></span>';
        cardClass = 'border-danger';
      } else if (a.near_limit) {
        statusDot = '<span class="mci-scraper-dot dot-yellow" title="Approaching limit"></span>';
        cardClass = 'border-warning';
      } else {
        statusDot = '<span class="mci-scraper-dot dot-green" title="Available"></span>';
      }

      let usageBar = '';
      if (a.available && !isUnlim && a.monthly_limit > 0) {
        const barColor = pct >= 80 ? 'bg-danger' : pct >= 60 ? 'bg-warning' : 'bg-success';
        usageBar = `<div class="progress mt-2" style="height:4px;">
          <div class="progress-bar ${barColor}" style="width:${Math.min(pct,100)}%;"></div>
        </div>
        <div class="text-muted mt-1" style="font-size:0.72rem;">
          ${a.call_count.toLocaleString()} / ${a.monthly_limit.toLocaleString()} calls (${pct}%)
        </div>`;
      } else if (a.available && isUnlim) {
        usageBar = `<div class="text-muted mt-1" style="font-size:0.72rem;">${a.call_count.toLocaleString()} calls this month</div>`;
      } else {
        usageBar = '<div class="text-muted mt-1" style="font-size:0.72rem;">Not configured</div>';
      }

      return `<div class="col-12 col-sm-6 col-xl-4">
        <div class="card border h-100 ${cardClass}" style="border-radius:8px;">
          <div class="card-body p-3">
            <div class="d-flex align-items-center gap-2 mb-1">
              ${statusDot}
              <span class="fw-semibold small">${meta.label}</span>
              <span class="ms-auto text-muted small">${meta.tier}</span>
            </div>
            ${usageBar}
          </div>
        </div>
      </div>`;
    }).join('');
  }

  function populateSourceDropdown(adapters) {
    const sel = document.getElementById('mciScraperSource');
    sel.innerHTML = '';

    const addOpt = (value, label, disabled) => {
      const opt = document.createElement('option');
      opt.value    = value;
      opt.textContent = label;
      opt.disabled = disabled;
      sel.appendChild(opt);
    };

    addOpt('auto', 'Auto (best available)', false);

    const SOURCE_LABELS = {
      osm:           'OpenStreetMap (Free)',
      tomtom:        'TomTom Places (Free tier)',
      here:          'HERE Places (Free tier)',
      google_places: 'Google Places (Paid)',
      foursquare:    'Foursquare (Free tier)',
      curl_scrape:   'cURL / HTML fallback',
    };

    adapters.forEach(function (a) {
      const label = SOURCE_LABELS[a.source] || a.source;
      if (!a.available) {
        addOpt(a.source, label + ' — Not configured', true);
      } else if (a.at_limit) {
        addOpt(a.source, label + ' — Monthly limit reached', true);
      } else {
        const callInfo = a.monthly_limit ? ` (${a.call_count.toLocaleString()}/${a.monthly_limit.toLocaleString()} used)` : '';
        addOpt(a.source, label + callInfo, false);
      }
    });
  }

  function renderAlerts(adapters) {
    const container = document.getElementById('mciScraperAlerts');
    const warnings  = adapters.filter(a => a.available && a.near_limit && !a.at_limit);
    const errors    = adapters.filter(a => a.available && a.at_limit);
    let html = '';

    errors.forEach(function (a) {
      html += `<div class="alert alert-danger d-flex gap-2 align-items-start mb-2" role="alert">
        <i class="bi bi-x-circle-fill mt-1 flex-shrink-0"></i>
        <div><strong>${a.source}</strong> has reached its monthly limit (${(a.call_count || 0).toLocaleString()} calls).
        Switch to OpenStreetMap for the rest of the month.</div>
      </div>`;
    });
    warnings.forEach(function (a) {
      html += `<div class="alert alert-warning d-flex gap-2 align-items-start mb-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
        <div><strong>${a.source}</strong> is at ${a.usage_pct}% of its monthly limit
        (${(a.call_count || 0).toLocaleString()} / ${(a.monthly_limit || 0).toLocaleString()} calls).
        Consider switching to OpenStreetMap.</div>
      </div>`;
    });

    container.innerHTML = html ? `<div class="mb-3">${html}</div>` : '';
  }

  // ── Search form submission ──────────────────────────────────────────────
  document.getElementById('mciScraperForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const q      = document.getElementById('mciScraperQ').value.trim();
    const city   = document.getElementById('mciScraperCity').value.trim();
    const source = document.getElementById('mciScraperSource').value;

    if (!q && !city) {
      document.getElementById('mciScraperQ').classList.add('is-invalid');
      return;
    }
    document.getElementById('mciScraperQ').classList.remove('is-invalid');

    const btn     = document.getElementById('mciScraperBtn');
    const spinner = document.getElementById('mciScraperSpinner');
    btn.disabled  = true;
    spinner.classList.remove('d-none');

    const resultsDiv = document.getElementById('mciScraperResults');
    const summaryEl  = document.getElementById('mciResultsSummary');
    const errorEl    = document.getElementById('mciResultsError');
    const tbody      = document.getElementById('mciResultsTbody');

    summaryEl.classList.add('d-none');
    errorEl.classList.add('d-none');
    tbody.innerHTML = '';

    try {
      const res  = await fetch(API + '/search', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', ...AUTH },
        body:        JSON.stringify({ q, city, source }),
      });
      const json = await res.json();

      resultsDiv.classList.remove('d-none');

      if (!json.ok) {
        errorEl.textContent = 'Error: ' + (json.error || 'Unknown error');
        errorEl.classList.remove('d-none');
      } else {
        const src = json.source_used || source;
        document.getElementById('mciResultsHeading').textContent =
          `Results via ${src} — ${json.inserted} new, ${json.skipped_dups} duplicates skipped`;

        summaryEl.innerHTML = `<i class="bi bi-check-circle me-1"></i>
          Found <strong>${json.inserted}</strong> new businesses via <strong>${src}</strong>.
          ${json.skipped_dups ? `${json.skipped_dups} duplicate(s) skipped.` : ''}
          <a href="/cp/scraper/results/" class="ms-2">View in review queue →</a>`;
        summaryEl.classList.remove('d-none');

        if (json.results && json.results.length) {
          tbody.innerHTML = json.results.map(function (r) {
            return `<tr>
              <td><a href="/cp/scraper/review/?id=${encodeURIComponent(r.id)}" class="text-decoration-none fw-semibold">${esc(r.name)}</a></td>
              <td class="text-muted small">${esc(r.city || '—')}</td>
              <td class="text-muted small">${esc(r.category_hint || '—')}</td>
              <td class="small">${esc(r.phone || '—')}</td>
              <td>${r.has_hours ? '<span class="badge text-bg-success">✓</span>' : '<span class="text-muted">—</span>'}</td>
            </tr>`;
          }).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-3">No new businesses found.</td></tr>';
        }

        // Refresh usage after search
        reloadUsage();
      }
    } catch (err) {
      resultsDiv.classList.remove('d-none');
      errorEl.textContent = 'Network error. Please try again.';
      errorEl.classList.remove('d-none');
    } finally {
      btn.disabled = false;
      spinner.classList.add('d-none');
    }
  });

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Init ───────────────────────────────────────────────────────────────
  loadUsage();
}());
</script>

<style>
.mci-scraper-dot {
  display: inline-block;
  width: 10px; height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.dot-green  { background: #22c55e; }
.dot-yellow { background: #f59e0b; }
.dot-red    { background: #ef4444; }
.dot-grey   { background: #9ca3af; }
</style>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
