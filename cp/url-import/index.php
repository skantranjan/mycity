<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../api/v1/lib/config.php';
require_once __DIR__ . '/../../api/v1/lib/db.php';
require_once __DIR__ . '/../../api/v1/lib/jwt.php';
require_once __DIR__ . '/../../includes/mci_cp_jwt.php';

mci_require_cp_session();

$jwtForJs  = mci_cp_ensure_jwt();
$pageTitle = 'Import from URLs — My City Info CP';
$cpActive  = 'url-import';
$hideCta   = true;
$appArea   = 'cp';

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">

    <!-- ── Page header ──────────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-4">

        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
          <div>
            <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Import from URLs</div>
            <div class="text-muted small mt-1">Paste business URLs directly, or provide a directory page and let the tool discover listings automatically.</div>
          </div>
          <a href="/cp/scraper/results/" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
            <i class="bi bi-list-ul" aria-hidden="true"></i>
            <span>Review queue</span>
          </a>
        </div>

        <!-- ── Mode tabs ─────────────────────────────────────────────────── -->
        <ul class="nav nav-tabs mb-4" id="mciModeTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-url-list" data-bs-toggle="tab" data-bs-target="#paneUrlList"
                    type="button" role="tab">
              <i class="bi bi-list-check me-1" aria-hidden="true"></i> URL List
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-crawler" data-bs-toggle="tab" data-bs-target="#paneCrawler"
                    type="button" role="tab">
              <i class="bi bi-diagram-3 me-1" aria-hidden="true"></i> Directory Crawler
            </button>
          </li>
        </ul>

        <div class="tab-content">

          <!-- ── Tab 1: URL List ────────────────────────────────────────── -->
          <div class="tab-pane fade show active" id="paneUrlList" role="tabpanel">
            <div class="mb-3">
              <label for="mciUrlList" class="form-label fw-semibold">Business URLs <span class="text-muted fw-normal">(one per line)</span></label>
              <textarea id="mciUrlList" class="form-control font-monospace" rows="10"
                        placeholder="https://www.somerestaurant.com/&#10;https://www.hotel-abc.com/contact&#10;https://www.clinic-xyz.in/about"></textarea>
              <div class="form-text">Each URL should be a page for a single business. Pages with schema.org / JSON-LD markup give the best results.</div>
            </div>
          </div>

          <!-- ── Tab 2: Directory Crawler ──────────────────────────────── -->
          <div class="tab-pane fade" id="paneCrawler" role="tabpanel">
            <div class="mb-3">
              <label for="mciIndexUrl" class="form-label fw-semibold">Directory / listing page URL</label>
              <input type="url" id="mciIndexUrl" class="form-control"
                     placeholder="https://www.somedirectory.in/pune/restaurants/">
              <div class="form-text">The tool will find all business links on this page and crawl each one.</div>
            </div>
            <div class="row g-3">
              <div class="col-12 col-sm-7">
                <label for="mciPattern" class="form-label fw-semibold">Link pattern <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" id="mciPattern" class="form-control"
                       placeholder="e.g. /pune/ or /restaurant/">
                <div class="form-text">Only follow links whose URL contains this text.</div>
              </div>
              <div class="col-12 col-sm-5">
                <label for="mciLimit" class="form-label fw-semibold">Max pages to crawl</label>
                <input type="number" id="mciLimit" class="form-control" value="20" min="1" max="100">
              </div>
            </div>
          </div>

        </div><!-- /tab-content -->

        <!-- ── Submit ────────────────────────────────────────────────────── -->
        <div class="d-flex align-items-center gap-3 mt-4 flex-wrap">
          <button type="button" id="mciStartBtn" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="bi bi-cloud-download" aria-hidden="true"></i>
            <span>Start import</span>
          </button>
          <div id="mciStartSpinner" class="d-none text-muted small">
            <span class="spinner-border spinner-border-sm me-1" role="status"></span> Starting…
          </div>
          <div id="mciStartError" class="text-danger small d-none"></div>
        </div>

      </div>
    </div><!-- /form card -->

    <!-- ── Progress card (shown while running) ───────────────────────────── -->
    <div id="mciProgressCard" class="card border-0 shadow-sm mb-4 d-none">
      <div class="card-body p-4">

        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div class="fw-semibold">Import progress</div>
          <span id="mciJobStatus" class="badge text-bg-warning">Running</span>
        </div>

        <div class="progress mb-2" style="height:8px;">
          <div id="mciProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
               role="progressbar" style="width:0%"></div>
        </div>
        <div class="text-muted small mb-3" id="mciProgressText">0 / 0 URLs processed</div>

        <!-- Live log feed -->
        <div class="fw-semibold small mb-2">Activity log</div>
        <ul id="mciImportLog" class="list-unstyled small mb-0"
            style="max-height:280px;overflow-y:auto;background:var(--bs-tertiary-bg);border-radius:6px;padding:10px 14px;">
        </ul>

      </div>
    </div>

    <!-- ── Results summary card (shown when done) ────────────────────────── -->
    <div id="mciDoneCard" class="card border-0 shadow-sm d-none">
      <div class="card-body p-4">

        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div class="fw-semibold" id="mciDoneHeading">Import complete</div>
          <a href="/cp/scraper/results/?source=curl_scrape" class="btn btn-sm btn-outline-primary">
            Go to review queue <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        <div id="mciDoneSummary" class="alert alert-success py-2 small mb-3"></div>

        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>City</th>
                <th>Category</th>
                <th>Phone</th>
                <th>Source URL</th>
              </tr>
            </thead>
            <tbody id="mciDoneTbody"></tbody>
          </table>
        </div>

      </div>
    </div>

  </div><!-- /col -->
