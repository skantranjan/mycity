<?php
declare(strict_types=1);

$pageTitle = 'Co-admins - CP - My City Info';
$activePage = '';
$cpActive = 'coadmins';
$hideCta = true;
$appArea = 'cp';

$extraJS = <<<'HTML'
<script>
(function () {
  var COADMIN_KEY = 'mci_cp_coadmins';
  var ROLE_KEY = 'mci_cp_active_cp_role';
  var LABEL_KEY = 'mci_cp_active_cp_user_label';

  // API-first mode with localStorage fallback.
  var useApi = false;
  var apiCoadmins = [];

  function safeParse(json, fallback) {
    try { return JSON.parse(json); } catch (e) { return fallback; }
  }

  function loadCoadmins() {
    if (useApi) return apiCoadmins || [];
    var raw = localStorage.getItem(COADMIN_KEY);
    if (!raw) return [];
    var v = safeParse(raw, []);
    return Array.isArray(v) ? v : [];
  }

  function saveCoadmins(arr) {
    localStorage.setItem(COADMIN_KEY, JSON.stringify(Array.isArray(arr) ? arr : []));
  }

  function setActive(role, label) {
    localStorage.setItem(ROLE_KEY, role);
    localStorage.setItem(LABEL_KEY, label);
  }

  function getActiveRole() {
    var role = (localStorage.getItem(ROLE_KEY) || '').toString();
    if (role !== 'super_admin' && role !== 'co_admin') return 'super_admin';
    return role;
  }

  function getActiveLabel() {
    var label = (localStorage.getItem(LABEL_KEY) || '').toString().trim();
    return label || 'Super admin';
  }

  function refetchFromApi() {
    return fetch('/api/v1/cp/co-admins', { credentials: 'include' })
      .then(function (r) {
        if (!r.ok) throw new Error('api_failed');
        return r.json();
      })
      .then(function (data) {
        var rows = data && (data.co_admins || data.coadmins || []);
        apiCoadmins = (rows || []).map(function (u) {
          return {
            id: u.id,
            name: u.display_name || u.name || '-',
            email: u.email || '',
            createdAt: u.created_at || u.createdAt || ''
          };
        });
        useApi = true;
      });
  }

  function initApiMode() {
    return refetchFromApi().catch(function () {
      useApi = false;
    });
  }

  function renderCoadmins() {
    var tbody = document.getElementById('mciCpCoadminsBody');
    if (!tbody) return;
    var list = loadCoadmins();
    tbody.innerHTML = '';

    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted small">No co-admins added yet.</td></tr>';
      return;
    }

    list.forEach(function (u) {
      if (!u || typeof u !== 'object') return;
      var tr = document.createElement('tr');

      var tdName = document.createElement('td');
      tdName.textContent = u.name || '-';
      tr.appendChild(tdName);

      var tdEmail = document.createElement('td');
      tdEmail.textContent = u.email || '-';
      tr.appendChild(tdEmail);

      var tdWhen = document.createElement('td');
      if (u.createdAt) {
        var d = new Date(u.createdAt);
        tdWhen.textContent = isNaN(d.getTime()) ? u.createdAt : d.toLocaleString();
      } else {
        tdWhen.textContent = '—';
      }
      tr.appendChild(tdWhen);

      var tdActions = document.createElement('td');
      tdActions.className = 'text-end';

      var btnEdit = document.createElement('button');
      btnEdit.type = 'button';
      btnEdit.className = 'btn btn-sm btn-outline-dark me-2';
      btnEdit.textContent = 'Edit';
      btnEdit.addEventListener('click', function () {
        var nextName = window.prompt('Edit name:', u.name || '');
        if (nextName === null) return;
        var nextEmail = window.prompt('Edit email:', u.email || '');
        if (nextEmail === null) return;

        nextName = (nextName || '').toString().trim();
        nextEmail = (nextEmail || '').toString().trim();
        if (!nextName || !nextEmail) return;

        if (useApi) {
          fetch('/api/v1/cp/co-admins', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'edit',
              id: u.id,
              display_name: nextName,
              email: nextEmail
            })
          })
            .then(function () { return refetchFromApi(); })
            .then(function () {
              renderCoadmins();
              renderRoleSwitcher();
            })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = loadCoadmins();
        var idx = arr.findIndex(function (x) { return x && x.id === u.id; });
        if (idx >= 0) {
          arr[idx].name = nextName;
          arr[idx].email = nextEmail;
          saveCoadmins(arr);
          renderCoadmins();
          renderRoleSwitcher();
        }
      });

      var btnRevoke = document.createElement('button');
      btnRevoke.type = 'button';
      btnRevoke.className = 'btn btn-sm btn-outline-danger';
      btnRevoke.textContent = 'Revoke';
      btnRevoke.addEventListener('click', function () {
        if (!window.confirm('Revoke access for ' + (u.name || 'this co-admin') + '?')) return;

        if (useApi) {
          fetch('/api/v1/cp/co-admins/' + encodeURIComponent(String(u.id)) + '/revoke', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
          })
            .then(function () { return refetchFromApi(); })
            .then(function () {
              renderCoadmins();
              renderRoleSwitcher();
            })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = loadCoadmins();
        arr = arr.filter(function (x) { return x && x.id !== u.id; });
        saveCoadmins(arr);
        renderCoadmins();
        renderRoleSwitcher();

        // If they were active, switch back to super admin.
        var role = getActiveRole();
        if (role === 'co_admin') setActive('super_admin', 'Super admin');
      });

      tdActions.appendChild(btnEdit);
      tdActions.appendChild(btnRevoke);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });
  }

  function renderRoleSwitcher() {
    var sel = document.getElementById('mciCpActiveRoleSelect');
    if (!sel) return;

    var list = loadCoadmins();
    var role = getActiveRole();
    var label = getActiveLabel();

    sel.innerHTML = '';

    var optSuper = document.createElement('option');
    optSuper.value = 'super_admin';
    optSuper.textContent = 'Super admin';
    sel.appendChild(optSuper);

    list.forEach(function (u) {
      var opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = 'Co-admin: ' + (u.name || 'User');
      sel.appendChild(opt);
    });

    if (role === 'super_admin') {
      sel.value = 'super_admin';
      setActive('super_admin', 'Super admin');
    } else {
      // If active label doesn't match any current item, fall back.
      var match = list.find(function (u) { return ('Co-admin: ' + (u.name || 'User')) === label; });
      if (!match) {
        sel.value = 'super_admin';
        setActive('super_admin', 'Super admin');
      } else {
        sel.value = match.id;
        setActive('co_admin', label);
      }
    }

    sel.onchange = function () {
      if (sel.value === 'super_admin') {
        setActive('super_admin', 'Super admin');
      } else {
        var u = list.find(function (x) { return x && x.id === sel.value; });
        var name = (u && u.name) ? u.name : 'Co-admin';
        setActive('co_admin', 'Co-admin: ' + name);
      }
      renderRoleSwitcher();
      var banner = document.getElementById('mciCpRoleBanner');
      if (banner) banner.textContent = getActiveLabel();
    };
  }

  // Add co-admin
  var btnAdd = document.getElementById('mciCpAddCoadminBtn');
  if (btnAdd) {
    btnAdd.addEventListener('click', function () {
      var name = (document.getElementById('mciCpCoadminNameInput').value || '').toString().trim();
      var email = (document.getElementById('mciCpCoadminEmailInput').value || '').toString().trim();
      if (!name || !email) return;

      if (useApi) {
        var password = (document.getElementById('mciCpCoadminPasswordInput').value || '').toString();
        password = password.trim();
        if (!password) {
          alert('Password is required to create a co-admin via API.');
          return;
        }

        fetch('/api/v1/cp/co-admins', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'add',
            display_name: name,
            email: email,
            password: password
          })
        })
          .then(function () { return refetchFromApi(); })
          .then(function () {
            document.getElementById('mciCpCoadminNameInput').value = '';
            document.getElementById('mciCpCoadminEmailInput').value = '';
            document.getElementById('mciCpCoadminPasswordInput').value = '';
            renderCoadmins();
            renderRoleSwitcher();
          })
          .catch(function () { useApi = false; });

        return;
      }

      var arr = loadCoadmins();
      var id = 'co_' + Date.now().toString(36) + '_' + Math.random().toString(16).slice(2);
      arr.push({ id: id, name: name, email: email, createdAt: new Date().toISOString() });
      saveCoadmins(arr);

      document.getElementById('mciCpCoadminNameInput').value = '';
      document.getElementById('mciCpCoadminEmailInput').value = '';

      renderCoadmins();
      renderRoleSwitcher();
    });
  }

  var initial = document.getElementById('mciCpRoleBanner');
  if (initial) initial.textContent = getActiveLabel();

  initApiMode().then(function () {
    renderCoadmins();
    renderRoleSwitcher();
  }).catch(function () {
    renderCoadmins();
    renderRoleSwitcher();
  });
})();
</script>
HTML;

