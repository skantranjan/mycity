<?php
// Shared header/nav (responsive: Bootstrap navbar collapse on small screens).
$activePage = $activePage ?? '';
$appArea = $appArea ?? '';
$headerUserArea = '';

function navActive(string $key, string $activePage): string
{
    return $key === $activePage ? 'active' : '';
}

// On public pages ($appArea === ''), detect any active logged-in session
// so we can show the correct header controls instead of Register/Login.
if ($appArea === '') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }
    if (!empty($_SESSION['mci_cp_logged_in']) && !empty($_SESSION['mci_cp_user_id'])) {
        $headerUserArea = 'cp';
        require_once __DIR__ . '/../../includes/mci_app_profile.php';
    } elseif (!empty($_SESSION['mci_logged_in']) && (($_SESSION['mci_role'] ?? '') === 'subscriber')) {
        $headerUserArea = 'subscriber';
        require_once __DIR__ . '/../../includes/mci_app_profile.php';
    }
} else {
    // In app areas, user controls match the current app shell.
    $headerUserArea = $appArea;
}
?>
<!-- Skip to main content — keyboard / screen-reader shortcut -->
<a href="#mci-main-content" class="visually-hidden-focusable position-absolute top-0 start-0 p-2 m-1 rounded-2 fw-bold" style="z-index:9999;background:var(--mci-gradient-cta);color:#fff;text-decoration:none;">
  Skip to main content
