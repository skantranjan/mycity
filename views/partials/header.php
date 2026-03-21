<?php
// Shared header/nav (responsive: Bootstrap navbar collapse on small screens).
$activePage = $activePage ?? '';
$appArea = $appArea ?? '';

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
        $appArea = 'cp';
        require_once __DIR__ . '/../../includes/mci_app_profile.php';
    } elseif (!empty($_SESSION['mci_logged_in']) && (($_SESSION['mci_role'] ?? '') === 'subscriber')) {
        $appArea = 'subscriber';
        require_once __DIR__ . '/../../includes/mci_app_profile.php';
    }
}
?>
<header class="site-header sticky-top">
  <nav class="navbar navbar-expand-lg navbar-dark py-2" aria-label="Main navigation">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center py-1 me-2" href="/" title="My City Info">
        <img
          src="https://www.mycityinfo.com/wp-content/uploads/2017/04/my-city-info-logo-t-3.png"
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
        <ul class="navbar-nav ms-lg-auto my-3 my-lg-0 gap-lg-1">
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('listings', $activePage) ?>" href="/business-listing/">Listed Business</a>
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
            <a class="nav-link rounded px-3 <?= navActive('submit', $activePage) ?>" href="<?= $appArea === 'subscriber' ? '/subscriber/list-business/' : '/submit-business-listing/' ?>">Add Business</a>
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
              <span id="mciActiveCityLabel">your city</span>
              <i class="bi bi-pencil-fill mci-city-pill__edit" aria-hidden="true"></i>
            </button>
            <!-- Popover -->
            <div id="mciCityPickerPopover" class="mci-city-popover" hidden>
              <div class="mci-city-popover__label">Change your city</div>
              <div class="d-flex gap-2">
                <input
                  type="text"
                  id="mciCityPickerInput"
                  class="form-control form-control-sm"
                  placeholder="Enter city name…"
                  autocomplete="address-level2"
                  maxlength="80"
                />
                <button type="button" id="mciCityPickerSave" class="btn btn-sm btn-dark text-nowrap">Save</button>
              </div>
              <div class="mci-city-popover__hint">Press Enter or click Save</div>
            </div>
          </li>
        </ul>

        <?php if ($appArea === 'subscriber'): ?>
          <?php
          $mciHdrName = mci_app_profile_display_name('subscriber');
          $mciHdrAvatar = mci_app_profile_avatar_for_header('subscriber');
          ?>
          <div class="d-flex align-items-center gap-2 ms-lg-3 pt-2 pt-lg-0">
            <span class="text-white-50 d-none d-lg-inline" aria-hidden="true">|</span>
            <a class="btn btn-sm rounded-pill px-3 mci-touch-target btn-header-login" href="/subscriber/dashboard/">
              <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>Dashboard
            </a>
            <div class="dropdown mci-header-user">
            <button
              type="button"
              class="btn mci-header-user__toggle dropdown-toggle d-flex align-items-center justify-content-center"
              data-bs-toggle="dropdown"
              data-bs-display="static"
              aria-expanded="false"
              aria-label="Open account menu"
            >
              <?php if ($mciHdrAvatar !== null): ?>
                <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-header-user__avatar" width="40" height="40" decoding="async" />
              <?php else: ?>
                <span class="mci-header-user__avatar mci-header-user__avatar--placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
              <?php endif; ?>
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
        <?php elseif ($appArea === 'cp'): ?>
          <?php
          $mciHdrName = mci_app_profile_display_name('cp');
          $mciHdrAvatar = mci_app_profile_avatar_for_header('cp');
          ?>
          <div class="d-flex align-items-center gap-2 ms-lg-3 pt-2 pt-lg-0">
            <span class="text-white-50 d-none d-lg-inline" aria-hidden="true">|</span>
            <a class="btn btn-sm rounded-pill px-3 mci-touch-target btn-header-login" href="/cp/dashboard/">
              <i class="bi bi-grid-1x2-fill me-1" aria-hidden="true"></i>Dashboard
            </a>
            <div class="dropdown mci-header-user">
            <button
              type="button"
              class="btn mci-header-user__toggle dropdown-toggle d-flex align-items-center justify-content-center"
              data-bs-toggle="dropdown"
              data-bs-display="static"
              aria-expanded="false"
              aria-label="Open admin account menu"
            >
              <?php if ($mciHdrAvatar !== null): ?>
                <img src="<?= htmlspecialchars($mciHdrAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-header-user__avatar" width="40" height="40" decoding="async" />
              <?php else: ?>
                <span class="mci-header-user__avatar mci-header-user__avatar--placeholder" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
              <?php endif; ?>
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
                  <div class="mci-hud__role-pill mci-hud__role-pill--cp">Super admin</div>
                  <div class="mci-hud__name"><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></div>
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