$content = '';
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
            <div class="fw-semibold mb-1">Co-admin management</div>
            <div class="text-muted small">Add, edit, revoke via API when auth is ready (fallback to localStorage demo).</div>
          </div>
          <div class="text-muted small">
            Acting as: <span class="fw-semibold" id="mciCpRoleBanner">Super admin</span>
          </div>
        </div>

        <div class="card border-0 bg-light rounded-4 p-3 mb-4">
          <div class="fw-semibold mb-2">Switch CP role (UI demo)</div>
          <div class="d-flex gap-2 flex-wrap align-items-center">
            <label class="form-label mb-0" for="mciCpActiveRoleSelect" style="min-width: 160px;">Active role</label>
            <select id="mciCpActiveRoleSelect" class="form-select form-select-sm" aria-label="Switch CP role"></select>
            <div class="text-muted small">Co-admins can access super-admin screens (demo).</div>
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap align-items-end mb-3">
          <div style="min-width: 240px;">
            <label class="form-label small mb-1" for="mciCpCoadminNameInput">Name</label>
            <input id="mciCpCoadminNameInput" class="form-control" type="text" placeholder="Co-admin name" />
          </div>
          <div style="min-width: 280px;">
            <label class="form-label small mb-1" for="mciCpCoadminEmailInput">Email</label>
            <input id="mciCpCoadminEmailInput" class="form-control" type="email" placeholder="coadmin@example.com" />
          </div>
          <div style="min-width: 280px;">
            <label class="form-label small mb-1" for="mciCpCoadminPasswordInput">Password</label>
            <input id="mciCpCoadminPasswordInput" class="form-control" type="password" placeholder="Temporary password" />
          </div>
          <button id="mciCpAddCoadminBtn" class="btn btn-dark" type="button">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add co-admin
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle bg-white mb-0">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Added</th>
                <th style="width: 220px;" class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="mciCpCoadminsBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

