<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';

mci_require_cp_session();

$pageTitle = 'Categories & Tags — My City Info CP';
$activePage = '';
$cpActive = 'categories';
$hideCta = true;
$appArea = 'cp';

$extraHead = '';

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
    box.className = 'alert py-2 small mb-3 ' + (err ? 'alert-danger' : 'alert-success');
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
        try { j = JSON.parse(text); } catch (e) {
          throw new Error('API returned non-JSON. Check: ' + url);
        }
        if (!r.ok) throw new Error(apiErr(j));
        return j;
      });
    });
  }

  function esc(s) {
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function truncDesc(s, n) {
    if (s == null || s === '') return '';
    s = String(s);
    return s.length <= n ? s : s.slice(0, n) + '…';
  }

  var state = {
    categories: [], tags: [],
    catMode: 'add', tagMode: 'add',
    catEditId: null, tagEditId: null,
    catQ: '', tagQ: '',
    catParentFilter: ''   // '' = all, 'top' = top-level only, or numeric string parent id
  };

  function loadAll() {
    return Promise.all([
      fetchJson(apiCat, { credentials: 'include' }),
      fetchJson(apiTag, { credentials: 'include' })
    ]).then(function (pair) {
      state.categories = pair[0].categories || [];
      state.tags = pair[1].tags || [];
      updateCounts();
    });
  }

  function updateCounts() {
    var catCount = countVisibleRoots();
    var tagCount = state.tags.filter(function (t) {
      return matchQ(t.name, state.tagQ);
    }).length;
    var catBadge = el('mciCatCountBadge');
    var tagBadge = el('mciTagCountBadge');
    if (catBadge) catBadge.textContent = catCount;
    if (tagBadge) tagBadge.textContent = tagCount;
  }

  function matchQ(name, q) {
    if (!q) return true;
    return String(name || '').toLowerCase().indexOf(q.toLowerCase()) !== -1;
  }

  function buildRootsAndChildMap() {
    var roots = [], childMap = {};
    state.categories.forEach(function (c) {
      if (!c.parent_id) {
        roots.push(c);
        childMap[c.id] = childMap[c.id] || [];
      } else {
        childMap[c.parent_id] = childMap[c.parent_id] || [];
        childMap[c.parent_id].push(c);
      }
    });
    return { roots: roots, childMap: childMap };
  }

  function rootPassesFilter(r, childMap) {
    var pf = state.catParentFilter;
    var q  = state.catQ;
    // Parent filter
    if (pf === 'top') {
      // top-level only → only show roots that have no children
      if ((childMap[r.id] || []).length > 0) return false;
    } else if (pf !== '') {
      // specific parent id → only show that one root
      if (String(r.id) !== pf) return false;
    }
    // Text search
    if (matchQ(r.name, q)) return true;
    return (childMap[r.id] || []).some(function (ch) { return matchQ(ch.name, q); });
  }

  function countVisibleRoots() {
    var m = buildRootsAndChildMap();
    return m.roots.filter(function (r) { return rootPassesFilter(r, m.childMap); }).length;
  }

  function fillParentFilterSelect() {
    var sel = el('mciCatParentFilterSelect');
    if (!sel) return;
    var prev = sel.value;
    sel.innerHTML = '<option value="">All categories</option>' +
                    '<option value="top">Top-level only</option>';
    state.categories.forEach(function (c) {
      if (c.parent_id) return; // roots only
      var opt = document.createElement('option');
      opt.value = String(c.id);
      opt.textContent = c.name;
      sel.appendChild(opt);
    });
    // restore previous selection if still valid
    if (prev && Array.from(sel.options).some(function (o) { return o.value === prev; })) {
      sel.value = prev;
    }
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

  // ── Render categories as grouped tree ────────────────────
  function renderCategories() {
    var q = state.catQ;
    var tbody = el('mciCpCategoriesBody');
    tbody.innerHTML = '';

    var m = buildRootsAndChildMap();
    var roots = m.roots, childMap = m.childMap;
    var visibleRoots = roots.filter(function (r) { return rootPassesFilter(r, childMap); });

    if (!visibleRoots.length) {
      var emptyMsg = (q || state.catParentFilter)
        ? 'No categories match the current filters.'
        : 'No categories yet — add one!';
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4 small">' + emptyMsg + '</td></tr>';
      updateCounts();
      return;
    }

    visibleRoots.forEach(function (root) {
      // Parent row
      var tr = makeRow(root, false, q);
      tbody.appendChild(tr);

      // Children — hide if "top-level only" filter is active
      var children = state.catParentFilter === 'top' ? [] :
        (childMap[root.id] || []).filter(function (ch) { return !q || matchQ(ch.name, q); });
      children.forEach(function (child) {
        var ctr = makeRow(child, true, q);
        tbody.appendChild(ctr);
      });

      // "Add subcategory" shortcut row
      var addRow = document.createElement('tr');
      addRow.className = 'mci-cat-addchild-row';
      addRow.innerHTML =
        '<td colspan="6" style="padding:0.25rem 1rem 0.5rem 2.5rem;">' +
        '<button type="button" class="btn btn-link btn-sm text-muted p-0 mci-add-sub-btn" data-parent-id="' + esc(String(root.id)) + '" data-parent-name="' + esc(root.name) + '">' +
        '<i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Add subcategory under ' + esc(root.name) +
        '</button></td>';
      tbody.appendChild(addRow);
    });

    updateCounts();
  }

  function makeRow(c, isChild, q) {
    var tr = document.createElement('tr');
    tr.className = isChild ? 'mci-cat-child-row' : 'mci-cat-root-row';

    var iconHtml = c.icon
      ? '<span class="mci-cat-icon-bubble" aria-hidden="true">' + esc(c.icon) + '</span>'
      : '<span class="mci-cat-icon-bubble mci-cat-icon-bubble--empty" aria-hidden="true"><i class="bi bi-tag" style="font-size:0.85rem;opacity:0.4;"></i></span>';

    var nameHtml = isChild
      ? '<span class="mci-cat-child-indent"><i class="bi bi-arrow-return-right text-muted me-1" aria-hidden="true" style="font-size:0.7rem;"></i>' + esc(c.name) + '</span>'
      : '<span class="fw-semibold">' + esc(c.name) + '</span>';

    var descHtml = c.description
      ? '<span class="mci-cat-desc-tip" title="' + esc(c.description) + '">' + esc(truncDesc(c.description, 60)) + '</span>'
      : '<span class="text-muted" style="font-size:var(--mci-text-micro);">—</span>';

    var slugHtml = '<code class="mci-cat-slug">' + esc(c.slug || '') + '</code>';

    var parentLabel = isChild
      ? '<span class="badge text-bg-light border text-muted" style="font-size:var(--mci-text-micro);">' + esc(c.parent_name || '') + '</span>'
      : '<span class="mci-top-level-badge">Top level</span>';

    tr.innerHTML =
      '<td class="mci-cat-name-cell">' + iconHtml + ' ' + nameHtml + '</td>' +
      '<td>' + parentLabel + '</td>' +
      '<td>' + slugHtml + '</td>' +
      '<td class="text-muted">' + descHtml + '</td>' +
      '<td class="text-center text-muted" style="width:52px;font-size:var(--mci-text-xs);">' + esc(String(c.sort_order != null ? c.sort_order : 0)) + '</td>' +
      '<td class="text-end mci-cat-actions-cell"></td>';

    var actionTd = tr.querySelector('.mci-cat-actions-cell');

    var editBtn = document.createElement('button');
    editBtn.type = 'button';
    editBtn.className = 'btn btn-sm btn-outline-secondary mci-icon-btn';
    editBtn.title = 'Edit';
    editBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
    editBtn.addEventListener('click', function () { openCatPanel('edit', c); });

    var delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger mci-icon-btn';
    delBtn.title = 'Delete';
    delBtn.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
    delBtn.addEventListener('click', function () {
      if (!window.confirm('Delete "' + (c.name || '') + '"? This cannot be undone.')) return;
      fetchJson(apiCat, {
        method: 'POST', credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: c.id })
      })
        .then(function () { toast('Category deleted.', false); return loadAll(); })
        .then(function () { fillParentFilterSelect(); renderCategories(); renderTags(); })
        .catch(function (e) { toast(e.message, true); });
    });

    actionTd.appendChild(editBtn);
    actionTd.appendChild(delBtn);
    return tr;
  }

  // ── Render tags ──────────────────────────────────────────
  function renderTags() {
    var q = state.tagQ;
    var tbody = el('mciCpTagsBody');
    tbody.innerHTML = '';

    var filtered = state.tags.filter(function (t) { return matchQ(t.name, q); });

    if (!filtered.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4 small">' +
        (q ? 'No tags match your search.' : 'No tags yet — add one!') + '</td></tr>';
      updateCounts();
      return;
    }

    filtered.forEach(function (t) {
      var tr = document.createElement('tr');
      var descHtml = t.description
        ? '<span class="mci-cat-desc-tip" title="' + esc(t.description) + '">' + esc(truncDesc(t.description, 80)) + '</span>'
        : '<span class="text-muted" style="font-size:var(--mci-text-micro);">—</span>';

      tr.innerHTML =
        '<td><span class="mci-tag-chip">' + esc(t.name) + '</span></td>' +
        '<td><code class="mci-cat-slug">' + esc(t.slug || '') + '</code></td>' +
        '<td class="text-muted small">' + descHtml + '</td>' +
        '<td class="text-end mci-cat-actions-cell"></td>';

      var actionTd = tr.querySelector('.mci-cat-actions-cell');

      var editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn btn-sm btn-outline-secondary mci-icon-btn';
      editBtn.title = 'Edit';
      editBtn.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';
      editBtn.addEventListener('click', function () { openTagPanel('edit', t); });

      var delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn btn-sm btn-outline-danger mci-icon-btn';
      delBtn.title = 'Delete';
      delBtn.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i>';
      delBtn.addEventListener('click', function () {
        if (!window.confirm('Delete tag "' + (t.name || '') + '"?')) return;
        fetchJson(apiTag, {
          method: 'POST', credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'delete', id: t.id })
        })
          .then(function () { toast('Tag deleted.', false); return loadAll(); })
          .then(function () { renderCategories(); renderTags(); })
          .catch(function (e) { toast(e.message, true); });
      });

      actionTd.appendChild(editBtn);
      actionTd.appendChild(delBtn);
      tbody.appendChild(tr);
    });

    updateCounts();
  }

  // ── Panel: category ──────────────────────────────────────
  function openCatPanel(mode, row) {
    state.catMode = mode;
    state.catEditId = row && row.id ? row.id : null;
    el('mciCatPanelTitle').textContent = mode === 'add' ? 'Add category' : 'Edit "' + (row ? row.name : '') + '"';
    el('mciCatName').value = row && row.name ? row.name : '';
    el('mciCatSort').value = row && row.sort_order != null ? String(row.sort_order) : '0';
    el('mciCatSlugNote').textContent = row && row.slug
      ? 'Current slug: ' + row.slug
      : 'Slug auto-generated from name.';
    var lockParent = !!(row && !row.parent_id && rootHasChildren(row.id));
    fillCatParentOptions(row && row.parent_id ? row.parent_id : null, lockParent);
    el('mciCatParentHelp').textContent = lockParent
      ? 'Has subcategories — must stay top-level.'
      : 'Subcategories can only nest one level deep.';
    el('mciCatPageTitle').value = row && row.page_title ? row.page_title : '';
    el('mciCatMetaKeywords').value = row && row.meta_keywords ? row.meta_keywords : '';
    el('mciCatMetaDescription').value = row && row.meta_description ? row.meta_description : '';
    el('mciCatDescription').value = row && row.description ? row.description : '';
    var iconVal = row && row.icon ? row.icon : '';
    el('mciCatIcon').value = iconVal;
    el('mciCatIconPreview').textContent = iconVal || '—';
    el('mciCatIconSuggestions').innerHTML = '';
    el('mciCatIconSuggestions').style.display = 'none';
    el('mciCatIconPicker').style.display = 'none';
    var panel = el('mciCatOffcanvas');
    (bootstrap.Offcanvas.getInstance(panel) || new bootstrap.Offcanvas(panel)).show();
  }

  function openTagPanel(mode, row) {
    state.tagMode = mode;
    state.tagEditId = row && row.id ? row.id : null;
    el('mciTagPanelTitle').textContent = mode === 'add' ? 'Add tag' : 'Edit "' + (row ? row.name : '') + '"';
    el('mciTagName').value = row && row.name ? row.name : '';
    el('mciTagSlugNote').textContent = row && row.slug
      ? 'Current slug: ' + row.slug
      : 'Slug auto-generated from name.';
    el('mciTagPageTitle').value = row && row.page_title ? row.page_title : '';
    el('mciTagMetaKeywords').value = row && row.meta_keywords ? row.meta_keywords : '';
    el('mciTagMetaDescription').value = row && row.meta_description ? row.meta_description : '';
    el('mciTagDescription').value = row && row.description ? row.description : '';
    var panel = el('mciTagOffcanvas');
    (bootstrap.Offcanvas.getInstance(panel) || new bootstrap.Offcanvas(panel)).show();
  }

  // "Add subcategory" shortcut
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.mci-add-sub-btn');
    if (!btn) return;
    var pid = btn.dataset.parentId;
    var pname = btn.dataset.parentName;
    openCatPanel('add', null);
    // pre-select the parent after panel opens
    setTimeout(function () {
      var sel = el('mciCatParent');
      if (sel) {
        sel.value = pid;
        el('mciCatParentHelp').textContent = 'Subcategory of "' + pname + '"';
      }
    }, 50);
  });

  el('mciCpAddCategoryBtn').addEventListener('click', function () { openCatPanel('add', null); });
  el('mciCpAddTagBtn').addEventListener('click', function () { openTagPanel('add', null); });

  // ── Search + filter wiring ───────────────────────────────
  el('mciCatSearch').addEventListener('input', function () {
    state.catQ = this.value.trim();
    renderCategories();
  });
  el('mciCatParentFilterSelect').addEventListener('change', function () {
    state.catParentFilter = this.value;
    renderCategories();
  });
  el('mciTagSearch').addEventListener('input', function () {
    state.tagQ = this.value.trim();
    renderTags();
  });

  // ── Form: save category ──────────────────────────────────
  el('mciCatForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var name = el('mciCatName').value.trim();
    if (!name) return;
    var sortOrder = parseInt(el('mciCatSort').value, 10);
    if (isNaN(sortOrder) || sortOrder < 0) sortOrder = 0;
    var parentVal = el('mciCatParent').value;
    var parentId = parentVal === '' ? null : parseInt(parentVal, 10);
    var icon = el('mciCatIcon').value.trim();
    var body = state.catMode === 'edit' && state.catEditId
      ? { action:'update', id:state.catEditId, name:name, icon:icon, sort_order:sortOrder, parent_id:parentId,
          description:el('mciCatDescription').value,
          page_title:el('mciCatPageTitle').value.trim(),
          meta_keywords:el('mciCatMetaKeywords').value.trim(),
          meta_description:el('mciCatMetaDescription').value.trim() }
      : { action:'create', name:name, icon:icon, sort_order:sortOrder, parent_id:parentId,
          description:el('mciCatDescription').value,
          page_title:el('mciCatPageTitle').value.trim(),
          meta_keywords:el('mciCatMetaKeywords').value.trim(),
          meta_description:el('mciCatMetaDescription').value.trim() };
    var saveBtn = ev.target.querySelector('[type="submit"]');
    saveBtn.disabled = true;
    fetchJson(apiCat, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) })
      .then(function () {
        toast('Category saved.', false);
        var oc = bootstrap.Offcanvas.getInstance(el('mciCatOffcanvas'));
        if (oc) oc.hide();
        return loadAll();
      })
      .then(function () { fillParentFilterSelect(); renderCategories(); })
      .catch(function (e) { toast(e.message, true); })
      .finally(function () { saveBtn.disabled = false; });
  });

  // ── Form: save tag ───────────────────────────────────────
  el('mciTagForm').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var name = el('mciTagName').value.trim();
    if (!name) return;
    var body = state.tagMode === 'edit' && state.tagEditId
      ? { action:'update', id:state.tagEditId, name:name,
          description:el('mciTagDescription').value,
          page_title:el('mciTagPageTitle').value.trim(),
          meta_keywords:el('mciTagMetaKeywords').value.trim(),
          meta_description:el('mciTagMetaDescription').value.trim() }
      : { action:'create', name:name,
          description:el('mciTagDescription').value,
          page_title:el('mciTagPageTitle').value.trim(),
          meta_keywords:el('mciTagMetaKeywords').value.trim(),
          meta_description:el('mciTagMetaDescription').value.trim() };
    var saveBtn = ev.target.querySelector('[type="submit"]');
    saveBtn.disabled = true;
    fetchJson(apiTag, { method:'POST', credentials:'include', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) })
      .then(function () {
        toast('Tag saved.', false);
        var oc = bootstrap.Offcanvas.getInstance(el('mciTagOffcanvas'));
        if (oc) oc.hide();
        return loadAll();
      })
      .then(function () { renderTags(); })
      .catch(function (e) { toast(e.message, true); })
      .finally(function () { saveBtn.disabled = false; });
  });

  // ── Icon field wiring ────────────────────────────────────
  var MCI_ICON_KEYWORDS = [
    { keywords:['real estate','property','housing','land'], icon:'🏠' },
    { keywords:['restaurant','food','dining','eat'], icon:'🍽️' },
    { keywords:['cafe','coffee','tea'], icon:'☕' },
    { keywords:['health','medical','doctor','clinic','hospital'], icon:'⚕️' },
    { keywords:['gym','fitness','sport','exercise','crossfit'], icon:'💪' },
    { keywords:['hotel','accommodation','stay','inn'], icon:'🏨' },
    { keywords:['resort','holiday','leisure'], icon:'🏖️' },
    { keywords:['bakery','bread','cake','pastry'], icon:'🥐' },
    { keywords:['painter','painting','decor','colour'], icon:'🎨' },
    { keywords:['furniture','sofa','locker','storage','shelf'], icon:'🛋️' },
    { keywords:['electrician','electric','wiring','power'], icon:'⚡' },
    { keywords:['automotive','car','vehicle','garage'], icon:'🚗' },
    { keywords:['park','garden','outdoor','nature','walk'], icon:'🌳' },
    { keywords:['dentist','dental','teeth','orthodontic'], icon:'🦷' },
    { keywords:['spa','massage','wellness','beauty','salon'], icon:'🧖' },
    { keywords:['school','education','college','university','learn','coaching'], icon:'🎓' },
    { keywords:['bank','finance','insurance','money','loan'], icon:'🏦' },
    { keywords:['pharmacy','medicine','drug','chemist'], icon:'💊' },
    { keywords:['travel','tourism','agency','trip','tour'], icon:'✈️' },
    { keywords:['supermarket','grocery','mart','store'], icon:'🛒' },
    { keywords:['pet','vet','animal'], icon:'🐾' },
    { keywords:['lawyer','legal','law','advocate'], icon:'⚖️' },
    { keywords:['plumber','plumbing','pipe'], icon:'🔧' },
    { keywords:['church','mosque','temple','religion'], icon:'🙏' },
    { keywords:['movie','cinema','theater','entertainment'], icon:'🎬' },
    { keywords:['laundry','dry clean','wash'], icon:'👕' },
    { keywords:['florist','flower','plant'], icon:'🌸' },
    { keywords:['hospital','emergency'], icon:'🏥' },
    { keywords:['library','book','reading'], icon:'📚' },
    { keywords:['hardware','tools','builder','construction'], icon:'🔨' },
    { keywords:['jewellery','jewelry','gold','gem'], icon:'💎' },
    { keywords:['clothing','fashion','apparel'], icon:'👗' },
    { keywords:['shopping','mall','boutique','gift'], icon:'🛍️' },
    { keywords:['petrol','fuel','pump','gas'], icon:'⛽' },
    { keywords:['physiotherapist','physio','rehab'], icon:'🏃' },
  ];

  var MCI_EMOJI_GRID = [
    '🏠','🏢','🏪','🏨','🏦','🏥','🏫','🏬','🏭','🏛️',
    '🍽️','☕','🥐','🍕','🍣','🥗','🍺','🎂','🛒','🌮',
    '⚕️','💊','🦷','🩺','💪','🧖','🏋️','🤸','⚽','🎾',
    '🚗','🚌','✈️','🚢','🚲','⚡','🔧','🔨','🎨','🛋️',
    '🎓','📚','💼','⚖️','💰','🏦','📱','💻','📷','🎬',
    '🌳','🌸','🐾','🙏','🎵','🎪','🎭','🌟','💡','🔑',
  ];

  var iconPickerBuilt = false;

  function updateIconPreview(val) {
    el('mciCatIconPreview').textContent = val || '—';
  }

  el('mciCatIcon').addEventListener('input', function () {
    updateIconPreview(this.value.trim());
  });

  el('mciCatIconSuggest').addEventListener('click', function () {
    var name = (el('mciCatName').value || '').toLowerCase();
    var scored = MCI_ICON_KEYWORDS.map(function (entry) {
      var score = entry.keywords.filter(function (kw) { return name.indexOf(kw) !== -1; }).length;
      return { icon:entry.icon, score:score };
    }).filter(function (x) { return x.score > 0; });
    scored.sort(function (a, b) { return b.score - a.score; });
    var top = scored.slice(0, 5);
    var box = el('mciCatIconSuggestions');
    box.innerHTML = '';
    if (!top.length) {
      box.innerHTML = '<span class="text-muted small">No suggestion — browse below.</span>';
      box.style.display = 'block';
    } else {
      top.forEach(function (item) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mci-icon-pick-btn';
        btn.textContent = item.icon;
        btn.title = item.icon;
        btn.addEventListener('click', function () {
          el('mciCatIcon').value = item.icon;
          updateIconPreview(item.icon);
        });
        box.appendChild(btn);
      });
      box.style.display = 'flex';
    }
  });

  el('mciCatIconPickerToggle').addEventListener('click', function () {
    var picker = el('mciCatIconPicker');
    var isHidden = picker.style.display === 'none' || picker.style.display === '';
    if (!iconPickerBuilt) {
      MCI_EMOJI_GRID.forEach(function (emoji) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.title = emoji;
        btn.className = 'mci-icon-pick-btn';
        btn.textContent = emoji;
        btn.addEventListener('click', function () {
          el('mciCatIcon').value = emoji;
          updateIconPreview(emoji);
          picker.style.display = 'none';
        });
        picker.appendChild(btn);
      });
      iconPickerBuilt = true;
    }
    picker.style.display = isHidden ? 'flex' : 'none';
  });

  loadAll()
    .then(function () { fillParentFilterSelect(); renderCategories(); renderTags(); })
    .catch(function (e) { toast(e.message || 'Could not load data', true); });
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

    <!-- Page header -->
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
      <div>
        <div class="fw-semibold" style="font-size:var(--mci-text-xl);letter-spacing:-0.02em;">Categories &amp; Tags</div>
        <div class="text-muted small mt-1">Organise your directory. Categories support one level of subcategories. Tags are flat labels.</div>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-body p-0">

        <!-- Tab bar -->
        <div class="d-flex align-items-center justify-content-between border-bottom px-4 pt-3 pb-0 flex-wrap gap-2">
          <ul class="nav nav-tabs border-0 gap-1" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active px-3" type="button" data-bs-toggle="tab" data-bs-target="#mciTabCategories" role="tab">
                <i class="bi bi-tags me-1" aria-hidden="true"></i>
                Categories
                <span class="badge rounded-pill text-bg-secondary ms-1" id="mciCatCountBadge">—</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link px-3" type="button" data-bs-toggle="tab" data-bs-target="#mciTabTags" role="tab">
                <i class="bi bi-hash me-1" aria-hidden="true"></i>
                Tags
                <span class="badge rounded-pill text-bg-secondary ms-1" id="mciTagCountBadge">—</span>
              </button>
            </li>
          </ul>
        </div>

        <div class="tab-content">

          <!-- ── Categories tab ──────────────────────────── -->
          <div class="tab-pane fade show active" id="mciTabCategories" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between gap-2 px-4 py-3 border-bottom flex-wrap">
              <!-- Search + parent filter -->
              <div class="d-flex align-items-center gap-2 flex-wrap" style="flex:1 1 auto;">
                <div class="position-relative" style="min-width:180px;flex:1 1 180px;max-width:260px;">
                  <i class="bi bi-search position-absolute text-muted" style="left:0.7rem;top:50%;transform:translateY(-50%);pointer-events:none;" aria-hidden="true"></i>
                  <input type="text" id="mciCatSearch" class="form-control form-control-sm ps-4" placeholder="Search categories…" autocomplete="off" />
                </div>
                <select id="mciCatParentFilterSelect" class="form-select form-select-sm" style="min-width:160px;max-width:220px;flex:1 1 160px;" aria-label="Filter by parent category">
                  <option value="">All categories</option>
                </select>
              </div>
              <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-1 flex-shrink-0" id="mciCpAddCategoryBtn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add category
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 mci-tax-table">
                <thead class="table-light">
                  <tr>
                    <th style="min-width:220px;">Name</th>
                    <th style="width:120px;">Parent</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th style="width:52px;" class="text-center">Sort</th>
                    <th style="width:88px;" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpCategoriesBody">
                  <tr><td colspan="6" class="text-muted small py-4 text-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…
                  </td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ── Tags tab ────────────────────────────────── -->
          <div class="tab-pane fade" id="mciTabTags" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between gap-3 px-4 py-3 border-bottom flex-wrap">
              <!-- Search -->
              <div class="position-relative" style="max-width:280px;flex:1 1 200px;">
                <i class="bi bi-search position-absolute text-muted" style="left:0.7rem;top:50%;transform:translateY(-50%);pointer-events:none;" aria-hidden="true"></i>
                <input type="text" id="mciTagSearch" class="form-control form-control-sm ps-4" placeholder="Search tags…" autocomplete="off" />
              </div>
              <button type="button" class="btn btn-dark btn-sm d-flex align-items-center gap-1" id="mciCpAddTagBtn">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>Add tag
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 mci-tax-table">
                <thead class="table-light">
                  <tr>
                    <th style="min-width:180px;">Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th style="width:88px;" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody id="mciCpTagsBody">
                  <tr><td colspan="4" class="text-muted small py-4 text-center">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…
                  </td></tr>
                </tbody>
              </table>
            </div>
          </div>

        </div><!-- /tab-content -->
      </div>
    </div>
  </div>
