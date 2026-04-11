<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_super_admin_session();

$pageTitle = 'Subscribers - My City Info';
$activePage = '';
$cpActive = 'subscribers';
$hideCta = true;
$appArea = 'cp';

$currentUserId = (string) ($_SESSION['mci_cp_user_id'] ?? '');

$extraHead = '';

$extraJS = <<<'HTML'
<script>
(function () {
  var API = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })('/cp/users');
  var currentUserId = document.getElementById('mciCpUsersCurrentId').textContent.trim();

  var state = {
    perPage: 25,
    q: '',
    includeDeleted: false,
    sort: 'created_at',
    sortDir: 'desc',
    nextPage: 1,
    loading: false,
    exhausted: true,
    total: 0,
    displayedCount: 0,
    last: null
  };
  var listRequestGen = 0;

  function el(id) { return document.getElementById(id); }

  function showToast(msg, isErr) {
    var box = el('mciCpUsersAlert');
    if (!box) return;
    box.className = 'alert py-2 small ' + (isErr ? 'alert-danger' : 'alert-success');
    box.textContent = msg;
    box.hidden = false;
    setTimeout(function () { box.hidden = true; }, 6000);
  }

  function apiErr(data) {
    if (!data || typeof data !== 'object') return 'Request failed';
    if (data.error) return String(data.error);
    if (data.message) return String(data.message);
    return 'Request failed';
  }

  function fetchJson(url, opts) {
    return fetch(url, opts).then(function (r) {
      return r.text().then(function (text) {
        var j;
        try {
          j = JSON.parse(text);
        } catch (e) {
          throw new Error('API returned non-JSON (check URL, rewrite rules, or PHP errors). Open Network tab for: ' + url);
        }
        if (!r.ok) throw new Error(apiErr(j));
        return j;
      });
    });
  }

  function listQuery(pageNum) {
    var qs = new URLSearchParams();
    qs.set('page', String(pageNum));
    qs.set('per_page', String(state.perPage));
    if (state.q) qs.set('q', state.q);
    qs.set('role', 'subscriber');
    if (state.includeDeleted) qs.set('include_deleted', '1');
    qs.set('sort', state.sort);
    qs.set('sort_dir', state.sortDir);
    return qs.toString();
  }

  function fetchPage(pageNum) {
    return fetchJson(API + '?' + listQuery(pageNum), { credentials: 'include' });
  }

  function updateSortUi() {
    var btn = el('mciCpUsersSortCreated');
    var ic = el('mciCpUsersSortCreatedIcon');
    if (btn) {
      btn.setAttribute('aria-sort', state.sortDir === 'desc' ? 'descending' : 'ascending');
    }
    if (ic) {
      ic.className = 'bi ' + (state.sortDir === 'desc' ? 'bi-sort-down' : 'bi-sort-up');
    }
  }

  function updateMeta() {
    var meta = el('mciCpUsersMeta');
    if (!meta) return;
    var sortPhrase = state.sortDir === 'desc' ? 'newest first' : 'oldest first';
    var tail = '';
    if (state.total > 0 && !state.exhausted) {
      tail = ' · Scroll the list to load more';
    } else if (state.total > 0 && state.exhausted && state.displayedCount >= state.total) {
      tail = ' · End of list';
    }
    meta.textContent = 'Loaded ' + state.displayedCount + ' of ' + state.total + ' · Sorted by created (' + sortPhrase + ')' + tail;
  }

  function setSentinel(loading, text) {
    var s = el('mciCpUsersSentinel');
    if (!s) return;
    s.className = 'py-2 text-center small text-muted border-top';
    if (loading) {
      s.textContent = 'Loading more…';
      s.setAttribute('aria-busy', 'true');
    } else {
      s.textContent = text || '';
      s.removeAttribute('aria-busy');
    }
  }

  function clearSentinel() {
    var s = el('mciCpUsersSentinel');
    if (!s) return;
    s.textContent = '';
    s.removeAttribute('aria-busy');
  }

  function buildRow(u) {
    var tr = document.createElement('tr');
    if (u.deleted_at) tr.classList.add('table-secondary');

    tr.innerHTML =
      '<td class="small text-muted"><button type="button" class="btn btn-link btn-sm p-0 text-start mci-user-detail-link">' + escapeHtml(u.display_name || '—') + '</button></td>' +
      '<td class="small text-muted">' + escapeHtml(u.email || '') + '</td>' +
      '<td><span class="badge text-bg-light border">' + escapeHtml(u.status || '') + '</span></td>' +
      '<td class="small text-muted">' + escapeHtml(u.phone || '—') + '</td>' +
      '<td class="small text-muted text-nowrap">' + fmtDate(u.created_at) + '</td>' +
      '<td class="text-end"></td>';

    var tdAct = tr.querySelector('td:last-child');
    tr.querySelector('.mci-user-detail-link').addEventListener('click', function () { openDetailPanel(u); });
    var btnEdit = document.createElement('button');
    btnEdit.type = 'button';
    btnEdit.className = 'btn btn-sm btn-outline-secondary mci-icon-btn';
    btnEdit.title = 'Edit';
    btnEdit.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
    btnEdit.disabled = !!u.deleted_at;
    btnEdit.addEventListener('click', function () { openPanel('edit', u); });

    var btnDel = document.createElement('button');
    btnDel.type = 'button';
    btnDel.className = 'btn btn-sm btn-outline-danger mci-icon-btn';
    btnDel.title = (u.id === currentUserId) ? 'Cannot delete your own account' : 'Delete';
    btnDel.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
    btnDel.disabled = !!u.deleted_at || (u.id === currentUserId);
    btnDel.addEventListener('click', function () { confirmDelete(u); });

    var wrap = document.createElement('div');
    wrap.className = 'd-flex gap-1 justify-content-end';
    wrap.appendChild(btnEdit);
    wrap.appendChild(btnDel);
    tdAct.appendChild(wrap);
    return tr;
  }

  function applyFirstPage(data) {
    state.last = data;
    var tbody = el('mciCpUsersBody');
    if (!tbody) return;

    state.total = (data && data.total) ? data.total : 0;
    var rows = (data && data.users) ? data.users : [];
    tbody.innerHTML = '';
    state.displayedCount = 0;
    state.nextPage = 2;
    clearSentinel();

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">No subscribers found.</td></tr>';
      state.exhausted = true;
      updateMeta();
      return;
    }

    rows.forEach(function (u) { tbody.appendChild(buildRow(u)); });
    state.displayedCount = rows.length;
    state.exhausted = rows.length < state.perPage || state.displayedCount >= state.total;
    updateSortUi();
    updateMeta();
  }

  function appendPage(data) {
    state.last = data;
    var tbody = el('mciCpUsersBody');
    if (!tbody) return;

    var rows = (data && data.users) ? data.users : [];
    if (!rows.length) {
      state.exhausted = true;
      updateMeta();
      return;
    }
    rows.forEach(function (u) { tbody.appendChild(buildRow(u)); });
    state.displayedCount += rows.length;
    state.exhausted = rows.length < state.perPage || state.displayedCount >= state.total;
    updateMeta();
  }

  function reloadList() {
    var gen = ++listRequestGen;
    state.loading = true;
    state.exhausted = false;
    state.nextPage = 1;
    var tbody = el('mciCpUsersBody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">Loading…</td></tr>';
    clearSentinel();
    updateSortUi();

    return fetchPage(1)
      .then(function (data) {
        if (gen !== listRequestGen) return;
        applyFirstPage(data);
      })
      .catch(function (e) {
        if (gen !== listRequestGen) return;
        var tb = el('mciCpUsersBody');
        if (tb) tb.innerHTML = '<tr><td colspan="6" class="text-muted small">Could not load subscribers.</td></tr>';
        showToast(e.message || 'Failed to load users', true);
      })
      .finally(function () {
        if (gen === listRequestGen) state.loading = false;
      });
  }

  function loadMore() {
    if (state.loading || state.exhausted || state.displayedCount >= state.total) {
      if (state.displayedCount >= state.total) state.exhausted = true;
      return Promise.resolve();
    }
    var gen = listRequestGen;
    state.loading = true;
    setSentinel(true);
    var pageToLoad = state.nextPage;

    return fetchPage(pageToLoad)
      .then(function (data) {
        if (gen !== listRequestGen) return;
        appendPage(data);
        state.nextPage = pageToLoad + 1;
      })
      .catch(function (e) {
        if (gen !== listRequestGen) return;
        showToast(e.message || 'Failed to load more', true);
      })
      .finally(function () {
        if (gen !== listRequestGen) return;
        state.loading = false;
        if (state.exhausted) {
          setSentinel(false, state.total > 0 && state.displayedCount >= state.total ? 'All subscribers loaded.' : '');
        } else {
          clearSentinel();
        }
      });
  }

  function escapeHtml(s) {
    if (s == null) return '';
    s = String(s);
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function fmtDate(x) {
    if (!x) return '—';
    var d = new Date(x);
    if (isNaN(d.getTime())) return String(x);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) +
      ' at ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true });
  }

  function openPanel(mode, u) {
    el('mciUserPanelMode').value = mode;
    el('mciUserPanelTitle').textContent = mode === 'add' ? 'Add subscriber' : 'Edit subscriber';
    el('mciUserId').value = mode === 'edit' && u ? u.id : '';
    el('mciUserEmail').value = u && u.email ? u.email : '';
    el('mciUserEmail').disabled = mode === 'edit';
    el('mciUserPassword').value = '';
    el('mciUserPassword').required = mode === 'add';
    el('mciUserPasswordHelp').textContent = mode === 'edit' ? 'Leave blank to keep current password.' : 'Minimum 8 characters.';
    el('mciUserDisplayName').value = u && u.display_name ? u.display_name : '';
    el('mciUserPhone').value = u && u.phone ? u.phone : '';
    el('mciUserStatus').value = u && u.status ? u.status : 'active';

    var panel = document.getElementById('mciUserOffcanvas');
    var bs = bootstrap.Offcanvas.getInstance(panel);
    if (!bs) bs = new bootstrap.Offcanvas(panel);
    bs.show();
  }

  function openDetailPanel(u) {
    if (!u) return;
    el('mciUserDetailName').textContent = u.display_name || '—';
    el('mciUserDetailEmail').textContent = u.email || '—';
    el('mciUserDetailPhone').textContent = u.phone || '—';
    el('mciUserDetailStatus').textContent = u.status || '—';
    el('mciUserDetailCreated').textContent = fmtDate(u.created_at);
    el('mciUserDetailLastLogin').textContent = fmtDate(u.last_login_at);
    var panel = document.getElementById('mciUserDetailOffcanvas');
    var bs = bootstrap.Offcanvas.getInstance(panel);
    if (!bs) bs = new bootstrap.Offcanvas(panel);
    bs.show();
  }

  function confirmDelete(u) {
    if (!u || !u.id) return;
    if (!window.confirm('Delete this subscriber and soft-delete all their associated listings?')) return;
    var typed = window.prompt('Type DELETE to confirm');
    if (typed !== 'DELETE') return;

    fetchJson(API, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: u.id })
    })
      .then(function () {
        showToast('User marked as deleted.', false);
        return reloadList();
      })
      .catch(function (e) { showToast(e.message || 'Failed', true); });
  }

  function saveUser(ev) {
    ev.preventDefault();
    var mode = el('mciUserPanelMode').value;
    var payload = {
      display_name: el('mciUserDisplayName').value.trim(),
      phone: el('mciUserPhone').value.trim(),
      role: 'subscriber',
      status: el('mciUserStatus').value
    };

    if (mode === 'add') {
      payload.action = 'create';
      payload.email = el('mciUserEmail').value.trim();
      payload.password = el('mciUserPassword').value;
      fetchJson(API, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function () {
          showToast('User created.', false);
          var p = el('mciUserOffcanvas');
          var o = bootstrap.Offcanvas.getInstance(p);
          if (o) o.hide();
          return reloadList();
        })
        .catch(function (e) { showToast(e.message || 'Failed', true); });
      return;
    }

    payload.action = 'update';
    payload.id = el('mciUserId').value;
    var pw = el('mciUserPassword').value;
    if (pw) payload.password = pw;

    fetchJson(API, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function () {
        showToast('User updated.', false);
        var p = el('mciUserOffcanvas');
        var o = bootstrap.Offcanvas.getInstance(p);
        if (o) o.hide();
        return reloadList();
      })
      .catch(function (e) { showToast(e.message || 'Failed', true); });
  }

  el('mciCpUsersSearchBtn').addEventListener('click', function () {
    state.q = el('mciCpUsersSearch').value.trim();
    state.includeDeleted = el('mciCpUsersIncludeDeleted').checked;
    reloadList();
  });

  var sortCreatedBtn = el('mciCpUsersSortCreated');
  if (sortCreatedBtn) {
    sortCreatedBtn.addEventListener('click', function () {
      state.sortDir = state.sortDir === 'desc' ? 'asc' : 'desc';
      reloadList();
    });
  }

  el('mciCpUsersAddBtn').addEventListener('click', function () { openPanel('add', null); });
  el('mciUserForm').addEventListener('submit', saveUser);

  var scrollRoot = el('mciCpUsersScroll');
  var sentinel = el('mciCpUsersSentinel');
  if (scrollRoot && sentinel && typeof IntersectionObserver !== 'undefined') {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        if (state.loading || state.exhausted || state.displayedCount >= state.total) return;
        loadMore();
      });
    }, { root: scrollRoot, rootMargin: '100px', threshold: 0 });
    io.observe(sentinel);
  }

  reloadList();
})();
</script>
HTML;

