<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'How It Works — Feature Sections';
$pdo = db();

if ($pdo) {
    cms_ensure_how_it_works_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Layout Setting
    if ($action === 'save_layout_setting') {
        $layout = trim((string) ($_POST['how_it_works_layout'] ?? 'default'));
        if (!in_array($layout, ['default', 'left', 'right', 'center'], true)) {
            $layout = 'default';
        }

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'how_it_works_layout', ':v' => $layout]);

        cms_invalidate_how_it_works_cache();
        admin_flash('success', 'Section layout setting updated successfully.');
        header('Location: how-it-works-sections.php');
        exit;
    }

    // 2. Save Hero Content Setting
    if ($action === 'save_hero_setting') {
        $heroTitle = trim((string) ($_POST['how_it_works_hero_title'] ?? ''));
        $heroDesc = trim((string) ($_POST['how_it_works_hero_description'] ?? ''));

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'how_it_works_hero_title', ':v' => $heroTitle]);
        $stmt->execute([':k' => 'how_it_works_hero_description', ':v' => $heroDesc]);

        cms_invalidate_how_it_works_cache();
        admin_flash('success', 'Hero headline & description updated successfully.');
        header('Location: how-it-works-sections.php');
        exit;
    }

    // 3. Save / Add / Edit Section
    if ($action === 'save_section') {
        $id = (int) ($_POST['id'] ?? 0);
        $sectionTitle = trim((string) ($_POST['title'] ?? ''));
        $body1 = trim((string) ($_POST['body_1'] ?? ''));
        $body2 = trim((string) ($_POST['body_2'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $existingImage = trim((string) ($_POST['existing_image_path'] ?? ''));

        $imagePath = $existingImage;
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'how-it-works', 5_000_000, false);
            if ($stored) {
                $imagePath = (string) $stored['public_path'];
            }
        }

        if ($sectionTitle === '' || $body1 === '') {
            admin_flash('danger', 'Title and Paragraph 1 are required.');
            header('Location: how-it-works-sections.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($imagePath === '') {
            admin_flash('danger', 'Feature section image is required.');
            header('Location: how-it-works-sections.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE how_it_works_sections SET title = :title, body_1 = :body_1, body_2 = :body_2, image_path = :image_path, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':title' => $sectionTitle,
                ':body_1' => $body1,
                ':body_2' => $body2 !== '' ? $body2 : null,
                ':image_path' => $imagePath,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Feature section updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO how_it_works_sections (title, body_1, body_2, image_path, sort_order, is_active) VALUES (:title, :body_1, :body_2, :image_path, :sort_order, :is_active)');
            $stmt->execute([
                ':title' => $sectionTitle,
                ':body_1' => $body1,
                ':body_2' => $body2 !== '' ? $body2 : null,
                ':image_path' => $imagePath,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Feature section added successfully.');
        }

        cms_invalidate_how_it_works_cache();
        header('Location: how-it-works-sections.php');
        exit;
    }

    // 4. Toggle Active Status
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE how_it_works_sections SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_how_it_works_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: how-it-works-sections.php');
        exit;
    }

    // 5. Delete Single Section
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM how_it_works_sections WHERE id = :id', [':id' => $id]);
            cms_invalidate_how_it_works_cache();
            admin_flash('success', 'Section deleted successfully.');
        }
        header('Location: how-it-works-sections.php');
        exit;
    }

    // 6. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM how_it_works_sections WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_how_it_works_cache();
            admin_flash('success', count($ids) . ' sections deleted successfully.');
        }
        header('Location: how-it-works-sections.php');
        exit;
    }
}

