<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'About Us — Info Blocks';
$pdo = db();

if ($pdo) {
    cms_ensure_about_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 0a. Save Global Layout Setting
    if ($action === 'save_layout_setting') {
        $layout = trim((string) ($_POST['about_blocks_layout'] ?? 'default'));
        if (!in_array($layout, ['default', 'left', 'right', 'center'], true)) {
            $layout = 'default';
        }

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'about_blocks_layout', ':v' => $layout]);

        cms_invalidate_about_cache();
        admin_flash('success', 'Info blocks layout updated successfully.');
        header('Location: about-blocks.php');
        exit;
    }

    // 0b. Save Intro Heading
    if ($action === 'save_intro_heading') {
        $heading = trim((string) ($_POST['about_intro_heading'] ?? ''));
        $certHeading = trim((string) ($_POST['about_certifications_heading'] ?? ''));

        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute([':k' => 'about_intro_heading', ':v' => $heading]);
        $stmt->execute([':k' => 'about_certifications_heading', ':v' => $certHeading]);

        cms_invalidate_about_cache();
        admin_flash('success', 'Page headings updated successfully.');
        header('Location: about-blocks.php');
        exit;
    }

    if ($action === 'save_block') {
        $id = (int) ($_POST['id'] ?? 0);
        $blockTitle = trim((string) ($_POST['block_title'] ?? ''));
        $secHeading = trim((string) ($_POST['section_heading'] ?? ''));
        $secIntro = trim((string) ($_POST['section_intro'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $imageAlt = trim((string) ($_POST['image_alt'] ?? ''));
        $layout = trim((string) ($_POST['layout'] ?? 'left'));
        if (!in_array($layout, ['left', 'right'], true)) {
            $layout = 'left';
        }
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $existingImage = trim((string) ($_POST['existing_image_path'] ?? ''));

        $imagePath = $existingImage;
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'about/blocks', 5_000_000, false);
            if ($stored) {
                $imagePath = (string) $stored['public_path'];
            }
        }

        if ($blockTitle === '' || $body === '') {
            admin_flash('danger', 'Block Title and Body content are required.');
            header('Location: about-blocks.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($imagePath === '') {
            admin_flash('danger', 'Block image is required.');
            header('Location: about-blocks.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE about_blocks SET section_heading = :sec_heading, section_intro = :sec_intro, block_title = :block_title, body = :body, image_path = :image_path, image_alt = :image_alt, layout = :layout, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':sec_heading' => $secHeading !== '' ? $secHeading : null,
                ':sec_intro' => $secIntro !== '' ? $secIntro : null,
                ':block_title' => $blockTitle,
                ':body' => $body,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt !== '' ? $imageAlt : null,
                ':layout' => $layout,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Info block updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO about_blocks (section_heading, section_intro, block_title, body, image_path, image_alt, layout, sort_order, is_active) VALUES (:sec_heading, :sec_intro, :block_title, :body, :image_path, :image_alt, :layout, :sort_order, :is_active)');
            $stmt->execute([
                ':sec_heading' => $secHeading !== '' ? $secHeading : null,
                ':sec_intro' => $secIntro !== '' ? $secIntro : null,
                ':block_title' => $blockTitle,
                ':body' => $body,
                ':image_path' => $imagePath,
                ':image_alt' => $imageAlt !== '' ? $imageAlt : null,
                ':layout' => $layout,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Info block added successfully.');
        }

        cms_invalidate_about_cache();
        header('Location: about-blocks.php');
        exit;
    }

    // 2. Toggle Active Status
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE about_blocks SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: about-blocks.php');
        exit;
    }

    // 3. Delete Single Block
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM about_blocks WHERE id = :id', [':id' => $id]);
            cms_invalidate_about_cache();
            admin_flash('success', 'Info block deleted successfully.');
        }
        header('Location: about-blocks.php');
        exit;
    }

    // 4. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM about_blocks WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_about_cache();
            admin_flash('success', count($ids) . ' blocks deleted successfully.');
        }
        header('Location: about-blocks.php');
        exit;
    }
}

