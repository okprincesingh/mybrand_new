<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Footer — Links Management';
$pdo = db();

if ($pdo) {
    cms_ensure_footer_and_social_tables($pdo);
}

// Current Filter Tab
$currentGroup = trim((string) ($_GET['group'] ?? 'all'));
$validGroups = ['quick_links', 'compliances', 'legal_disclaimers'];
if (!in_array($currentGroup, array_merge(['all'], $validGroups), true)) {
    $currentGroup = 'all';
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');
    $groupRedirect = trim((string) ($_POST['current_group'] ?? $currentGroup));

    // 1. Save Link (Add or Edit)
    if ($action === 'save_link') {
        $id = (int) ($_POST['id'] ?? 0);
        $groupKey = trim((string) ($_POST['group_key'] ?? 'quick_links'));
        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $openInNewTab = !empty($_POST['open_in_new_tab']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if (!in_array($groupKey, $validGroups, true)) {
            admin_flash('danger', 'Invalid link group selected.');
            header('Location: footer-links.php?group=' . urlencode($groupRedirect));
            exit;
        }

        if ($label === '' || $url === '') {
            admin_flash('danger', 'Link Label and URL are required.');
            header('Location: footer-links.php?group=' . urlencode($groupRedirect) . ($id > 0 ? '&edit=' . $id : '&action=add'));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE footer_links SET group_key = :group_key, label = :label, url = :url, open_in_new_tab = :open_in_new_tab, sort_order = :sort_order, status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':group_key' => $groupKey,
                ':label' => $label,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash('success', 'Footer link updated successfully.');
        } else {
            if ($sortOrder === 0) {
                $maxOrder = (int) db_fetch_value($pdo, 'SELECT MAX(sort_order) FROM footer_links WHERE group_key = :gk', [':gk' => $groupKey]);
                $sortOrder = $maxOrder + 1;
            }
            $stmt = $pdo->prepare('INSERT INTO footer_links (group_key, label, url, open_in_new_tab, sort_order, status) VALUES (:group_key, :label, :url, :open_in_new_tab, :sort_order, :status)');
            $stmt->execute([
                ':group_key' => $groupKey,
                ':label' => $label,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
            ]);
            admin_flash('success', 'New footer link added successfully.');
        }

        cms_invalidate_footer_cache();
        header('Location: footer-links.php?group=' . urlencode($groupKey));
        exit;
    }

    // 2. Toggle Status (Active / Inactive)
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $currentStatus = db_fetch_value($pdo, 'SELECT status FROM footer_links WHERE id = :id', [':id' => $id]);
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            db_execute($pdo, 'UPDATE footer_links SET status = :status, updated_at = NOW() WHERE id = :id', [
                ':status' => $newStatus,
                ':id' => $id,
            ]);
            cms_invalidate_footer_cache();
            admin_flash('success', 'Link status updated to ' . ucfirst($newStatus) . '.');
        }
        header('Location: footer-links.php?group=' . urlencode($groupRedirect));
        exit;
    }

    // 3. Delete Single Link
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM footer_links WHERE id = :id', [':id' => $id]);
            cms_invalidate_footer_cache();
            admin_flash('success', 'Footer link deleted successfully.');
        }
        header('Location: footer-links.php?group=' . urlencode($groupRedirect));
        exit;
    }

    // 4. Bulk Multi-Select Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM footer_links WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_footer_cache();
            admin_flash('success', count($ids) . ' footer links deleted successfully.');
        } else {
            admin_flash('warning', 'No links were selected for deletion.');
        }
        header('Location: footer-links.php?group=' . urlencode($groupRedirect));
        exit;
    }
}