</div><!-- /row -->

<script>
(function () {
  'use strict';

  const API  = '/api/v1/cp';
  const JWT  = <?= json_encode($jwtForJs) ?>;
  const AUTH = JWT ? { 'Authorization': 'Bearer ' + JWT } : {};

  let currentJobId  = null;
  let pollTimer     = null;
  let logRendered   = 0;   // how many log entries already appended to the UI

  // ── Active tab detection ─────────────────────────────────────────────────
  function activeMode() {
    return document.getElementById('tab-url-list').classList.contains('active') ? 'url_list' : 'crawler';
  }

  // ── Start import ─────────────────────────────────────────────────────────
  document.getElementById('mciStartBtn').addEventListener('click', async function () {
    const mode = activeMode();
    let payload = { mode };

    if (mode === 'url_list') {
      const raw  = document.getElementById('mciUrlList').value;
      const urls = raw.split('\n').map(s => s.trim()).filter(s => s !== '');
      if (urls.length === 0) {
        showStartError('Please enter at least one URL.');
        return;
      }
      payload.urls = urls;
    } else {
      const indexUrl = document.getElementById('mciIndexUrl').value.trim();
      if (indexUrl === '') {
        showStartError('Please enter a directory page URL.');
        return;
      }
      payload.index_url = indexUrl;
      payload.pattern   = document.getElementById('mciPattern').value.trim();
      payload.limit     = parseInt(document.getElementById('mciLimit').value, 10) || 20;
    }

    hideStartError();
    setStartLoading(true);

    try {
      const res  = await fetch(API + '/url-import/jobs', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', ...AUTH },
        body:        JSON.stringify(payload),
      });
      const json = await res.json();

      if (!json.ok || !json.job_id) {
        showStartError('Error: ' + (json.error || 'Could not start job.'));
        setStartLoading(false);
        return;
      }

      currentJobId = json.job_id;
      logRendered  = 0;
      showProgressCard();
      startPolling(currentJobId);

    } catch (e) {
      showStartError('Network error. Please try again.');
      setStartLoading(false);
    }
  });

  // ── Polling ──────────────────────────────────────────────────────────────
  function startPolling(jobId) {
    pollTimer = setInterval(() => pollJob(jobId), 1500);
  }

  async function pollJob(jobId) {
    try {
      const res  = await fetch(API + '/url-import/jobs/' + encodeURIComponent(jobId), {
        credentials: 'include',
        headers:     AUTH,
      });
      const job = await res.json();

      updateProgress(job);
      appendNewLogEntries(job.log || []);

      if (job.status === 'done' || job.status === 'failed') {
        clearInterval(pollTimer);
        pollTimer = null;
        setStartLoading(false);
        showDoneCard(job);
        if (job.inserted_count > 0) {
          loadResultsPreview();
        }
      }
    } catch (_) { /* network blip — keep polling */ }
  }

  // ── Progress UI ──────────────────────────────────────────────────────────
  function updateProgress(job) {
    const total     = job.total_urls || 0;
    const processed = job.processed_urls || 0;
    const pct       = total > 0 ? Math.round(processed / total * 100) : (job.status === 'running' ? 10 : 100);

    document.getElementById('mciProgressBar').style.width = pct + '%';
    document.getElementById('mciProgressText').textContent =
      total > 0 ? `${processed} / ${total} URLs processed` : `${processed} URLs processed`;

    const statusEl = document.getElementById('mciJobStatus');
    statusEl.textContent = job.status === 'running' ? 'Running'
                         : job.status === 'done'    ? 'Done'
                         : job.status === 'failed'  ? 'Failed' : job.status;
    statusEl.className = 'badge ' + (
      job.status === 'done'    ? 'text-bg-success' :
      job.status === 'failed'  ? 'text-bg-danger'  : 'text-bg-warning'
    );

    if (job.status === 'done') {
      document.getElementById('mciProgressBar').classList.remove('progress-bar-striped', 'progress-bar-animated');
    }
  }

  function appendNewLogEntries(log) {
    const ul = document.getElementById('mciImportLog');
    const newEntries = log.slice(logRendered);
    logRendered += newEntries.length;

    newEntries.forEach(function (entry) {
      const li   = document.createElement('li');
      li.className = 'mb-1';
      const icon = entry.status === 'ok'          ? '✅'
                 : entry.status === 'skipped_dup' ? '⏭'
                 : entry.status === 'no_data'     ? '⚠️' : '❌';
      const names = (entry.names || []).join(', ');
      li.innerHTML = `${icon} <span class="text-muted">${esc(shortUrl(entry.url || ''))}</span>`
                   + (names ? ` — <strong>${esc(names)}</strong>` : '')
                   + (entry.error ? ` <span class="text-danger">${esc(entry.error)}</span>` : '');
      ul.appendChild(li);
      ul.scrollTop = ul.scrollHeight;
    });
  }

  // ── Done card ────────────────────────────────────────────────────────────
  function showDoneCard(job) {
    const el = document.getElementById('mciDoneCard');
    el.classList.remove('d-none');

    const inserted = job.inserted_count || 0;
    const skipped  = job.skipped_count  || 0;
    const summary  = job.status === 'failed'
      ? 'Import failed: ' + (job.error_message || 'Unknown error.')
      : `${inserted} business record${inserted !== 1 ? 's' : ''} added to review queue. ${skipped} duplicate${skipped !== 1 ? 's' : ''} skipped.`;

    const summaryEl = document.getElementById('mciDoneSummary');
    summaryEl.textContent = summary;
    summaryEl.className   = 'alert py-2 small mb-3 ' + (job.status === 'failed' ? 'alert-danger' : 'alert-success');

    document.getElementById('mciDoneHeading').textContent =
      job.status === 'failed' ? 'Import failed' : 'Import complete';
  }

  async function loadResultsPreview() {
    try {
      const res  = await fetch('/api/v1/cp/scraper/results?source=curl_scrape&per_page=25&page=1', {
        credentials: 'include',
        headers:     AUTH,
      });
      const json = await res.json();
      if (!json.items || json.items.length === 0) return;

      const tbody = document.getElementById('mciDoneTbody');
      tbody.innerHTML = json.items.map(function (r) {
        const url = r.source_url || '';
        return `<tr>
          <td class="fw-semibold"><a href="/cp/scraper/review/?id=${encodeURIComponent(r.id)}" class="text-decoration-none">${esc(r.name)}</a></td>
          <td class="text-muted small">${esc(r.city || '—')}</td>
          <td class="text-muted small">${esc(r.category_hint || '—')}</td>
          <td class="small">${esc(r.phone || '—')}</td>
          <td class="small"><a href="${esc(url)}" target="_blank" rel="noopener noreferrer" class="text-truncate d-inline-block" style="max-width:180px;" title="${esc(url)}">${esc(shortUrl(url))}</a></td>
        </tr>`;
      }).join('');
    } catch (_) {}
  }

  // ── UI helpers ───────────────────────────────────────────────────────────
  function showProgressCard() {
    document.getElementById('mciProgressCard').classList.remove('d-none');
    document.getElementById('mciDoneCard').classList.add('d-none');
    document.getElementById('mciDoneTbody').innerHTML = '';
    document.getElementById('mciImportLog').innerHTML = '';
    document.getElementById('mciProgressBar').style.width = '0%';
    document.getElementById('mciProgressText').textContent = '0 / 0 URLs processed';
    document.getElementById('mciJobStatus').textContent = 'Running';
    document.getElementById('mciJobStatus').className = 'badge text-bg-warning';
  }

  function setStartLoading(loading) {
    document.getElementById('mciStartBtn').disabled = loading;
    document.getElementById('mciStartSpinner').classList.toggle('d-none', !loading);
  }

  function showStartError(msg) {
    const el = document.getElementById('mciStartError');
    el.textContent = msg;
    el.classList.remove('d-none');
  }

  function hideStartError() {
    document.getElementById('mciStartError').classList.add('d-none');
  }

  function shortUrl(url) {
    try { return new URL(url).hostname + new URL(url).pathname.substring(0, 30); } catch (_) { return url; }
  }

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

}());
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
