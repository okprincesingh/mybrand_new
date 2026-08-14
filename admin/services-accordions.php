<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Services — Order Process Accordion';
$pdo = db();

if ($pdo) {
    cms_ensure_services_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save / Add / Edit Accordion Item
    if ($action === 'save_accordion') {
        $id = (int) ($_POST['id'] ?? 0);
        $accTitle = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isOpenDefault = isset($_POST['is_open_default']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($accTitle === '' || $body === '') {
            admin_flash('danger', 'Accordion title and body content are required.');
            header('Location: services-accordions.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        // If setting this item as default open, uncheck default open on all other accordions
        if ($isOpenDefault === 1) {
            db_execute($pdo, 'UPDATE services_accordions SET is_open_default = 0');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE services_accordions SET title = :title, body = :body, sort_order = :sort_order, is_open_default = :is_open_default, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':title' => $accTitle,
                ':body' => $body,
                ':sort_order' => $sortOrder,
                ':is_open_default' => $isOpenDefault,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Accordion item updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO services_accordions (title, body, sort_order, is_open_default, is_active) VALUES (:title, :body, :sort_order, :is_open_default, :is_active)');
            $stmt->execute([
                ':title' => $accTitle,
                ':body' => $body,
                ':sort_order' => $sortOrder,
                ':is_open_default' => $isOpenDefault,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Accordion item added successfully.');
        }

        cms_invalidate_services_cache();
        header('Location: services-accordions.php');
        exit;
    }

    // 2. Toggle Active Status
    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE services_accordions SET is_active = NOT is_active WHERE id = :id', [':id' => $id]);
            cms_invalidate_services_cache();
            admin_flash('success', 'Status toggled successfully.');
        }
        header('Location: services-accordions.php');
        exit;
    }

    // 3. Toggle Default Open Status
    if ($action === 'set_default_open') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'UPDATE services_accordions SET is_open_default = 0');
            db_execute($pdo, 'UPDATE services_accordions SET is_open_default = 1 WHERE id = :id', [':id' => $id]);
            cms_invalidate_services_cache();
            admin_flash('success', 'Default open item updated.');
        }
        header('Location: services-accordions.php');
        exit;
    }

    // 4. Delete Single Accordion
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM services_accordions WHERE id = :id', [':id' => $id]);
            cms_invalidate_services_cache();
            admin_flash('success', 'Accordion item deleted successfully.');
        }
        header('Location: services-accordions.php');
        exit;
    }

    // 5. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM services_accordions WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_services_cache();
            admin_flash('success', count($ids) . ' accordion items deleted successfully.');
        }
        header('Location: services-accordions.php');
        exit;
    }
}

