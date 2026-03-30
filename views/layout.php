<?php
// Shared page layout for server-rendered PHP pages.
// Expects:
// - $pageTitle (optional)
// - $activePage (optional: e.g. "home", "submit")
// - $content (required): HTML string
// - $extraHead (optional): raw HTML for extra <link>/<style> in <head>
// - $appArea (optional): "subscriber" | "cp" — themed app shell + app-areas.css
$mciErrorHandlerIncluded = false;
require_once __DIR__ . '/../includes/mci_error_handler.php';
$mciErrorHandlerIncluded = true;
require_once __DIR__ . '/../includes/mci_paths.php';
require_once __DIR__ . '/../includes/mci_config.php';
$appArea = isset($appArea) && in_array($appArea, ['subscriber', 'cp'], true) ? $appArea : '';
$__mciBodyClass = 'mci-body';
if ($appArea !== '') {
    $__mciBodyClass .= ' mci-app-area mci-app-area--' . $appArea;
    require_once __DIR__ . '/../includes/mci_session.php';
    require_once __DIR__ . '/../includes/mci_app_profile.php';
}

// On public pages, detect whether a subscriber/CP session is active so we can
// load app-areas.css in <head> before the header partial runs.
$__needsAppCss = $appArea !== '';
if (!$__needsAppCss) {
    require_once __DIR__ . '/../includes/mci_session.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }
    $__needsAppCss = !empty($_SESSION['mci_logged_in']) || !empty($_SESSION['mci_cp_logged_in']);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Explore local business, services and places in your city.', ENT_QUOTES, 'UTF-8') ?>" />
    <title><?= htmlspecialchars($pageTitle ?? 'My City Info') ?></title>
    <script>
(function () {
  var b = <?= json_encode(mci_api_v1_base(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  window.MCI_API_BASE = b;
  window.mciApiUrl = function (p) {
    p = p || '';
    if (p.charAt(0) !== '/') {
      p = '/' + p;
    }
    var base = (typeof window.MCI_API_BASE === 'string' && window.MCI_API_BASE !== '')
      ? window.MCI_API_BASE.replace(/\/$/, '')
      : '/api/v1';
    return base + p;
  };
})();
    </script>
    <?= $extraHead ?? '' ?>

    <!-- Bootstrap (components + grid) -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <!-- Site tokens + base overrides (after Bootstrap) -->
    <link rel="stylesheet" href="/assets/css/theme.css" />
    <!-- Icons (used by header controls like theme toggle) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <?php if ($__needsAppCss): ?>
      <link rel="stylesheet" href="/assets/css/app-areas.css" />
    <?php endif; ?>
  </head>
  <body class="<?= htmlspecialchars($__mciBodyClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php include __DIR__ . '/partials/header.php'; ?>

    <main class="mci-main" id="mci-main-content">
      <div class="container px-3 px-sm-4">
        <?php
        // $content is expected to be a safe HTML string produced by templates.
        // If later we wire backend, we can add escaping for untrusted fields.
        echo $content ?? '';
        ?>
      </div>
    </main>

    <?php if (empty($hideCta)) include __DIR__ . '/partials/cta-banner.php'; ?>

    <?php include __DIR__ . '/partials/footer.php'; ?>

    <!-- JS (jQuery + Bootstrap bundle) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>
    <!-- Sitewide city detection & persistence -->
    <script src="/assets/js/mci-city.js" defer></script>
    <?php if (!empty($extraJS)) echo $extraJS; ?>
    <script>
(function () {
  // Close Bootstrap navbar on outside click
  var nav = document.getElementById('mciMainNav');
  if (!nav) return;
  document.addEventListener('click', function (e) {
    if (!nav.classList.contains('show')) return;
    var toggler = document.querySelector('[data-bs-target="#mciMainNav"]');
    if (nav.contains(e.target) || (toggler && toggler.contains(e.target))) return;
    var bsNav = bootstrap.Collapse.getInstance(nav);
    if (bsNav) bsNav.hide();
  });
  // Close on nav link click (mobile)
  nav.querySelectorAll('.nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
      var bsNav = bootstrap.Collapse.getInstance(nav);
      if (bsNav && nav.classList.contains('show')) bsNav.hide();
    });
  });
}());
    </script>
    <script>
(function () {
  var btn = document.getElementById('mciGoTop');
  if (!btn) return;
  function syncBtn() {
    if (window.pageYOffset > 300) {
      btn.removeAttribute('hidden');
    } else {
      btn.setAttribute('hidden', '');
    }
  }
  window.addEventListener('scroll', syncBtn, { passive: true });
  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  syncBtn();
}());
    </script>
  </body>
</html>

