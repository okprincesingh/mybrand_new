<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'About Us — Accreditations';
$pdo = db();

if ($pdo) {
    cms_ensure_about_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Section Header
    if ($action === 'save_header') {
        $heading = trim((string) ($_POST['about_accreditations_heading'] ?? ''));
        $intro = trim((string) ($_POST['about_accreditations_intro'] ?? ''));

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'about_accreditations_heading', ':v' => $heading]);
        $stmt->execute([':k' => 'about_accreditations_intro', ':v' => $intro]);

        cms_invalidate_about_cache();
        admin_flash('success', 'Accreditations section heading updated successfully.');
        header('Location: about-accreditations.php');
        exit;
    }

    // 2. Save / Add / Edit Accreditation Logo
    if ($action === 'save_accreditation') {
        $id = (int) ($_POST['id'] ?? 0);
        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $existingImage = trim((string) ($_POST['existing_image_path'] ?? ''));

        $imagePath = $existingImage;
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'about/accreditations', 5_000_000, false);
            if ($stored) {
                $imagePath = (string) $stored['public_path'];
            }
        }

        if ($altText === '') {
            admin_flash('danger', 'Alt Text / Title is required.');
            header('Location: about-accreditations.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($imagePath === '') {
            admin_flash('danger', 'Logo image is required.');
            header('Location: about-accreditations.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE about_accreditations SET image_path = :image_path, alt_text = :alt_text, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':image_path' => $imagePath,
                ':alt_text' => $altText,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Accreditation updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO about_accreditations (image_path, alt_text, sort_order, is_active) VALUES (:image_path, :alt_text, :sort_order, :is_active)');
            $stmt->execute([
                ':image_path' => $imagePath,
                ':alt_text' => $altText,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Accreditation added successfully.');
        }

        cms_invalidate_about_cache();
        header('Location: about-accreditations.php');
        exit;
    }

    // 3. Toggle Active Status
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE about_accreditations SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: about-accreditations.php');
        exit;
    }

    // 4. Delete Single Accreditation
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM about_accreditations WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Accreditation deleted successfully.');
        }
        header('Location: about-accreditations.php');
        exit;
    }

    // 5. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM about_accreditations WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_about_cache();
            admin_flash('success', count($ids) . ' accreditations deleted successfully.');
        }
        header('Location: about-accreditations.php');
        exit;
    }
}

// Fetch Settings & Accreditations
$accredHeading = cms_get_setting('about_accreditations_heading', 'Accreditations & Associations');
$accredIntro = cms_get_setting('about_accreditations_intro', 'Trusted compliance and industry partnerships that reinforce global quality standards.');
$accreditations = $pdo ? cms_get_about_accreditations(true) : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'image_path' => '',
    'alt_text' => '',
    'sort_order' => count($accreditations) + 1,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM about_accreditations WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$livePreviewUrl = url('about.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- Top Card: Accreditations Section Header Form -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-patch-check text-primary"></i> Accreditations Section Header
    </h5>
  </div>
  <div class="card-body">
    <form method="post" action="about-accreditations.php" data-section-preview='{"content_type":"site_settings","entity_id":0}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_header">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Section Heading</label>
          <input type="text" name="about_accreditations_heading" class="form-control" value="<?= e($accredHeading) ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Section Subtitle / Intro</label>
          <input type="text" name="about_accreditations_intro" class="form-control" value="<?= e($accredIntro) ?>">
        </div>

        <div class="col-12 text-end">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Section Header</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Accreditation Logo' : 'Add New Accreditation Logo' ?>
    </h5>
    <a href="about-accreditations.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="about-accreditations.php" enctype="multipart/form-data" data-section-preview='{"content_type":"about_accreditation","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_accreditation">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Alt Text / Name <span class="text-danger">*</span></label>
          <input type="text" name="alt_text" class="form-control" value="<?= e($formData['alt_text']) ?>" placeholder="e.g. FDA Compliant Facility, TUV Certified" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Logo Image Upload <?= $formData['image_path'] !== '' ? '' : '<span class="text-danger">*</span>' ?></label>
          <input type="file" name="image" class="form-control" accept="image/*">
          <?php if ($formData['image_path'] !== ''): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= e(url($formData['image_path'])) ?>" alt="Preview" class="img-thumbnail" style="max-height:80px;">
              <small class="text-muted"><?= e($formData['image_path']) ?></small>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-12">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="isActiveCheck">Active (Visible on frontend page)</label>
          </div>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Accreditation Logo</button>
        <a href="about-accreditations.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- List View Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-shield-check text-primary"></i> About Us Accreditations &amp; Logos
    </h5>
    <a href="about-accreditations.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i> Add Accreditation Logo</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="about-accreditations.php">
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
              <th style="width: 100px;">Logo</th>
              <th>Alt Text / Name</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableAccred">
            <?php if (empty($accreditations)): ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">No accreditations found. Click "Add Accreditation Logo" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($accreditations as $a): ?>
                <tr data-id="<?= (int) $a['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $a['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <td>
                    <img src="<?= e(url($a['image_path'])) ?>" alt="<?= e($a['alt_text']) ?>" class="rounded" style="max-width: 80px; max-height: 50px; object-fit: contain;">
                  </td>
                  <td>
                    <strong class="text-dark"><?= e($a['alt_text']) ?></strong>
                  </td>
                  <td>
                    <form method="post" action="about-accreditations.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= !empty($a['is_active']) ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= !empty($a['is_active']) ? 'Active' : 'Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="about-accreditations.php?edit=<?= (int) $a['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="about-accreditations.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this logo?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($accreditations)): ?>
        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected accreditations?');">
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

  const tbody = document.getElementById('sortableAccred');
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
            type: 'accreditations',
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
