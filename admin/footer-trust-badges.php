<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Footer Trust Badges';
$pdo = db();
if ($pdo) {
    cms_ensure_footer_and_social_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Badge (Add or Edit)
    if ($action === 'save_badge') {
        $id = (int) ($_POST['id'] ?? 0);
        $label = trim((string) ($_POST['label'] ?? ''));
        $linkUrl = trim((string) ($_POST['link_url'] ?? ''));
        $openInNewTab = !empty($_POST['open_in_new_tab']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $existingImage = trim((string) ($_POST['existing_image'] ?? ''));

        if ($label === '') {
            admin_flash('danger', 'Badge label is required.');
            header('Location: footer-trust-badges.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        // Handle image upload
        $image = $existingImage;
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'trust-badges', 5_000_000, false);
            if ($stored) {
                $image = (string) $stored['public_path'];
            } else {
                admin_flash('danger', 'Image upload failed. Please check the file format (JPG, PNG, WEBP, SVG) and size (max 5 MB).');
                header('Location: footer-trust-badges.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
                exit;
            }
        }

        if ($image === '' && $id === 0) {
            admin_flash('danger', 'Badge image is required.');
            header('Location: footer-trust-badges.php?action=add');
            exit;
        }

        if ($linkUrl === '') {
            $linkUrl = null;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE footer_trust_badges SET label = :label, image = :image, link_url = :link_url, open_in_new_tab = :open_in_new_tab, sort_order = :sort_order, status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':label' => $label,
                ':image' => $image,
                ':link_url' => $linkUrl,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash('success', 'Trust badge updated successfully.');
        } else {
            if ($sortOrder === 0) {
                $maxOrder = (int) db_fetch_value($pdo, 'SELECT MAX(sort_order) FROM footer_trust_badges');
                $sortOrder = $maxOrder + 1;
            }
            $stmt = $pdo->prepare('INSERT INTO footer_trust_badges (label, image, link_url, open_in_new_tab, sort_order, status) VALUES (:label, :image, :link_url, :open_in_new_tab, :sort_order, :status)');
            $stmt->execute([
                ':label' => $label,
                ':image' => $image,
                ':link_url' => $linkUrl,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
            ]);
            admin_flash('success', 'New trust badge added successfully.');
        }

        cms_invalidate_trust_badges_cache();
        header('Location: footer-trust-badges.php');
        exit;
    }

    // 2. Toggle Status
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $currentStatus = db_fetch_value($pdo, 'SELECT status FROM footer_trust_badges WHERE id = :id', [':id' => $id]);
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            db_execute($pdo, 'UPDATE footer_trust_badges SET status = :status, updated_at = NOW() WHERE id = :id', [
                ':status' => $newStatus,
                ':id' => $id,
            ]);
            cms_invalidate_trust_badges_cache();
            admin_flash('success', 'Badge status updated to ' . ucfirst($newStatus) . '.');
        }
        header('Location: footer-trust-badges.php');
        exit;
    }

    // 3. Delete Single Badge
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM footer_trust_badges WHERE id = :id', [':id' => $id]);
            cms_invalidate_trust_badges_cache();
            admin_flash('success', 'Trust badge deleted successfully.');
        }
        header('Location: footer-trust-badges.php');
        exit;
    }

    // 4. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM footer_trust_badges WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_trust_badges_cache();
            admin_flash('success', count($ids) . ' trust badge(s) deleted successfully.');
        } else {
            admin_flash('warning', 'No items selected for deletion.');
        }
        header('Location: footer-trust-badges.php');
        exit;
    }
}

