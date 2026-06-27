<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Home Offices';
$pdo = db();

$defaults = ['id'=>0,'country'=>'','address'=>'','email'=>'','phone'=>'','sort_order'=>0,'is_active'=>1,'image_path'=>''];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);
if ($pdo && $editId > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM home_offices WHERE id=:id LIMIT 1', [':id'=>$editId]);
  if ($row) { $formData = array_merge($defaults, $row); }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? 'save');
  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM home_offices WHERE id=:id', [':id'=>$id]);
      cms_invalidate_home_offices_cache();
      admin_flash('success', 'Office deleted.');
    }
    header('Location: home-offices.php'); exit;
  }

  $id = (int) ($_POST['id'] ?? 0);
  $country = trim((string) ($_POST['country'] ?? ''));
  $address = trim((string) ($_POST['address'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $sortOrder = (int) ($_POST['sort_order'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;
  $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));

  if (!empty($_FILES['image']['name'])) {
    $stored = store_uploaded_image($_FILES['image'], 'offices', 5_000_000, false);
    if ($stored) {
      $imagePath = (string) $stored['public_path'];
    }
  }

  if ($country === '' || $address === '') {
    admin_flash('danger', 'Country and address are required.');
    header('Location: home-offices.php' . ($id > 0 ? '?edit=' . $id : '')); exit;
  }

  if ($id > 0) {
    db_execute($pdo, 'UPDATE home_offices SET country=:country, address=:address, email=:email, phone=:phone, image_path=:image, sort_order=:sort_order, is_active=:is_active WHERE id=:id', [
      ':country'=>$country, ':address'=>$address, ':email'=>$email, ':phone'=>$phone, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive, ':id'=>$id
    ]);
    admin_flash('success', 'Office updated.');
  } else {
    db_execute($pdo, 'INSERT INTO home_offices (country, address, email, phone, image_path, sort_order, is_active) VALUES (:country,:address,:email,:phone,:image,:sort_order,:is_active)', [
      ':country'=>$country, ':address'=>$address, ':email'=>$email, ':phone'=>$phone, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive
    ]);
    admin_flash('success', 'Office added.');
  }

  cms_invalidate_home_offices_cache();
  header('Location: home-offices.php'); exit;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_offices ORDER BY sort_order ASC, id ASC') : [];
include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-3"><?= $formData['id'] ? 'Edit Office' : 'Add Office' ?></h5>
      <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Country</label>
          <input class="form-control" name="country" value="<?= e((string) $formData['country']) ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea class="form-control" rows="4" name="address" required><?= e((string) $formData['address']) ?></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" value="<?= e((string) $formData['email']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" name="phone" value="<?= e((string) $formData['phone']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" class="form-control" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Status</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int)$formData['is_active'])===1?'checked':'' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Flag / Image</label>
          <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp">
          <?php if((string)$formData['image_path']!==''): ?>
            <img src="<?= e(url((string)$formData['image_path'])) ?>" style="margin-top:10px;width:80px;height:80px;object-fit:cover;border-radius:50%;">
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <div class="d-flex gap-2">
            <button class="btn btn-primary-modern"><?= $formData['id']?'Update':'Add' ?></button>
            <?php if($formData['id']): ?>
              <a href="home-offices.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Office Locations</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">Export</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table" style="width: 100%;">
          <thead>
            <tr>
              <th>Country</th>
              <th>Email</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><?= e((string)$r['country']) ?></td>
                <td><?= e((string)$r['email']) ?></td>
                <td>
                  <span class="status-badge <?= ((int)$r['is_active'])===1?'status-active':'status-inactive' ?>">
                    <?= ((int)$r['is_active'])===1?'Active':'Inactive' ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="home-offices.php?edit=<?= (int)$r['id'] ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" onsubmit="return confirm('Delete this office?');" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(!$rows): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">No offices found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