ob_start();
?>

<span id="mciCpUsersCurrentId" class="d-none"><?= htmlspecialchars($currentUserId, ENT_QUOTES, 'UTF-8') ?></span>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div id="mciCpUsersAlert" class="alert py-2 small" role="status" hidden></div>

    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold">Subscribers</div>
            <div class="text-muted small">Create, edit, or soft-delete subscriber accounts only.</div>
          </div>
          <button type="button" class="btn btn-dark btn-sm" id="mciCpUsersAddBtn">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add subscriber
          </button>
        </div>

        <div class="row g-2 align-items-end mb-3">
          <div class="col-12 col-md-4">
            <label class="form-label small mb-0" for="mciCpUsersSearch">Search</label>
            <input type="search" class="form-control form-control-sm" id="mciCpUsersSearch" placeholder="Email, name, phone" autocomplete="off" />
          </div>
          <div class="col-12 col-md-3 form-check mt-3 mt-md-4">
            <input class="form-check-input" type="checkbox" id="mciCpUsersIncludeDeleted" />
            <label class="form-check-label small" for="mciCpUsersIncludeDeleted">Show deleted</label>
          </div>
          <div class="col-12 col-md-2">
            <button type="button" class="btn btn-outline-dark btn-sm w-100" id="mciCpUsersSearchBtn">Apply</button>
          </div>
        </div>

        <div class="text-muted small mb-2" id="mciCpUsersMeta"></div>

        <div id="mciCpUsersScroll" class="border rounded bg-white" style="max-height:min(70vh,640px);overflow-y:auto;">
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle bg-white mb-0">
              <thead class="table-light">
                <tr>
                  <th style="min-width:140px;">Display name</th>
                  <th>Email</th>
                  <th style="width:80px;">Status</th>
                  <th style="width:120px;">Phone</th>
                  <th style="min-width:200px;">
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-body fw-semibold d-inline-flex align-items-center gap-1"
                      id="mciCpUsersSortCreated" aria-sort="descending" title="Toggle sort by created date">
                      Created
                      <i class="bi bi-sort-down" id="mciCpUsersSortCreatedIcon" aria-hidden="true"></i>
                    </button>
                  </th>
                  <th class="text-end" style="width:80px;">Actions</th>
                </tr>
              </thead>
              <tbody id="mciCpUsersBody">
                <tr><td colspan="6" class="text-muted small">Loading…</td></tr>
              </tbody>
            </table>
          </div>
          <div id="mciCpUsersSentinel" class="border-top py-2 text-center small text-muted" aria-live="polite"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="mciUserDetailOffcanvas" aria-labelledby="mciUserDetailTitle" style="width:clamp(300px,92vw,480px)">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h5 mb-0" id="mciUserDetailTitle">Subscriber details</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="small text-muted mb-1">Display name</div>
    <div class="fw-semibold mb-3" id="mciUserDetailName">—</div>
    <div class="small text-muted mb-1">Email</div>
    <div class="mb-3" id="mciUserDetailEmail">—</div>
    <div class="small text-muted mb-1">Phone</div>
    <div class="mb-3" id="mciUserDetailPhone">—</div>
    <div class="small text-muted mb-1">Status</div>
    <div class="mb-3" id="mciUserDetailStatus">—</div>
    <div class="small text-muted mb-1">Created</div>
    <div class="mb-3" id="mciUserDetailCreated">—</div>
    <div class="small text-muted mb-1">Last login</div>
    <div class="mb-3" id="mciUserDetailLastLogin">—</div>
  </div>