// Fetch Current Settings & Sections
$currentLayout = cms_get_how_it_works_layout();
$heroTitle = cms_get_setting('how_it_works_hero_title', 'Unleash Your Brand\'s Potential With Our Perfect Solution.');
$heroDescription = cms_get_setting('how_it_works_hero_description', 'Embrace complete customization, meticulously tailoring your product line to seamlessly harmonize with your brand and visionary essence.');
$sections = $pdo ? cms_get_how_it_works_sections(true) : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'title' => '',
    'body_1' => '',
    'body_2' => '',
    'image_path' => '',
    'sort_order' => count($sections) + 1,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM how_it_works_sections WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$livePreviewUrl = url('how-it-works.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4 mb-4">
  <!-- Layout Control Card -->
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-layout-split text-primary"></i> Global Section Layout
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="how-it-works-sections.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_layout_setting">
          
          <p class="text-muted small mb-3">Controls how image-and-text feature cards are aligned across the entire page.</p>

          <div class="row g-3">
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'default' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="how_it_works_layout" value="default" class="d-none" <?= $currentLayout === 'default' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Default</div>
                <small class="text-muted fs-12">Alternate Left / Right</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'left' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="how_it_works_layout" value="left" class="d-none" <?= $currentLayout === 'left' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Image Left</div>
                <small class="text-muted fs-12">All images on left</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'right' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="how_it_works_layout" value="right" class="d-none" <?= $currentLayout === 'right' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Image Right</div>
                <small class="text-muted fs-12">All images on right</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'center' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="how_it_works_layout" value="center" class="d-none" <?= $currentLayout === 'center' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Centered</div>
                <small class="text-muted fs-12">Image top, stacked</small>
              </label>
            </div>
          </div>
          <button type="submit" class="btn btn-sm btn-primary mt-3"><i class="bi bi-check-lg"></i> Save Layout</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Hero Header Setting Card -->
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-type-h1 text-primary"></i> Page Hero Header Content
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="how-it-works-sections.php" data-section-preview='{"content_type":"site_settings","entity_id":0}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_hero_setting">
          
          <div class="mb-2">
            <label class="form-label fw-semibold">Headline</label>
            <input type="text" name="how_it_works_hero_title" class="form-control form-control-sm" value="<?= e($heroTitle) ?>" required>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Intro Description</label>
            <textarea name="how_it_works_hero_description" class="form-control form-control-sm" rows="3"><?= e($heroDescription) ?></textarea>
          </div>
          <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Save Hero Text</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Feature Section' : 'Add New Feature Section' ?>
    </h5>
    <a href="how-it-works-sections.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="how-it-works-sections.php" enctype="multipart/form-data" data-section-preview='{"content_type":"how_it_works_section","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_section">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_image_path" value="<?= e($formData['image_path']) ?>">

      <div class="row g-3">
        <div class="col-12 col-md-8">
          <label class="form-label fw-semibold">Section Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= e($formData['title']) ?>" placeholder="e.g. Choose Your Product Components" required>
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label fw-semibold">Status</label>
          <div class="form-check form-switch mt-2">
            <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActiveSwitch">Active</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Paragraph 1 Content <span class="text-danger">*</span></label>
          <textarea name="body_1" class="form-control js-editor" rows="5"><?= e($formData['body_1']) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Paragraph 2 Content (Optional)</label>
          <textarea name="body_2" class="form-control js-editor" rows="4"><?= e($formData['body_2']) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Feature Image <span class="text-danger">*</span></label>
          <?php if (!empty($formData['image_path'])): ?>
            <div class="mb-2 d-flex align-items-center gap-3">
              <img src="<?= e(url($formData['image_path'])) ?>" alt="Current Preview" class="img-thumbnail" style="max-height: 100px; object-fit: contain;">
              <span class="text-muted small"><?= e($formData['image_path']) ?></span>
            </div>
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept="image/*" <?= empty($formData['image_path']) ? 'required' : '' ?>>
          <small class="text-muted">Recommended dimensions: 2048x1244 WebP/JPG/PNG. Max size: 5MB.</small>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Section</button>
        <a href="how-it-works-sections.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- List View Table -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="card-title mb-0">Feature Sections</h5>
      <small class="text-muted">Drag rows to reorder or click Edit to update details.</small>
    </div>
    <a href="how-it-works-sections.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Add Section</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="how-it-works-sections.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" id="bulkActionInput" value="bulk_delete">

      <div class="p-2 bg-light border-bottom d-flex align-items-center justify-content-between gap-2" id="bulkActionBar" style="display: none !important;">
        <span class="fs-14 fw-semibold text-muted ms-2"><span id="selectedCount">0</span> item(s) selected</span>
        <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Delete selected sections?')) document.getElementById('bulkForm').submit();">
          <i class="bi bi-trash"></i> Delete Selected
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="sectionsTable">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="text-center">
                <input type="checkbox" class="form-check-input" id="selectAll">
              </th>
              <th style="width: 40px;"></th>
              <th style="width: 100px;">Image</th>
              <th>Title</th>
              <th style="width: 100px;">Sort</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end me-2">Actions</th>
            </tr>
          </thead>
          <tbody id="reorderableTbody">
            <?php if (empty($sections)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No feature sections found. Click "Add Section" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($sections as $sec): ?>
                <tr data-id="<?= (int) $sec['id'] ?>" draggable="true" class="draggable-row">
                  <td class="text-center">
                    <input type="checkbox" name="ids[]" value="<?= (int) $sec['id'] ?>" class="form-check-input row-checkbox">
                  </td>
                  <td class="text-muted text-center cursor-grab drag-handle" title="Drag to reorder">
                    <i class="bi bi-grip-vertical fs-5"></i>
                  </td>
                  <td>
                    <?php if (!empty($sec['image_path'])): ?>
                      <img src="<?= e(url($sec['image_path'])) ?>" alt="" class="rounded border" style="width: 64px; height: 40px; object-fit: cover;">
                    <?php else: ?>
                      <span class="badge bg-secondary">No Image</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="fw-bold text-dark fs-15"><?= e($sec['title']) ?></div>
                    <div class="text-muted small text-truncate" style="max-width: 400px;"><?= e(strip_tags($sec['body_1'])) ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1 fs-13"><?= (int) $sec['sort_order'] ?></span>
                  </td>
                  <td>
                    <form method="post" action="how-it-works-sections.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                      <button type="submit" class="btn btn-sm p-0 border-0">
                        <?php if (!empty($sec['is_active'])): ?>
                          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                        <?php else: ?>
                          <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>
                        <?php endif; ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm me-2">
                      <a href="how-it-works-sections.php?edit=<?= (int) $sec['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                      <form method="post" action="how-it-works-sections.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this section?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $sec['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<!-- TinyMCE Initialization -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-editor').forEach(function (el, idx) {
      if (!el.id) {
        el.id = 'sec_editor_' + idx;
      }
    });

    if (window.tinymce) {
      tinymce.init({
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
        suffix: '.min',
        selector: '.js-editor',
        height: 220,
        menubar: false,
        branding: false,
        plugins: 'advlist autolink lists link image table code fullscreen preview searchreplace wordcount',
        toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table | code preview',
        setup: function (editor) {
          editor.on('change input undo redo', function () {
            editor.save();
          });
        }
      });
    }


    // Bulk Checkbox Handling
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const bulkActionBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');

    function updateBulkBar() {
      const checked = document.querySelectorAll('.row-checkbox:checked');
      if (checked.length > 0) {
        bulkActionBar.style.setProperty('display', 'flex', 'important');
        selectedCount.textContent = checked.length;
      } else {
        bulkActionBar.style.setProperty('display', 'none', 'important');
      }
    }

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkBar();
      });
    }

    rowCheckboxes.forEach(cb => {
      cb.addEventListener('change', updateBulkBar);
    });

    // Drag and Drop Table Rows
    const tbody = document.getElementById('reorderableTbody');
    if (tbody) {
      let dragEl = null;

      tbody.querySelectorAll('.draggable-row').forEach(row => {
        row.addEventListener('dragstart', function (e) {
          dragEl = this;
          e.dataTransfer.effectAllowed = 'move';
          this.classList.add('bg-light');
        });

        row.addEventListener('dragover', function (e) {
          e.preventDefault();
          e.dataTransfer.dropEffect = 'move';
          const target = e.currentTarget;
          if (target && target !== dragEl) {
            const rect = target.getBoundingClientRect();
            const next = (e.clientY - rect.top) / (rect.bottom - rect.top) > 0.5;
            tbody.insertBefore(dragEl, next ? target.nextSibling : target);
          }
        });

        row.addEventListener('dragend', function () {
          this.classList.remove('bg-light');
          saveOrder();
        });
      });

      function saveOrder() {
        const order = Array.from(tbody.querySelectorAll('.draggable-row')).map(r => r.getAttribute('data-id'));
        fetch('api/how-it-works-reorder.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: '<?= e(csrf_token()) ?>',
            type: 'sections',
            order: order
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Update sort badge displays visually
            tbody.querySelectorAll('.draggable-row').forEach((row, idx) => {
              const badge = row.querySelector('.badge.bg-light');
              if (badge) badge.textContent = idx + 1;
            });
          }
        });
      }
    }
  });
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
