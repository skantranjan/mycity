<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_cp_session();

$pageTitle = 'Categories & Tags - CP - My City Info';
$activePage = '';
$cpActive = 'categories';
$hideCta = true;
$appArea = 'cp';

$extraHead = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
HTML;

$extraJS = <<<'HTML'
<script>
(function () {
  var apiBase = (typeof window.mciApiUrl === 'function' ? window.mciApiUrl : function (p) { return '/api/v1' + p; });
  var apiCat = apiBase('/cp/categories');
  var apiTag = apiBase('/cp/tags');

  function el(id) { return document.getElementById(id); }

  function toast(msg, err) {
    var box = el('mciCpTaxAlert');
    if (!box) return;
    box.className = 'alert py-2 small ' + (err ? 'alert-danger' : 'alert-success');
    box.textContent = msg;
    box.hidden = false;
    setTimeout(function () { box.hidden = true; }, 5000);
  }

  function apiErr(j) {
    if (!j || typeof j !== 'object') return 'Request failed';
    return String(j.error || j.message || 'Request failed');
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

  var state = { categories: [], tags: [], catMode: 'add', tagMode: 'add', catEditId: null, tagEditId: null };

  function loadAll() {
    return Promise.all([
      fetchJson(apiCat, { credentials: 'include' }),
      fetchJson(apiTag, { credentials: 'include' })
    ]).then(function (pair) {
      state.categories = pair[0].categories || [];
      state.tags = pair[1].tags || [];
    });
  }

  function rootHasChildren(catId) {
    return state.categories.some(function (x) {
      return x.parent_id != null && String(x.parent_id) === String(catId);
    });
  }

  function fillCatParentOptions(selectedId, lockIfRootWithKids) {
    var sel = el('mciCatParent');
    sel.innerHTML = '<option value="">— Top level —</option>';
    state.categories.forEach(function (c) {
      if (c.parent_id) return;
      if (state.catMode === 'edit' && state.catEditId && String(c.id) === String(state.catEditId)) return;
      var opt = document.createElement('option');
      opt.value = String(c.id);
      opt.textContent = c.name;
      if (selectedId != null && String(c.id) === String(selectedId)) opt.selected = true;
      sel.appendChild(opt);
    });
    sel.disabled = !!lockIfRootWithKids;
  }

  function renderCategories() {
    var tbody = el('mciCpCategoriesBody');
    tbody.innerHTML = '';
    if (!state.categories.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted small">No categories in database.</td></tr>';
      return;
    }
    state.categories.forEach(function (c) {
      var tr = document.createElement('tr');
      var parentLabel = '—';
      if (c.parent_id) {
        parentLabel = c.parent_name || ('#' + c.parent_id);
      }
      tr.innerHTML =
        '<td class="fw-semibold">' + esc(c.name) + '</td>' +
        '<td class="small">' + esc(parentLabel) + '</td>' +
        '<td class="small text-muted">' + esc(c.slug || '—') + '</td>' +
        '<td class="small">' + esc(String(c.sort_order != null ? c.sort_order : 0)) + '</td>' +
        '<td class="small text-muted">' + esc(truncDesc(c.description, 80)) + '</td>' +
        '<td class="text-end"></td>';
      var td = tr.querySelector('td:last-child');
      var b1 = document.createElement('button');
      b1.type = 'button';
      b1.className = 'btn btn-sm btn-outline-dark me-1';
      b1.textContent = 'Edit';
      b1.addEventListener('click', function () { openCatPanel('edit', c); });
      var b2 = document.createElement('button');
      b2.type = 'button';
      b2.className = 'btn btn-sm btn-outline-danger';
      b2.textContent = 'Delete';
      b2.addEventListener('click', function () {
        if (!window.confirm('Delete category “' + (c.name || '') + '”?')) return;
        fetchJson(apiCat, {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: c.id })
        })
          .then(function () { toast('Category deleted.', false); return loadAll(); })
          .then(function () { renderCategories(); renderTags(); })
          .catch(function (e) { toast(e.message, true); });
      });
      td.appendChild(b1);
      td.appendChild(b2);
      tbody.appendChild(tr);
    });
  }

  function renderTags() {
    var tbody = el('mciCpTagsBody');
    tbody.innerHTML = '';
    if (!state.tags.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted small">No tags in database.</td></tr>';
      return;
    }
    state.tags.forEach(function (t) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td class="fw-semibold">' + esc(t.name) + '</td>' +
        '<td class="small text-muted">' + esc(t.slug || '—') + '</td>' +
        '<td class="small text-muted">' + esc(truncDesc(t.description, 80)) + '</td>' +
        '<td class="text-end"></td>';
      var td = tr.querySelector('td:last-child');
      var b1 = document.createElement('button');
      b1.type = 'button';
      b1.className = 'btn btn-sm btn-outline-dark me-1';
      b1.textContent = 'Edit';
      b1.addEventListener('click', function () { openTagPanel('edit', t); });
      var b2 = document.createElement('button');
      b2.type = 'button';
      b2.className = 'btn btn-sm btn-outline-danger';
      b2.textContent = 'Delete';
      b2.addEventListener('click', function () {
        if (!window.confirm('Delete tag “' + (t.name || '') + '”?')) return;
        fetchJson(apiTag, {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: t.id })
        })
          .then(function () { toast('Tag deleted.', false); return loadAll(); })
          .then(function () { renderCategories(); renderTags(); })
          .catch(function (e) { toast(e.message, true); });
      });
      td.appendChild(b1);
      td.appendChild(b2);
      tbody.appendChild(tr);
    });
  }

  function esc(s) {
    if (s == null) return '';
    s = String(s);
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function truncDesc(s, n) {
    if (s == null || s === '') return '—';
    s = String(s);
    if (s.length <= n) return s;
    return s.slice(0, n) + '…';
  }

  function openCatPanel(mode, row) {
    state.catMode = mode;
    state.catEditId = row && row.id ? row.id : null;
    el('mciCatPanelTitle').textContent = mode === 'add' ? 'Add category' : 'Edit category';
    el('mciCatName').value = row && row.name ? row.name : '';
    el('mciCatSort').value = row && row.sort_order != null ? String(row.sort_order) : '0';
    el('mciCatSlugNote').textContent = row && row.slug
      ? 'Slug: ' + row.slug + ' (regenerated when name changes).'
      : 'Slug is generated from name (globally unique).';
    var lockParent = !!(row && !row.parent_id && rootHasChildren(row.id));
    fillCatParentOptions(row && row.parent_id ? row.parent_id : null, lockParent);
    el('mciCatParentHelp').textContent = lockParent
      ? 'This category has subcategories; it must stay a top-level category.'
      : 'Subcategories can only be created under a top-level category.';
    el('mciCatPageTitle').value = row && row.page_title ? row.page_title : '';
    el('mciCatMetaKeywords').value = row && row.meta_keywords ? row.meta_keywords : '';
    el('mciCatMetaDescription').value = row && row.meta_description ? row.meta_description : '';
    el('mciCatDescription').value = row && row.description ? row.description : '';
    var panel = el('mciCatOffcanvas');
    (bootstrap.Offcanvas.getInstance(panel) || new bootstrap.Offcanvas(panel)).show();
  }

  function openTagPanel(mode, row) {
    state.tagMode = mode;
    state.tagEditId = row && row.id ? row.id : null;
    el('mciTagPanelTitle').textContent = mode === 'add' ? 'Add tag' : 'Edit tag';
    el('mciTagName').value = row && row.name ? row.name : '';
    el('mciTagSlugNote').textContent = row && row.slug
      ? 'Slug: ' + row.slug + ' (regenerated when name changes).'
      : 'Slug is generated from name (globally unique).';
    el('mciTagPageTitle').value = row && row.page_title ? row.page_title : '';
    el('mciTagMetaKeywords').value = row && row.meta_keywords ? row.meta_keywords : '';
    el('mciTagMetaDescription').value = row && row.meta_description ? row.meta_description : '';
    el('mciTagDescription').value = row && row.description ? row.description : '';
    var panel = el('mciTagOffcanvas');
    (bootstrap.Offcanvas.getInstance(panel) || new bootstrap.Offcanvas(panel)).show();
  }

  el('mciCpAddCategoryBtn').addEventListener('click', function () { openCatPanel('add', null); });
  el('mciCpAddTagBtn').addEventListener('click', function () { openTagPanel('add', null); });

  el('mciCatForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var name = el('mciCatName').value.trim();
    if (!name) return;
    var sortOrder = parseInt(el('mciCatSort').value, 10);
    if (isNaN(sortOrder) || sortOrder < 0) sortOrder = 0;
    var parentVal = el('mciCatParent').value;
    var parentId = parentVal === '' ? null : parseInt(parentVal, 10);

    var seo = {
      page_title: el('mciCatPageTitle').value.trim(),
      meta_keywords: el('mciCatMetaKeywords').value.trim(),
      meta_description: el('mciCatMetaDescription').value.trim()
    };
    var desc = el('mciCatDescription').value;
    var body = state.catMode === 'edit' && state.catEditId
      ? { action: 'update', id: state.catEditId, name: name, sort_order: sortOrder, parent_id: parentId, description: desc, page_title: seo.page_title, meta_keywords: seo.meta_keywords, meta_description: seo.meta_description }
      : { action: 'create', name: name, sort_order: sortOrder, parent_id: parentId, description: desc, page_title: seo.page_title, meta_keywords: seo.meta_keywords, meta_description: seo.meta_description };
    fetchJson(apiCat, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
      .then(function () {
        toast('Category saved.', false);
        var oc = bootstrap.Offcanvas.getInstance(el('mciCatOffcanvas'));
        if (oc) oc.hide();
        return loadAll();
      })
      .then(function () { renderCategories(); })
      .catch(function (e) { toast(e.message, true); });
  });

  el('mciTagForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var name = el('mciTagName').value.trim();
    if (!name) return;
    var seo = {
      page_title: el('mciTagPageTitle').value.trim(),
      meta_keywords: el('mciTagMetaKeywords').value.trim(),
      meta_description: el('mciTagMetaDescription').value.trim()
    };
    var desc = el('mciTagDescription').value;
    var body = state.tagMode === 'edit' && state.tagEditId
      ? { action: 'update', id: state.tagEditId, name: name, description: desc, page_title: seo.page_title, meta_keywords: seo.meta_keywords, meta_description: seo.meta_description }
      : { action: 'create', name: name, description: desc, page_title: seo.page_title, meta_keywords: seo.meta_keywords, meta_description: seo.meta_description };
    fetchJson(apiTag, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    })
      .then(function () {
        toast('Tag saved.', false);
        var oc = bootstrap.Offcanvas.getInstance(el('mciTagOffcanvas'));
        if (oc) oc.hide();
        return loadAll();
      })
      .then(function () { renderTags(); })
      .catch(function (e) { toast(e.message, true); });
  });

  loadAll()
    .then(function () { renderCategories(); renderTags(); })
    .catch(function (e) { toast(e.message || 'Could not load taxonomy', true); });
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
    <div id="mciCpTaxAlert" class="alert py-2 small" role="status" hidden></div>

    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="mb-3">
          <div class="fw-semibold">Categories &amp; tags</div>
          <div class="text-muted small">Categories (and subcategories), and tags can store an optional description plus SEO fields (page title, meta keywords, meta description).</div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#mciTabCategories" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#mciTabTags" role="tab">Tags</button>
          </li>
        </ul>

        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="mciTabCategories" role="tabpanel">
            <div class="d-flex justify-content-end mb-2">
              <button type="button" class="btn btn-dark btn-sm" id="mciCpAddCategoryBtn"><i class="bi bi-plus-lg me-1"></i>Add category</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Slug</th>
                    <th>Sort</th>
                    <th>Description</th>
                    <th class="text-end" style="min-width:140px">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpCategoriesBody">
                  <tr><td colspan="6" class="text-muted small">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="mciTabTags" role="tabpanel">
            <div class="d-flex justify-content-end mb-2">
              <button type="button" class="btn btn-dark btn-sm" id="mciCpAddTagBtn"><i class="bi bi-plus-lg me-1"></i>Add tag</button>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered align-middle bg-white mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th class="text-end" style="min-width:140px">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpTagsBody">
                  <tr><td colspan="4" class="text-muted small">Loading…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="mciCatOffcanvas" aria-labelledby="mciCatPanelTitle">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h5 mb-0" id="mciCatPanelTitle">Category</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciCatForm">
      <div class="mb-3">
        <label class="form-label" for="mciCatName">Name</label>
        <input type="text" class="form-control" id="mciCatName" required maxlength="255" />
        <div class="form-text" id="mciCatSlugNote"></div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCatParent">Parent</label>
        <select class="form-select" id="mciCatParent" aria-describedby="mciCatParentHelp"></select>
        <div class="form-text" id="mciCatParentHelp"></div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCatSort">Sort order</label>
        <input type="number" class="form-control" id="mciCatSort" min="0" value="0" />
        <div class="form-text">Lower numbers first within the same parent.</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCatDescription">Description</label>
        <textarea class="form-control" id="mciCatDescription" rows="4" placeholder="Optional text shown on category pages or in admin (not the SEO meta description below)"></textarea>
      </div>
      <hr class="my-3" />
      <div class="fw-semibold small text-uppercase text-muted mb-2">SEO</div>
      <div class="mb-3">
        <label class="form-label" for="mciCatPageTitle">Page title</label>
        <input type="text" class="form-control" id="mciCatPageTitle" maxlength="255" placeholder="&lt;title&gt; — defaults to category name if empty" autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCatMetaKeywords">Meta keywords</label>
        <input type="text" class="form-control" id="mciCatMetaKeywords" maxlength="512" placeholder="Comma-separated keywords" autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciCatMetaDescription">Meta description</label>
        <textarea class="form-control" id="mciCatMetaDescription" rows="3" maxlength="512" placeholder="Short summary for search results"></textarea>
      </div>
      <button type="submit" class="btn btn-dark w-100">Save</button>
    </form>
  </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="mciTagOffcanvas" aria-labelledby="mciTagPanelTitle">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h5 mb-0" id="mciTagPanelTitle">Tag</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciTagForm">
      <div class="mb-3">
        <label class="form-label" for="mciTagName">Name</label>
        <input type="text" class="form-control" id="mciTagName" required maxlength="255" />
        <div class="form-text" id="mciTagSlugNote"></div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciTagDescription">Description</label>
        <textarea class="form-control" id="mciTagDescription" rows="4" placeholder="Optional text for tag landing pages or internal use"></textarea>
      </div>
      <hr class="my-3" />
      <div class="fw-semibold small text-uppercase text-muted mb-2">SEO</div>
      <div class="mb-3">
        <label class="form-label" for="mciTagPageTitle">Page title</label>
        <input type="text" class="form-control" id="mciTagPageTitle" maxlength="255" placeholder="&lt;title&gt; — optional" autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciTagMetaKeywords">Meta keywords</label>
        <input type="text" class="form-control" id="mciTagMetaKeywords" maxlength="512" placeholder="Comma-separated keywords" autocomplete="off" />
      </div>
      <div class="mb-3">
        <label class="form-label" for="mciTagMetaDescription">Meta description</label>
        <textarea class="form-control" id="mciTagMetaDescription" rows="3" maxlength="512" placeholder="Short summary for search results"></textarea>
      </div>
      <button type="submit" class="btn btn-dark w-100">Save</button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
