<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Homepage Slider';
$pdo = db();

$defaults = [
    'id' => 0,
    'badge_text' => '',
    'title' => '',
    'description' => '',
    'button_text' => '',
    'button_url' => '',
    'sort_order' => 0,
    'is_active' => 1,
    'image_path' => '',
    'image_alt' => '',
];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);

if ($pdo && $editId > 0) {
    $row = db_fetch_one($pdo, 'SELECT * FROM home_slides WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($defaults, $row);
    }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM home_slides WHERE id = :id', [':id' => $id]);
            cms_invalidate_home_slides_cache();
            admin_flash('success', 'Slide deleted.');
        }
        header('Location: home-slider.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $badgeText = trim((string) ($_POST['badge_text'] ?? ''));
    $slideTitle = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $buttonText = trim((string) ($_POST['button_text'] ?? ''));
    $buttonUrl = trim((string) ($_POST['button_url'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageAlt = trim((string) ($_POST['image_alt'] ?? ''));
    $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));

    if (!empty($_FILES['image']['name'])) {
        $stored = store_uploaded_image($_FILES['image'], 'home-slides', 5_000_000, false);
        if ($stored) {
            $imagePath = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Image upload failed. Please upload jpg, jpeg, png or webp (max 5MB).');
            header('Location: home-slider.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }

    if ($slideTitle === '' || $imagePath === '') {
        admin_flash('danger', 'Slide title and image are required.');
        header('Location: home-slider.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($id > 0) {
        db_execute(
            $pdo,
            'UPDATE home_slides SET badge_text = :badge, title = :title, description = :description, button_text = :btn_text, button_url = :btn_url, image_path = :image_path, image_alt = :image_alt, sort_order = :sort_order, is_active = :is_active WHERE id = :id',
            [
                ':badge' => $badgeText,
                ':title' => $slideTitle,
                ':description' => $description,
                ':btn_text' => $buttonText,
                ':btn_url' => $buttonUrl,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]
        );
        admin_flash('success', 'Slide updated.');
    } else {
        db_execute(
            $pdo,
            'INSERT INTO home_slides (badge_text, title, description, button_text, button_url, image_path, image_alt, sort_order, is_active) VALUES (:badge, :title, :description, :btn_text, :btn_url, :image_path, :image_alt, :sort_order, :is_active)',
            [
                ':badge' => $badgeText,
                ':title' => $slideTitle,
                ':description' => $description,
                ':btn_text' => $buttonText,
                ':btn_url' => $buttonUrl,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]
        );
        admin_flash('success', 'Slide added.');
    }

    cms_invalidate_home_slides_cache();
    header('Location: home-slider.php');
    exit;
}

$slides = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_slides ORDER BY sort_order ASC, id ASC') : [];

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-4"><?= $formData['id'] ? 'Edit Slide' : 'Add New Slide' ?></h5>
      <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Badge Text</label>
          <input type="text" name="badge_text" class="form-control" value="<?= e((string) $formData['badge_text']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" value="<?= e((string) $formData['title']) ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" rows="4" class="form-control"><?= e((string) $formData['description']) ?></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Button Text</label>
          <input type="text" name="button_text" class="form-control" value="<?= e((string) $formData['button_text']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Button URL</label>
          <input type="text" name="button_url" class="form-control" value="<?= e((string) $formData['button_url']) ?>" placeholder="shop.php">
        </div>
        
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Image Alt</label>
          <input type="text" name="image_alt" class="form-control" value="<?= e((string) $formData['image_alt']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Slide Image</label>
          <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $formData['id'] ? '' : 'required' ?>>
          <?php if ((string) $formData['image_path'] !== ''): ?>
            <div class="mt-3">
              <img src="<?= e(url((string) $formData['image_path'])) ?>" alt="" style="width:100%;max-height:200px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
            </div>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int) $formData['is_active']) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>
        
        <div class="form-group">
          <div class="d-flex gap-3">
            <button class="btn btn-primary-modern"><?= $formData['id'] ? 'Update Slide' : 'Add Slide' ?></button>
            <?php if ($formData['id']): ?>
              <a href="home-slider.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Homepage Slides (<?= count($slides) ?>)</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">Export</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Preview</th>
              <th>Title</th>
              <th>Status</th>
              <th>Order</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($slides as $slide): ?>
            <tr>
              <td>
                <div class="preview-image">
                  <img src="<?= e(url((string) $slide['image_path'])) ?>" alt="" style="width:100px;height:60px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                </div>
              </td>
              <td><?= e((string) $slide['title']) ?></td>
              <td>
                <span class="status-badge <?= ((int) $slide['is_active']) === 1 ? 'status-active' : 'status-inactive' ?>">
                  <?= ((int) $slide['is_active']) === 1 ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td><?= (int) $slide['sort_order'] ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a href="home-slider.php?edit=<?= (int) $slide['id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete this slide?');">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$slides): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">No slides found. Add your first slide.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
