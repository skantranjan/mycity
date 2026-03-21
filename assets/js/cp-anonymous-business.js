/**
 * Super-admin anonymous submission:
 * - Reads the current wizard payload from `mci_listing_preview`
 * - Pushes it into `mci_cp_anon_business_queue` (demo/localStorage)
 * - Clears the preview draft afterward
 */
$(function () {
  var PREVIEW_KEY = 'mci_listing_preview';
  var QUEUE_KEY = 'mci_cp_anon_business_queue';

  var ROLE_KEY = 'mci_cp_active_cp_role';
  var LABEL_KEY = 'mci_cp_active_cp_user_label';

  function safeParse(json, fallback) {
    try { return JSON.parse(json); } catch (e) { return fallback; }
  }

  function getActiveRole() {
    var role = (localStorage.getItem(ROLE_KEY) || '').toString();
    return (role === 'co_admin' || role === 'super_admin') ? role : 'super_admin';
  }

  function getActiveLabel() {
    var label = (localStorage.getItem(LABEL_KEY) || '').toString().trim();
    return label || 'Super admin';
  }

  function isAllowed() {
    var role = getActiveRole();
    return role === 'super_admin' || role === 'co_admin';
  }

  function getQueue() {
    var raw = localStorage.getItem(QUEUE_KEY);
    var v = safeParse(raw, []);
    return Array.isArray(v) ? v : [];
  }

  function setQueue(arr) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(Array.isArray(arr) ? arr : []));
  }

  var gateEl = document.getElementById('mciCpAnonGate');
  if (gateEl && !isAllowed()) {
    gateEl.classList.remove('d-none');
    return;
  }

  $('#mciSubmitForm').on('submit', function (e) {
    e.preventDefault();

    var rawPreview = localStorage.getItem(PREVIEW_KEY);
    if (!rawPreview) {
      alert('Please fill the form (and let previews save) before submitting anonymously.');
      return;
    }

    var payload = safeParse(rawPreview, {});
    if (!payload || typeof payload !== 'object') payload = {};

    try { localStorage.removeItem(PREVIEW_KEY); } catch (e2) {}

    var okEl = document.getElementById('mciAnonSuccess');
    if (okEl) {
      okEl.classList.remove('d-none');
    }

    var fallbackToLocalStorage = function () {
      var queue = getQueue();
      var id = 'anon_' + Date.now().toString(36) + '_' + Math.random().toString(16).slice(2);

      var role = getActiveRole();
      var submittedBy = getActiveLabel();

      queue.unshift({
        id: id,
        submittedAt: new Date().toISOString(),
        submittedBy: submittedBy,
        submittedByRole: role,
        status: 'pending',
        postedBySuperadminAnonymous: true,
        payload: payload
      });

      setQueue(queue);
    };

    // Prefer API when auth cookie exists; otherwise keep the demo behavior.
    fetch('/api/v1/cp/anon-business-submissions', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        payload: payload
      })
    })
      .then(function (res) {
        if (!res.ok) throw new Error('api_failed');
        return res.json().catch(function () { return {}; });
      })
      .then(function () {
        setTimeout(function () {
          window.location.href = '/cp/anonymous-approvals/';
        }, 800);
      })
      .catch(function () {
        fallbackToLocalStorage();
        setTimeout(function () {
          window.location.href = '/cp/anonymous-approvals/';
        }, 800);
      });
  });
});

