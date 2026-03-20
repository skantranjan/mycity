<?php
// CP sidebar partial.
// Expects $cpActive: dashboard | users | listings | anonymous | '' (no highlight).
$cpActive = $cpActive ?? 'dashboard';

function cpLinkClass(string $key, string $cpActive): string
{
    return ($cpActive !== '' && $key === $cpActive) ? 'mci-app-nav-link is-active' : 'mci-app-nav-link';
}
?>

<div class="mci-app-sidebar">
  <div class="mci-app-sidebar__head">
    <div class="mci-app-sidebar__title">
      Control panel
      <span class="mci-app-badge">Super admin</span>
    </div>
    <div class="mci-app-sidebar__sub">Moderation &amp; directory — profile &amp; logout are in the top bar.</div>
  </div>

  <nav class="mci-app-sidebar__nav" aria-label="Admin navigation">
    <a class="<?= cpLinkClass('dashboard', $cpActive) ?>" href="/cp/dashboard/">
      <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i> Dashboard
    </a>
    <a class="<?= cpLinkClass('users', $cpActive) ?>" href="/cp/users/">
      <i class="bi bi-people-fill" aria-hidden="true"></i> Registered users
    </a>
    <a class="<?= cpLinkClass('listings', $cpActive) ?>" href="/cp/listings/">
      <i class="bi bi-collection" aria-hidden="true"></i> All listings
    </a>
    <a class="<?= cpLinkClass('anonymous', $cpActive) ?>" href="/cp/anonymous-approvals/">
      <i class="bi bi-person-fill-slash" aria-hidden="true"></i> Anonymous approvals
    </a>
  </nav>

  <div class="mci-app-sidebar__foot">
    UI placeholders; moderation and approval workflows connect when the backend is ready.
  </div>
</div>
