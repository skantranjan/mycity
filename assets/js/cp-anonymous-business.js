/**
 * CP anonymous-business page — role gate only.
 * Submit logic is handled by subscriber-list-business.js via window._mciSubmitContext = 'cp_admin'.
 */
(function () {
  var gateEl = document.getElementById('mciCpAnonGate');
  if (!gateEl) return;

  // Verify the JWT cookie has a cp-role claim by calling /api/v1/auth/me.
  // If not authorised, show the gate and disable the form.
  fetch('/api/v1/auth/me', { credentials: 'include' })
    .then(function (res) { return res.ok ? res.json() : { ok: false }; })
    .then(function (data) {
      var role = (data && data.user && data.user.role) || '';
      var allowed = role === 'super_admin' || role === 'co_admin';
      if (!allowed) {
        gateEl.classList.remove('d-none');
        var form = document.getElementById('mciSubmitForm');
        if (form) {
          form.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            el.disabled = true;
          });
        }
      }
    })
    .catch(function () {
      // Can't verify — show gate to be safe.
      gateEl.classList.remove('d-none');
    });
})();
