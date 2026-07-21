<?php
require_once __DIR__ . '/_init.php';

if (admin_current()) {
    header('Location: ' . url('admin/dashboard.php'), true, 302);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $errors = validate_required_fields($_POST, ['email', 'password']);
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!empty($errors)) {
        $error = 'Email and password are required.';
    } elseif (!validate_email_value($email)) {
        $error = 'Invalid email format.';
    } else {
        $token = admin_login($email, $password);
        if ($token) {
            header('Location: ' . url('admin/dashboard.php'), true, 302);
            exit;
        }
        $error = 'Invalid credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body admin-login-body">
  <main class="admin-login-page">
    <section class="admin-login-panel" aria-label="Admin login">
      <div class="admin-login-visual">
        <div class="admin-login-copy">
          <span class="admin-login-kicker">Private label beauty CMS</span>
          <h1>Welcome back</h1>
          <p>Manage products, content, orders, and brand enquiries from one refined workspace.</p>
        </div>
        <div class="admin-login-highlights" aria-label="Admin modules">
          <span><i class="bi bi-box-seam"></i> Catalog</span>
          <span><i class="bi bi-journal-richtext"></i> Content</span>
          <span><i class="bi bi-receipt"></i> Orders</span>
        </div>
      </div>

      <div class="admin-login-form-wrap">
        <div class="admin-login-card">
          <div class="admin-login-card-header">
            <span class="admin-login-icon"><i class="bi bi-shield-lock"></i></span>
            <div>
              <h2>Admin Login</h2>
              <p>Sign in to continue to the CMS panel.</p>
            </div>
          </div>

          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
          <?php if (admin_count() === 0): ?><div class="alert alert-warning">No admin exists. <a href="signup.php">Create first admin</a>.</div><?php endif; ?>

          <form method="post" class="admin-login-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="admin-login-field">
              <label class="form-label" for="admin-email">Email Address</label>
              <div class="admin-login-input">
                <i class="bi bi-envelope"></i>
                <input id="admin-email" type="email" name="email" class="form-control" placeholder="admin@example.com" autocomplete="email" required>
              </div>
            </div>
            <div class="admin-login-field">
              <label class="form-label" for="admin-password">Password</label>
              <div class="admin-login-input">
                <i class="bi bi-key"></i>
                <input id="admin-password" type="password" name="password" class="form-control" placeholder="Enter password" autocomplete="current-password" required>
              </div>
            </div>
            <button class="btn admin-login-submit w-100" type="submit">
              <span>Login</span>
              <i class="bi bi-arrow-right"></i>
            </button>
          </form>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
