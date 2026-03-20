<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_app_profile.php';

$pageTitle = 'CP Profile - My City Info';
$activePage = '';
$cpActive = '';
$hideCta = true;
$appArea = 'cp';

$csrfAction = 'cp_profile_update';
require_once __DIR__ . '/../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flashOk = '';
$flashErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $result = mci_app_profile_save_from_request('cp');
        if ($result['ok']) {
            $flashOk = 'Profile saved. Your photo appears in the header menu.';
        } else {
            $flashErr = $result['error'] ?? 'Something went wrong.';
        }
    }
}

$fullName = mci_app_profile_full_name_for_form('cp');
if ($fullName === '') {
    $fullName = 'Admin User';
}
$avatarUri = mci_app_profile_avatar_data_uri('cp');

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/cp-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Profile Management</div>
        <div class="text-muted small mb-4">Update your admin display name and profile photo — shown in the top navigation menu.</div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="full_name">Full name</label>
              <input class="form-control" id="full_name" type="text" name="full_name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="name" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" id="email" type="email" name="email" value="admin@example.com" autocomplete="email" disabled />
              <div class="form-text">Email changes will be available when admin accounts are fully wired.</div>
            </div>
            <div class="col-12">
              <label class="form-label" for="role">Role</label>
              <input class="form-control" id="role" type="text" value="Super Admin" disabled />
            </div>
            <div class="col-12">
              <label class="form-label" for="avatar">Profile photo</label>
              <input class="form-control" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" />
              <div class="form-text">JPG, PNG, GIF, or WebP — max 500 KB. Used for the icon in the top bar.</div>
            </div>
            <?php if ($avatarUri !== null): ?>
            <div class="col-12">
              <div class="text-muted small mb-1">Current photo</div>
              <img src="<?= htmlspecialchars($avatarUri, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-profile-avatar-preview rounded-circle border" width="96" height="96" style="object-fit:cover" />
            </div>
            <?php endif; ?>
          </div>

          <button class="btn btn-dark mt-3" type="submit">Save changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layout.php';
?>
