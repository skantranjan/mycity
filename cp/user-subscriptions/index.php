<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_super_admin_session();

$pageTitle = 'User Subscriptions - My City Info';
$activePage = '';
$cpActive = 'user-subscriptions';
$hideCta = true;
$appArea = 'cp';

$extraJS = <<<'HTML'
<script>
(function () {
  var apiList = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })('/cp/user-subscriptions');
  var apiPackages = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })('/cp/subscription-packages');
  var state = { items: [], packages: [] };
  function el(id) { return document.getElementById(id); }
  function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function notice(msg, err) {
    var box = el('mciUsrSubAlert');
    box.className = 'alert py-2 small mb-3 ' + (err ? 'alert-danger' : 'alert-success');
    box.textContent = msg;
    box.hidden = false;
    setTimeout(function () { box.hidden = true; }, 5000);
  }
  function req(url, opts) {
    return fetch(url, opts).then(function (r) {
      return r.text().then(function (txt) {
        var j;
        try { j = JSON.parse(txt); } catch (e) { throw new Error('Invalid server response'); }
        if (!r.ok) throw new Error(String(j.error || j.message || 'Request failed'));
        return j;
      });
    });
  }
  function renderPackages() {
    var sel = el('assignPackage');
    sel.innerHTML = '<option value="">Select package</option>';
    state.packages.forEach(function (p) {
      var opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.package_name + ' (' + p.status + ')';
      sel.appendChild(opt);
    });
  }
  function renderRows() {
    var tbody = el('mciUsrSubBody');
    if (!state.items.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-muted small">No subscriptions found.</td></tr>';
      return;
    }
    tbody.innerHTML = state.items.map(function (s) {
      return '<tr>' +
        '<td class="small">' + esc(s.user_display_name || '—') + '</td>' +
        '<td class="small">' + esc(s.user_email) + '</td>' +
        '<td class="small fw-semibold">' + esc(s.package_name) + '</td>' +
        '<td><span class="badge text-bg-light border">' + esc(s.subscription_status) + '</span></td>' +
        '<td class="small">' + esc(s.subscription_start_date || '—') + '</td>' +
        '<td class="small">' + esc(s.subscription_end_date || '—') + '</td>' +
        '<td class="small">' + (s.auto_assigned ? 'Yes' : 'No') + '</td>' +
        '<td class="small">' + esc(s.upgrade_source || '—') + '</td>' +
      '</tr>';
    }).join('');
  }
  function load() {
    Promise.all([
      req(apiList + '?page=1&per_page=100', { credentials: 'include' }),
      req(apiPackages, { credentials: 'include' })
    ]).then(function (all) {
      state.items = all[0].items || [];
      state.packages = all[1].packages || [];
      renderRows();
      renderPackages();
    }).catch(function (e) {
      notice(e.message, true);
    });
  }
  el('mciAssignSubscription').addEventListener('submit', function (e) {
    e.preventDefault();
    var payload = {
      action: 'assign',
      user_id: el('assignUserId').value.trim(),
      package_id: el('assignPackage').value,
      subscription_status: el('assignStatus').value,
      upgrade_source: el('assignSource').value.trim() || 'manual_admin'
    };
    req(apiList, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function () {
      notice('User subscription updated.');
      el('mciAssignSubscription').reset();
      load();
    }).catch(function (e2) {
      notice(e2.message, true);
    });
  });
  load();
}());
</script>
HTML;

ob_start();
?>
<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white mb-4">
      <div class="card-body p-4">
        <div class="fw-semibold">User subscriptions</div>
        <div class="text-muted small mb-3">Review current package assignments and statuses.</div>
        <div id="mciUsrSubAlert" class="alert py-2 small mb-3" hidden></div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Name</th><th>Email</th><th>Package</th><th>Status</th><th>Start</th><th>End</th><th>Auto</th><th>Source</th>
              </tr>
            </thead>
            <tbody id="mciUsrSubBody">
              <tr><td colspan="8" class="text-muted small">Loading subscriptions...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Assign package manually</div>
        <form id="mciAssignSubscription">
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label" for="assignUserId">User ID</label>
              <input id="assignUserId" class="form-control" required />
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label" for="assignPackage">Package</label>
              <select id="assignPackage" class="form-select" required></select>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label" for="assignStatus">Status</label>
              <select id="assignStatus" class="form-select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending_activation">Pending activation</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-12 col-md-2">
              <label class="form-label" for="assignSource">Source</label>
              <input id="assignSource" class="form-control" value="manual_admin" />
            </div>
          </div>
          <button class="btn btn-dark mt-3" type="submit">Assign subscription</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
