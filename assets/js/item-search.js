/**
 * item-search.js
 * Handles: quick-view modal, active-tag dismiss, filter-pill auto-submit,
 *          infinite scroll (IntersectionObserver on #mciInfiniteEnd).
 * Loaded with `defer` on /products/ and /services/ pages.
 */
(function () {
  'use strict';

  var MCI_LOGO_PLACEHOLDER = '/assets/images/business-logo-placeholder.svg';

  // ── Quick-view modal ──────────────────────────────────────────────────────
  var modal    = document.getElementById('mciItemModal');
  var modalImg = document.getElementById('mciModalImg');
  var modalImgEl = document.getElementById('mciModalImgEl');
  var modalName  = document.getElementById('mciModalName');
  var modalPrice = document.getElementById('mciModalPrice');
  var modalUnit  = document.getElementById('mciModalUnit');
  var modalDesc  = document.getElementById('mciModalDesc');
  var modalBizLogo = document.getElementById('mciModalBizLogo');
  var modalBizLogoImg = document.getElementById('mciModalBizLogoImg');
  var modalBizName = document.getElementById('mciModalBizName');
  var modalBizMeta = document.getElementById('mciModalBizMeta');
  var modalBizLink = document.getElementById('mciModalBizLink');
  var modalCta     = document.getElementById('mciModalCta');
  var modalContact = document.getElementById('mciModalContact');

  function openModal(card) {
    if (!modal) return;
    var d = card.dataset;

    // Image
    var imgPath = d.image || '';
    if (imgPath && modalImgEl) {
      modalImgEl.src = imgPath;
      var itemName = d.name || '';
      modalImgEl.alt = itemName ? itemName + ' — item photo' : 'Item photo';
      modalImgEl.style.display = '';
      if (modalImg) modalImg.style.fontSize = '0';
    } else {
      if (modalImgEl) modalImgEl.style.display = 'none';
      if (modalImg)   modalImg.style.fontSize = '';
    }

    // Text fields
    if (modalName)  modalName.textContent  = d.name  || '';
    if (modalPrice) modalPrice.textContent = d.price || '';
    if (modalUnit)  modalUnit.textContent  = d.unit  ? '/ ' + d.unit : '';
    if (modalDesc)  modalDesc.textContent  = d.desc  || '';

    // Business panel
    var bizSlug = d.bizSlug || '';
    var bizUrl  = bizSlug ? '/business/' + bizSlug + '/' : '#';
    var bizLogoPath = d.bizLogo || MCI_LOGO_PLACEHOLDER;

    if (modalBizLogoImg) {
      modalBizLogoImg.src = bizLogoPath;
      var bn = d.bizName || '';
      modalBizLogoImg.alt = bn ? bn + ' logo' : 'Business logo';
      modalBizLogoImg.style.display = '';
      if (modalBizLogo) modalBizLogo.style.fontSize = '0';
    }

    if (modalBizName) modalBizName.textContent = d.bizName || '';
    if (modalBizMeta) {
      var meta = [];
      if (d.city)        meta.push('📍 ' + d.city);
      if (d.bizCategory) meta.push(d.bizCategory);
      modalBizMeta.textContent = meta.join(' · ');
    }
    if (modalBizLink) { modalBizLink.href = bizUrl; }
    if (modalCta)     { modalCta.href = bizUrl; }
    if (modalContact) { modalContact.href = bizUrl + '#contact'; }

    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // Open modal on card click
  document.addEventListener('click', function (e) {
    var card = e.target.closest('[data-item-card]');
    if (card) { e.preventDefault(); openModal(card); return; }

    // Close on overlay click
    if (e.target === modal) { closeModal(); return; }

    // Close button
    if (e.target.closest('[data-modal-close]')) { closeModal(); return; }

    // Active tag dismiss
    var dismissBtn = e.target.closest('[data-dismiss-param]');
    if (dismissBtn) {
      var param = dismissBtn.dataset.dismissParam;
      var form  = document.getElementById('mciItemsFilterForm');
      if (form && param) {
        // Clear the param's hidden/select/text input within the form
        var inputs = form.querySelectorAll('[name="' + param + '"], [name="price_min"], [name="price_max"]');
        if (param === 'price') {
          // Remove both price params
          form.querySelectorAll('[name="price_min"], [name="price_max"]').forEach(function (el) { el.value = ''; });
        } else {
          form.querySelectorAll('[name="' + param + '"]').forEach(function (el) { el.value = ''; });
        }
        // Reset page to 1
        var pageInput = form.querySelector('[name="page"]');
        if (pageInput) pageInput.value = '1';
        form.submit();
      }
    }
  });

  // Close on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  // ── Filter pill auto-submit on change ─────────────────────────────────────
  var form = document.getElementById('mciItemsFilterForm');
  if (form) {
    form.querySelectorAll('.mci-items-filter-pill').forEach(function (el) {
      el.addEventListener('change', function () {
        // Reset to page 1 when filter changes
        var pageInput = form.querySelector('[name="page"]');
        if (pageInput) pageInput.value = '1';
        form.submit();
      });
    });
  }

  // ── Search typeahead ──────────────────────────────────────────────────────
  var searchForm  = document.getElementById('mciItemsSearchForm');
  var searchInput = searchForm ? searchForm.querySelector('input[name="q"]') : null;
  var infiniteEndEl = document.getElementById('mciInfiniteEnd');
  var suggestType = infiniteEndEl ? (infiniteEndEl.dataset.type || 'products') : 'products';

  if (searchInput) {
    var suggestList  = null;
    var suggestTimer = null;
    var activeIdx    = -1;
    var lastQuery    = '';

    function suggestEsc(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function highlightMatch(text, query) {
      var idx = text.toLowerCase().indexOf(query.toLowerCase());
      if (idx === -1) return suggestEsc(text);
      return suggestEsc(text.slice(0, idx))
        + '<mark>' + suggestEsc(text.slice(idx, idx + query.length)) + '</mark>'
        + suggestEsc(text.slice(idx + query.length));
    }

    function closeSuggest() {
      if (suggestList) { suggestList.remove(); suggestList = null; }
      activeIdx = -1;
    }

    function renderSuggest(items, query) {
      closeSuggest();
      if (!items.length) return;
      suggestList = document.createElement('ul');
      suggestList.className = 'mci-items-suggest';
      suggestList.setAttribute('role', 'listbox');
      items.forEach(function (name, i) {
        var li = document.createElement('li');
        li.className = 'mci-items-suggest__item';
        li.setAttribute('role', 'option');
        li.dataset.idx = String(i);
        li.innerHTML = '<i class="bi bi-search mci-suggest-icon" aria-hidden="true"></i>'
          + '<span>' + highlightMatch(name, query) + '</span>';
        li.addEventListener('mousedown', function (e) {
          e.preventDefault(); // keep focus on input
          searchInput.value = name;
          closeSuggest();
          searchForm.submit();
        });
        suggestList.appendChild(li);
      });
      // Insert after the search box inside the wrap
      searchInput.closest('.mci-items-search-wrap').appendChild(suggestList);
    }

    function setActive(idx) {
      if (!suggestList) return;
      var items = suggestList.querySelectorAll('.mci-items-suggest__item');
      items.forEach(function (el) { el.classList.remove('is-active'); });
      activeIdx = idx;
      if (idx >= 0 && idx < items.length) {
        items[idx].classList.add('is-active');
        searchInput.value = items[idx].querySelector('span').textContent;
      }
    }

    function fetchSuggestions(q) {
      if (q === lastQuery) return;
      lastQuery = q;
      fetch('/api/v1/public/items/suggest?type=' + encodeURIComponent(suggestType) + '&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok && searchInput.value.length >= 3) {
            renderSuggest(data.suggestions || [], searchInput.value);
          }
        })
        .catch(function () {});
    }

    searchInput.addEventListener('input', function () {
      clearTimeout(suggestTimer);
      var val = searchInput.value.trim();
      if (val.length < 3) { closeSuggest(); lastQuery = ''; return; }
      suggestTimer = setTimeout(function () { fetchSuggestions(val); }, 220);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (!suggestList) return;
      var items = suggestList.querySelectorAll('.mci-items-suggest__item');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIdx + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIdx - 1, -1));
        if (activeIdx === -1) searchInput.value = lastQuery;
      } else if (e.key === 'Enter') {
        if (activeIdx >= 0) { e.preventDefault(); closeSuggest(); searchForm.submit(); }
      } else if (e.key === 'Escape') {
        closeSuggest();
      }
    });

    document.addEventListener('click', function (e) {
      if (suggestList && !suggestList.contains(e.target) && e.target !== searchInput) {
        closeSuggest();
      }
    });
  }

  // ── Mobile filter toggle (mciFilterToggle / mciFiltersPanel) ──────────────
  var filterToggle = document.getElementById('mciFilterToggle');
  var filtersPanel = document.getElementById('mciFiltersPanel');
  if (filterToggle && filtersPanel) {
    // On large screens the panel is always visible; on small it starts hidden
    function syncPanelVisibility() {
      if (window.innerWidth >= 992) {
        filtersPanel.style.display = '';
      }
    }
    syncPanelVisibility();
    window.addEventListener('resize', syncPanelVisibility);

    filterToggle.addEventListener('click', function () {
      var expanded = filterToggle.getAttribute('aria-expanded') === 'true';
      filterToggle.setAttribute('aria-expanded', String(!expanded));
      filtersPanel.style.display = expanded ? 'none' : '';
    });

    // Hide panel by default on mobile
    if (window.innerWidth < 992) {
      filtersPanel.style.display = 'none';
    }
  }

  // ── Infinite scroll ───────────────────────────────────────────────────────
  var infiniteEnd = document.getElementById('mciInfiniteEnd');
  var cardGrid    = document.getElementById('mciCardGrid');
  if (infiniteEnd && cardGrid) {
    var isLoading  = false;
    var curPage    = parseInt(infiniteEnd.dataset.page,    10) || 1;
    var totalPages = parseInt(infiniteEnd.dataset.pages,   10) || 1;
    var itemType   = infiniteEnd.dataset.type   || 'products';
    var q          = infiniteEnd.dataset.q      || '';
    var city       = infiniteEnd.dataset.city   || '';
    var category   = infiniteEnd.dataset.category || '';
    var priceMin   = infiniteEnd.dataset.priceMin || '';
    var priceMax   = infiniteEnd.dataset.priceMax || '';
    var sort       = infiniteEnd.dataset.sort   || 'relevance';

    var loader = document.getElementById('mciInfiniteLoader');

    function buildCard(item) {
      var priceStr = '';
      if (item.price_min !== null && item.price_max !== null) {
        priceStr = '₹' + item.price_min.toLocaleString('en-IN') + ' – ₹' + item.price_max.toLocaleString('en-IN');
      } else if (item.price_min !== null) {
        priceStr = 'From ₹' + item.price_min.toLocaleString('en-IN');
      } else if (item.price_max !== null) {
        priceStr = 'Up to ₹' + item.price_max.toLocaleString('en-IN');
      }

      var imgHtml = item.image_path
        ? '<img src="' + esc(item.image_path) + '" alt="' + esc(item.name) + '" loading="lazy" />'
        : '<i class="bi bi-' + (itemType === 'services' ? 'stars' : 'box-seam') + '" style="font-size:2rem;color:var(--mci-muted);" aria-hidden="true"></i>';

      var badgeHtml = item.business_category
        ? '<span class="mci-item-card__cat-badge">' + esc(item.business_category) + '</span>'
        : '';

      var descHtml = item.description
        ? '<div class="mci-item-card__desc">' + esc(item.description) + '</div>'
        : '';

      var priceHtml = priceStr
        ? '<div class="mci-item-card__price">' + esc(priceStr) + '</div>'
        : '';

      var bizLogoSrc = item.business_logo || MCI_LOGO_PLACEHOLDER;
      var bizLogoAlt = item.business_name ? esc(item.business_name) + ' logo' : 'Business logo';
      var bizLogoHtml = '<img src="' + esc(bizLogoSrc) + '" alt="' + bizLogoAlt + '" loading="lazy" />';

      var cityHtml = item.city
        ? '<span class="mci-item-card__city">📍 ' + esc(item.city) + '</span>'
        : '';

      return '<div class="col">'
        + '<div class="mci-item-card h-100" data-item-card="1"'
        + ' data-name="'        + esc(item.name)              + '"'
        + ' data-desc="'        + esc(item.description)       + '"'
        + ' data-price="'       + esc(priceStr)               + '"'
        + ' data-unit="'        + esc(item.price_unit)        + '"'
        + ' data-image="'       + esc(item.image_path)        + '"'
        + ' data-biz-name="'    + esc(item.business_name)     + '"'
        + ' data-biz-slug="'    + esc(item.business_slug)     + '"'
        + ' data-biz-logo="'    + esc(item.business_logo)     + '"'
        + ' data-biz-category="'+ esc(item.business_category) + '"'
        + ' data-city="'        + esc(item.city)              + '">'
        + '<div class="mci-item-card__img">' + imgHtml + badgeHtml + '</div>'
        + '<div class="mci-item-card__body">'
        + '<div class="mci-item-card__name">' + esc(item.name) + '</div>'
        + descHtml
        + priceHtml
        + '<div class="mci-item-card__biz-strip">'
        + '<div class="mci-item-card__biz-logo">' + bizLogoHtml + '</div>'
        + '<span class="mci-item-card__biz-name">' + esc(item.business_name) + '</span>'
        + cityHtml
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';
    }

    function esc(str) {
      return String(str || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function fetchNextPage() {
      if (isLoading || curPage >= totalPages) return;
      isLoading = true;
      if (loader) loader.style.display = '';

      var nextPage = curPage + 1;
      var qs = 'type=' + encodeURIComponent(itemType)
        + '&page=' + nextPage
        + '&per_page=12'
        + (q        ? '&q='        + encodeURIComponent(q)        : '')
        + (city     ? '&city='     + encodeURIComponent(city)     : '')
        + (category ? '&category=' + encodeURIComponent(category) : '')
        + (priceMin ? '&price_min='+ encodeURIComponent(priceMin) : '')
        + (priceMax ? '&price_max='+ encodeURIComponent(priceMax) : '')
        + (sort     ? '&sort='     + encodeURIComponent(sort)     : '');

      fetch('/api/v1/public/items?' + qs)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.ok && data.items && data.items.length) {
            var html = '';
            data.items.forEach(function (item) { html += buildCard(item); });
            cardGrid.insertAdjacentHTML('beforeend', html);
            curPage = data.page;
            totalPages = data.pages;
            infiniteEnd.dataset.page  = curPage;
            infiniteEnd.dataset.pages = totalPages;
          } else {
            // No more pages — stop observing
            observer.unobserve(infiniteEnd);
            if (loader) loader.style.display = 'none';
          }
        })
        .catch(function () { /* silent — keep observer active to retry on re-entry */ })
        .finally(function () {
          isLoading = false;
          if (loader) loader.style.display = 'none';
        });
    }

    var observer = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) fetchNextPage();
    }, { rootMargin: '200px' });

    if (curPage < totalPages) {
      observer.observe(infiniteEnd);
    }
  }

}());
