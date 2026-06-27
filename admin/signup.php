<?php
require_once __DIR__ . '/_init.php';

if (admin_count() > 0) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $errors = validate_required_fields($_POST, ['name', 'email', 'password']);
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!empty($errors) || !validate_email_value($email) || strlen($password) < 8) {
        $error = 'Name, valid email and password(min 8 chars) required.';
    } else {
        if (admin_signup_first_user($name, $email, $password)) {
            admin_login($email, $password);
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Unable to create admin.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="card card-body">
          <h4 class="mb-2">Create First Admin</h4>
          <p class="text-muted mb-3">Set up your secure CMS owner account.</p>

          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Create Admin</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
