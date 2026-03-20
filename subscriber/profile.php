<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/mci_session.php';
require_once __DIR__ . '/../includes/mci_app_profile.php';

$pageTitle = 'Profile - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

$flashOk = '';
$flashErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = mci_app_profile_save_from_request('subscriber');
    if ($result['ok']) {
        $flashOk = 'Profile saved. Your photo appears in the header menu.';
    } else {
        $flashErr = $result['error'] ?? 'Something went wrong.';
    }
}

$fullName = mci_app_profile_full_name_for_form('subscriber');
if ($fullName === '') {
    $fullName = 'Demo User';
}
$avatarUri = mci_app_profile_avatar_data_uri('subscriber');

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Profile Management</div>
        <div class="text-muted small mb-3">Update your name and profile photo — the photo is shown in the top navigation menu.</div>

        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="full_name">Full name</label>
              <input class="form-control" id="full_name" type="text" name="full_name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required autocomplete="name" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" id="email" type="email" name="email" value="demo@example.com" autocomplete="email" disabled />
              <div class="form-text">Email changes will be available when accounts are fully wired.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input class="form-control" id="phone" type="text" name="phone" value="+1 000-000-0000" autocomplete="tel" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="whatsapp">WhatsApp</label>
              <input class="form-control" id="whatsapp" type="text" name="whatsapp" value="+1 000-000-0000" />
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
