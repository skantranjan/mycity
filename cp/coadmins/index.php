<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_super_admin_session();

$pageTitle = 'Co-admins - CP - My City Info';
$activePage = '';
$cpActive = 'coadmins';
$hideCta = true;
$appArea = 'cp';

$extraHead = '';

$extraJS = <<<'HTML'
<script>
(function () {
  var API = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })('/cp/co-admins');

  function el(id) { return document.getElementById(id); }

  function showToast(msg, isErr) {
    var box = el('mciCpCoAlert');
    if (!box) return;
    box.className = 'alert py-2 small ' + (isErr ? 'alert-danger' : 'alert-success');
    box.textContent = msg;
    box.hidden = false;
    setTimeout(function () { box.hidden = true; }, 6000);
  }

  function apiErr(data) {
    if (!data || typeof data !== 'object') return 'Request failed';
    return String(data.error || data.message || 'Request failed');
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

  function loadList() {
    return fetchJson(API, { credentials: 'include' });
  }

  function render(rows) {
    var tbody = el('mciCpCoadminsBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    var list = rows && rows.co_admins ? rows.co_admins : [];
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted small">No co-admins yet.</td></tr>';
      return;
    }
    list.forEach(function (u) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td class="small text-muted">' + escapeHtml(u.display_name || '—') + '</td>' +
        '<td class="small text-muted">' + escapeHtml(u.email || '') + '</td>' +
        '<td class="small text-muted text-nowrap">' + fmtDate(u.created_at) + '</td>' +
        '<td class="text-end"></td>';
      var td = tr.querySelector('td:last-child');
      var bEdit = document.createElement('button');
      bEdit.type = 'button';
      bEdit.className = 'btn btn-sm btn-outline-secondary mci-icon-btn';
      bEdit.title = 'Edit';
      bEdit.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
      bEdit.addEventListener('click', function () { openPanel(u); });
      var bRev = document.createElement('button');
      bRev.type = 'button';
      bRev.className = 'btn btn-sm btn-outline-danger mci-icon-btn';
      bRev.title = 'Revoke access';
      bRev.innerHTML = '<i class="bi bi-shield-x" aria-hidden="true"></i>';
      bRev.addEventListener('click', function () { revoke(u); });
      var wrap = document.createElement('div');
      wrap.className = 'd-flex gap-1 justify-content-end';
      wrap.appendChild(bEdit);
      wrap.appendChild(bRev);
      td.appendChild(wrap);
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

  function openPanel(u) {
    var isEdit = !!u;
    el('mciCoPanelTitle').textContent = isEdit ? 'Edit co-admin' : 'Add co-admin';
    el('mciCoId').value = isEdit ? u.id : '';
    el('mciCoDisplayName').value = isEdit && u.display_name ? u.display_name : '';
    el('mciCoEmail').value = isEdit && u.email ? u.email : '';
    el('mciCoEmail').disabled = isEdit;
    el('mciCoPassword').value = '';
    el('mciCoPassword').required = !isEdit;
    el('mciCoPasswordHelp').textContent = isEdit ? 'Leave blank to keep current password.' : 'Minimum 8 characters.';

    var panel = el('mciCoOffcanvas');
    var oc = bootstrap.Offcanvas.getInstance(panel) || new bootstrap.Offcanvas(panel);
    oc.show();
  }

  function revoke(u) {
    if (!u || !u.id) return;
    if (!window.confirm('Revoke co-admin access for ' + (u.display_name || u.email || 'this user') + '? They will become a subscriber.')) return;
    fetchJson(API + '/' + encodeURIComponent(u.id) + '/revoke', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    })
      .then(function () {
        showToast('Co-admin revoked.', false);
        return loadList();
      })
      .then(render)
      .catch(function (e) { showToast(e.message || 'Failed', true); });
  }

  el('mciCoForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var id = el('mciCoId').value;
    var payload = {
      display_name: el('mciCoDisplayName').value.trim(),
      email: el('mciCoEmail').value.trim(),
      password: el('mciCoPassword').value
    };

    if (id) {
      payload.action = 'edit';
      payload.id = id;
      if (!payload.password) delete payload.password;
      fetchJson(API, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
        .then(function () {
          showToast('Co-admin updated.', false);
          var oc = bootstrap.Offcanvas.getInstance(el('mciCoOffcanvas'));
          if (oc) oc.hide();
          return loadList();
        })
        .then(render)
        .catch(function (e) { showToast(e.message || 'Failed', true); });
      return;
    }

    if (!payload.password || payload.password.length < 8) {
      showToast('Password is required (min 8 characters).', true);
      return;
    }

    fetchJson(API, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'add',
        display_name: payload.display_name,
        email: payload.email,
        password: payload.password
      })
    })
      .then(function () {
        showToast('Co-admin created.', false);
        var oc = bootstrap.Offcanvas.getInstance(el('mciCoOffcanvas'));
        if (oc) oc.hide();
        el('mciCoDisplayName').value = '';
        el('mciCoEmail').value = '';
        el('mciCoPassword').value = '';
        return loadList();
      })
      .then(render)
      .catch(function (e) { showToast(e.message || 'Failed', true); });
  });

  el('mciCpAddCoadminBtn').addEventListener('click', function () { openPanel(null); });

  loadList().then(render).catch(function (e) { showToast(e.message || 'Failed to load', true); });
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
    <div id="mciCpCoAlert" class="alert py-2 small" role="status" hidden></div>

    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
          <div>
            <div class="fw-semibold mb-1">Co-admin management</div>
            <div class="text-muted small">Co-admins share control panel access (except modifying super admins). Data: <code>mci_users</code> with role <code>co_admin</code>.</div>
          </div>
          <button type="button" class="btn btn-dark btn-sm" id="mciCpAddCoadminBtn">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add co-admin
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle bg-white mb-0">
            <thead class="table-light">
              <tr>
                <th style="min-width:140px;">Name</th>
                <th>Email</th>
                <th style="min-width:200px;">Added</th>
                <th class="text-end" style="width:80px;">Actions</th>
              </tr>
            </thead>
            <tbody id="mciCpCoadminsBody">
              <tr><td colspan="4" class="text-muted small">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="mciCoOffcanvas" aria-labelledby="mciCoPanelTitle">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h5 mb-0" id="mciCoPanelTitle">Co-admin</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciCoForm">
      <input type="hidden" id="mciCoId" value="" />
      <div class="mb-3">
        <label class="form-label" for="mciCoDisplayName">Display name</label>
        <input type="text" class="form-control" id="mciCoDisplayName" maxlength="255" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCoEmail">Email</label>
        <input type="email" class="form-control" id="mciCoEmail" required autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCoPassword">Password</label>
        <input type="password" class="form-control" id="mciCoPassword" minlength="8" autocomplete="new-password" />
        <div class="form-text" id="mciCoPasswordHelp"></div>
      </div>
      <button type="submit" class="btn btn-dark w-100">Save</button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
