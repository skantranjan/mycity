<?php
$pageTitle = 'Anonymous Approvals - My City Info';
$activePage = '';
$cpActive = 'anonymous';
$hideCta = true;
$appArea = 'cp';

$extraJS = <<<'HTML'
<script>
(function () {
  var QUEUE_KEY = 'mci_cp_anon_business_queue';

  function apiUrl(suffix) {
    return (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })(suffix);
  }

  // API-first mode with localStorage fallback.
  var useApi = false;
  var apiSubmissions = [];

  function safeParse(json, fallback) {
    try { return JSON.parse(json); } catch (e) { return fallback; }
  }

  function getQueue() {
    if (useApi) {
      return (apiSubmissions || []).map(function (s) {
        var payload = {};
        if (s && typeof s.payload_json === 'string' && s.payload_json) {
          try { payload = JSON.parse(s.payload_json); } catch (e0) { payload = {}; }
        }
        return {
          id: s.id,
          payload: payload,
          submittedAt: s.created_at || s.createdAt || '',
          createdAt: s.created_at || s.createdAt || '',
          submittedBy: s.submitted_by_role || '',
          status: s.status || 'pending'
        };
      });
    }
    var raw = localStorage.getItem(QUEUE_KEY);
    var v = safeParse(raw, []);
    return Array.isArray(v) ? v : [];
  }

  function setQueue(arr) {
    if (useApi) return;
    localStorage.setItem(QUEUE_KEY, JSON.stringify(Array.isArray(arr) ? arr : []));
  }

  function toBadgeClass(status) {
    if (status === 'pending') return 'text-bg-warning';
    if (status === 'approved_live' || status === 'approved') return 'text-bg-success';
    if (status === 'approved_claim') return 'text-bg-primary';
    if (status === 'rejected') return 'text-bg-secondary';
    return 'text-bg-light';
  }

  function formatWhen(iso) {
    if (!iso) return '—';
    var d = new Date(iso);
    return isNaN(d.getTime()) ? iso : d.toLocaleString();
  }

  function render() {
    var tbody = document.getElementById('mciAnonQueueBody');
    if (!tbody) return;
    var queue = getQueue();
    tbody.innerHTML = '';

    if (!queue.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted small">No anonymous submissions yet.</td></tr>';
      return;
    }

    queue.forEach(function (item) {
      if (!item || typeof item !== 'object') return;
      var payload = (item.payload && typeof item.payload === 'object') ? item.payload : {};

      var title = (payload.listing_title || payload.title || 'Untitled listing').toString();
      var category = (payload.category || payload.listing_category || '').toString();
      var submitted = formatWhen(item.submittedAt || item.createdAt);

      var tr = document.createElement('tr');

      var tdTitle = document.createElement('td');
      tdTitle.className = 'fw-semibold';
      tdTitle.textContent = title;
      tr.appendChild(tdTitle);

      var tdCategory = document.createElement('td');
      tdCategory.className = 'text-muted small';
      tdCategory.textContent = category;
      tr.appendChild(tdCategory);

      var tdSubmitted = document.createElement('td');
      tdSubmitted.className = 'text-muted small';
      tdSubmitted.textContent = submitted + (item.submittedBy ? ' · ' + item.submittedBy : '');
      tr.appendChild(tdSubmitted);

      var tdActions = document.createElement('td');
      tdActions.style.minWidth = '320px';

      var disabled = (item.status && item.status !== 'pending');
      var statusBadge = document.createElement('div');
      statusBadge.className = 'mb-2';
      var span = document.createElement('span');
      span.className = 'badge rounded-pill ' + toBadgeClass(item.status || 'pending');
      span.textContent = item.status || 'pending';
      statusBadge.appendChild(span);

      var rowActions = document.createElement('div');
      rowActions.className = 'd-flex gap-2 flex-wrap';

      var btnApproveLive = document.createElement('button');
      btnApproveLive.type = 'button';
      btnApproveLive.className = 'btn btn-sm btn-outline-success';
      btnApproveLive.textContent = 'Approve & make live';
      btnApproveLive.disabled = !!disabled;
      btnApproveLive.addEventListener('click', function () {
        if (useApi) {
          fetch(apiUrl('/cp/anon-business-submissions/' + encodeURIComponent(String(item.id)) + '/approve'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mode: 'live' })
          })
            .then(function () { return loadApiQueue().then(function () { render(); }); })
            .catch(function () { useApi = false; render(); });
          return;
        }

        item.status = 'approved_live';
        setQueue(queue);
        render();
      });

      var btnApproveClaim = document.createElement('button');
      btnApproveClaim.type = 'button';
      btnApproveClaim.className = 'btn btn-sm btn-outline-primary';
      btnApproveClaim.textContent = 'Approve & open for claim';
      btnApproveClaim.disabled = !!disabled;
      btnApproveClaim.addEventListener('click', function () {
        if (useApi) {
          fetch(apiUrl('/cp/anon-business-submissions/' + encodeURIComponent(String(item.id)) + '/approve'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mode: 'claim' })
          })
            .then(function () { return loadApiQueue().then(function () { render(); }); })
            .catch(function () { useApi = false; render(); });
          return;
        }

        item.status = 'approved_claim';
        setQueue(queue);
        render();
      });

      var btnReject = document.createElement('button');
      btnReject.type = 'button';
      btnReject.className = 'btn btn-sm btn-outline-danger';
      btnReject.textContent = 'Reject';
      btnReject.disabled = !!disabled;
      btnReject.addEventListener('click', function () {
        if (useApi) {
          fetch(apiUrl('/cp/anon-business-submissions/' + encodeURIComponent(String(item.id)) + '/reject'), {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
          })
            .then(function () { return loadApiQueue().then(function () { render(); }); })
            .catch(function () { useApi = false; render(); });
          return;
        }

        item.status = 'rejected';
        setQueue(queue);
        render();
      });

      var btnPreview = document.createElement('button');
      btnPreview.type = 'button';
      btnPreview.className = 'btn btn-sm btn-outline-dark';
      btnPreview.textContent = 'Preview';
      btnPreview.addEventListener('click', function () {
        try { localStorage.setItem('mci_listing_preview', JSON.stringify(payload)); } catch (e) {}
        window.open('/listing-preview/', '_blank', 'noopener,noreferrer');
      });

      rowActions.appendChild(btnApproveLive);
      rowActions.appendChild(btnApproveClaim);
      rowActions.appendChild(btnReject);
      rowActions.appendChild(btnPreview);

      tdActions.appendChild(statusBadge);
      tdActions.appendChild(rowActions);

      tr.appendChild(tdActions);
      tbody.appendChild(tr);
    });
  }

  if (typeof window.localStorage === 'undefined') return;

  function loadApiQueue() {
    return fetch(apiUrl('/cp/anon-business-submissions'), { credentials: 'include' })
      .then(function (r) {
        return r.text().then(function (text) {
          var data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            throw new Error('non_json');
          }
          if (!r.ok) throw new Error('api_failed');
          return data;
        });
      })
      .then(function (data) {
        apiSubmissions = (data && data.submissions) ? data.submissions : [];
        useApi = true;
      });
  }

  // Prefer API; fallback to localStorage if auth/DB isn't ready.
  loadApiQueue()
    .then(function () {
      render();
    })
    .catch(function () {
      useApi = false;
      render();
    });
})();
</script>
HTML;

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Approve Anonymous Listings</div>
            <div class="text-muted small">Approve items submitted anonymously and optionally mark them as open for claim.</div>
          </div>
          <div class="text-muted small">UI demo</div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th>Listing</th>
                <th>Category</th>
                <th>Submitted</th>
                <th style="min-width: 320px;">Admin actions</th>
              </tr>
            </thead>
            <tbody id="mciAnonQueueBody">
              <tr>
                <td colspan="4" class="text-muted small">Loading anonymous queue...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3 text-muted small">When approved anonymously, listings should become claimable after verification (backend later).</div>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

