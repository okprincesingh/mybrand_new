<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'URLs Management (Homepage CTA Cards)';
$pdo = db();
if ($pdo) {
    cms_ensure_home_cta_cards_table($pdo);
}

$defaults = [
    'id' => 0,
    'card_key' => '',
    'title' => '',
    'button_text' => '',
    'button_url' => '',
    'image_path' => '',
    'image_alt' => '',
    'sort_order' => 0,
    'is_active' => 1,
];

$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);
if ($pdo && $editId > 0) {
    $row = db_fetch_one($pdo, 'SELECT * FROM home_cta_cards WHERE id = :id LIMIT 1', [':id' => $editId]);
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
            db_execute($pdo, 'DELETE FROM home_cta_cards WHERE id = :id', [':id' => $id]);
            cms_invalidate_home_cta_cards_cache();
            admin_flash('success', 'CTA Card deleted successfully.');
        }
        header('Location: home-urls.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $cardTitle = trim((string) ($_POST['title'] ?? ''));
    $buttonText = trim((string) ($_POST['button_text'] ?? ''));
    $buttonUrl = trim((string) ($_POST['button_url'] ?? ''));
    $imageAlt = trim((string) ($_POST['image_alt'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));

    // Image upload validation and processing
    if (!empty($_FILES['image']['name'])) {
        $stored = store_uploaded_image($_FILES['image'], 'home-cta', 5_000_000, false);
        if ($stored) {
            $imagePath = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Image upload failed. Please upload a valid image (JPG, JPEG, PNG, WEBP, or GIF up to 5MB).');
            header('Location: home-urls.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }

    // Input Validation
    if ($cardTitle === '') {
        admin_flash('danger', 'Card Title is required.');
        header('Location: home-urls.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($buttonText === '') {
        admin_flash('danger', 'Button Text is required.');
        header('Location: home-urls.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($buttonUrl === '') {
        admin_flash('danger', 'Button URL/Link is required.');
        header('Location: home-urls.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($imagePath === '') {
        admin_flash('danger', 'Card Image is required. Please upload an image.');
        header('Location: home-urls.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($id > 0) {
        db_execute(
            $pdo,
            'UPDATE home_cta_cards SET title = :title, button_text = :btn_text, button_url = :btn_url, image_path = :image_path, image_alt = :image_alt, sort_order = :sort_order, is_active = :is_active WHERE id = :id',
            [
                ':title' => $cardTitle,
                ':btn_text' => $buttonText,
                ':btn_url' => $buttonUrl,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]
        );
        admin_flash('success', 'CTA Card updated successfully.');
    } else {
        db_execute(
            $pdo,
            'INSERT INTO home_cta_cards (title, button_text, button_url, image_path, image_alt, sort_order, is_active) VALUES (:title, :btn_text, :btn_url, :image_path, :image_alt, :sort_order, :is_active)',
            [
                ':title' => $cardTitle,
                ':btn_text' => $buttonText,
                ':btn_url' => $buttonUrl,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]
        );
        admin_flash('success', 'CTA Card added successfully.');
    }

    cms_invalidate_home_cta_cards_cache();
    header('Location: home-urls.php');
    exit;
}

$cards = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_cta_cards ORDER BY sort_order ASC, id ASC') : [];
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0"><?= $formData['id'] ? 'Edit CTA Card' : 'Add New CTA Card' ?></h5>
        <?php if ($formData['id']): ?>
          <a href="home-urls.php" class="btn btn-sm btn-outline-secondary">Clear Edit</a>
        <?php endif; ?>
      </div>

      <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:0.75rem;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Card Title / Identifier</label>
          <input class="form-control" name="title" value="<?= e((string) $formData['title']) ?>" placeholder="e.g. Explore Now, Try Our Products" required>
          <small class="text-muted">Used to identify the card in the admin panel.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Button Text</label>
          <input class="form-control" name="button_text" value="<?= e((string) $formData['button_text']) ?>" placeholder="e.g. Explore now" required>
        </div>

        <div class="form-group">
          <label class="form-label">Button URL / Link</label>
          <input class="form-control" name="button_url" value="<?= e((string) $formData['button_url']) ?>" placeholder="e.g. shop.php, contact.php" required>
        </div>

        <div class="form-group">
          <label class="form-label">Card Background Image</label>
          <?php if (!empty($formData['image_path'])): ?>
            <div class="mb-2 p-2 border rounded bg-light text-center">
              <img src="<?= url(e((string) $formData['image_path'])) ?>" alt="Current Image" style="max-height:100px;object-fit:cover;" class="rounded img-fluid mb-1 d-block mx-auto">
              <small class="text-muted d-block">Current: <?= e((string) $formData['image_path']) ?></small>
            </div>
          <?php endif; ?>
          <input class="form-control" type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif">
          <small class="text-muted">Leave empty during edit to keep the existing image.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Image Alt Text (Optional)</label>
          <input class="form-control" name="image_alt" value="<?= e((string) $formData['image_alt']) ?>" placeholder="e.g. Cosmetic products banner">
        </div>

        <div class="row g-2">
          <div class="col-6">
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input class="form-control" type="number" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
            </div>
          </div>
          <div class="col-6 d-flex align-items-end pb-1">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?= $formData['is_active'] ? 'checked' : '' ?>>
              <label class="form-check-label" for="isActiveCheck">Active Status</label>
            </div>
          </div>
        </div>

        <div class="mt-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-circle me-1"></i><?= $formData['id'] ? 'Update Card' : 'Add Card' ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="table-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Homepage CTA Cards (<?= count($cards) ?>)</h5>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th style="width:70px;">Image</th>
              <th>Card Title</th>
              <th>Button Text</th>
              <th>Link</th>
              <th style="width:60px;">Sort</th>
              <th style="width:70px;">Status</th>
              <th style="width:110px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($cards)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No CTA cards found. Click 'Add Card' to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($cards as $card): ?>
                <tr>
                  <td>
                    <?php if (!empty($card['image_path'])): ?>
                      <img src="<?= url(e((string) $card['image_path'])) ?>" alt="<?= e((string) $card['title']) ?>" style="width:50px;height:50px;object-fit:cover;" class="rounded border">
                    <?php else: ?>
                      <span class="badge bg-secondary">No img</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= e((string) $card['title']) ?></strong>
                    <?php if (!empty($card['card_key'])): ?>
                      <br><small class="text-muted">key: <?= e((string) $card['card_key']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border"><?= e((string) $card['button_text']) ?></span>
                  </td>
                  <td>
                    <small class="text-break"><code><?= e((string) $card['button_url']) ?></code></small>
                  </td>
                  <td><?= (int) $card['sort_order'] ?></td>
                  <td>
                    <?php if ($card['is_active']): ?>
                      <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                    <?php else: ?>
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                      <a href="home-urls.php?edit=<?= (int) $card['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Card">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="post" onsubmit="return confirm('Are you sure you want to delete this CTA card?');" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $card['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Card">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
