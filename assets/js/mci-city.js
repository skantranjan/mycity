/**
 * mci-city.js — Sitewide active-city management for My City Info.
 *
 * Responsibilities:
 *  1. On first visit: detect city from IP → store in localStorage (24h TTL).
 *  2. On every page: read stored city, apply it to known UI slots.
 *  3. When user submits any location-carrying form (where= field) → save that city.
 *  4. When user picks a city via the header city-picker → save + apply immediately.
 *  5. The header city pill always shows the active city and opens a quick-change input.
 */
(function () {
  'use strict';

  var STORAGE_KEY    = 'mci_active_city';
  var DETECTED_KEY   = 'mci_detected_city';
  var DETECTED_TS    = 'mci_detected_city_ts';
  var TTL_MS         = 24 * 60 * 60 * 1000; // 24 h

  // ── Helpers ───────────────────────────────────────────────────────────────

  function store(city) {
    if (!city) return;
    try { localStorage.setItem(STORAGE_KEY, city); } catch (e) { /* ignore */ }
  }

  function load() {
    try { return localStorage.getItem(STORAGE_KEY) || ''; } catch (e) { return ''; }
  }

  function onReady(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }

  // ── Apply city to all known UI slots on the current page ──────────────────

  function applyCity(city) {
    if (!city || typeof city !== 'string') return;
    city = city.trim();
    if (!city) return;

    // Header city pill label
    var pill = document.getElementById('mciActiveCityLabel');
    if (pill) pill.textContent = city;

    // Home hero
    var heroName = document.getElementById('heroCityName');
    if (heroName) heroName.textContent = city;

    var heroTagline = document.getElementById('heroTaglineCity');
    if (heroTagline) heroTagline.textContent = city;

    var heroBadge = document.querySelector('.home-hero-badge');
    if (heroBadge) heroBadge.textContent = '\uD83D\uDDFA\uFE0F Discover ' + city;

    // "Popular in [city]" section
    var popularCity = document.getElementById('homePopularCity');
    if (popularCity) popularCity.textContent = city;

    // Home search "Where" input — only if empty and user hasn't typed
    var homeWhere = document.getElementById('homeWhere');
    if (homeWhere && !homeWhere.dataset.userEdited && !homeWhere.value.trim()) {
      homeWhere.value = city;
    }

    // Business listing page "Where" filter — only if empty and no GET param
    var listingWhere = document.getElementById('mciListingWhere');
    if (listingWhere && !listingWhere.dataset.userEdited && !listingWhere.value.trim()) {
      listingWhere.value = city;
    }

    // Page title on home
    if (document.title.indexOf('Explore Your City') !== -1) {
      document.title = 'Explore ' + city + ' - My City Info';
    }
  }

  // ── Capture city from URL GET params on page load ─────────────────────────
  // If this page was loaded with ?where=X or ?location=X, treat it as an
  // explicit city choice and persist it.

  function captureFromUrl() {
    var params = new URLSearchParams(window.location.search);
    var city = (params.get('where') || params.get('location') || '').trim();
    if (city) {
      store(city);
      return city;
    }
    return '';
  }

  // ── Detect city from IP (fallback, runs only when no stored city) ─────────

  function fetchJson(url) {
    return fetch(url, { credentials: 'omit', cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
  }

  var SOURCES = [
    { url: 'https://ipapi.co/json/',                parse: function (d) { return d && !d.error && d.city ? String(d.city) : null; } },
    { url: 'https://get.geojs.io/v1/ip/geo.json',  parse: function (d) { return d && d.city ? String(d.city) : null; } },
    { url: 'https://ipwho.is/',                     parse: function (d) { return d && d.success !== false && d.city ? String(d.city) : null; } },
  ];

  function detectFromIp() {
    // Already have a fresh detection this session?
    try {
      var cached = localStorage.getItem(DETECTED_KEY);
      var ts = parseInt(localStorage.getItem(DETECTED_TS) || '0', 10);
      if (cached && ts && Date.now() - ts < TTL_MS) {
        store(cached);
        applyCity(cached);
        return;
      }
    } catch (e) { /* ignore */ }

    function tryNext(i) {
      if (i >= SOURCES.length) return;
      fetchJson(SOURCES[i].url)
        .then(function (data) {
          var city = SOURCES[i].parse(data);
          if (city) {
            try {
              localStorage.setItem(DETECTED_KEY, city);
              localStorage.setItem(DETECTED_TS, String(Date.now()));
            } catch (e) { /* ignore */ }
            store(city);
            applyCity(city);
          } else {
            tryNext(i + 1);
          }
        })
        .catch(function () { tryNext(i + 1); });
    }
    tryNext(0);
  }

  // ── Watch location inputs for user edits → auto-save on change ───────────

  function watchLocationInput(inputEl) {
    if (!inputEl) return;
    inputEl.addEventListener('input', function () {
      inputEl.dataset.userEdited = '1';
    }, { passive: true });
    // Save when the containing form is submitted
    var form = inputEl.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        var val = inputEl.value.trim();
        if (val) store(val);
      });
    }
  }

  // ── Header city-picker popover ────────────────────────────────────────────

  function initCityPicker() {
    var btn    = document.getElementById('mciCityPickerBtn');
    var popover = document.getElementById('mciCityPickerPopover');
    var input  = document.getElementById('mciCityPickerInput');
    var saveBtn = document.getElementById('mciCityPickerSave');
    if (!btn || !popover || !input || !saveBtn) return;

    function showPopover() {
      input.value = load();
      popover.hidden = false;
      input.focus();
      input.select();
    }

    function hidePopover() {
      popover.hidden = true;
    }

    function saveCity() {
      var city = input.value.trim();
      if (!city) { hidePopover(); return; }
      store(city);
      applyCity(city);
      hidePopover();
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (popover.hidden) { showPopover(); } else { hidePopover(); }
    });

    saveBtn.addEventListener('click', saveCity);

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); saveCity(); }
      if (e.key === 'Escape') { hidePopover(); }
    });

    document.addEventListener('click', function (e) {
      if (!popover.hidden && !popover.contains(e.target) && e.target !== btn) {
        hidePopover();
      }
    });
  }

  // ── Boot ──────────────────────────────────────────────────────────────────

  onReady(function () {
    // 1. Capture from URL first (highest priority — explicit user navigation)
    var urlCity = captureFromUrl();

    // 2. Load whatever is stored
    var storedCity = load();
    var activeCity = urlCity || storedCity;

    // 3. Apply immediately if we have something
    if (activeCity) {
      applyCity(activeCity);
    }

    // 4. Init city picker in header
    initCityPicker();

    // 5. Watch known location inputs
    watchLocationInput(document.getElementById('homeWhere'));
    watchLocationInput(document.getElementById('mciListingWhere'));

    // 6. If nothing stored at all, detect from IP
    if (!activeCity) {
      detectFromIp();
    }
  });

})();
