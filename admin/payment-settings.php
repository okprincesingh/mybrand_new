<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Payment Settings';
$pdo = db();

if (!$pdo) {
    admin_flash_set('error', 'Database connection failed.');
    include __DIR__ . '/_layout_top.php';
    echo '<div class="alert alert-danger">Database unavailable.</div>';
    include __DIR__ . '/_layout_bottom.php';
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  method_name VARCHAR(120) NOT NULL,
  method_type VARCHAR(50) NOT NULL,
  stripe_publishable_key VARCHAR(255) NULL,
  stripe_secret_key VARCHAR(255) NULL,
  mode ENUM('test','live') NOT NULL DEFAULT 'test',
  status ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payment_methods_status (status),
  INDEX idx_payment_methods_type (method_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function mask_key(string $key): string
{
    $key = trim($key);
    if ($key === '') {
        return '';
    }
    if (strlen($key) <= 8) {
        return str_repeat('*', strlen($key));
    }
    return substr($key, 0, 8) . str_repeat('*', max(4, strlen($key) - 8));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_status' && $id > 0) {
        $stmt = $pdo->prepare('SELECT status FROM payment_methods WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $current = (string) ($stmt->fetchColumn() ?: 'inactive');
        $next = $current === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE payment_methods SET status = :status WHERE id = :id')->execute([':status' => $next, ':id' => $id]);
        admin_flash_set('success', 'Payment method status updated.');
        header('Location: payment-settings.php');
        exit;
    }

    if ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM payment_methods WHERE id = :id')->execute([':id' => $id]);
        admin_flash_set('success', 'Payment method deleted.');
        header('Location: payment-settings.php');
        exit;
    }

    if ($action === 'save') {
        $methodName = trim((string) ($_POST['method_name'] ?? ''));
        $methodType = strtolower(trim((string) ($_POST['method_type'] ?? '')));
        $mode = (($_POST['mode'] ?? 'test') === 'live') ? 'live' : 'test';
        $status = (($_POST['status'] ?? 'inactive') === 'active') ? 'active' : 'inactive';

        $stripePublishableKey = trim((string) ($_POST['stripe_publishable_key'] ?? ''));
        $stripeSecretKey = trim((string) ($_POST['stripe_secret_key'] ?? ''));

        if ($methodName === '' || $methodType === '') {
            admin_flash_set('error', 'Method name and type are required.');
            header('Location: payment-settings.php' . ($id > 0 ? ('?edit=' . $id) : ''));
            exit;
        }

        if ($methodType === 'stripe' && ($stripePublishableKey === '' || $stripeSecretKey === '')) {
            if ($id > 0) {
                $existing = $pdo->prepare('SELECT stripe_publishable_key, stripe_secret_key FROM payment_methods WHERE id = :id');
                $existing->execute([':id' => $id]);
                $row = $existing->fetch(PDO::FETCH_ASSOC) ?: [];
                if ($stripePublishableKey === '') {
                    $stripePublishableKey = (string) ($row['stripe_publishable_key'] ?? '');
                }
                if ($stripeSecretKey === '') {
                    $stripeSecretKey = (string) ($row['stripe_secret_key'] ?? '');
                }
            }

            if ($stripePublishableKey === '' || $stripeSecretKey === '') {
                admin_flash_set('error', 'Stripe publishable and secret keys are required for Stripe method.');
                header('Location: payment-settings.php' . ($id > 0 ? ('?edit=' . $id) : ''));
                exit;
            }
        }

        if ($id > 0) {
            $sql = 'UPDATE payment_methods
                    SET method_name = :method_name,
                        method_type = :method_type,
                        stripe_publishable_key = :stripe_publishable_key,
                        stripe_secret_key = :stripe_secret_key,
                        mode = :mode,
                        status = :status
                    WHERE id = :id';
            $pdo->prepare($sql)->execute([
                ':method_name' => $methodName,
                ':method_type' => $methodType,
                ':stripe_publishable_key' => $stripePublishableKey !== '' ? $stripePublishableKey : null,
                ':stripe_secret_key' => $stripeSecretKey !== '' ? $stripeSecretKey : null,
                ':mode' => $mode,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash_set('success', 'Payment method updated.');
        } else {
            $sql = 'INSERT INTO payment_methods
                    (method_name, method_type, stripe_publishable_key, stripe_secret_key, mode, status)
                    VALUES
                    (:method_name, :method_type, :stripe_publishable_key, :stripe_secret_key, :mode, :status)';
            $pdo->prepare($sql)->execute([
                ':method_name' => $methodName,
                ':method_type' => $methodType,
                ':stripe_publishable_key' => $stripePublishableKey !== '' ? $stripePublishableKey : null,
                ':stripe_secret_key' => $stripeSecretKey !== '' ? $stripeSecretKey : null,
                ':mode' => $mode,
                ':status' => $status,
            ]);
            admin_flash_set('success', 'Payment method created.');
        }

        header('Location: payment-settings.php');
        exit;
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = [
    'id' => 0,
    'method_name' => '',
    'method_type' => 'stripe',
    'stripe_publishable_key' => '',
    'stripe_secret_key' => '',
    'mode' => 'test',
    'status' => 'inactive',
];
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM payment_methods WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $edit = $row;
    }
}

$methods = $pdo->query('SELECT * FROM payment_methods ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-12">
    <div class="form-section">
      <h5 class="mb-3"><?= $editId > 0 ? 'Edit' : 'Add' ?> Payment Method</h5>
      <form method="post" id="payment-form" class="form-row" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

        <div class="form-group">
          <label class="form-label">Method Name</label>
          <input type="text" name="method_name" class="form-control" value="<?= e((string) ($edit['method_name'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Method Type</label>
          <select name="method_type" id="method_type" class="form-select">
            <option value="stripe" <?= (($edit['method_type'] ?? '') === 'stripe') ? 'selected' : '' ?>>Stripe</option>
            <option value="cod" <?= (($edit['method_type'] ?? '') === 'cod') ? 'selected' : '' ?>>Cash on Delivery</option>
            <option value="razorpay" <?= (($edit['method_type'] ?? '') === 'razorpay') ? 'selected' : '' ?>>Razorpay</option>
          </select>
        </div>

        <div class="form-group stripe-only">
          <label class="form-label">Stripe Publishable Key</label>
          <input type="text" name="stripe_publishable_key" class="form-control" value="" placeholder="<?= e(mask_key((string) ($edit['stripe_publishable_key'] ?? ''))) ?>">
        </div>

        <div class="form-group stripe-only">
          <label class="form-label">Stripe Secret Key</label>
          <input type="password" name="stripe_secret_key" class="form-control" value="" placeholder="<?= e(mask_key((string) ($edit['stripe_secret_key'] ?? ''))) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Mode</label>
          <select name="mode" class="form-select">
            <option value="test" <?= (($edit['mode'] ?? 'test') === 'test') ? 'selected' : '' ?>>Test</option>
            <option value="live" <?= (($edit['mode'] ?? 'test') === 'live') ? 'selected' : '' ?>>Live</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= (($edit['status'] ?? 'inactive') === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($edit['status'] ?? 'inactive') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="form-group">
          <div class="d-flex gap-2">
            <button class="btn btn-primary-modern" type="submit">Save Payment Method</button>
            <?php if ($editId > 0): ?><a class="btn btn-secondary-modern" href="payment-settings.php">Cancel</a><?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-12">
    <div class="widget-card">
      <div class="widget-header"><h5 class="widget-title">Payment Methods (<?= count($methods) ?>)</h5></div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Mode</th>
              <th>Publishable Key</th>
              <th>Secret Key</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($methods as $method): ?>
            <tr>
              <td><?= (int) $method['id'] ?></td>
              <td><?= e((string) $method['method_name']) ?></td>
              <td><?= e(strtoupper((string) $method['method_type'])) ?></td>
              <td><?= e(strtoupper((string) $method['mode'])) ?></td>
              <td><?= e(mask_key((string) ($method['stripe_publishable_key'] ?? ''))) ?></td>
              <td><?= e(mask_key((string) ($method['stripe_secret_key'] ?? ''))) ?></td>
              <td><span class="status-badge <?= ($method['status'] ?? '') === 'active' ? 'status-active' : 'status-inactive' ?>"><?= e(ucfirst((string) ($method['status'] ?? 'inactive'))) ?></span></td>
              <td>
                <div class="d-flex gap-2">
                  <a class="btn btn-outline-primary btn-sm" href="payment-settings.php?edit=<?= (int) $method['id'] ?>"><i class="bi bi-pencil"></i></a>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="id" value="<?= (int) $method['id'] ?>">
                    <button class="btn btn-outline-warning btn-sm" type="submit"><?= ($method['status'] ?? '') === 'active' ? 'Disable' : 'Enable' ?></button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete payment method?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $method['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
    const methodType = document.getElementById('method_type');
    const stripeOnlyFields = document.querySelectorAll('.stripe-only');

    function updateFields() {
        const isStripe = methodType && methodType.value === 'stripe';
        stripeOnlyFields.forEach((field) => {
            field.style.display = isStripe ? '' : 'none';
        });
    }

    methodType?.addEventListener('change', updateFields);
    updateFields();
})();
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
