<?php
// Shared header/nav (responsive: Bootstrap navbar collapse on small screens).
$activePage = $activePage ?? '';
$appArea = $appArea ?? '';

function navActive(string $key, string $activePage): string
{
    return $key === $activePage ? 'active' : '';
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
            <a class="nav-link rounded px-3" href="/category/">Business by Categories</a>
          </li>
          <li class="nav-item">
            <a class="nav-link rounded px-3 <?= navActive('submit', $activePage) ?>" href="<?= $appArea === 'subscriber' ? '/subscriber/list-business/' : '/submit-business-listing/' ?>">Add Business</a>
          </li>
        </ul>

        <?php if ($appArea === 'subscriber'): ?>
          <?php
          $mciHdrName = mci_app_profile_display_name('subscriber');
          $mciHdrAvatar = mci_app_profile_avatar_data_uri('subscriber');
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
            <div class="dropdown-menu dropdown-menu-end mci-header-user__menu shadow border-0">
              <div class="mci-header-user__card">
                <div class="mci-header-user__label text-uppercase">Subscriber</div>
                <div class="mci-header-user__name"><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="d-grid gap-2 mt-3">
                  <a class="btn btn-sm btn-outline-dark text-start" href="/subscriber/profile/">
                    <i class="bi bi-person-badge me-2" aria-hidden="true"></i>Update profile
                  </a>
                  <a class="btn btn-sm btn-outline-dark text-start" href="/subscriber/change-password/">
                    <i class="bi bi-key me-2" aria-hidden="true"></i>Change password
                  </a>
                  <a class="btn btn-sm btn-dark text-start" href="/subscriber/logout/?perform=1">
                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                  </a>
                </div>
              </div>
            </div>
            </div>
          </div>
        <?php elseif ($appArea === 'cp'): ?>
          <?php
          $mciHdrName = mci_app_profile_display_name('cp');
          $mciHdrAvatar = mci_app_profile_avatar_data_uri('cp');
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
            <div class="dropdown-menu dropdown-menu-end mci-header-user__menu shadow border-0">
              <div class="mci-header-user__card">
                <div class="mci-header-user__label text-uppercase">Super admin</div>
                <div class="mci-header-user__name"><?= htmlspecialchars($mciHdrName, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="d-grid gap-2 mt-3">
                  <a class="btn btn-sm btn-outline-dark text-start" href="/cp/profile/">
                    <i class="bi bi-person-vcard-fill me-2" aria-hidden="true"></i>Update profile
                  </a>
                  <a class="btn btn-sm btn-outline-dark text-start" href="/cp/change-password/">
                    <i class="bi bi-shield-lock me-2" aria-hidden="true"></i>Change password
                  </a>
                  <a class="btn btn-sm btn-dark text-start" href="/cp/logout/?perform=1">
                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                  </a>
                </div>
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
