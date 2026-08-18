<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'About Us — Certifications';
$pdo = db();

if ($pdo) {
    cms_ensure_about_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save / Add / Edit Certification
    if ($action === 'save_certification') {
        $id = (int) ($_POST['id'] ?? 0);
        $certTitle = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $existingIcon = trim((string) ($_POST['existing_icon_path'] ?? ''));

        $iconPath = $existingIcon;
        if (!empty($_FILES['icon']['name'])) {
            $stored = store_uploaded_image($_FILES['icon'], 'about/certifications', 5_000_000, false);
            if ($stored) {
                $iconPath = (string) $stored['public_path'];
            }
        }

        if ($certTitle === '' || $description === '') {
            admin_flash('danger', 'Title and Description are required.');
            header('Location: about-certifications.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($iconPath === '') {
            admin_flash('danger', 'Certification icon is required.');
            header('Location: about-certifications.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE about_certifications SET icon_path = :icon_path, title = :title, description = :description, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':icon_path' => $iconPath,
                ':title' => $certTitle,
                ':description' => $description,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Certification updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO about_certifications (icon_path, title, description, sort_order, is_active) VALUES (:icon_path, :title, :description, :sort_order, :is_active)');
            $stmt->execute([
                ':icon_path' => $iconPath,
                ':title' => $certTitle,
                ':description' => $description,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Certification added successfully.');
        }

        cms_invalidate_about_cache();
        header('Location: about-certifications.php');
        exit;
    }

    // 2. Toggle Active Status
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE about_certifications SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: about-certifications.php');
        exit;
    }

    // 3. Delete Single Certification
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM about_certifications WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Certification deleted successfully.');
        }
        header('Location: about-certifications.php');
        exit;
    }

    // 4. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM about_certifications WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_about_cache();
            admin_flash('success', count($ids) . ' certifications deleted successfully.');
        }
        header('Location: about-certifications.php');
        exit;
    }
}

// Fetch Certifications
$certs = $pdo ? cms_get_about_certifications(true) : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'icon_path' => '',
    'title' => '',
    'description' => '',
    'sort_order' => count($certs) + 1,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM about_certifications WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$livePreviewUrl = url('about.php');
include __DIR__ . '/_layout_top.php';
?>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Certification' : 'Add New Certification' ?>
    </h5>
    <a href="about-certifications.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="about-certifications.php" enctype="multipart/form-data" data-section-preview='{"content_type":"about_certification","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_certification">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_icon_path" value="<?= e((string) $formData['icon_path']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= e($formData['title']) ?>" placeholder="e.g. Vegan Formulas, Cruelty Free, GMP Certified" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
          <input type="text" name="description" class="form-control" value="<?= e($formData['description']) ?>" placeholder="e.g. The majority of our formulations offered are Vegan." required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Icon Upload <?= $formData['icon_path'] !== '' ? '' : '<span class="text-danger">*</span>' ?></label>
          <input type="file" name="icon" class="form-control" accept="image/*">
          <?php if ($formData['icon_path'] !== ''): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= e(url($formData['icon_path'])) ?>" alt="Preview" class="img-thumbnail" style="max-height:80px;">
              <small class="text-muted"><?= e($formData['icon_path']) ?></small>
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
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Certification</button>
        <a href="about-certifications.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- List View Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-award text-primary"></i> About Us Certifications
    </h5>
    <a href="about-certifications.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i> Add Certification</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="about-certifications.php">
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
              <th style="width: 80px;">Icon</th>
              <th>Certification Title</th>
              <th>Description</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableCerts">
            <?php if (empty($certs)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No certifications found. Click "Add Certification" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($certs as $c): ?>
                <tr data-id="<?= (int) $c['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <td>
                    <img src="<?= e(url($c['icon_path'])) ?>" alt="<?= e($c['title']) ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                  </td>
                  <td>
                    <strong class="text-dark"><?= e($c['title']) ?></strong>
                  </td>
                  <td>
                    <small class="text-muted d-block text-truncate" style="max-width:400px;"><?= e($c['description']) ?></small>
                  </td>
                  <td>
                    <form method="post" action="about-certifications.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= !empty($c['is_active']) ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= !empty($c['is_active']) ? 'Active' : 'Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="about-certifications.php?edit=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="about-certifications.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this certification?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($certs)): ?>
        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected certifications?');">
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

  const tbody = document.getElementById('sortableCerts');
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
            type: 'certifications',
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
