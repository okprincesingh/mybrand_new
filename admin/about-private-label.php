<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'About Us — Private Label & Key Benefits';
$pdo = db();

if ($pdo) {
    cms_ensure_about_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Private Label Header Settings
    if ($action === 'save_private_label') {
        $heading = trim((string) ($_POST['about_private_label_heading'] ?? ''));
        $intro = trim((string) ($_POST['about_private_label_intro'] ?? ''));
        $blockTitle = trim((string) ($_POST['about_private_label_block_title'] ?? ''));
        $existingImage = trim((string) ($_POST['existing_image_path'] ?? ''));

        $imagePath = $existingImage;
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'about/private-label', 5_000_000, false);
            if ($stored) {
                $imagePath = (string) $stored['public_path'];
            }
        }

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'about_private_label_heading', ':v' => $heading]);
        $stmt->execute([':k' => 'about_private_label_intro', ':v' => $intro]);
        $stmt->execute([':k' => 'about_private_label_block_title', ':v' => $blockTitle]);
        $stmt->execute([':k' => 'about_private_label_image', ':v' => $imagePath]);

        cms_invalidate_about_cache();
        admin_flash('success', 'Private Label section header & image updated successfully.');
        header('Location: about-private-label.php');
        exit;
    }

    // 2. Save / Add / Edit Key Benefit
    if ($action === 'save_benefit') {
        $id = (int) ($_POST['id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($label === '' || $description === '') {
            admin_flash('danger', 'Label and Description are required.');
            header('Location: about-private-label.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE about_key_benefits SET label = :label, description = :description, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':label' => $label,
                ':description' => $description,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Key Benefit updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO about_key_benefits (label, description, sort_order, is_active) VALUES (:label, :description, :sort_order, :is_active)');
            $stmt->execute([
                ':label' => $label,
                ':description' => $description,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Key Benefit added successfully.');
        }

        cms_invalidate_about_cache();
        header('Location: about-private-label.php');
        exit;
    }

    // 3. Toggle Active Status for Key Benefit
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE about_key_benefits SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: about-private-label.php');
        exit;
    }

    // 4. Delete Single Key Benefit
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM about_key_benefits WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Key Benefit deleted successfully.');
        }
        header('Location: about-private-label.php');
        exit;
    }

    // 5. Bulk Delete Key Benefits
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM about_key_benefits WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_about_cache();
            admin_flash('success', count($ids) . ' benefits deleted successfully.');
        }
        header('Location: about-private-label.php');
        exit;
    }
}

// Fetch Settings & Key Benefits
$plHeading = cms_get_setting('about_private_label_heading', 'Why Private Label?');
$plIntro = cms_get_setting('about_private_label_intro', 'Unleash the power of your brand with our exclusive range of private label skin, hair, and body care products...');
$plBlockTitle = cms_get_setting('about_private_label_block_title', 'Key Benefits');
$plImage = cms_get_setting('about_private_label_image', 'assets/imgs/about/Key-Benefits-min-768x466.jpg');

$benefits = $pdo ? cms_get_about_key_benefits(true) : [];

// Benefit Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$benefitForm = [
    'id' => 0,
    'label' => '',
    'description' => '',
    'sort_order' => count($benefits) + 1,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM about_key_benefits WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $benefitForm = array_merge($benefitForm, $row);
    }
}

