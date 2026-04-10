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

    // Infinite scroll: append pages via public API; pagination is <noscript> only.
    var cfg = window.MCI_LISTING_CONFIG || null;
    if (!cfg || !cfg.endpoint) {
      return;
    }
    if ((cfg.pages || 1) <= (cfg.page || 1)) {
      return;
    }

    var statusEl = document.getElementById('mciInfiniteStatus');
    var sentinelEl = document.getElementById('mciInfiniteSentinel');

    var page = Number(cfg.page || 1);
    var pages = Number(cfg.pages || 1);
    var perPage = Number(cfg.perPage || 12);
    var isLoading = false;
    var done = false;
    var observer = null;
    var retryBtn = null;

    function removeRetryBtn() {
      if (retryBtn && retryBtn.parentNode) {
        retryBtn.parentNode.removeChild(retryBtn);
      }
      retryBtn = null;
    }

    function updateCount(delta) {
      var ids = ['mciShownCount', 'mciShownCountDesktop'];
      ids.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        var current = parseInt(String(el.textContent || '0').replace(/,/g, ''), 10) || 0;
        el.textContent = String(current + delta);
      });
    }

    function listingImage(item) {
      if (item.logo_path) return item.logo_path;
      if (item.banner_path) return item.banner_path;
      return '/assets/images/listing-placeholder.svg';
    }

    function escapeHtml(text) {
      return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function buildGridCard(item) {
      var title = escapeHtml(item.name || 'Untitled');
      var category = escapeHtml(item.category_name || '');
      var city = escapeHtml(item.city || '');
      var slug = encodeURIComponent(item.slug || '');
      var image = escapeHtml(listingImage(item));
      return '<div class="col-12 col-md-6 col-lg-4">' +
        '<a href="/business/' + slug + '/" class="text-decoration-none text-body d-block h-100">' +
        '<div class="card h-100 border-0 shadow-sm mci-listing-card">' +
        '<div class="card-img-wrap"><img src="' + image + '" class="card-img-top" alt="' + title + '" loading="lazy"></div>' +
        '<div class="card-body"><div class="d-flex align-items-start justify-content-between gap-2"><div class="pe-2 w-100">' +
        (category ? '<span class="category-pill d-inline-flex align-items-center gap-1 mb-2"><span>' + category + '</span></span>' : '') +
        '<div class="fw-bold fs-6 text-dark mci-line-clamp-2">' + title + '</div>' +
        (city ? '<div class="text-muted small mt-1 d-flex align-items-start gap-1"><span class="visually-hidden">Location: </span><i class="bi bi-geo-alt flex-shrink-0 me-1" aria-hidden="true"></i><span>' + city + '</span></div>' : '') +
        '</div><span class="btn-view-pill flex-shrink-0 align-self-start">View</span></div></div></div></a></div>';
    }

    function buildListRow(item) {
      var title = escapeHtml(item.name || 'Untitled');
      var category = escapeHtml(item.category_name || '');
      var city = escapeHtml(item.city || '');
      var slug = encodeURIComponent(item.slug || '');
      var image = escapeHtml(listingImage(item));
      return '<div class="col-12"><a href="/business/' + slug + '/" class="text-decoration-none text-body d-block mci-listing-row-link">' +
        '<div class="card border-0 shadow-sm mci-listing-row-card h-100 overflow-hidden"><div class="row g-0 align-items-stretch">' +
        '<div class="col-4 col-sm-3 col-md-3 col-lg-4"><div class="mci-listing-row-img-wrap h-100">' +
        '<img src="' + image + '" class="mci-listing-row-img" alt="' + title + '" loading="lazy"></div></div>' +
        '<div class="col-8 col-sm-9 col-md-9 col-lg-8"><div class="card-body py-3 px-3 d-flex flex-column h-100 justify-content-center">' +
        '<div class="d-flex align-items-start justify-content-between gap-2"><div class="min-w-0 pe-2">' +
        (category ? '<span class="category-pill d-inline-block mb-2">' + category + '</span>' : '') +
        '<div class="fw-bold text-dark mci-listing-row-title">' + title + '</div>' +
        (city ? '<div class="text-muted small mt-1 d-flex align-items-start gap-1"><span class="visually-hidden">Location: </span><i class="bi bi-geo-alt flex-shrink-0 me-1" aria-hidden="true"></i><span>' + city + '</span></div>' : '') +
        '</div><span class="btn btn-sm btn-dark rounded-pill flex-shrink-0 align-self-center d-none d-sm-inline-block">View</span></div></div></div></div></div></a></div>';
    }

    function nextPageUrl(nextPage) {
      var params = new URLSearchParams();
      Object.keys(cfg.filters || {}).forEach(function (key) {
        var value = cfg.filters[key];
        if (value !== null && value !== undefined && String(value) !== '') {
          params.set(key, String(value));
        }
      });
      params.set('page', String(nextPage));
      params.set('per_page', String(perPage));
      return cfg.endpoint + '?' + params.toString();
    }

    function loadNextPage() {
      if (isLoading || done || page >= pages) {
        return;
      }
      removeRetryBtn();
      isLoading = true;
      if (statusEl) {
        statusEl.style.display = '';
        statusEl.textContent = 'Loading more listings...';
      }
      fetch(nextPageUrl(page + 1), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (payload) {
          var businesses = (payload && payload.businesses) ? payload.businesses : [];
          if (!businesses.length) {
            done = true;
            if (statusEl) {
              statusEl.textContent = 'You have reached the end of listings.';
            }
            if (observer) observer.disconnect();
            return;
          }
          var gridHtml = '';
          var listHtml = '';
          businesses.forEach(function (item) {
            gridHtml += buildGridCard(item);
            listHtml += buildListRow(item);
          });
          gridEl.insertAdjacentHTML('beforeend', gridHtml);
          listEl.insertAdjacentHTML('beforeend', listHtml);
          page += 1;
          updateCount(businesses.length);
          if (page >= pages) {
            done = true;
            if (statusEl) {
              statusEl.textContent = 'You have reached the end of listings.';
            }
            if (observer) observer.disconnect();
          } else if (statusEl) {
            statusEl.textContent = 'Scroll for more listings.';
          }
        })
        .catch(function () {
          if (statusEl) {
            statusEl.textContent = 'Unable to load more listings.';
          }
          removeRetryBtn();
          if (statusEl && statusEl.parentNode) {
            retryBtn = document.createElement('button');
            retryBtn.type = 'button';
            retryBtn.className = 'btn btn-outline-dark btn-sm mt-2';
            retryBtn.textContent = 'Try again';
            retryBtn.addEventListener('click', function () {
              removeRetryBtn();
              loadNextPage();
            });
            statusEl.parentNode.insertBefore(retryBtn, statusEl.nextSibling);
          }
        })
        .finally(function () {
          isLoading = false;
        });
    }

    if (statusEl) {
      statusEl.style.display = '';
      statusEl.textContent = 'Scroll for more listings.';
    }

    if (sentinelEl && typeof IntersectionObserver !== 'undefined') {
      observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              loadNextPage();
            }
          });
        },
        { root: null, rootMargin: '0px 0px 480px 0px', threshold: 0 }
      );
      observer.observe(sentinelEl);
    } else {
      var loadMoreBtn = document.createElement('button');
      loadMoreBtn.type = 'button';
      loadMoreBtn.className = 'btn btn-outline-dark btn-sm mt-3';
      loadMoreBtn.id = 'mciLoadMoreBtn';
      loadMoreBtn.textContent = 'Load more';
      if (statusEl && statusEl.parentNode) {
        statusEl.parentNode.insertBefore(loadMoreBtn, statusEl);
      } else if (listEl && listEl.parentNode) {
        listEl.parentNode.appendChild(loadMoreBtn);
      }
      loadMoreBtn.addEventListener('click', loadNextPage);
    }
  });
})();