</a>
<header class="site-header sticky-top">
  <nav class="navbar navbar-expand-lg navbar-dark py-2" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center py-1 me-2" href="/" title="My City Info">
        <img
          src="/assets/images/logo.png"
          alt="My City Info"
          class="site-logo"
          loading="eager"
          decoding="async"
        />
      </a>

      <button
        class="navbar-toggler rounded-3"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mciMainNav"
        aria-controls="mciMainNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="mciMainNav">
        <button
          type="button"
          class="btn mci-mobile-nav-close d-lg-none"
          data-bs-toggle="collapse"
          data-bs-target="#mciMainNav"
          aria-controls="mciMainNav"
          aria-label="Close navigation"
        >
          <span aria-hidden="true">&times;</span>
        </button>
        <?php if ($appArea === ''): ?>
        <ul class="navbar-nav ms-lg-auto my-3 my-lg-0 gap-lg-1">
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('listings', $activePage) ?>" href="/business-listing/">Browse listings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('categories', $activePage) ?>" href="/business-category/">Business Categories</a>
          </li>
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('products', $activePage) ?>" href="/products/">Products</a>
          </li>
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('services', $activePage) ?>" href="/services/">Services</a>
          </li>
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('submit', $activePage) ?>" href="/submit-business-listing/">Add Business</a>
          </li>
          <!-- City picker pill -->
          <li class="nav-item position-relative d-flex align-items-center ms-lg-1">
            <button
              type="button"
              id="mciCityPickerBtn"
              class="mci-city-pill"
              aria-label="Change city"
              title="Change city"
            >
              <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
              <span id="mciActiveCityLabel">All locations</span>
              <i class="bi bi-pencil-fill mci-city-pill__edit" aria-hidden="true"></i>
            </button>
            <!-- Popover -->
            <div id="mciCityPickerPopover" class="mci-city-popover" hidden>
              <div class="mci-city-popover__label">Change your city</div>
              <div class="d-flex gap-2" style="position:relative;">
                <input
                  type="text"
                  id="mciCityPickerInput"
                  class="form-control form-control-sm"
                  placeholder="Enter city or area…"
                  autocomplete="off"
                  maxlength="80"
                  role="combobox"
                  aria-autocomplete="list"
                  aria-expanded="false"
                  aria-controls="mciCitySuggestions"
                />
                <button type="button" id="mciCityPickerSave" class="btn btn-sm btn-dark text-nowrap">Save</button>
              </div>
              <ul id="mciCitySuggestions" class="mci-city-suggestions" role="listbox" hidden></ul>
              <div class="mci-city-popover__hint">Type to search, then press Enter or click Save</div>
              <div class="mt-2 text-center">
                <button type="button" id="mciCityShowAll" class="btn btn-link btn-sm p-0 text-muted small">
                  Show all locations
                </button>
              </div>
            </div>
          </li>
        </ul>
        <?php endif; ?>

        <?php if ($headerUserArea === 'subscriber'): ?>
          <?php
          $mciHdrName   = mci_app_profile_display_name('subscriber');
          $mciHdrAvatar = mci_app_profile_avatar_for_header('subscriber');
          $mciSiteUrl   = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
                          . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com');
          ?>
          <div class="d-flex align-items-center ms-lg-auto pt-2 pt-lg-0">
            <div class="dropdown mci-header-user">
              <button
                type="button"
                class="btn mci-header-user__toggle dropdown-toggle d-flex align-items-center gap-2 pe-2"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-label="Open account menu"
              >
                <?php if ($mciHdrAvatar !== null): ?>
                  <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-header-user__avatar" width="36" height="36" decoding="async" />
                <?php else: ?>
                  <span class="mci-header-user__avatar mci-header-user__avatar--placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                <?php endif; ?>
                <span class="mci-hud__welcome d-none d-lg-inline">Welcome, <strong><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <i class="bi bi-chevron-down mci-hud__chevron d-none d-lg-inline" aria-hidden="true"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end mci-header-user__menu shadow-lg border-0">
                <!-- Identity panel -->
                <div class="mci-hud__identity">
                  <div class="mci-hud__avatar-wrap">
                    <?php if ($mciHdrAvatar !== null): ?>
                      <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-hud__avatar" width="44" height="44" decoding="async" />
                    <?php else: ?>
                      <span class="mci-hud__avatar mci-hud__avatar--placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                    <?php endif; ?>
                  </div>
                  <div class="mci-hud__identity-text">
                    <div class="mci-hud__role-pill">Subscriber</div>
                    <div class="mci-hud__name"><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="mci-hud__site-url"><?= htmlspecialchars($mciSiteUrl, ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
                <!-- Nav links -->
                <div class="mci-hud__nav">
                  <a class="mci-hud__item" href="/subscriber/dashboard/">
                    <span class="mci-hud__item-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                    <span>Dashboard</span>
                  </a>
                  <a class="mci-hud__item" href="/subscriber/profile/">
                    <span class="mci-hud__item-icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                    <span>Update profile</span>
                  </a>
                  <a class="mci-hud__item" href="/subscriber/change-password/">
                    <span class="mci-hud__item-icon"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <span>Change password</span>
                  </a>
                  <a class="mci-hud__item" href="/" target="_blank" rel="noopener">
                    <span class="mci-hud__item-icon"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></span>
                    <span>Browse public website</span>
                  </a>
                </div>
                <div class="mci-hud__footer">
                  <a class="mci-hud__logout" href="/subscriber/logout/?perform=1">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Sign out</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php elseif ($headerUserArea === 'cp'): ?>
          <?php
          $mciHdrName   = mci_app_profile_display_name('cp');
          $mciHdrAvatar = mci_app_profile_avatar_for_header('cp');
          $mciCpRole    = (string) ($_SESSION['mci_cp_role'] ?? 'co_admin');
          $mciRoleLabel = $mciCpRole === 'super_admin' ? 'Super admin' : 'Co-admin';
          $mciSiteUrl   = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
                          . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.mycityinfo.com');
          ?>
          <div class="d-flex align-items-center ms-lg-auto pt-2 pt-lg-0">
            <div class="dropdown mci-header-user">
              <button
                type="button"
                class="btn mci-header-user__toggle dropdown-toggle d-flex align-items-center gap-2 pe-2"
                data-bs-toggle="dropdown"
                data-bs-display="static"
                aria-expanded="false"
                aria-label="Open admin account menu"
              >
                <?php if ($mciHdrAvatar !== null): ?>
                  <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-header-user__avatar" width="36" height="36" decoding="async" />
                <?php else: ?>
                  <span class="mci-header-user__avatar mci-header-user__avatar--placeholder" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
                <?php endif; ?>
                <span class="mci-hud__welcome d-none d-lg-inline">Welcome, <strong><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <i class="bi bi-chevron-down mci-hud__chevron d-none d-lg-inline" aria-hidden="true"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end mci-header-user__menu shadow-lg border-0">
                <!-- Identity panel -->
                <div class="mci-hud__identity mci-hud__identity--cp">
                  <div class="mci-hud__avatar-wrap">
                    <?php if ($mciHdrAvatar !== null): ?>
                      <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-hud__avatar" width="44" height="44" decoding="async" />
                    <?php else: ?>
                      <span class="mci-hud__avatar mci-hud__avatar--placeholder mci-hud__avatar--cp" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
                    <?php endif; ?>
                  </div>
                  <div class="mci-hud__identity-text">
                    <div class="mci-hud__role-pill mci-hud__role-pill--cp"><?= htmlspecialchars($mciRoleLabel, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="mci-hud__name"><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="mci-hud__site-url"><?= htmlspecialchars($mciSiteUrl, ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
                <!-- Nav links -->
                <div class="mci-hud__nav">
                  <a class="mci-hud__item" href="/cp/dashboard/">
                    <span class="mci-hud__item-icon"><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
                    <span>Control panel</span>
                  </a>
                  <a class="mci-hud__item" href="/cp/profile/">
                    <span class="mci-hud__item-icon"><i class="bi bi-person-vcard-fill" aria-hidden="true"></i></span>
                    <span>Update profile</span>
                  </a>
                  <a class="mci-hud__item" href="/cp/change-password/">
                    <span class="mci-hud__item-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
                    <span>Change password</span>
                  </a>
                  <a class="mci-hud__item" href="/" target="_blank" rel="noopener">
                    <span class="mci-hud__item-icon"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></span>
                    <span>Browse public website</span>
                  </a>
                </div>
                <div class="mci-hud__footer">
                  <a class="mci-hud__logout" href="/cp/logout/?perform=1">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Sign out</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-lg-center gap-2 ms-lg-3 pt-2 pt-lg-0 mci-header-login">
          <span class="text-white-50 d-none d-lg-inline align-self-center" aria-hidden="true">|</span>
          <a class="btn btn-sm rounded-pill px-3 mci-touch-target text-center w-100 w-lg-auto btn-header-register" href="/register/">Register</a>
          <a class="btn btn-sm rounded-pill px-3 mci-touch-target text-center w-100 w-lg-auto btn-header-login" href="/login/">Login</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
