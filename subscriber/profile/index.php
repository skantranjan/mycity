<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/mci_session.php';
require_once __DIR__ . '/../../includes/mci_require_session.php';
require_once __DIR__ . '/../../includes/mci_app_profile.php';
require_once __DIR__ . '/../../includes/mci_account_bridge.php';
require_once __DIR__ . '/../../includes/mci_auth_messages.php';
require_once __DIR__ . '/../../includes/mci_env_public.php';

mci_require_subscriber_session();

$pageTitle = 'Profile - My City Info';
$activePage = '';
$subActive = '';
$hideCta = true;
$appArea = 'subscriber';

$userId = (string) $_SESSION['mci_user_id'];

$csrfAction = 'subscriber_profile_update';
require_once __DIR__ . '/../../includes/mci_csrf.php';
$csrfToken = mci_csrf_token($csrfAction);

$flashOk = '';
$flashErr = '';

$bundle = mci_account_get_profile_bundle($userId);
$loadErr = '';
if (!$bundle['ok']) {
    $loadErr = mci_auth_error_message((string) ($bundle['error'] ?? 'profile_error'));
} else {
    mci_app_profile_apply_bundle_to_session('subscriber', $bundle);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loadErr === '') {
    $csrfPost = trim((string) ($_POST['csrf_token'] ?? ''));
    if (!mci_csrf_verify($csrfAction, $csrfPost)) {
        $flashErr = 'Invalid request token. Please refresh and try again.';
    } else {
        $action = trim((string) ($_POST['form_action'] ?? 'save'));

        if ($action === 'unlink_provider') {
            $provider = trim((string) ($_POST['provider'] ?? ''));
            $res = mci_account_unlink_auth_provider($userId, $provider);
            if ($res['ok']) {
                $flashOk = 'That sign-in provider was disconnected from your account.';
            } else {
                $flashErr = mci_auth_error_message((string) ($res['error'] ?? 'profile_error'));
            }
        } elseif ($action === 'link_provider') {
            if (!mci_env_flag('MCI_ALLOW_DEV_SOCIAL_LINK')) {
                $flashErr = mci_auth_error_message('social_link_disabled');
            } else {
                $provider = trim((string) ($_POST['link_provider'] ?? ''));
                $puid = trim((string) ($_POST['provider_user_id'] ?? ''));
                $pem = trim((string) ($_POST['provider_email'] ?? ''));
                $pem = $pem === '' ? null : $pem;
                $res = mci_account_link_auth_provider_manual($userId, $provider, $puid, $pem);
                if ($res['ok']) {
                    $flashOk = 'Social provider linked to your account.';
                } else {
                    $flashErr = mci_auth_error_message((string) ($res['error'] ?? 'profile_error'));
                }
            }
        } else {
            $patch = [
                'display_name' => trim((string) ($_POST['display_name'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'first_name' => trim((string) ($_POST['first_name'] ?? '')),
                'last_name' => trim((string) ($_POST['last_name'] ?? '')),
                'profile_image' => trim((string) ($_POST['profile_image'] ?? '')),
                'gender' => trim((string) ($_POST['gender'] ?? '')),
                'date_of_birth' => trim((string) ($_POST['date_of_birth'] ?? '')),
                'timezone' => trim((string) ($_POST['timezone'] ?? '')),
            ];
            $res = mci_account_patch_profile($userId, $patch, api_client_ip());
            if (!$res['ok']) {
                $flashErr = mci_auth_error_message((string) ($res['error'] ?? 'profile_error'));
            } else {
                $av = mci_app_profile_save_from_request('subscriber');
                if (!$av['ok']) {
                    $flashErr = (string) ($av['error'] ?? 'Upload failed.');
                } else {
                    $flashOk = 'Profile saved.';
                    $bundle = mci_account_get_profile_bundle($userId);
                    if (!empty($bundle['ok'])) {
                        mci_app_profile_apply_bundle_to_session('subscriber', $bundle);
                    }
                }
            }
        }
    }

    if ($flashOk !== '' || $flashErr !== '') {
        $bundle = mci_account_get_profile_bundle($userId);
        if (!empty($bundle['ok'])) {
            mci_app_profile_apply_bundle_to_session('subscriber', $bundle);
        }
    }
}

$u = $bundle['user'] ?? [];
$p = is_array($bundle['profile'] ?? null) ? $bundle['profile'] : null;
$providers = is_array($bundle['auth_providers'] ?? null) ? $bundle['auth_providers'] : [];
$email = (string) ($u['email'] ?? '');
$displayName = (string) ($u['display_name'] ?? '');
$phone = (string) ($u['phone'] ?? '');
$fn = $p ? (string) ($p['first_name'] ?? '') : '';
$ln = $p ? (string) ($p['last_name'] ?? '') : '';
$profileImage = $p ? (string) ($p['profile_image'] ?? '') : '';
$gender = $p ? (string) ($p['gender'] ?? '') : '';
$dob = $p && !empty($p['date_of_birth']) ? (string) $p['date_of_birth'] : '';
if ($dob !== '' && strlen($dob) >= 10) {
    $dob = substr($dob, 0, 10);
}
$tz = $p ? (string) ($p['timezone'] ?? '') : '';
$roleLabel = (string) ($u['role'] ?? 'subscriber');

$avatarPreview = mci_app_profile_avatar_for_header('subscriber');
$devSocial = mci_env_flag('MCI_ALLOW_DEV_SOCIAL_LINK');

ob_start();
?>

<div class="row g-4">
  <div class="col-12 col-lg-3">
    <?php include __DIR__ . '/../../views/partials/subscriber-sidebar.php'; ?>
  </div>
  <div class="col-12 col-lg-9">
    <div class="card border-0 shadow-sm bg-white">
      <div class="card-body p-4">
        <div class="fw-semibold mb-2">Profile</div>
        <div class="text-muted small mb-3">
          Account details, extended profile, and linked sign-in methods. Display name and photo appear in the top navigation.
        </div>

        <?php if ($loadErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($loadErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success py-2 small mb-3" role="status"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger py-2 small mb-3" role="alert"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($loadErr === ''): ?>
        <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
          <input type="hidden" name="form_action" value="save" />

          <h6 class="text-uppercase text-muted small mb-2">Account</h6>
          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label class="form-label" for="email">Email</label>
              <input class="form-control" id="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" disabled />
              <div class="form-text">Email changes are not available here yet.</div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="role_ro">Role</label>
              <input class="form-control" id="role_ro" type="text" value="<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>" disabled />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="display_name">Display name</label>
              <input class="form-control" id="display_name" type="text" name="display_name" value="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>" maxlength="255" autocomplete="nickname" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input class="form-control" id="phone" type="text" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>" maxlength="64" autocomplete="tel" />
            </div>
          </div>

          <h6 class="text-uppercase text-muted small mb-2">Profile details</h6>
          <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
              <label class="form-label" for="first_name">First name</label>
              <input class="form-control" id="first_name" type="text" name="first_name" value="<?= htmlspecialchars($fn, ENT_QUOTES, 'UTF-8') ?>" maxlength="64" autocomplete="given-name" />
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="last_name">Last name</label>
              <input class="form-control" id="last_name" type="text" name="last_name" value="<?= htmlspecialchars($ln, ENT_QUOTES, 'UTF-8') ?>" maxlength="64" autocomplete="family-name" />
            </div>
            <div class="col-12">
              <label class="form-label" for="profile_image">Profile image URL</label>
              <input class="form-control" id="profile_image" type="text" name="profile_image" value="<?= htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8') ?>" maxlength="512" placeholder="https://..." inputmode="url" />
              <div class="form-text">Public image URL (HTTPS). Shown in the header if you have not uploaded a photo below.</div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label" for="gender">Gender</label>
              <select class="form-select" id="gender" name="gender">
                <?php
                $gopts = ['' => '—', 'female' => 'Female', 'male' => 'Male', 'nonbinary' => 'Non-binary', 'other' => 'Other', 'unspecified' => 'Prefer not to say'];
                foreach ($gopts as $val => $lab) {
                    $sel = ($gender === $val) ? ' selected' : '';
                    echo '<option value="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"' . $sel . '>' . htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') . '</option>';
                }
                ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label" for="date_of_birth">Date of birth</label>
              <input class="form-control" id="date_of_birth" type="date" name="date_of_birth" value="<?= htmlspecialchars($dob, ENT_QUOTES, 'UTF-8') ?>" />
            </div>
            <?php
            $tzSelected = $tz;
            include __DIR__ . '/../../views/partials/profile-timezone-field.php';
            ?>
            <div class="col-12">
              <label class="form-label" for="avatar">Upload profile photo</label>
              <input class="form-control" id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" />
              <div class="form-text">JPG, PNG, GIF, or WebP — max 500 KB. Overrides the image URL for the menu avatar.</div>
            </div>
            <?php if ($avatarPreview !== null): ?>
            <div class="col-12">
              <div class="text-muted small mb-1">Current photo in menu</div>
              <img src="<?= htmlspecialchars($avatarPreview, ENT_QUOTES, 'UTF-8') ?>" alt="" class="mci-profile-avatar-preview rounded-circle border" width="96" height="96" style="object-fit:cover" />
            </div>
            <?php endif; ?>
          </div>

          <button class="btn btn-dark" type="submit">Save profile</button>
        </form>

        <hr class="my-4" />

        <h6 class="text-uppercase text-muted small mb-2">Linked sign-in</h6>
        <p class="text-muted small">Social accounts you can use to sign in (when OAuth is enabled).</p>
        <?php if ($providers === []): ?>
          <p class="small mb-0">No social providers linked yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead><tr><th>Provider</th><th>Email on provider</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($providers as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars((string) ($row['provider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($row['provider_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                      <form method="post" class="d-inline" onsubmit="return confirm('Disconnect this provider?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
                        <input type="hidden" name="form_action" value="unlink_provider" />
                        <input type="hidden" name="provider" value="<?= htmlspecialchars((string) ($row['provider'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                        <button type="submit" class="btn btn-outline-danger btn-sm">Disconnect</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <?php if ($devSocial): ?>
        <div class="card bg-light border-0 mt-3">
          <div class="card-body">
            <div class="fw-semibold small mb-1">Developer: link provider manually</div>
            <p class="small text-muted mb-3">Only when <code>MCI_ALLOW_DEV_SOCIAL_LINK</code> is enabled. Production should use OAuth.</p>
            <form method="post" class="row g-2">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>" />
              <input type="hidden" name="form_action" value="link_provider" />
              <div class="col-12 col-md-3">
                <select class="form-select form-select-sm" name="link_provider" required>
                  <option value="google">google</option>
                  <option value="facebook">facebook</option>
                  <option value="apple">apple</option>
                  <option value="linkedin">linkedin</option>
                </select>
              </div>
              <div class="col-12 col-md-3">
                <input class="form-control form-control-sm" type="text" name="provider_user_id" placeholder="Provider user id" required />
              </div>
              <div class="col-12 col-md-3">
                <input class="form-control form-control-sm" type="email" name="provider_email" placeholder="Email (optional)" />
              </div>
              <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-dark w-100">Link</button>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layout.php';
