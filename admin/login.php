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
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card card-body">
          <h4 class="mb-2">Welcome Back</h4>
          <p class="text-muted mb-3">Login to manage your CMS modules.</p>

          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
          <?php if (admin_count() === 0): ?><div class="alert alert-warning">No admin exists. <a href="signup.php">Create first admin</a>.</div><?php endif; ?>

          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
