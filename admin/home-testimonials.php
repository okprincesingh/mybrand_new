<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Home Testimonials';
$pdo = db();

$defaults = ['id'=>0,'name'=>'','location'=>'','content'=>'','rating'=>5,'sort_order'=>0,'is_active'=>1,'image_path'=>''];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);
if ($pdo && $editId > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM home_testimonials WHERE id=:id LIMIT 1', [':id'=>$editId]);
  if ($row) { $formData = array_merge($defaults, $row); }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? 'save');
  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM home_testimonials WHERE id=:id', [':id'=>$id]);
      cms_invalidate_home_testimonials_cache();
      admin_flash('success', 'Testimonial deleted.');
    }
    header('Location: home-testimonials.php'); exit;
  }

  $id = (int) ($_POST['id'] ?? 0);
  $name = trim((string) ($_POST['name'] ?? ''));
  $location = trim((string) ($_POST['location'] ?? ''));
  $content = trim((string) ($_POST['content'] ?? ''));
  $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
  $sortOrder = (int) ($_POST['sort_order'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;
  $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));

  if (!empty($_FILES['image']['name'])) {
    $stored = store_uploaded_image($_FILES['image'], 'testimonials', 5_000_000, false);
    if ($stored) {
      $imagePath = (string) $stored['public_path'];
    }
  }

  if ($name === '' || $content === '') {
    admin_flash('danger', 'Name and testimonial content are required.');
    header('Location: home-testimonials.php' . ($id > 0 ? '?edit=' . $id : '')); exit;
  }

  if ($id > 0) {
    db_execute($pdo, 'UPDATE home_testimonials SET name=:name, location=:location, content=:content, rating=:rating, image_path=:image, sort_order=:sort_order, is_active=:is_active WHERE id=:id', [
      ':name'=>$name, ':location'=>$location, ':content'=>$content, ':rating'=>$rating, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive, ':id'=>$id
    ]);
    admin_flash('success', 'Testimonial updated.');
  } else {
    db_execute($pdo, 'INSERT INTO home_testimonials (name, location, content, rating, image_path, sort_order, is_active) VALUES (:name,:location,:content,:rating,:image,:sort_order,:is_active)', [
      ':name'=>$name, ':location'=>$location, ':content'=>$content, ':rating'=>$rating, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive
    ]);
    admin_flash('success', 'Testimonial added.');
  }

  cms_invalidate_home_testimonials_cache();
  header('Location: home-testimonials.php'); exit;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_testimonials ORDER BY sort_order ASC, id ASC') : [];
include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-4"><?= $formData['id'] ? 'Edit Testimonial' : 'Add Testimonial' ?></h5>
      <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" value="<?= e((string) $formData['name']) ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Location</label>
          <input class="form-control" name="location" value="<?= e((string) $formData['location']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Content</label>
          <textarea class="form-control" rows="4" name="content" required><?= e((string) $formData['content']) ?></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Rating (1-5)</label>
          <input type="number" min="1" max="5" class="form-control" name="rating" value="<?= (int) $formData['rating'] ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" class="form-control" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
        </div>
        
        <div class="form-group">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int)$formData['is_active'])===1?'checked':'' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Image</label>
          <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp">
          <?php if((string)$formData['image_path']!==''): ?>
            <div class="mt-3">
              <img src="<?= e(url((string)$formData['image_path'])) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
            </div>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <div class="d-flex gap-3">
            <button class="btn btn-primary-modern"><?= $formData['id']?'Update':'Add' ?></button>
            <?php if($formData['id']): ?>
              <a href="home-testimonials.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Testimonials (<?= count($rows) ?>)</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">Export</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table" style="width: 100%;">
          <thead>
            <tr>
              <th>Name</th>
              <th>Location</th>
              <th>Rating</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($rows as $r): ?>
            <tr>
              <td><?= e((string)$r['name']) ?></td>
              <td><?= e((string)$r['location']) ?></td>
              <td>
                <span class="badge bg-primary"><?= (int)$r['rating'] ?>/5</span>
              </td>
              <td>
                <span class="status-badge <?= ((int)$r['is_active'])===1?'status-active':'status-inactive' ?>">
                  <?= ((int)$r['is_active'])===1?'Active':'Inactive' ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a class="btn btn-outline-primary btn-sm" href="home-testimonials.php?edit=<?= (int)$r['id'] ?>" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete this testimonial?');">
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
              <td colspan="5" class="text-center text-muted py-4">No testimonials found.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