$livePreviewUrl = url('about.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- Top Card: Private Label Section Header & Image Form -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-box-seam text-primary"></i> Private Label Section Header &amp; Image
    </h5>
  </div>
  <div class="card-body">
    <form method="post" action="about-private-label.php" enctype="multipart/form-data" data-section-preview='{"content_type":"site_settings","entity_id":0}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_private_label">
      <input type="hidden" name="existing_image_path" value="<?= e($plImage) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Section Heading</label>
          <input type="text" name="about_private_label_heading" class="form-control" value="<?= e($plHeading) ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Key Benefits Title</label>
          <input type="text" name="about_private_label_block_title" class="form-control" value="<?= e($plBlockTitle) ?>" required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Introductory Text</label>
          <textarea name="about_private_label_intro" class="form-control" rows="3"><?= e($plIntro) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Feature Image Upload</label>
          <input type="file" name="image" class="form-control" accept="image/*">
          <?php if ($plImage !== ''): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= e(url($plImage)) ?>" alt="Preview" class="img-thumbnail" style="max-height:80px;">
              <small class="text-muted"><?= e($plImage) ?></small>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-12 text-end">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Section Header</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Benefit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Key Benefit' : 'Add New Key Benefit' ?>
    </h5>
    <a href="about-private-label.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="about-private-label.php" data-section-preview='{"content_type":"about_key_benefit","entity_id":<?= (int) ($benefitForm['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_benefit">
      <input type="hidden" name="id" value="<?= (int) $benefitForm['id'] ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Benefit Label (Bold Title) <span class="text-danger">*</span></label>
          <input type="text" name="label" class="form-control" value="<?= e($benefitForm['label']) ?>" placeholder="e.g. Higher Profits, Brand Equity" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $benefitForm['sort_order'] ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Description Text <span class="text-danger">*</span></label>
          <textarea name="description" class="form-control" rows="3" placeholder="Detailed benefit sentence" required><?= e($benefitForm['description']) ?></textarea>
        </div>

        <div class="col-12">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" <?= !empty($benefitForm['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="isActiveCheck">Active (Visible on frontend page)</label>
          </div>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Key Benefit</button>
        <a href="about-private-label.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Key Benefits List View Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-list-check text-primary"></i> Key Benefits List
    </h5>
    <a href="about-private-label.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i> Add Key Benefit</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="about-private-label.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="bulk_delete">

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="ps-3">
                <input type="checkbox" id="selectAll" class="form-check-input">
              </th>
              <th style="width: 50px;">Order</th>
              <th style="width: 200px;">Label</th>
              <th>Description</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableBenefits">
            <?php if (empty($benefits)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">No key benefits found. Click "Add Key Benefit" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($benefits as $b): ?>
                <tr data-id="<?= (int) $b['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $b['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <td>
                    <strong class="text-primary"><?= e($b['label']) ?></strong>
                  </td>
                  <td>
                    <small class="text-muted d-block text-truncate" style="max-width:500px;"><?= e($b['description']) ?></small>
                  </td>
                  <td>
                    <form method="post" action="about-private-label.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= !empty($b['is_active']) ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= !empty($b['is_active']) ? 'Active' : 'Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="about-private-label.php?edit=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="about-private-label.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this benefit?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($benefits)): ?>
        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected key benefits?');">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>
          <span class="text-muted small">Drag table rows using the handle icon to instantly reorder items.</span>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- SortableJS for drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const selectAll = document.getElementById('selectAll');
  const rowSelects = document.querySelectorAll('.row-select');
  const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

  function updateBulkBtn() {
    const checked = document.querySelectorAll('.row-select:checked').length;
    if (bulkDeleteBtn) bulkDeleteBtn.disabled = (checked === 0);
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      rowSelects.forEach(cb => cb.checked = selectAll.checked);
      updateBulkBtn();
    });
  }

  rowSelects.forEach(cb => {
    cb.addEventListener('change', updateBulkBtn);
  });

  const tbody = document.getElementById('sortableBenefits');
  if (tbody && typeof Sortable !== 'undefined') {
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const order = Array.from(rows).map(row => parseInt(row.getAttribute('data-id'), 10));

        fetch('api/about-reorder.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= e(csrf_token()) ?>'
          },
          body: JSON.stringify({
            csrf_token: '<?= e(csrf_token()) ?>',
            type: 'key_benefits',
            order: order
          })
        }).then(r => r.json()).then(data => {
          if (data.success) {
            window.dispatchEvent(new CustomEvent('mybrandplease:preview-reload'));
          } else {
            alert(data.message || 'Reorder failed.');
          }
        }).catch(err => console.error(err));
      }
    });
  }
});
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
