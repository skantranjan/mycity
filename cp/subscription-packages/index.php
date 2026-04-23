<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_super_admin_session();

$pageTitle = 'Subscription Packages - My City Info';
$activePage = '';
$cpActive = 'subscription-packages';
$hideCta = true;
$appArea = 'cp';

$extraJS = <<<'HTML'
<script>
(function () {
  var api = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; })('/cp/subscription-packages');
  var state = { items: [] };

  function el(id) { return document.getElementById(id); }
  function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function show(msg, err) {
    var box = el('mciSubPkgAlert');
    if (!box) return;
    box.className = 'alert py-2 small mb-3 ' + (err ? 'alert-danger' : 'alert-success');
    box.textContent = msg;
    box.hidden = false;
    setTimeout(function () { box.hidden = true; }, 5000);
  }
  function req(url, opts) {
    return fetch(url, opts).then(function (r) {
      return r.text().then(function (text) {
        var j;
        try { j = JSON.parse(text); } catch (e) { throw new Error('Invalid server response'); }
        if (!r.ok) throw new Error(String(j.error || j.message || 'Request failed'));
        return j;
      });
    });
  }

  function render() {
    var tbody = el('mciSubPkgBody');
    if (!tbody) return;
    if (!state.items.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-muted small">No packages found.</td></tr>';
      return;
    }
    tbody.innerHTML = state.items.map(function (item) {
      var features = item.features || {};
      var enabledCount = Object.keys(features).filter(function (k) { return !!features[k]; }).length;
      return '<tr>' +
        '<td class="small fw-semibold">' + esc(item.package_name) + '</td>' +
        '<td class="small">' + esc(item.package_type) + '</td>' +
        '<td><span class="badge text-bg-light border">' + esc(item.status) + '</span></td>' +
        '<td class="small">' + esc(item.activation_date || '—') + '</td>' +
        '<td class="small">' + esc(item.expiry_date || '—') + '</td>' +
        '<td class="small">' + esc(item.price) + '</td>' +
        '<td class="small">' + enabledCount + '</td>' +
        '<td class="text-end">' +
          '<button class="btn btn-sm btn-outline-secondary me-1" data-action="edit" data-id="' + esc(item.id) + '">Edit</button>' +
          '<button class="btn btn-sm btn-outline-dark" data-action="default" data-id="' + esc(item.id) + '"' + (item.is_default ? ' disabled' : '') + '>Set default</button>' +
        '</td>' +
      '</tr>';
    }).join('');
  }

  function load() {
    req(api, { credentials: 'include' }).then(function (j) {
      state.items = j.packages || [];
      render();
    }).catch(function (e) {
      show(e.message, true);
    });
  }

  function openForEdit(id) {
    var item = state.items.find(function (x) { return x.id === id; });
    if (!item) return;
    el('pkgId').value = item.id;
    el('pkgName').value = item.package_name || '';
    el('pkgType').value = item.package_type || 'free';
    el('pkgStatus').value = item.status || 'active';
    el('pkgActivation').value = item.activation_date ? String(item.activation_date).slice(0, 19) : '';
    el('pkgExpiry').value = item.expiry_date ? String(item.expiry_date).slice(0, 19) : '';
    el('pkgPrice').value = item.price || 0;
    el('pkgDefault').checked = !!item.is_default;
    el('pkgFeatures').value = JSON.stringify(item.features || {}, null, 2);
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-action]');
    if (!btn) return;
    var id = btn.getAttribute('data-id') || '';
    var action = btn.getAttribute('data-action') || '';
    if (action === 'edit') {
      openForEdit(id);
      return;
    }
    if (action === 'default') {
      req(api, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'set_default', id: id })
      }).then(function () {
        show('Default package updated.');
        load();
      }).catch(function (err) {
        show(err.message, true);
      });
    }
  });

  el('mciSubPkgForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var id = el('pkgId').value.trim();
    var action = id ? 'update' : 'create';
    var featuresText = el('pkgFeatures').value.trim();
    var features;
    try {
      features = featuresText ? JSON.parse(featuresText) : {};
    } catch (err) {
      show('Features must be valid JSON.', true);
      return;
    }
    var payload = {
      action: action,
      id: id || undefined,
      package_name: el('pkgName').value.trim(),
      package_type: el('pkgType').value,
      status: el('pkgStatus').value,
      activation_date: el('pkgActivation').value.trim(),
      expiry_date: el('pkgExpiry').value.trim(),
      price: parseFloat(el('pkgPrice').value || '0'),
      is_default: el('pkgDefault').checked,
      features: features
    };
    req(api, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function () {
      show('Package saved successfully.');
      el('mciSubPkgForm').reset();
      el('pkgId').value = '';
      load();
    }).catch(function (err) {
      show(err.message, true);
    });
  });

  el('pkgResetBtn').addEventListener('click', function () {
    el('pkgId').value = '';
    el('mciSubPkgForm').reset();
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
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div>
            <div class="fw-semibold">Subscription packages</div>
            <div class="text-muted small">Create and manage free/premium plans, activation dates, and defaults.</div>
          </div>
        </div>
        <div id="mciSubPkgAlert" class="alert py-2 small mb-3" hidden></div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Name</th><th>Type</th><th>Status</th><th>Activation</th><th>Expiry</th><th>Price</th><th>Features</th><th></th>
              </tr>
            </thead>
            <tbody id="mciSubPkgBody">
              <tr><td colspan="8" class="text-muted small">Loading packages...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Create / Edit package</div>
        <form id="mciSubPkgForm">
          <input type="hidden" id="pkgId" />
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label" for="pkgName">Package name</label>
              <input id="pkgName" class="form-control" required />
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label" for="pkgType">Type</label>
              <select id="pkgType" class="form-select">
                <option value="free">Free</option>
                <option value="premium">Premium</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label" for="pkgStatus">Status</label>
              <select id="pkgStatus" class="form-select">
                <option value="active">Active</option>
                <option value="coming_soon">Coming soon</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label" for="pkgPrice">Price</label>
              <input id="pkgPrice" type="number" step="0.01" class="form-control" value="0.00" />
            </div>
            <div class="col-6 col-md-2 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" id="pkgDefault" type="checkbox" />
                <label class="form-check-label" for="pkgDefault">Default</label>
              </div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="pkgActivation">Activation date</label>
              <input id="pkgActivation" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="pkgExpiry">Expiry date</label>
              <input id="pkgExpiry" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" />
            </div>
            <div class="col-12">
              <label class="form-label" for="pkgFeatures">Features (JSON)</label>
              <textarea id="pkgFeatures" class="form-control" rows="6" placeholder='{"send_enquiries":true}'></textarea>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-dark" type="submit">Save package</button>
            <button class="btn btn-outline-secondary" id="pkgResetBtn" type="button">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