</div>

<!-- Offcanvas: add / edit user -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mciUserOffcanvas" aria-labelledby="mciUserPanelTitle" style="width:clamp(300px,92vw,480px)">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h5 mb-0" id="mciUserPanelTitle">User</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciUserForm" novalidate>
      <input type="hidden" id="mciUserPanelMode" value="add" />
      <input type="hidden" id="mciUserId" value="" />

      <div class="mb-3">
        <label class="form-label" for="mciUserEmail">Email</label>
        <input type="email" class="form-control" id="mciUserEmail" required autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciUserPassword">Password</label>
        <input type="password" class="form-control" id="mciUserPassword" minlength="8" autocomplete="new-password" />
        <div class="form-text" id="mciUserPasswordHelp"></div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciUserDisplayName">Display name</label>
        <input type="text" class="form-control" id="mciUserDisplayName" maxlength="255" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciUserPhone">Phone</label>
        <input type="text" class="form-control" id="mciUserPhone" maxlength="32" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciUserStatus">Status</label>
        <select class="form-select" id="mciUserStatus" required>
          <option value="active">active</option>
          <option value="inactive">inactive</option>
          <option value="blocked">blocked</option>
        </select>
      </div>

      <button type="submit" class="btn btn-dark w-100">Save</button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
