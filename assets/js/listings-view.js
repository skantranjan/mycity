/**
 * Listings page: switch between grid and list layout; persist in localStorage.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'mci_listings_view';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    var gridEl = document.getElementById('listingsGridView');
    var listEl = document.getElementById('listingsListView');
    var btnGrid = document.getElementById('listingsViewGrid');
    var btnList = document.getElementById('listingsViewList');
    if (!gridEl || !listEl || !btnGrid || !btnList) {
      return;
    }

    function setView(mode, persist) {
      var isList = mode === 'list';
      gridEl.classList.toggle('d-none', isList);
      listEl.classList.toggle('d-none', !isList);
      btnGrid.classList.toggle('active', !isList);
      btnList.classList.toggle('active', isList);
      btnGrid.setAttribute('aria-pressed', String(!isList));
      btnList.setAttribute('aria-pressed', String(isList));
      if (persist !== false) {
        try {
          localStorage.setItem(STORAGE_KEY, isList ? 'list' : 'grid');
        } catch (e) {
          /* ignore */
        }
      }
    }

    var saved = null;
    try {
      saved = localStorage.getItem(STORAGE_KEY);
    } catch (e) {
      /* ignore */
    }
    if (saved === 'list') {
      setView('list', false);
    }

    btnGrid.addEventListener('click', function () {
      setView('grid');
    });
    btnList.addEventListener('click', function () {
      setView('list');
    });
  });
})();