</div>

<!-- ── Category offcanvas ──────────────────────────────────── -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mciCatOffcanvas" aria-labelledby="mciCatPanelTitle" style="width:clamp(340px,32vw,560px)">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h6 mb-0" id="mciCatPanelTitle">Category</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciCatForm" novalidate>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciCatName">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="mciCatName" required maxlength="255" placeholder="e.g. Food & Dining" />
        <div class="form-text" id="mciCatSlugNote"></div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Icon <span class="text-muted fw-normal">(optional emoji)</span></label>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span id="mciCatIconPreview" class="mci-icon-preview-lg" aria-hidden="true">—</span>
          <input type="text" id="mciCatIcon" class="form-control" maxlength="32"
            placeholder="Paste emoji e.g. 🏠" autocomplete="off" />
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" id="mciCatIconSuggest">
            <i class="bi bi-lightbulb me-1"></i>Suggest
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="mciCatIconPickerToggle">
            <i class="bi bi-grid-3x3-gap me-1"></i>Browse
          </button>
        </div>
        <div id="mciCatIconSuggestions" class="mci-icon-pick-row mt-2 flex-wrap gap-2" style="display:none;"></div>
        <div id="mciCatIconPicker" class="mci-icon-pick-row mt-2 flex-wrap gap-1 p-2 border rounded-3 bg-light" style="display:none;max-height:180px;overflow-y:auto;"></div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciCatParent">Parent category</label>
        <select class="form-select" id="mciCatParent" aria-describedby="mciCatParentHelp"></select>
        <div class="form-text" id="mciCatParentHelp"></div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciCatSort">Sort order</label>
        <input type="number" class="form-control" id="mciCatSort" min="0" value="0" style="max-width:100px;" />
        <div class="form-text">Lower numbers appear first among siblings.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciCatDescription">Description</label>
        <textarea class="form-control" id="mciCatDescription" rows="3" placeholder="Shown on category pages (not the meta description below)"></textarea>
      </div>

      <details class="mb-3">
        <summary class="fw-semibold small text-muted mb-2" style="cursor:pointer;list-style:none;user-select:none;">
          <i class="bi bi-search me-1" aria-hidden="true"></i>SEO fields
          <span class="text-muted fw-normal">(optional)</span>
        </summary>
        <div class="pt-2 d-flex flex-column gap-3">
          <div>
            <label class="form-label small" for="mciCatPageTitle">Page title</label>
            <input type="text" class="form-control form-control-sm" id="mciCatPageTitle" maxlength="255"
              placeholder="<title> — defaults to name if empty" autocomplete="off" />
          </div>
          <div>
            <label class="form-label small" for="mciCatMetaKeywords">Meta keywords</label>
            <input type="text" class="form-control form-control-sm" id="mciCatMetaKeywords" maxlength="512"
              placeholder="Comma-separated" autocomplete="off" />
          </div>
          <div>
            <label class="form-label small" for="mciCatMetaDescription">Meta description</label>
            <textarea class="form-control form-control-sm" id="mciCatMetaDescription" rows="2" maxlength="512"
              placeholder="Short search-result summary"></textarea>
          </div>
        </div>
      </details>

      <button type="submit" class="btn btn-dark w-100">
        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save category
      </button>
    </form>
  </div>
