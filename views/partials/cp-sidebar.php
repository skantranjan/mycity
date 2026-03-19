<?php
// CP sidebar partial.
// Expects $cpActive: one of dashboard|users|listings|anonymous|profile|change-password|logout.
$cpActive = $cpActive ?? 'dashboard';

function cpLinkClass(string $key, string $cpActive): string {
  return $key === $cpActive ? 'active fw-semibold' : 'text-muted';
}
?>

<div class="card border-0 shadow-sm bg-white">
  <div class="card-body p-3">
    <div class="fw-semibold mb-3">Control Panel</div>

    <div class="d-flex flex-column gap-1">
      <a class="btn btn-light text-start <?= cpLinkClass('dashboard', $cpActive) ?> " href="/cp/dashboard.php">Dashboard</a>
      <a class="btn btn-light text-start <?= cpLinkClass('users', $cpActive) ?> " href="/cp/users.php">Registered Users</a>
      <a class="btn btn-light text-start <?= cpLinkClass('listings', $cpActive) ?> " href="/cp/listings.php">All Listings</a>
      <a class="btn btn-light text-start <?= cpLinkClass('anonymous', $cpActive) ?> " href="/cp/anonymous-approvals.php">
        Anonymous Approvals
      </a>
      <a class="btn btn-light text-start <?= cpLinkClass('profile', $cpActive) ?> " href="/cp/profile.php">Profile</a>
      <a class="btn btn-light text-start <?= cpLinkClass('change-password', $cpActive) ?> " href="/cp/change-password.php">
        Change Password
      </a>
      <a class="btn btn-light text-start <?= cpLinkClass('logout', $cpActive) ?> " href="/cp/logout.php">Logout</a>
    </div>

    <div class="text-muted small mt-3">
      UI placeholders; backend moderation/approval wiring will be added later.
    </div>
  </div>
</div>

