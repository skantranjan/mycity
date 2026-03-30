/**
 * item-search.js
 * Handles: quick-view modal, active-tag dismiss, filter-pill auto-submit.
 * Loaded with `defer` on /products/ and /services/ pages.
 */
(function () {
  'use strict';

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
    var bizLogoPath = d.bizLogo || '';

    if (modalBizLogoImg) {
      if (bizLogoPath) {
        modalBizLogoImg.src = bizLogoPath;
        modalBizLogoImg.style.display = '';
        if (modalBizLogo) modalBizLogo.style.fontSize = '0';
      } else {
        modalBizLogoImg.style.display = 'none';
        if (modalBizLogo) modalBizLogo.style.fontSize = '';
      }
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

}());