// Fetch all badges
$badges = $pdo ? db_fetch_all($pdo, 'SELECT * FROM footer_trust_badges ORDER BY sort_order ASC, id ASC') : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'label' => '',
    'image' => '',
    'link_url' => '',
    'open_in_new_tab' => 1,
    'sort_order' => count($badges) + 1,
    'status' => 'active',
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM footer_trust_badges WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Footer Trust Badges</h4>
    <p class="text-muted small mb-0">Manage the trust/certification badges row displayed above the footer bottom bar.</p>
  </div>
  <?php if (!$isAdding && $editId === 0): ?>
    <a href="footer-trust-badges.php?action=add" class="btn btn-success">
      <i class="bi bi-plus-lg me-1"></i> Add Badge
    </a>
  <?php endif; ?>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Trust Badge' : 'Add New Trust Badge' ?>
    </h5>
    <a href="footer-trust-badges.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-x-lg"></i> Cancel
    </a>
  </div>
  <div class="card-body p-4">
    <form method="post" enctype="multipart/form-data" action="footer-trust-badges.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_badge">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_image" value="<?= e($formData['image']) ?>">

      <div class="row g-3">
        <!-- Label -->
        <div class="col-md-6">
          <label for="badge-label" class="form-label fw-semibold">Label / Alt Text <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="badge-label" name="label" value="<?= e($formData['label']) ?>" placeholder="e.g. Google Reviews, USFDA, Trustpilot" required>
          <div class="form-text">Used as image alt text and admin reference name.</div>
        </div>

        <!-- Link URL (optional) -->
        <div class="col-md-6">
          <label for="badge-link-url" class="form-label fw-semibold">Link URL <span class="text-muted fw-normal">(optional)</span></label>
          <input type="url" class="form-control" id="badge-link-url" name="link_url" value="<?= e($formData['link_url'] ?? '') ?>" placeholder="https://example.com/reviews">
          <div class="form-text">Leave empty for badges that should not be clickable (e.g. USFDA, DUNS).</div>
        </div>

        <!-- Badge Image -->
        <div class="col-md-6">
          <label for="badge-image" class="form-label fw-semibold">Badge Image <?= $editId === 0 ? '<span class="text-danger">*</span>' : '' ?></label>
          <input type="file" class="form-control" id="badge-image" name="image" accept=".jpg,.jpeg,.png,.webp,.svg" <?= $editId === 0 ? 'required' : '' ?>>
          <div class="form-text">Accepted: JPG, PNG, WEBP, SVG (max 5 MB)</div>
          <?php if (!empty($formData['image'])): ?>
            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2">
              <img src="<?= e(url($formData['image'])) ?>" alt="Current badge" style="max-height:42px;max-width:160px;object-fit:contain;" class="border rounded">
              <span class="small text-muted">Current image</span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Status & Options Row -->
        <div class="col-md-3">
          <label for="badge-status" class="form-label fw-semibold">Status</label>
          <select class="form-select" id="badge-status" name="status">
            <option value="active" <?= ($formData['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($formData['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-md-3">
          <label for="badge-sort-order" class="form-label fw-semibold">Sort Order</label>
          <input type="number" class="form-control" id="badge-sort-order" name="sort_order" value="<?= (int) $formData['sort_order'] ?>" min="0">
        </div>

        <!-- Open in New Tab -->
        <div class="col-12">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="open_in_new_tab" id="badge-new-tab" value="1" <?= !empty($formData['open_in_new_tab']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="badge-new-tab">Open link in new tab <span class="text-muted small">(only applies if Link URL is set)</span></label>
          </div>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi <?= $editId > 0 ? 'bi-check-lg' : 'bi-plus-lg' ?> me-1"></i>
          <?= $editId > 0 ? 'Update Badge' : 'Add Badge' ?>
        </button>
        <a href="footer-trust-badges.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Badge Listing -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-shield-check text-primary"></i> All Trust Badges
      <span class="badge bg-secondary"><?= count($badges) ?></span>
    </h5>
    <div class="d-flex gap-2">
      <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-outline-danger d-none" onclick="submitBulkDelete()">
        <i class="bi bi-trash3 me-1"></i> Delete Selected (<span id="bulkCount">0</span>)
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (empty($badges)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-shield fs-1 d-block mb-2"></i>
        <p>No trust badges yet.</p>
        <a href="footer-trust-badges.php?action=add" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i> Add Your First Badge</a>
      </div>
    <?php else: ?>
      <form id="bulkDeleteForm" method="post" action="footer-trust-badges.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="bulk_delete">

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th width="40"><input type="checkbox" id="selectAll" class="form-check-input" title="Select all"></th>
                <th width="50" class="text-center">#</th>
                <th width="100">Thumbnail</th>
                <th>Label</th>
                <th>Link</th>
                <th width="100" class="text-center">Status</th>
                <th width="180" class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="sortableBadges">
              <?php foreach ($badges as $badge): ?>
                <tr data-id="<?= (int) $badge['id'] ?>" class="sortable-row" style="cursor: grab;">
                  <td>
                    <input type="checkbox" class="form-check-input row-checkbox" name="ids[]" value="<?= (int) $badge['id'] ?>">
                  </td>
                  <td class="text-center text-muted small">
                    <i class="bi bi-grip-vertical me-1 drag-handle" style="cursor:grab;"></i>
                    <?= (int) $badge['sort_order'] ?>
                  </td>
                  <td>
                    <?php if (!empty($badge['image'])): ?>
                      <img src="<?= e(url($badge['image'])) ?>" alt="<?= e($badge['label']) ?>" style="max-height:36px;max-width:130px;object-fit:contain;" class="rounded">
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold"><?= e($badge['label']) ?></td>
                  <td>
                    <?php if (!empty($badge['link_url'])): ?>
                      <a href="<?= e($badge['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-truncate d-inline-block" style="max-width:220px;" title="<?= e($badge['link_url']) ?>">
                        <?= e($badge['link_url']) ?>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <form method="post" action="footer-trust-badges.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= (int) $badge['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $badge['status'] === 'active' ? 'btn-outline-success' : 'btn-outline-secondary' ?>" title="Click to toggle">
                        <i class="bi <?= $badge['status'] === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle' ?>"></i>
                        <?= ucfirst($badge['status']) ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <a href="footer-trust-badges.php?edit=<?= (int) $badge['id'] ?>" class="btn btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="post" action="footer-trust-badges.php" class="d-inline" onsubmit="return confirm('Delete this badge?')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $badge['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                          <i class="bi bi-trash3"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- SortableJS + Bulk Delete Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Drag-and-drop reorder
  const tbody = document.getElementById('sortableBadges');
  if (tbody) {
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 180,
      ghostClass: 'table-active',
      onEnd: function() {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const order = Array.from(rows).map(r => r.getAttribute('data-id'));
        const csrfInput = document.querySelector('#bulkDeleteForm input[name="csrf_token"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        order.forEach(id => formData.append('order[]', id));

        fetch('api/trust-badges-reorder.php', {
          method: 'POST',
          body: formData
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            rows.forEach((row, i) => {
              const orderCell = row.querySelector('td:nth-child(2)');
              if (orderCell) {
                orderCell.innerHTML = '<i class="bi bi-grip-vertical me-1 drag-handle" style="cursor:grab;"></i> ' + (i + 1);
              }
            });
          }
        })
        .catch(() => {});
      }
    });
  }

  // Bulk select logic
  const selectAll = document.getElementById('selectAll');
  const bulkBtn = document.getElementById('bulkDeleteBtn');
  const bulkCount = document.getElementById('bulkCount');

  function updateBulkUI() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    if (bulkBtn) {
      bulkBtn.classList.toggle('d-none', checked === 0);
    }
    if (bulkCount) {
      bulkCount.textContent = checked;
    }
    if (selectAll) {
      const total = document.querySelectorAll('.row-checkbox').length;
      selectAll.checked = checked > 0 && checked === total;
      selectAll.indeterminate = checked > 0 && checked < total;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      document.querySelectorAll('.row-checkbox').forEach(cb => { cb.checked = selectAll.checked; });
      updateBulkUI();
    });
  }

  document.querySelectorAll('.row-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkUI);
  });
});

function submitBulkDelete() {
  const checked = document.querySelectorAll('.row-checkbox:checked').length;
  if (checked === 0) return;
  if (!confirm('Delete ' + checked + ' selected badge(s)?')) return;
  document.getElementById('bulkDeleteForm').submit();
}
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
