/**
 * mci-city.js — Sitewide active-city management for My City Info.
 *
 * Responsibilities:
 *  1. On first visit: detect city from IP → store in localStorage (24h TTL).
 *  2. On every page: read stored city, apply it to known UI slots.
 *  3. When user submits any location-carrying form (where= field) → save that city.
 *  4. When user picks a city via the header city-picker → save + apply immediately.
 *  5. The header city pill always shows the active city and opens a quick-change input.
 *  6. User can choose "Show all locations" to clear the city filter entirely.
 *     A sentinel value '__all__' is stored so IP detection is not re-triggered.
 */
(function () {
  'use strict';

  var STORAGE_KEY    = 'mci_active_city';
  var DETECTED_KEY   = 'mci_detected_city';
  var DETECTED_TS    = 'mci_detected_city_ts';
  var TTL_MS         = 24 * 60 * 60 * 1000; // 24 h
  var ALL_SENTINEL   = '__all__';            // stored when user chooses "all locations"

  // ── Helpers ───────────────────────────────────────────────────────────────

  function store(city) {
    if (city === ALL_SENTINEL) {
      // User explicitly chose "all locations" — save sentinel, expire cookie
      try { localStorage.setItem(STORAGE_KEY, ALL_SENTINEL); } catch (e) { /* ignore */ }
      try { document.cookie = STORAGE_KEY + '=; path=/; max-age=0; SameSite=Lax'; } catch (e) { /* ignore */ }
      return;
    }
    if (!city) return;
    try { localStorage.setItem(STORAGE_KEY, city); } catch (e) { /* ignore */ }
    // Also set a cookie so PHP can read the active city for server-side filtering
    try {
      var maxAge = 365 * 24 * 60 * 60; // 1 year
      document.cookie = STORAGE_KEY + '=' + encodeURIComponent(city) +
        '; path=/; max-age=' + maxAge + '; SameSite=Lax';
    } catch (e) { /* ignore */ }
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
    city = (city && typeof city === 'string') ? city.trim() : '';

    // Header city pill label
    var pill = document.getElementById('mciActiveCityLabel');
    if (pill) pill.textContent = city || 'All locations';

    if (!city) {
      // Clear location inputs when showing all
      var homeWhere = document.getElementById('homeWhere');
      if (homeWhere && !homeWhere.dataset.userEdited) homeWhere.value = '';
      var listingWhere = document.getElementById('mciListingWhere');
      if (listingWhere && !listingWhere.dataset.userEdited) listingWhere.value = '';
      return;
    }

    // Home hero
    var heroName = document.getElementById('heroCityName');
    if (heroName) heroName.textContent = city;

    var heroTagline = document.getElementById('heroTaglineCity');
    if (heroTagline) heroTagline.textContent = city;

    var heroBadgeLabel = document.getElementById('homeHeroBadgeLabel');
    if (heroBadgeLabel) heroBadgeLabel.textContent = 'Discover ' + city;

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

  // ── Header city-picker popover with autocomplete ─────────────────────────

  function initCityPicker() {
    var btn      = document.getElementById('mciCityPickerBtn');
    var popover  = document.getElementById('mciCityPickerPopover');
    var input    = document.getElementById('mciCityPickerInput');
    var saveBtn  = document.getElementById('mciCityPickerSave');
    var showAll  = document.getElementById('mciCityShowAll');
    var list     = document.getElementById('mciCitySuggestions');
    if (!btn || !popover || !input || !saveBtn) return;

    var debounceTimer = null;
    var activeIndex   = -1;
    var suggestions   = [];

    function showPopover() {
      var current = load();
      input.value = (current === ALL_SENTINEL) ? '' : current;
      popover.hidden = false;
      input.focus();
      input.select();
      hideSuggestions();
    }

    function hidePopover() {
      popover.hidden = true;
      hideSuggestions();
    }

    // Pages that should reload with the new city as a URL param.
    // Each entry: { test: fn(pathname) → bool, param: string }
    var RELOAD_PAGES = [
      { test: function (p) { return p === '/' || p === '/index.php'; },             param: 'where' },
      { test: function (p) { return p.indexOf('/business-listing') === 0; },         param: 'where' },
      { test: function (p) { return p.indexOf('/business-category') === 0; },        param: 'location' },
      { test: function (p) { return p.indexOf('/products') === 0; },                 param: 'location' },
      { test: function (p) { return p.indexOf('/services') === 0; },                 param: 'location' },
    ];

    function reloadWithCity(city) {
      var pathname = window.location.pathname;
      var matched  = null;
      for (var i = 0; i < RELOAD_PAGES.length; i++) {
        if (RELOAD_PAGES[i].test(pathname)) { matched = RELOAD_PAGES[i]; break; }
      }
      if (!matched) return false;

      var params = new URLSearchParams(window.location.search);
      if (city) {
        params.set(matched.param, city);
      } else {
        // Clearing city — remove the location param entirely
        params.delete(matched.param);
      }
      // Reset pagination when city changes
      params.delete('page');
      var qs = params.toString();
      window.location.href = pathname + (qs ? '?' + qs : '');
      return true;
    }

    function clearCity() {
      store(ALL_SENTINEL);
      hidePopover();
      applyCity(''); // sets pill to "All locations", clears inputs
      reloadWithCity(''); // navigate without location param
    }

    function saveCity(city) {
      city = (city || input.value).trim();
      if (!city) { hidePopover(); return; }
      store(city);
      hidePopover();
      if (reloadWithCity(city)) return; // page will reload
      applyCity(city);
    }

    // ── Suggestions list ──────────────────────────────────────────────────

    function hideSuggestions() {
      if (!list) return;
      list.hidden = true;
      list.innerHTML = '';
      suggestions = [];
      activeIndex  = -1;
      input.setAttribute('aria-expanded', 'false');
    }

    function renderSuggestions(cities) {
      if (!list) return;
      suggestions = cities;
      activeIndex  = -1;
      list.innerHTML = '';
      if (!cities.length) { hideSuggestions(); return; }
      cities.forEach(function (city, i) {
        var li = document.createElement('li');
        li.setAttribute('role', 'option');
        li.setAttribute('aria-selected', 'false');
        li.dataset.index = String(i);
        li.textContent   = city;
        li.addEventListener('mousedown', function (e) {
          e.preventDefault(); // don't blur input
          input.value = city;
          saveCity(city);
        });
        list.appendChild(li);
      });
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    function setActive(index) {
      var items = list ? list.querySelectorAll('li') : [];
      items.forEach(function (li, i) {
        var on = (i === index);
        li.classList.toggle('is-active', on);
        li.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      activeIndex = index;
      if (index >= 0 && index < suggestions.length) {
        input.value = suggestions[index];
      }
    }

    function fetchSuggestions(q) {
      if (!q || q.length < 1) { hideSuggestions(); return; }
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(function () {
        var url = (window.MCI_API_BASE || '/api/v1') + '/public/cities?q=' + encodeURIComponent(q) + '&limit=8';
        fetch(url, { credentials: 'omit', headers: { Accept: 'application/json' } })
          .then(function (r) { return r.ok ? r.json() : { cities: [] }; })
          .then(function (d) { renderSuggestions(d.cities || []); })
          .catch(function () { hideSuggestions(); });
      }, 180);
    }

    // ── Event wiring ──────────────────────────────────────────────────────

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (popover.hidden) { showPopover(); } else { hidePopover(); }
    });

    saveBtn.addEventListener('click', function () { saveCity(); });

    if (showAll) {
      showAll.addEventListener('click', function () { clearCity(); });
    }

    input.addEventListener('input', function () {
      fetchSuggestions(input.value.trim());
    });

    input.addEventListener('keydown', function (e) {
      var items = list && !list.hidden ? list.querySelectorAll('li') : [];
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, suggestions.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, -1));
        if (activeIndex < 0) input.value = input.dataset.typed || '';
      } else if (e.key === 'Enter') {
        e.preventDefault();
        saveCity();
      } else if (e.key === 'Escape') {
        if (!list || list.hidden) { hidePopover(); } else { hideSuggestions(); }
      } else {
        input.dataset.typed = input.value;
      }
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

    // 3. Resolve active city — sentinel means user chose "all", treat as empty
    var activeCity = urlCity || (storedCity === ALL_SENTINEL ? '' : storedCity);

    // 4. Apply immediately (empty string → "All locations" label)
    applyCity(activeCity);

    // 5. Init city picker in header
    initCityPicker();

    // 6. Watch known location inputs
    watchLocationInput(document.getElementById('homeWhere'));
    watchLocationInput(document.getElementById('mciListingWhere'));

    // 7. If nothing stored at all (not even sentinel), detect from IP
    if (!storedCity && !urlCity) {
      detectFromIp();
    }
  });

})();
