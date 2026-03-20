/**
 * Approximate city from IP (no GPS permission). Updates hero, "Popular in [city]",
 * and the search "Where" field. Falls back through several APIs; caches in sessionStorage for 24h.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'mci_detected_city';
  var STORAGE_TS = 'mci_detected_city_ts';
  var TTL_MS = 24 * 60 * 60 * 1000;

  function onReady(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  function applyCity(city) {
    if (!city || typeof city !== 'string') {
      return;
    }
    city = city.trim();
    if (!city) {
      return;
    }

    var nameEl = document.getElementById('heroCityName');
    if (nameEl) {
      nameEl.textContent = city;
    }

    var taglineCityEl = document.getElementById('heroTaglineCity');
    if (taglineCityEl) {
      taglineCityEl.textContent = city;
    }

    var popularCityEl = document.getElementById('homePopularCity');
    if (popularCityEl) {
      popularCityEl.textContent = city;
    }

    var whereInput = document.getElementById('homeWhere');
    if (whereInput && !whereInput.dataset.userEdited && !whereInput.value.trim()) {
      whereInput.value = city;
    }

    var badge = document.querySelector('.home-hero-badge');
    if (badge) {
      badge.textContent = '🗺️ Discover ' + city;
    }

    if (document.title.indexOf('Explore Your City') !== -1) {
      document.title = 'Explore ' + city + ' - My City Info';
    }
  }

  function parseIpapi(d) {
    if (!d || d.error) {
      return null;
    }
    return d.city ? String(d.city) : null;
  }

  function parseGeojs(d) {
    return d && d.city ? String(d.city) : null;
  }

  function parseIpwho(d) {
    if (!d || d.success === false) {
      return null;
    }
    return d.city ? String(d.city) : null;
  }

  function fetchJson(url) {
    return fetch(url, {
      credentials: 'omit',
      cache: 'no-store',
      headers: { Accept: 'application/json' }
    }).then(function (r) {
      if (!r.ok) {
        throw new Error('HTTP ' + r.status);
      }
      return r.json();
    });
  }

  function detectCity() {
    try {
      var cached = sessionStorage.getItem(STORAGE_KEY);
      var ts = parseInt(sessionStorage.getItem(STORAGE_TS), 10);
      if (cached && ts && Date.now() - ts < TTL_MS) {
        applyCity(cached);
        return Promise.resolve();
      }
    } catch (e) {
      /* sessionStorage blocked */
    }

    var sources = [
      { url: 'https://ipapi.co/json/', parse: parseIpapi },
      { url: 'https://get.geojs.io/v1/ip/geo.json', parse: parseGeojs },
      { url: 'https://ipwho.is/', parse: parseIpwho }
    ];

    function tryNext(i) {
      if (i >= sources.length) {
        return Promise.resolve();
      }
      return fetchJson(sources[i].url)
        .then(function (data) {
          var city = sources[i].parse(data);
          if (city) {
            try {
              sessionStorage.setItem(STORAGE_KEY, city);
              sessionStorage.setItem(STORAGE_TS, String(Date.now()));
            } catch (err) {
              /* ignore */
            }
            applyCity(city);
            return;
          }
          return tryNext(i + 1);
        })
        .catch(function () {
          return tryNext(i + 1);
        });
    }

    return tryNext(0);
  }

  onReady(function () {
    var whereInput = document.getElementById('homeWhere');
    if (whereInput) {
      whereInput.addEventListener(
        'input',
        function () {
          whereInput.dataset.userEdited = '1';
        },
        { passive: true }
      );
    }
    detectCity();
  });
})();
