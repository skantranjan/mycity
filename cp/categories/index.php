<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_directory_listings.php';

$pageTitle = 'Categories & Tags - CP - My City Info';
$activePage = '';
$cpActive = 'categories';
$hideCta = true;
$appArea = 'cp';

$seedCategories = [];
$seedTags = [];

foreach ($mciDirectoryListings as $row) {
    $c = trim((string)($row['category'] ?? ''));
    if ($c !== '') {
        $seedCategories[$c] = true;
    }
    foreach (($row['tags'] ?? []) as $t) {
        $tt = trim((string) $t);
        if ($tt !== '') {
            $seedTags[$tt] = true;
        }
    }
}

$seedCategories = array_keys($seedCategories);
sort($seedCategories, SORT_NATURAL | SORT_FLAG_CASE);
$seedTags = array_keys($seedTags);
sort($seedTags, SORT_NATURAL | SORT_FLAG_CASE);

$extraJS = <<<'HTML'
<script>
(function () {
  var CAT_KEY = 'mci_cp_categories';
  var TAG_KEY = 'mci_cp_tags';
  var REQ_KEY = 'mci_cp_category_requests';

  // API-first mode (fallback to localStorage if API auth/DB isn't ready yet).
  var useApi = false;
  var apiState = {
    categories: [],
    tags: [],
    requests: []
  };

  var SEED_CATEGORIES = JSON.parse(document.getElementById('mciSeedCategories').textContent);
  var SEED_TAGS = JSON.parse(document.getElementById('mciSeedTags').textContent);

  function safeParse(json, fallback) {
    try { return JSON.parse(json); } catch (e) { return fallback; }
  }

  function loadArray(key) {
    var raw = localStorage.getItem(key);
    if (!raw) return null;
    var v = safeParse(raw, null);
    return Array.isArray(v) ? v : null;
  }

  function seedIfMissing(key, seed) {
    var v = loadArray(key);
    if (v && v.length) return v;
    localStorage.setItem(key, JSON.stringify(seed));
    return seed;
  }

  function uniqStrings(arr) {
    var out = [];
    var set = new Set();
    (arr || []).forEach(function (x) {
      var s = (typeof x === 'string') ? x : (x && (x.name || x.value) ? (x.name || x.value) : '');
      s = (s || '').toString().trim();
      if (!s || set.has(s)) return;
      set.add(s);
      out.push(s);
    });
    return out;
  }

  function getActiveRoleLabel() {
    var label = '';
    try { label = localStorage.getItem('mci_cp_active_cp_user_label') || ''; } catch (e) {}
    label = (label || '').toString().trim();
    return label || 'Super admin';
  }

  function mapRequestsFromApi(rows) {
    return (rows || []).map(function (r) {
      return {
        id: r.id,
        requester: r.requester_id || 'Subscriber',
        category: r.requested_category_name || '',
        reason: r.reason || '',
        createdAt: r.created_at || '',
        status: r.status || 'pending'
      };
    });
  }

  function refetchFromApi() {
    return Promise.all([
      fetch('/api/v1/cp/categories', { credentials: 'include' }).then(function (r) { return r.json(); }),
      fetch('/api/v1/cp/tags', { credentials: 'include' }).then(function (r) { return r.json(); }),
      fetch('/api/v1/cp/category-requests', { credentials: 'include' }).then(function (r) { return r.json(); })
    ]).then(function (results) {
      apiState.categories = (results[0] && results[0].categories) ? results[0].categories : [];
      apiState.tags = (results[1] && results[1].tags) ? results[1].tags : [];
      apiState.requests = mapRequestsFromApi((results[2] && results[2].requests) ? results[2].requests : []);
    });
  }

  function initApiMode() {
    // If the API call fails (401/500), we fall back to localStorage mode.
    return refetchFromApi()
      .then(function () {
        useApi = true;
      })
      .catch(function () {
        useApi = false;
      });
  }

  function getCategories() {
    if (useApi) {
      return (apiState.categories || []).map(function (c) { return c.name; });
    }
    var raw = loadArray(CAT_KEY);
    if (!raw) raw = seedIfMissing(CAT_KEY, SEED_CATEGORIES);
    return uniqStrings(raw);
  }

  function setCategories(arr) {
    localStorage.setItem(CAT_KEY, JSON.stringify(uniqStrings(arr)));
  }

  function getTags() {
    if (useApi) {
      return (apiState.tags || []).map(function (t) { return t.name; });
    }
    var raw = loadArray(TAG_KEY);
    if (!raw) raw = seedIfMissing(TAG_KEY, SEED_TAGS);
    return uniqStrings(raw);
  }

  function setTags(arr) {
    localStorage.setItem(TAG_KEY, JSON.stringify(uniqStrings(arr)));
  }

  function getRequests() {
    if (useApi) {
      return Array.isArray(apiState.requests) ? apiState.requests : [];
    }
    var raw = loadArray(REQ_KEY);
    if (!raw) raw = [];
    return Array.isArray(raw) ? raw : [];
  }

  function setRequests(arr) {
    localStorage.setItem(REQ_KEY, JSON.stringify(Array.isArray(arr) ? arr : []));
  }

  function renderCategories() {
    var tbody = document.getElementById('mciCpCategoriesBody');
    if (!tbody) return;

    var cats = getCategories();
    tbody.innerHTML = '';

    if (!cats.length) {
      tbody.innerHTML = '<tr><td colspan="2" class="text-muted small">No categories yet.</td></tr>';
      return;
    }

    cats.forEach(function (name) {
      var tr = document.createElement('tr');

      var tdName = document.createElement('td');
      tdName.textContent = name;
      tr.appendChild(tdName);

      var tdActions = document.createElement('td');
      tdActions.className = 'text-end';

      var btnEdit = document.createElement('button');
      btnEdit.type = 'button';
      btnEdit.className = 'btn btn-sm btn-outline-dark me-2';
      btnEdit.textContent = 'Edit';
      btnEdit.addEventListener('click', function () {
        var next = window.prompt('Edit category name:', name);
        if (!next) return;
        next = next.toString().trim();
        if (!next) return;

        if (useApi) {
          var cat = (apiState.categories || []).find(function (c) { return c.name === name; });
          if (!cat || !cat.id) return;
          fetch('/api/v1/cp/categories', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', id: cat.id, name: next })
          })
            .then(function () { return refetchFromApi(); })
            .then(function () { renderCategories(); renderRequests(); })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = getCategories();
        var idx = arr.indexOf(name);
        if (idx >= 0) arr[idx] = next;
        setCategories(arr);
        renderCategories();
        renderRequests();
      });

      var btnDelete = document.createElement('button');
      btnDelete.type = 'button';
      btnDelete.className = 'btn btn-sm btn-outline-danger';
      btnDelete.textContent = 'Delete';
      btnDelete.addEventListener('click', function () {
        if (!window.confirm('Delete this category?')) return;

        if (useApi) {
          var cat = (apiState.categories || []).find(function (c) { return c.name === name; });
          if (!cat || !cat.id) return;
          fetch('/api/v1/cp/categories', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: cat.id })
          })
            .then(function () { return refetchFromApi(); })
            .then(function () { renderCategories(); renderRequests(); })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = getCategories().filter(function (x) { return x !== name; });
        setCategories(arr);
        renderCategories();
        renderRequests();
      });

      tdActions.appendChild(btnEdit);
      tdActions.appendChild(btnDelete);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });
  }

  function renderTags() {
    var tbody = document.getElementById('mciCpTagsBody');
    if (!tbody) return;

    var tags = getTags();
    tbody.innerHTML = '';

    if (!tags.length) {
      tbody.innerHTML = '<tr><td colspan="2" class="text-muted small">No tags yet.</td></tr>';
      return;
    }

    tags.forEach(function (name) {
      var tr = document.createElement('tr');

      var tdName = document.createElement('td');
      tdName.textContent = name;
      tr.appendChild(tdName);

      var tdActions = document.createElement('td');
      tdActions.className = 'text-end';

      var btnEdit = document.createElement('button');
      btnEdit.type = 'button';
      btnEdit.className = 'btn btn-sm btn-outline-dark me-2';
      btnEdit.textContent = 'Edit';
      btnEdit.addEventListener('click', function () {
        var next = window.prompt('Edit tag:', name);
        if (!next) return;
        next = next.toString().trim();
        if (!next) return;

        if (useApi) {
          var tag = (apiState.tags || []).find(function (t) { return t.name === name; });
          if (!tag || !tag.id) return;
          fetch('/api/v1/cp/tags', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', id: tag.id, name: next })
          })
            .then(function () { return refetchFromApi(); })
            .then(function () { renderTags(); })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = getTags();
        var idx = arr.indexOf(name);
        if (idx >= 0) arr[idx] = next;
        setTags(arr);
        renderTags();
      });

      var btnDelete = document.createElement('button');
      btnDelete.type = 'button';
      btnDelete.className = 'btn btn-sm btn-outline-danger';
      btnDelete.textContent = 'Delete';
      btnDelete.addEventListener('click', function () {
        if (!window.confirm('Delete this tag?')) return;

        if (useApi) {
          var tag = (apiState.tags || []).find(function (t) { return t.name === name; });
          if (!tag || !tag.id) return;
          fetch('/api/v1/cp/tags', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id: tag.id })
          })
            .then(function () { return refetchFromApi(); })
            .then(function () { renderTags(); })
            .catch(function () { useApi = false; });
          return;
        }

        var arr = getTags().filter(function (x) { return x !== name; });
        setTags(arr);
        renderTags();
      });

      tdActions.appendChild(btnEdit);
      tdActions.appendChild(btnDelete);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });
  }

  function renderRequests() {
    var tbody = document.getElementById('mciCpCategoryRequestsBody');
    if (!tbody) return;

    var reqs = getRequests();
    tbody.innerHTML = '';

    var pending = reqs.filter(function (r) { return (r && r.status) === 'pending'; }).length;
    var resolved = reqs.length - pending;

    var badgePending = document.getElementById('mciCpReqPendingBadge');
    var badgeResolved = document.getElementById('mciCpReqResolvedBadge');
    if (badgePending) badgePending.textContent = String(pending);
    if (badgeResolved) badgeResolved.textContent = String(resolved);

    if (!reqs.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">No category requests yet.</td></tr>';
      return;
    }

    reqs.slice().sort(function (a, b) {
      var da = (a && a.createdAt) ? new Date(a.createdAt).getTime() : 0;
      var db = (b && b.createdAt) ? new Date(b.createdAt).getTime() : 0;
      return db - da;
    }).forEach(function (r) {
      if (!r) return;

      var tr = document.createElement('tr');
      var tdRequester = document.createElement('td');
      tdRequester.textContent = r.requester || 'Subscriber';

      var tdCategory = document.createElement('td');
      tdCategory.textContent = r.category || '';

      var tdReason = document.createElement('td');
      tdReason.textContent = r.reason || '';

      var tdWhen = document.createElement('td');
      if (r.createdAt) {
        var d = new Date(r.createdAt);
        tdWhen.textContent = isNaN(d.getTime()) ? r.createdAt : d.toLocaleString();
      } else {
        tdWhen.textContent = '—';
      }

      var tdStatus = document.createElement('td');
      var span = document.createElement('span');
      span.className = 'badge rounded-pill ' + ((r.status === 'pending') ? 'text-bg-warning' : (r.status === 'approved' ? 'text-bg-success' : 'text-bg-secondary'));
      span.textContent = (r.status || 'pending');
      tdStatus.appendChild(span);

      var tdActions = document.createElement('td');
      tdActions.className = 'text-end';

      if (r.status === 'pending') {
        var btnApprove = document.createElement('button');
        btnApprove.type = 'button';
        btnApprove.className = 'btn btn-sm btn-outline-success me-2';
        btnApprove.textContent = 'Approve';
        btnApprove.addEventListener('click', function () {
          if (useApi) {
            if (!r || !r.id) return;
            fetch('/api/v1/cp/category-requests/' + encodeURIComponent(String(r.id)) + '/approve', {
              method: 'POST',
              credentials: 'include',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({})
            })
              .then(function () { return refetchFromApi(); })
              .then(function () { renderCategories(); renderRequests(); })
              .catch(function () { useApi = false; });
            return;
          }

          var cats = getCategories();
          var cat = (r.category || '').toString().trim();
          if (cat && cats.indexOf(cat) === -1) cats.push(cat);
          setCategories(cats);

          var all = getRequests();
          all.forEach(function (x) {
            if (x === r) x.status = 'approved';
          });
          // In case object identity differs, fallback to matching by createdAt+requester+category.
          all = all.map(function (x) {
            if (!x) return x;
            var match = (x.requester === r.requester && x.category === r.category && x.createdAt === r.createdAt);
            if (match) x.status = 'approved';
            return x;
          });
          setRequests(all);

          renderCategories();
          renderRequests();
        });

        var btnReject = document.createElement('button');
        btnReject.type = 'button';
        btnReject.className = 'btn btn-sm btn-outline-danger';
        btnReject.textContent = 'Reject';
        btnReject.addEventListener('click', function () {
          if (useApi) {
            if (!r || !r.id) return;
            fetch('/api/v1/cp/category-requests/' + encodeURIComponent(String(r.id)) + '/reject', {
              method: 'POST',
              credentials: 'include',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({})
            })
              .then(function () { return refetchFromApi(); })
              .then(function () { renderRequests(); })
              .catch(function () { useApi = false; });
            return;
          }

          var all = getRequests();
          all = all.map(function (x) {
            if (!x) return x;
            var match = (x.requester === r.requester && x.category === r.category && x.createdAt === r.createdAt);
            if (match) x.status = 'rejected';
            return x;
          });
          setRequests(all);
          renderRequests();
        });

        tdActions.appendChild(btnApprove);
        tdActions.appendChild(btnReject);
      } else {
        tdActions.textContent = '—';
      }

      tr.appendChild(tdRequester);
      tr.appendChild(tdCategory);
      tr.appendChild(tdReason);
      tr.appendChild(tdWhen);
      tr.appendChild(tdStatus);
      tr.appendChild(tdActions);

      tbody.appendChild(tr);
    });
  }

  // UI: Add category
  var btnAddCategory = document.getElementById('mciCpAddCategoryBtn');
  if (btnAddCategory) {
    btnAddCategory.addEventListener('click', function () {
      var input = document.getElementById('mciCpNewCategoryInput');
      if (!input) return;
      var name = (input.value || '').toString().trim();
      if (!name) return;

      if (useApi) {
        fetch('/api/v1/cp/categories', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'create', name: name })
        })
          .then(function () { return refetchFromApi(); })
          .then(function () { input.value = ''; renderCategories(); renderRequests(); })
          .catch(function () { useApi = false; });
        return;
      }

      var cats = getCategories();
      if (cats.indexOf(name) === -1) cats.push(name);
      setCategories(cats);
      input.value = '';
      renderCategories();
      renderRequests();
    });
  }

  // UI: Add tag
  var btnAddTag = document.getElementById('mciCpAddTagBtn');
  if (btnAddTag) {
    btnAddTag.addEventListener('click', function () {
      var input = document.getElementById('mciCpNewTagInput');
      if (!input) return;
      var name = (input.value || '').toString().trim();
      if (!name) return;

      if (useApi) {
        fetch('/api/v1/cp/tags', {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'create', name: name })
        })
          .then(function () { return refetchFromApi(); })
          .then(function () { input.value = ''; renderTags(); })
          .catch(function () { useApi = false; });
        return;
      }

      var tags = getTags();
      if (tags.indexOf(name) === -1) tags.push(name);
      setTags(tags);
      input.value = '';
      renderTags();
    });
  }

  // Render initial UI
  initApiMode().then(function () {
    if (useApi) {
      renderCategories();
      renderTags();
      renderRequests();
    } else {
      seedIfMissing(CAT_KEY, SEED_CATEGORIES);
      seedIfMissing(TAG_KEY, SEED_TAGS);
      renderCategories();
      renderTags();
      renderRequests();
    }

    var roleLabel = getActiveRoleLabel();
    var roleEl = document.getElementById('mciCpActiveRoleLabel');
    if (roleEl) roleEl.textContent = roleLabel;
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
            <div class="fw-semibold mb-1">Categories, tags &amp; requests</div>
            <div class="text-muted small">
              Demo-only management in <code>localStorage</code>. Acting as: <span id="mciCpActiveRoleLabel">Super admin</span>
            </div>
          </div>
          <div class="text-muted small">
            <span class="badge text-bg-light border me-2">Pending <span id="mciCpReqPendingBadge">0</span></span>
            <span class="badge text-bg-light border">Resolved <span id="mciCpReqResolvedBadge">0</span></span>
          </div>
        </div>

        <ul class="nav nav-tabs mci-tabs" role="tablist" aria-label="Category admin tabs">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#mciTabCategories" role="tab" aria-selected="true">
              Categories
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#mciTabTags" role="tab" aria-selected="false">
              Tags
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#mciTabRequests" role="tab" aria-selected="false">
              Category requests
            </button>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="mciTabCategories" role="tabpanel">
            <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
              <input id="mciCpNewCategoryInput" class="form-control" style="max-width: 360px;" type="text" placeholder="New category name" />
              <button id="mciCpAddCategoryBtn" class="btn btn-dark">Add category</button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Category</th>
                    <th style="width: 200px;" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpCategoriesBody"></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="mciTabTags" role="tabpanel">
            <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
              <input id="mciCpNewTagInput" class="form-control" style="max-width: 360px;" type="text" placeholder="New tag" />
              <button id="mciCpAddTagBtn" class="btn btn-dark">Add tag</button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Tag</th>
                    <th style="width: 200px;" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpTagsBody"></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="mciTabRequests" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Requested by</th>
                    <th>Requested category</th>
                    <th>Reason / for what</th>
                    <th>When</th>
                    <th>Status</th>
                    <th style="width: 220px;" class="text-end">Admin actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpCategoryRequestsBody"></tbody>
              </table>
            </div>
            <div class="text-muted small mt-2">
              When a request is approved, the category is added to the CP categories list (demo/localStorage).
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script id="mciSeedCategories" type="application/json"><?= htmlspecialchars(json_encode($seedCategories, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>
<script id="mciSeedTags" type="application/json"><?= htmlspecialchars(json_encode($seedTags, JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>