</div>

<!-- ── Tag offcanvas ───────────────────────────────────────── -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mciTagOffcanvas" aria-labelledby="mciTagPanelTitle" style="width:clamp(340px,32vw,560px)">
  <div class="offcanvas-header border-bottom">
    <h2 class="offcanvas-title h6 mb-0" id="mciTagPanelTitle">Tag</h2>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <form id="mciTagForm" novalidate>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciTagName">Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="mciTagName" required maxlength="255" placeholder="e.g. outdoor-seating" />
        <div class="form-text" id="mciTagSlugNote"></div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold" for="mciTagDescription">Description</label>
        <textarea class="form-control" id="mciTagDescription" rows="3" placeholder="Optional — shown on tag pages"></textarea>
      </div>

      <details class="mb-3">
        <summary class="fw-semibold small text-muted mb-2" style="cursor:pointer;list-style:none;user-select:none;">
          <i class="bi bi-search me-1" aria-hidden="true"></i>SEO fields
          <span class="text-muted fw-normal">(optional)</span>
        </summary>
        <div class="pt-2 d-flex flex-column gap-3">
          <div>
            <label class="form-label small" for="mciTagPageTitle">Page title</label>
            <input type="text" class="form-control form-control-sm" id="mciTagPageTitle" maxlength="255"
              placeholder="<title> — optional" autocomplete="off" />
          </div>
          <div>
            <label class="form-label small" for="mciTagMetaKeywords">Meta keywords</label>
            <input type="text" class="form-control form-control-sm" id="mciTagMetaKeywords" maxlength="512"
              placeholder="Comma-separated" autocomplete="off" />
          </div>
          <div>
            <label class="form-label small" for="mciTagMetaDescription">Meta description</label>
            <textarea class="form-control form-control-sm" id="mciTagMetaDescription" rows="2" maxlength="512"
              placeholder="Short search-result summary"></textarea>
          </div>
        </div>
      </details>

      <button type="submit" class="btn btn-dark w-100">
        <i class="bi bi-check2 me-1" aria-hidden="true"></i>Save tag
      </button>
    </form>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
?>