// Fetch Accordion Rows
$accordions = $pdo ? cms_get_services_accordions(true) : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'title' => '',
    'body' => '',
    'sort_order' => count($accordions) + 1,
    'is_open_default' => 0,
    'is_active' => 1,
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM services_accordions WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$livePreviewUrl = url('services.php');
include __DIR__ . '/_layout_top.php';
?>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Order Process Accordion Item' : 'Add New Order Process Accordion Item' ?>
    </h5>
    <a href="services-accordions.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
  </div>
  <div class="card-body">
    <form method="post" action="services-accordions.php" data-section-preview='{"content_type":"services_accordion","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_accordion">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">

      <div class="row g-3">
        <div class="col-12 col-md-7">
          <label class="form-label fw-semibold">Accordion Header Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" value="<?= e($formData['title']) ?>" placeholder="e.g. Contact our Project Consultants to place your order." required>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <div class="form-check form-switch mt-2">
            <input type="checkbox" name="is_active" class="form-check-input" id="isActiveSwitch" value="1" <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActiveSwitch">Active</label>
          </div>
        </div>

        <div class="col-12">
          <div class="form-check form-switch mb-2">
            <input type="checkbox" name="is_open_default" class="form-check-input" id="isOpenDefaultSwitch" value="1" <?= !empty($formData['is_open_default']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="isOpenDefaultSwitch">Open by Default on Page Load</label>
            <div class="form-text">Note: Only one accordion item can be open by default. Checking this will uncheck others upon saving.</div>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Accordion Body Content <span class="text-danger">*</span></label>
          <textarea name="body" class="form-control js-editor" rows="6"><?= e($formData['body']) ?></textarea>
          <small class="text-muted">Supports formatted text, lists (<code>&lt;ul&gt;&lt;li&gt;</code>), and bold labels.</small>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Accordion Item</button>
        <a href="services-accordions.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- List View Table -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="card-title mb-0">Order Process Accordion Items</h5>
      <small class="text-muted">Manage the interactive expandable steps shown under Order Process (#logistics-support).</small>
    </div>
    <a href="services-accordions.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Add Accordion Step</a>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="services-accordions.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" id="bulkActionInput" value="bulk_delete">

      <!-- Bulk Action Bar -->
      <div class="p-2 bg-light border-bottom d-flex align-items-center justify-content-between gap-2" id="bulkActionBar" style="display: none !important;">
        <span class="fs-14 fw-semibold text-muted ms-2"><span id="selectedCount">0</span> item(s) selected</span>
        <button type="button" class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure you want to delete the selected accordion items?')) document.getElementById('bulkForm').submit();">
          <i class="bi bi-trash"></i> Delete Selected
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="accordionsTable">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="text-center">
                <input type="checkbox" class="form-check-input" id="selectAll">
              </th>
              <th style="width: 40px;"></th>
              <th>Header Title</th>
              <th style="width: 140px;" class="text-center">Default Open</th>
              <th style="width: 90px;" class="text-center">Sort</th>
              <th style="width: 100px;" class="text-center">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="reorderableTbody">
            <?php if (empty($accordions)): ?>
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">No accordion items found. Click "Add Accordion Step" to create one.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($accordions as $acc): ?>
                <tr data-id="<?= (int) $acc['id'] ?>" draggable="true" class="draggable-row">
                  <td class="text-center">
                    <input type="checkbox" name="ids[]" value="<?= (int) $acc['id'] ?>" class="form-check-input row-checkbox">
                  </td>
                  <td class="text-muted text-center cursor-grab drag-handle" title="Drag to reorder">
                    <i class="bi bi-grip-vertical fs-5"></i>
                  </td>
                  <td>
                    <div class="fw-bold text-dark fs-15"><?= e($acc['title']) ?></div>
                    <div class="text-muted small text-truncate" style="max-width: 450px;"><?= e(strip_tags($acc['body'])) ?></div>
                  </td>
                  <td class="text-center">
                    <form method="post" action="services-accordions.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="set_default_open">
                      <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
                      <button type="submit" class="btn btn-sm p-0 border-0" title="Click to make default open">
                        <?php if (!empty($acc['is_open_default'])): ?>
                          <span class="badge bg-primary px-2 py-1"><i class="bi bi-check-circle-fill"></i> Default Open</span>
                        <?php else: ?>
                          <span class="badge bg-light text-muted border px-2 py-1">Closed</span>
                        <?php endif; ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border px-2 py-1 fs-13"><?= (int) $acc['sort_order'] ?></span>
                  </td>
                  <td class="text-center">
                    <form method="post" action="services-accordions.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
                      <button type="submit" class="btn btn-sm p-0 border-0" title="Click to toggle status">
                        <?php if (!empty($acc['is_active'])): ?>
                          <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                        <?php else: ?>
                          <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>
                        <?php endif; ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <a href="services-accordions.php?edit=<?= (int) $acc['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                      <form method="post" action="services-accordions.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this accordion item?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
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

<!-- TinyMCE & Bulk Select JS -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // TinyMCE Init
    document.querySelectorAll('.js-editor').forEach(function (el, idx) {
      if (!el.id) {
        el.id = 'acc_editor_' + idx;
      }
    });

    if (window.tinymce) {
      tinymce.init({
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
        suffix: '.min',
        selector: '.js-editor',
        height: 250,
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
        fetch('api/services-reorder.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: '<?= e(csrf_token()) ?>',
            type: 'accordions',
            order: order
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data && data.success) {
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

<style>
  .cursor-grab { cursor: grab; }
  .cursor-grab:active { cursor: grabbing; }
</style>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