// Fetch Blocks
$blocks = $pdo ? cms_get_about_blocks(true) : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'section_heading' => '',
    'section_intro' => '',
    'block_title' => '',
    'body' => '',
    'image_path' => '',
    'image_alt' => '',
    'layout' => 'left',
    'sort_order' => count($blocks) + 1,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM about_blocks WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

// Fetch current settings
$currentLayout = cms_get_setting('about_blocks_layout', 'default');
$introHeading = cms_get_setting('about_intro_heading', 'Thank you for your interest in <span class="theme-color-font">mybrandplease.com!</span>');
$certHeading = cms_get_setting('about_certifications_heading', 'Our Trusted Certifications');

$livePreviewUrl = url('about.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4 mb-4">
  <!-- Global Layout Control Card -->
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-layout-split text-primary"></i> Global Section Layout
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="about-blocks.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_layout_setting">
          
          <p class="text-muted small mb-3">Controls how image-and-text info blocks are aligned across the entire About page.</p>

          <div class="row g-3">
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'default' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="about_blocks_layout" value="default" class="d-none" <?= $currentLayout === 'default' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Default</div>
                <small class="text-muted fs-12">Alternate Left / Right</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'left' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="about_blocks_layout" value="left" class="d-none" <?= $currentLayout === 'left' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Image Left</div>
                <small class="text-muted fs-12">All images on left</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'right' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="about_blocks_layout" value="right" class="d-none" <?= $currentLayout === 'right' ? 'checked' : '' ?> onchange="this.form.submit()">
                <div class="fw-bold mb-1 fs-14">Image Right</div>
                <small class="text-muted fs-12">All images on right</small>
              </label>
            </div>
            <div class="col-6 col-md-3">
              <label class="card h-100 border p-2 text-center layout-card <?= $currentLayout === 'center' ? 'border-primary bg-light' : '' ?>" style="cursor:pointer;">
                <input type="radio" name="about_blocks_layout" value="center" class="d-none" <?= $currentLayout === 'center' ? 'checked' : '' ?> onchange="this.form.submit()">
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

  <!-- Page Headings Card -->
  <div class="col-12 col-xl-6">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-type-h1 text-primary"></i> About Page Headings
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="about-blocks.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_intro_heading">
          
          <div class="mb-2">
            <label class="form-label fw-semibold">About Section Intro Heading</label>
            <input type="text" name="about_intro_heading" class="form-control form-control-sm" value="<?= e($introHeading) ?>" required>
            <small class="text-muted">HTML allowed. Example: Thank you for your interest in &lt;span class="theme-color-font"&gt;mybrandplease.com!&lt;/span&gt;</small>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">Certifications Section Heading</label>
            <input type="text" name="about_certifications_heading" class="form-control form-control-sm" value="<?= e($certHeading) ?>" required>
          </div>
          <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg"></i> Save Headings</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Info Block' : 'Add New Info Block' ?>
    </h5>
    <a href="about-blocks.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="about-blocks.php" enctype="multipart/form-data" data-section-preview='{"content_type":"about_block","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_block">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Block Title <span class="text-danger">*</span></label>
          <input type="text" name="block_title" class="form-control" value="<?= e($formData['block_title']) ?>" placeholder="e.g. Who We Are?" required>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Layout Position</label>
          <div class="d-flex gap-3 mt-1">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="layout" id="layoutLeft" value="left" <?= $formData['layout'] === 'left' ? 'checked' : '' ?>>
              <label class="form-check-label" for="layoutLeft">Image Left</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="layout" id="layoutRight" value="right" <?= $formData['layout'] === 'right' ? 'checked' : '' ?>>
              <label class="form-check-label" for="layoutRight">Image Right</label>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Optional Section Heading above Row</label>
          <input type="text" name="section_heading" class="form-control" value="<?= e((string) $formData['section_heading']) ?>" placeholder="e.g. We Located in the vibrant city of Delhi, India">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Optional Section Intro Paragraph</label>
          <input type="text" name="section_intro" class="form-control" value="<?= e((string) $formData['section_intro']) ?>" placeholder="e.g. mybrandplease.com proudly operates as a trusted hub...">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Body Content (Rich Text) <span class="text-danger">*</span></label>
          <textarea name="body" id="blockBodyEditor" class="form-control js-editor" rows="6"><?= e($formData['body']) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Image Upload <?= $formData['image_path'] !== '' ? '' : '<span class="text-danger">*</span>' ?></label>
          <input type="file" name="image" class="form-control" accept="image/*">
          <?php if ($formData['image_path'] !== ''): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= e(url($formData['image_path'])) ?>" alt="Preview" class="img-thumbnail" style="max-height:80px;">
              <small class="text-muted"><?= e($formData['image_path']) ?></small>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Image Alt Text</label>
          <input type="text" name="image_alt" class="form-control" value="<?= e((string) $formData['image_alt']) ?>" placeholder="Descriptive image text">
        </div>

        <div class="col-12">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="isActiveCheck">Active (Visible on frontend page)</label>
          </div>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Info Block</button>
        <a href="about-blocks.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- List View Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-info-square text-primary"></i> About Us Info Blocks
    </h5>
    <a href="about-blocks.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i> Add Info Block</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="about-blocks.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="bulk_delete">

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="blocksTable">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="ps-3">
                <input type="checkbox" id="selectAll" class="form-check-input">
              </th>
              <th style="width: 50px;">Order</th>
              <th style="width: 100px;">Image</th>
              <th>Block Title &amp; Details</th>
              <th style="width: 120px;">Layout</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableBlocks">
            <?php if (empty($blocks)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No info blocks found. Click "Add Info Block" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($blocks as $b): ?>
                <tr data-id="<?= (int) $b['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $b['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <td>
                    <img src="<?= e(url($b['image_path'])) ?>" alt="<?= e($b['block_title']) ?>" class="rounded" style="width: 70px; height: 50px; object-fit: cover;">
                  </td>
                  <td>
                    <strong class="d-block text-dark"><?= e($b['block_title']) ?></strong>
                    <?php if (!empty($b['section_heading'])): ?>
                      <small class="text-primary d-block text-truncate" style="max-width:360px;">Heading: <?= e(strip_tags($b['section_heading'])) ?></small>
                    <?php endif; ?>
                    <small class="text-muted d-block text-truncate" style="max-width:360px;"><?= e(strip_tags($b['body'])) ?></small>
                  </td>
                  <td>
                    <span class="badge <?= $b['layout'] === 'right' ? 'bg-info text-dark' : 'bg-secondary' ?>">
                      <i class="bi <?= $b['layout'] === 'right' ? 'bi-layout-sidebar-reverse' : 'bi-layout-sidebar' ?> me-1"></i>
                      <?= $b['layout'] === 'right' ? 'Image Right' : 'Image Left' ?>
                    </span>
                  </td>
                  <td>
                    <form method="post" action="about-blocks.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= !empty($b['is_active']) ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= !empty($b['is_active']) ? 'Active' : 'Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="about-blocks.php?edit=<?= (int) $b['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="about-blocks.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this info block?');">
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

      <?php if (!empty($blocks)): ?>
        <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Delete selected info blocks?');">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>
          <span class="text-muted small">Drag table rows using the handle icon to instantly reorder items.</span>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- TinyMCE CDN -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<!-- SortableJS for drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Initialize TinyMCE for body content
  if (typeof tinymce !== 'undefined' && document.getElementById('blockBodyEditor')) {
    tinymce.init({
      selector: '#blockBodyEditor',
      base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
      suffix: '.min',
      branding: false,
      height: 280,
      plugins: 'lists link code table image',
      toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image code',
      content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 15px; }'
    });
  }

  // Bulk selection logic
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

  // Drag and drop reordering
  const tbody = document.getElementById('sortableBlocks');
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
            type: 'blocks',
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
