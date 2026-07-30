<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Settings';
$pdo = db();

$formData = [
    'name' => (string) ($adminUser['name'] ?? ''),
    'email' => (string) ($adminUser['email'] ?? ''),
];
$errors = [];

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $formData['name'] = trim((string) ($_POST['name'] ?? ''));
    $formData['email'] = trim((string) ($_POST['email'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $isChangingPassword = $newPassword !== '' || $confirmPassword !== '';

    if ($formData['name'] === '') {
        $errors['name'] = 'Name is required.';
    }
    if (!validate_email_value($formData['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($currentPassword === '') {
        $errors['current_password'] = 'Current password is required.';
    }
    if ($isChangingPassword && strlen($newPassword) < 8) {
        $errors['new_password'] = 'New password must be at least 8 characters.';
    }
    if ($isChangingPassword && $newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Password confirmation does not match.';
    }

    if (!$errors) {
        $adminRow = db_fetch_one($pdo, 'SELECT id, password_hash FROM admins WHERE id = :id AND is_active = 1 LIMIT 1', [
            ':id' => (int) $adminUser['id'],
        ]);

        if (!$adminRow || !password_verify($currentPassword, (string) $adminRow['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }
    }

    if (!$errors) {
        $existingAdmin = db_fetch_one($pdo, 'SELECT id FROM admins WHERE email = :email AND id <> :id LIMIT 1', [
            ':email' => $formData['email'],
            ':id' => (int) $adminUser['id'],
        ]);

        if ($existingAdmin) {
            $errors['email'] = 'This email is already used by another admin.';
        }
    }

    if (!$errors) {
        $params = [
            ':name' => $formData['name'],
            ':email' => $formData['email'],
            ':id' => (int) $adminUser['id'],
        ];
        $passwordSql = '';

        if ($isChangingPassword) {
            $passwordSql = ', password_hash = :password_hash';
            $params[':password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        db_execute($pdo, "UPDATE admins SET name = :name, email = :email{$passwordSql} WHERE id = :id", $params);
        admin_flash('success', 'Admin settings updated.');
        header('Location: settings.php');
        exit;
    }
} elseif (!$pdo) {
    $errors['database'] = 'Database connection is unavailable.';
}

include __DIR__ . '/_layout_top.php';
?>

<?php if (isset($errors['database'])): ?>
  <div class="alert alert-danger"><?= e($errors['database']) ?></div>
<?php endif; ?>

<?php if ($errors && !isset($errors['database'])): ?>
  <div class="alert alert-danger">Please fix the highlighted fields and try again.</div>
<?php endif; ?>

<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">Admin Account</h5>
  </div>

  <form method="post" class="row g-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="col-md-6">
      <label class="form-label" for="admin-name">Name</label>
      <input id="admin-name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= e($formData['name']) ?>" autocomplete="name" required>
      <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
    </div>

    <div class="col-md-6">
      <label class="form-label" for="admin-email">Email Address</label>
      <input id="admin-email" type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= e($formData['email']) ?>" autocomplete="email" required>
      <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
    </div>

    <div class="col-12">
      <hr>
      <h6 class="mb-1">Change Password</h6>
      <p class="text-muted mb-0">Leave the new password fields empty to keep your current password.</p>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="current-password">Current Password</label>
      <div class="input-group">
        <input id="current-password" type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" name="current_password" autocomplete="current-password" required>
        <button class="btn btn-outline-secondary password-toggle" type="button" data-target="current-password" aria-label="Show current password">
          <i class="bi bi-eye"></i>
        </button>
        <?php if (isset($errors['current_password'])): ?><div class="invalid-feedback"><?= e($errors['current_password']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="new-password">New Password</label>
      <div class="input-group">
        <input id="new-password" type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" name="new_password" autocomplete="new-password" minlength="8">
        <button class="btn btn-outline-secondary password-toggle" type="button" data-target="new-password" aria-label="Show new password">
          <i class="bi bi-eye"></i>
        </button>
        <?php if (isset($errors['new_password'])): ?><div class="invalid-feedback"><?= e($errors['new_password']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="col-md-4">
      <label class="form-label" for="confirm-password">Confirm New Password</label>
      <div class="input-group">
        <input id="confirm-password" type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" name="confirm_password" autocomplete="new-password" minlength="8">
        <button class="btn btn-outline-secondary password-toggle" type="button" data-target="confirm-password" aria-label="Show confirmed password">
          <i class="bi bi-eye"></i>
        </button>
        <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
      </div>
    </div>

    <div class="col-12 d-flex justify-content-end">
      <button class="btn btn-primary-modern" type="submit"><i class="bi bi-save"></i> Save Settings</button>
    </div>
  </form>
</div>

<script>
document.querySelectorAll('.password-toggle').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        const icon = this.querySelector('i');
        if (!input || !icon) return;

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
});
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