// Fetch counts per group
$counts = [
    'all' => (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_links'),
    'quick_links' => (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_links WHERE group_key = "quick_links"'),
    'compliances' => (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_links WHERE group_key = "compliances"'),
    'legal_disclaimers' => (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_links WHERE group_key = "legal_disclaimers"'),
];

// Fetch links based on current filter
$sql = 'SELECT * FROM footer_links';
$params = [];
if ($currentGroup !== 'all') {
    $sql .= ' WHERE group_key = :gk';
    $params[':gk'] = $currentGroup;
}
$sql .= ' ORDER BY group_key ASC, sort_order ASC, id ASC';
$links = $pdo ? db_fetch_all($pdo, $sql, $params) : [];

// Edit / Add Form State
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'group_key' => ($currentGroup !== 'all') ? $currentGroup : 'quick_links',
    'label' => '',
    'url' => '',
    'open_in_new_tab' => 0,
    'sort_order' => count($links) + 1,
    'status' => 'active',
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM footer_links WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

$groupLabels = [
    'quick_links' => 'Quick Links',
    'compliances' => 'Compliances',
    'legal_disclaimers' => 'Legal Disclaimers',
];

$groupBadges = [
    'quick_links' => 'bg-info text-dark',
    'compliances' => 'bg-success',
    'legal_disclaimers' => 'bg-secondary',
];

$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- Tab Navigation Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <ul class="nav nav-pills admin-nav-pills">
    <li class="nav-item">
      <a class="nav-link <?= $currentGroup === 'all' ? 'active' : '' ?>" href="footer-links.php?group=all">
        All Links <span class="badge bg-light text-dark ms-1"><?= $counts['all'] ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentGroup === 'quick_links' ? 'active' : '' ?>" href="footer-links.php?group=quick_links">
        Quick Links <span class="badge bg-light text-dark ms-1"><?= $counts['quick_links'] ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentGroup === 'compliances' ? 'active' : '' ?>" href="footer-links.php?group=compliances">
        Compliances <span class="badge bg-light text-dark ms-1"><?= $counts['compliances'] ?></span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $currentGroup === 'legal_disclaimers' ? 'active' : '' ?>" href="footer-links.php?group=legal_disclaimers">
        Legal Disclaimers <span class="badge bg-light text-dark ms-1"><?= $counts['legal_disclaimers'] ?></span>
      </a>
    </li>
  </ul>

  <div class="d-flex gap-2">
    <?php if (!$isAdding && $editId === 0): ?>
      <a href="footer-links.php?group=<?= urlencode($currentGroup) ?>&action=add" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i> Add Footer Link
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Footer Link' : 'Add New Footer Link' ?>
    </h5>
    <a href="footer-links.php?group=<?= urlencode($currentGroup) ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-x-lg"></i> Cancel
    </a>
  </div>
  <div class="card-body p-4">
    <form method="post" action="footer-links.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_link">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="current_group" value="<?= e($currentGroup) ?>">

      <div class="row g-3">
        <!-- Group Key Selection -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Footer Column Group <span class="text-danger">*</span></label>
          <select name="group_key" class="form-select" required>
            <option value="quick_links" <?= $formData['group_key'] === 'quick_links' ? 'selected' : '' ?>>Quick Links</option>
            <option value="compliances" <?= $formData['group_key'] === 'compliances' ? 'selected' : '' ?>>Compliances</option>
            <option value="legal_disclaimers" <?= $formData['group_key'] === 'legal_disclaimers' ? 'selected' : '' ?>>Legal Disclaimers</option>
          </select>
          <div class="form-text small">Determines which of the 3 columns this link appears in.</div>
        </div>

        <!-- Label -->
        <div class="col-md-5">
          <label class="form-label fw-semibold">Link Label / Text <span class="text-danger">*</span></label>
          <input type="text" name="label" class="form-control" value="<?= e($formData['label']) ?>" placeholder="e.g. Skin Care, Privacy Policy, FDA Registered" required>
        </div>

        <!-- Sort Order -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <!-- URL -->
        <div class="col-md-8">
          <label class="form-label fw-semibold">Destination URL <span class="text-danger">*</span></label>
          <input type="text" name="url" class="form-control" value="<?= e($formData['url']) ?>" placeholder="e.g. shop.php?category=skin-care or https://www.fda.gov/" required>
          <div class="form-text small">Supports relative paths (e.g. <code>privacy.php</code>) or external URLs (e.g. <code>https://...</code>).</div>
        </div>

        <!-- Status -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
            <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
          </select>
        </div>

        <!-- Open in new tab -->
        <div class="col-12">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="open_in_new_tab" id="openTabCheck" value="1" <?= !empty($formData['open_in_new_tab']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="openTabCheck">
              Open link in a new tab (<code>target="_blank"</code>)
            </label>
          </div>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Link</button>
        <a href="footer-links.php?group=<?= urlencode($currentGroup) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Links Table Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-link-45deg text-primary"></i>
      <?= $currentGroup === 'all' ? 'All Footer Links' : ($groupLabels[$currentGroup] ?? 'Footer Links') ?>
    </h5>
    <span class="text-muted small">Showing <?= count($links) ?> item(s)</span>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="footer-links.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="bulk_delete">
      <input type="hidden" name="current_group" value="<?= e($currentGroup) ?>">

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="ps-3">
                <input type="checkbox" id="selectAll" class="form-check-input">
              </th>
              <th style="width: 50px;">Order</th>
              <?php if ($currentGroup === 'all'): ?>
                <th style="width: 140px;">Group Column</th>
              <?php endif; ?>
              <th>Label</th>
              <th>Destination URL</th>
              <th style="width: 110px;">Target</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableLinks">
            <?php if (empty($links)): ?>
              <tr>
                <td colspan="<?= $currentGroup === 'all' ? 8 : 7 ?>" class="text-center py-5 text-muted">
                  <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                  No links found in this view. Click "Add Footer Link" above to create one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($links as $link): ?>
                <tr data-id="<?= (int) $link['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $link['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <?php if ($currentGroup === 'all'): ?>
                    <td>
                      <span class="badge <?= $groupBadges[$link['group_key']] ?? 'bg-secondary' ?>">
                        <?= $groupLabels[$link['group_key']] ?? e($link['group_key']) ?>
                      </span>
                    </td>
                  <?php endif; ?>
                  <td>
                    <strong class="text-dark"><?= e($link['label']) ?></strong>
                  </td>
                  <td>
                    <a href="<?= e(url($link['url'])) ?>" target="_blank" class="text-decoration-none text-break font-monospace small text-primary">
                      <?= e($link['url']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                  </td>
                  <td>
                    <?php if (!empty($link['open_in_new_tab'])): ?>
                      <span class="badge bg-light text-dark border" title="Opens in new tab"><i class="bi bi-window-plus me-1"></i> New Tab</span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">Same Tab</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="post" action="footer-links.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                      <input type="hidden" name="current_group" value="<?= e($currentGroup) ?>">
                      <button type="submit" class="btn btn-sm <?= $link['status'] === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= ucfirst($link['status']) ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="footer-links.php?group=<?= urlencode($currentGroup) ?>&edit=<?= (int) $link['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="footer-links.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this footer link?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                      <input type="hidden" name="current_group" value="<?= e($currentGroup) ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($links)): ?>
        <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Are you sure you want to delete the selected footer links?');">
            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
          </button>
          <span class="text-muted small">
            <i class="bi bi-arrows-move me-1"></i> Drag rows to instantly reorder within the column.
          </span>
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
  const selectedCount = document.getElementById('selectedCount');

  function updateBulkBtn() {
    const checked = document.querySelectorAll('.row-select:checked').length;
    if (bulkDeleteBtn) bulkDeleteBtn.disabled = (checked === 0);
    if (selectedCount) selectedCount.textContent = checked;
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

  const tbody = document.getElementById('sortableLinks');
  if (tbody && typeof Sortable !== 'undefined') {
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const order = Array.from(rows).map(row => parseInt(row.getAttribute('data-id'), 10));

        fetch('api/footer-reorder.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?= e(csrf_token()) ?>'
          },
          body: JSON.stringify({
            csrf_token: '<?= e(csrf_token()) ?>',
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
