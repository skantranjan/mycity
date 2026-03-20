/**
 * Business page: interactive 1–5 star picker for anonymous reviews (after login).
 */
(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  ready(function () {
    var group = document.getElementById('mciStarPicker');
    var hidden = document.getElementById('mciRatingValue');
    var form = document.getElementById('mciReviewForm');
    if (!group || !hidden || !form) {
      return;
    }

    function paint(val) {
      var v = parseInt(val, 10) || 0;
      hidden.value = v > 0 ? String(v) : '0';
      group.querySelectorAll('[data-star-value]').forEach(function (btn) {
        var n = parseInt(btn.getAttribute('data-star-value'), 10);
        var icon = btn.querySelector('i');
        if (!icon) {
          return;
        }
        if (n <= v && v > 0) {
          btn.classList.add('is-active');
          icon.className = 'bi bi-star-fill mci-reviews-star--on fs-3';
        } else {
          btn.classList.remove('is-active');
          icon.className = 'bi bi-star mci-reviews-star--off fs-3';
        }
      });
    }

    group.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-star-value]');
      if (!btn) {
        return;
      }
      e.preventDefault();
      var v = parseInt(btn.getAttribute('data-star-value'), 10);
      if (v >= 1 && v <= 5) {
        paint(v);
        var hint = document.getElementById('mciRatingHint');
        if (hint) {
          hint.classList.add('visually-hidden');
        }
      }
    });

    form.addEventListener('submit', function (e) {
      var r = parseInt(hidden.value, 10);
      if (r < 1 || r > 5) {
        e.preventDefault();
        var hint = document.getElementById('mciRatingHint');
        if (hint) {
          hint.classList.remove('visually-hidden');
        }
      }
    });
  });
})();
