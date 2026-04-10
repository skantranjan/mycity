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

  var state = { page: 1, perPage: 15, q: '', includeDeleted: false, last: null };

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

  function loadUsers() {
    var qs = new URLSearchParams();
    qs.set('page', String(state.page));
    qs.set('per_page', String(state.perPage));
    if (state.q) qs.set('q', state.q);
    qs.set('role', 'subscriber');
    if (state.includeDeleted) qs.set('include_deleted', '1');

    return fetchJson(API + '?' + qs.toString(), { credentials: 'include' });
  }

  function maxPage() {
    var d = state.last;
    if (!d || !d.total) return 1;
    var pp = d.per_page || state.perPage;
    return Math.max(1, Math.ceil(d.total / pp));
  }

  function renderTable(data) {
    state.last = data;
    var tbody = el('mciCpUsersBody');
    var meta = el('mciCpUsersMeta');
    if (!tbody) return;

    var rows = (data && data.users) ? data.users : [];
    var total = (data && data.total) ? data.total : 0;
    if (meta) {
      meta.textContent = 'Total: ' + total + ' — page ' + (data.page || 1) + ' of ' + Math.max(1, Math.ceil(total / (data.per_page || state.perPage)));
    }

    tbody.innerHTML = '';
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">No subscribers found.</td></tr>';
      return;
    }

    rows.forEach(function (u) {
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
      tbody.appendChild(tr);
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
        return loadUsers();
      })
      .then(renderTable)
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
          state.page = 1;
          return loadUsers();
        })
        .then(renderTable)
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
        return loadUsers();
      })
      .then(renderTable)
      .catch(function (e) { showToast(e.message || 'Failed', true); });
  }

  el('mciCpUsersSearchBtn').addEventListener('click', function () {
    state.q = el('mciCpUsersSearch').value.trim();
    state.includeDeleted = el('mciCpUsersIncludeDeleted').checked;
    state.page = 1;
    loadUsers().then(renderTable).catch(function (e) { showToast(e.message || 'Failed', true); });
  });

  el('mciCpUsersPrev').addEventListener('click', function () {
    if (state.page <= 1) return;
    state.page--;
    loadUsers().then(renderTable).catch(function (e) { state.page++; showToast(e.message || 'Failed', true); });
  });
  el('mciCpUsersNext').addEventListener('click', function () {
    if (state.page >= maxPage()) return;
    state.page++;
    loadUsers().then(renderTable).catch(function (e) { state.page--; showToast(e.message || 'Failed', true); });
  });

  el('mciCpUsersAddBtn').addEventListener('click', function () { openPanel('add', null); });
  el('mciUserForm').addEventListener('submit', saveUser);

  loadUsers().then(renderTable).catch(function (e) { showToast(e.message || 'Failed to load users', true); });
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

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle bg-white">
            <thead class="table-light">
              <tr>
                <th style="min-width:140px;">Display name</th>
                <th>Email</th>
                <th style="width:80px;">Status</th>
                <th style="width:120px;">Phone</th>
                <th style="min-width:200px;">Created</th>
                <th class="text-end" style="width:80px;">Actions</th>
              </tr>
            </thead>
            <tbody id="mciCpUsersBody">
              <tr><td colspan="6" class="text-muted small">Loading…</td></tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="mciCpUsersPrev">Previous</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="mciCpUsersNext">Next</button>
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
