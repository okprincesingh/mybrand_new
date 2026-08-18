<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Navbar — Menu Management';
$pdo = db();

if ($pdo) {
    cms_ensure_navbar_tables($pdo);
}

// Handle Form & POST Actions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Menu Item (Add or Edit)
    if ($action === 'save_item') {
        $id = (int) ($_POST['id'] ?? 0);
        $parentIdRaw = trim((string) ($_POST['parent_id'] ?? ''));
        $parentId = ($parentIdRaw !== '' && is_numeric($parentIdRaw) && (int)$parentIdRaw > 0) ? (int)$parentIdRaw : null;
        $label = trim((string) ($_POST['label'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $openInNewTab = !empty($_POST['open_in_new_tab']) ? 1 : 0;
        $hasDropdown = !empty($_POST['has_dropdown']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

        if ($label === '') {
            admin_flash('danger', 'Menu item Label is required.');
            header('Location: navbar-management.php');
            exit;
        }

        if ($url === '') {
            $url = '#';
        }

        // Prevent setting self as parent
        if ($id > 0 && $parentId !== null && $id === $parentId) {
            $parentId = null;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('
                UPDATE nav_menu_items 
                SET parent_id = :parent_id, 
                    label = :label, 
                    url = :url, 
                    open_in_new_tab = :open_in_new_tab, 
                    has_dropdown = :has_dropdown, 
                    sort_order = :sort_order, 
                    status = :status, 
                    updated_at = NOW() 
                WHERE id = :id
            ');
            $stmt->execute([
                ':parent_id' => $parentId,
                ':label' => $label,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':has_dropdown' => $hasDropdown,
                ':sort_order' => $sortOrder,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash('success', 'Navbar item "' . htmlspecialchars($label) . '" updated successfully.');
        } else {
            if ($sortOrder === 0) {
                if ($parentId === null) {
                    $maxOrder = (int) db_fetch_value($pdo, 'SELECT MAX(sort_order) FROM nav_menu_items WHERE parent_id IS NULL');
                } else {
                    $maxOrder = (int) db_fetch_value($pdo, 'SELECT MAX(sort_order) FROM nav_menu_items WHERE parent_id = :pid', [':pid' => $parentId]);
                }
                $sortOrder = $maxOrder + 1;
            }

            $stmt = $pdo->prepare('
                INSERT INTO nav_menu_items (parent_id, label, url, open_in_new_tab, has_dropdown, sort_order, status) 
                VALUES (:parent_id, :label, :url, :open_in_new_tab, :has_dropdown, :sort_order, :status)
            ');
            $stmt->execute([
                ':parent_id' => $parentId,
                ':label' => $label,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':has_dropdown' => $hasDropdown,
                ':sort_order' => $sortOrder,
                ':status' => $status,
            ]);
            admin_flash('success', 'New navbar item "' . htmlspecialchars($label) . '" added successfully.');
        }

        cms_invalidate_nav_cache();
        header('Location: navbar-management.php');
        exit;
    }

    // 2. Toggle Status (Active / Inactive)
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $currentStatus = db_fetch_value($pdo, 'SELECT status FROM nav_menu_items WHERE id = :id', [':id' => $id]);
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            $stmt = $pdo->prepare('UPDATE nav_menu_items SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $id]);
            cms_invalidate_nav_cache();
            admin_flash('success', 'Menu item status changed to ' . ucfirst($newStatus) . '.');
        }
        header('Location: navbar-management.php');
        exit;
    }

    // 3. Move Item Order (Up / Down)
    if ($action === 'move_order') {
        $id = (int) ($_POST['id'] ?? 0);
        $direction = (string) ($_POST['direction'] ?? ''); // 'up' or 'down'

        if ($id > 0 && ($direction === 'up' || $direction === 'down')) {
            $item = cms_get_nav_menu_item($id);
            if ($item) {
                $pid = $item['parent_id'];
                $currentOrder = (int) $item['sort_order'];

                if ($pid === null) {
                    $siblings = $pdo->query("SELECT id, sort_order FROM nav_menu_items WHERE parent_id IS NULL ORDER BY sort_order ASC, id ASC")->fetchAll();
                } else {
                    $stmt = $pdo->prepare("SELECT id, sort_order FROM nav_menu_items WHERE parent_id = :pid ORDER BY sort_order ASC, id ASC");
                    $stmt->execute([':pid' => $pid]);
                    $siblings = $stmt->fetchAll();
                }

                $currentIndex = -1;
                foreach ($siblings as $idx => $sib) {
                    if ((int)$sib['id'] === $id) {
                        $currentIndex = $idx;
                        break;
                    }
                }

                if ($direction === 'up' && $currentIndex > 0) {
                    $prev = $siblings[$currentIndex - 1];
                    $pdo->prepare('UPDATE nav_menu_items SET sort_order = :so WHERE id = :id')->execute([':so' => (int)$prev['sort_order'], ':id' => $id]);
                    $pdo->prepare('UPDATE nav_menu_items SET sort_order = :so WHERE id = :id')->execute([':so' => $currentOrder, ':id' => (int)$prev['id']]);
                    cms_invalidate_nav_cache();
                    admin_flash('success', 'Item order updated.');
                } elseif ($direction === 'down' && $currentIndex >= 0 && $currentIndex < count($siblings) - 1) {
                    $next = $siblings[$currentIndex + 1];
                    $pdo->prepare('UPDATE nav_menu_items SET sort_order = :so WHERE id = :id')->execute([':so' => (int)$next['sort_order'], ':id' => $id]);
                    $pdo->prepare('UPDATE nav_menu_items SET sort_order = :so WHERE id = :id')->execute([':so' => $currentOrder, ':id' => (int)$next['id']]);
                    cms_invalidate_nav_cache();
                    admin_flash('success', 'Item order updated.');
                }
            }
        }
        header('Location: navbar-management.php');
        exit;
    }

    // 4. Save Quick Reordering Matrix (Drag & Drop or Manual Sort)
    if ($action === 'reorder_tree') {
        $orderDataRaw = $_POST['order_data'] ?? '';
        $orderData = json_decode($orderDataRaw, true);
        if (is_array($orderData)) {
            $stmt = $pdo->prepare('UPDATE nav_menu_items SET sort_order = :sort_order, parent_id = :parent_id, updated_at = NOW() WHERE id = :id');
            foreach ($orderData as $item) {
                $itemId = (int) ($item['id'] ?? 0);
                $sort = (int) ($item['sort_order'] ?? 0);
                $pid = !empty($item['parent_id']) && is_numeric($item['parent_id']) ? (int) $item['parent_id'] : null;
                if ($itemId > 0) {
                    $stmt->execute([
                        ':sort_order' => $sort,
                        ':parent_id' => $pid,
                        ':id' => $itemId,
                    ]);
                }
            }
            cms_invalidate_nav_cache();
            admin_flash('success', 'Navbar menu order updated successfully.');
        }
        header('Location: navbar-management.php');
        exit;
    }

    // 5. Delete Single Item
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM nav_menu_items WHERE id = :id');
            $stmt->execute([':id' => $id]);
            cms_invalidate_nav_cache();
            admin_flash('success', 'Navbar menu item and any associated dropdown children deleted successfully.');
        }
        header('Location: navbar-management.php');
        exit;
    }

    // 6. Bulk Multi-Select Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM nav_menu_items WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_nav_cache();
            admin_flash('success', count($ids) . ' navbar menu item(s) deleted successfully.');
        } else {
            admin_flash('warning', 'No items selected for bulk deletion.');
        }
        header('Location: navbar-management.php');
        exit;
    }
}

// Fetch Full Menu Tree (including Inactive items for admin view)
$menuTree = cms_get_nav_menu_tree(true, true);
$parentOptions = cms_get_nav_parent_options();

// Total counts
$totalItems = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM nav_menu_items');
$totalTopLevel = count($menuTree);
$totalActive = (int) db_fetch_value($pdo, "SELECT COUNT(*) FROM nav_menu_items WHERE status = 'active'");

include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4 mb-4">
  <!-- Stats Cards -->
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="bg-primary-subtle text-primary p-3 rounded-circle fs-3">
          <i class="bi bi-list-nested"></i>
        </div>
        <div>
          <h3 class="mb-0 fw-bold"><?= (int) $totalItems ?></h3>
          <span class="text-muted small">Total Menu Items</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="bg-info-subtle text-info p-3 rounded-circle fs-3">
          <i class="bi bi-diagram-2"></i>
        </div>
        <div>
          <h3 class="mb-0 fw-bold"><?= (int) $totalTopLevel ?></h3>
          <span class="text-muted small">Top-Level Navbar Items</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="bg-success-subtle text-success p-3 rounded-circle fs-3">
          <i class="bi bi-check-circle"></i>
        </div>
        <div>
          <h3 class="mb-0 fw-bold"><?= (int) $totalActive ?></h3>
          <span class="text-muted small">Active Live Items</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Management Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="card-title mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-menu-button-wide text-primary"></i> Main Navigation Structure
      </h5>
      <p class="text-muted small mb-0">Organize top-level links, dropdown menus, target URLs, and display order.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal" onclick="prepareAddModal(null)">
        <i class="bi bi-plus-lg me-1"></i> Add Top-Level Item
      </button>
      <a href="navbar-logo.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-image me-1"></i> Navbar Logo
      </a>
      <a href="<?= e(url('index.php')) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i> Preview Site
      </a>
    </div>
  </div>

  <div class="card-body p-0">
    <!-- Bulk Actions Toolbar (shown when checkboxes are selected) -->
    <form id="bulkActionForm" method="post" action="navbar-management.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="bulk_delete">

      <div id="bulkToolbar" class="p-3 bg-warning-subtle border-bottom d-none justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-warning text-dark fs-6" id="selectedCountBadge">0</span>
          <span class="fw-semibold">item(s) selected</span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-danger btn-sm" onclick="confirmBulkDelete()">
            <i class="bi bi-trash me-1"></i> Delete Selected
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllSelections()">
            Cancel
          </button>
        </div>
      </div>

      <!-- Menu Items Tree Table / Nested List -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="navbarTable">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;" class="text-center">
                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" title="Select All Items">
              </th>
              <th style="width: 50px;">Order</th>
              <th>Menu Item / Label</th>
              <th>URL / Route</th>
              <th style="width: 110px;" class="text-center">Type</th>
              <th style="width: 100px;" class="text-center">Target</th>
              <th style="width: 100px;" class="text-center">Status</th>
              <th style="width: 190px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($menuTree)): ?>
              <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                  No navbar menu items found. Click "Add Top-Level Item" to create your first navigation link.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($menuTree as $topIndex => $topItem): ?>
                <?php
                  $hasChildren = !empty($topItem['children']);
                  $childrenCount = count($topItem['children']);
                  $isTopActive = ($topItem['status'] === 'active');
                ?>
                <!-- TOP-LEVEL ITEM ROW -->
                <tr class="table-light-row border-top <?= $isTopActive ? '' : 'opacity-75 bg-light' ?>" data-item-id="<?= (int)$topItem['id'] ?>" data-parent-id="">
                  <td class="text-center">
                    <input type="checkbox" name="ids[]" value="<?= (int)$topItem['id'] ?>" class="form-check-input item-checkbox" onchange="updateBulkToolbar()">
                  </td>
                  <td>
                    <div class="btn-group-vertical btn-group-sm">
                      <button type="button" class="btn btn-sm btn-link p-0 text-secondary" title="Move Up" onclick="submitMoveOrder(<?= (int)$topItem['id'] ?>, 'up')">
                        <i class="bi bi-chevron-up"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-link p-0 text-secondary" title="Move Down" onclick="submitMoveOrder(<?= (int)$topItem['id'] ?>, 'down')">
                        <i class="bi bi-chevron-down"></i>
                      </button>
                    </div>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if ($hasChildren): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary p-0 px-1 toggle-children-btn" data-target="children-<?= (int)$topItem['id'] ?>" title="Toggle Dropdown List">
                          <i class="bi bi-chevron-down toggle-icon"></i>
                        </button>
                      <?php else: ?>
                        <span class="p-1 px-2 text-muted" style="width: 24px;">•</span>
                      <?php endif; ?>
                      <strong class="fs-6"><?= e($topItem['label']) ?></strong>
                      <?php if ($hasChildren): ?>
                        <span class="badge bg-primary-subtle text-primary rounded-pill"><?= $childrenCount ?> submenu items</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <code class="text-break"><?= e($topItem['url']) ?></code>
                  </td>
                  <td class="text-center">
                    <?php if ($hasChildren || !empty($topItem['has_dropdown'])): ?>
                      <span class="badge bg-info-subtle text-info border border-info-subtle">Dropdown</span>
                    <?php else: ?>
                      <span class="badge bg-light text-secondary border">Direct Link</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if (!empty($topItem['open_in_new_tab'])): ?>
                      <span class="badge bg-secondary-subtle text-secondary" title="Opens in new tab"><i class="bi bi-box-arrow-up-right me-1"></i> New Tab</span>
                    <?php else: ?>
                      <span class="text-muted small">Same Tab</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <form method="post" action="navbar-management.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= (int)$topItem['id'] ?>">
                      <button type="submit" class="btn btn-sm rounded-pill px-2 py-0 border-0 <?= $isTopActive ? 'btn-success' : 'btn-secondary' ?>" title="Click to toggle status">
                        <small><?= $isTopActive ? 'Active' : 'Inactive' ?></small>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary" title="Add Submenu Child" onclick="prepareAddModal(<?= (int)$topItem['id'] ?>)">
                        <i class="bi bi-plus-lg"></i> Child
                      </button>
                      <button type="button" class="btn btn-outline-secondary" title="Edit Item" onclick='prepareEditModal(<?= json_encode($topItem, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button type="button" class="btn btn-outline-danger" title="Delete Item" onclick="confirmDelete(<?= (int)$topItem['id'] ?>, '<?= e(addslashes($topItem['label'])) ?>', <?= $hasChildren ? 'true' : 'false' ?>)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- CHILD ROWS (DROPDOWN SUBMENU ITEMS) -->
                <?php if ($hasChildren): ?>
                  <tbody id="children-<?= (int)$topItem['id'] ?>" class="children-group">
                    <?php foreach ($topItem['children'] as $childIndex => $childItem): ?>
                      <?php $isChildActive = ($childItem['status'] === 'active'); ?>
                      <tr class="bg-light-subtle <?= $isChildActive ? '' : 'opacity-75' ?>" data-item-id="<?= (int)$childItem['id'] ?>" data-parent-id="<?= (int)$topItem['id'] ?>">
                        <td class="text-center">
                          <input type="checkbox" name="ids[]" value="<?= (int)$childItem['id'] ?>" class="form-check-input item-checkbox" onchange="updateBulkToolbar()">
                        </td>
                        <td class="ps-3">
                          <div class="btn-group-vertical btn-group-sm">
                            <button type="button" class="btn btn-sm btn-link p-0 text-secondary" title="Move Up" onclick="submitMoveOrder(<?= (int)$childItem['id'] ?>, 'up')">
                              <i class="bi bi-chevron-up"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-link p-0 text-secondary" title="Move Down" onclick="submitMoveOrder(<?= (int)$childItem['id'] ?>, 'down')">
                              <i class="bi bi-chevron-down"></i>
                            </button>
                          </div>
                        </td>
                        <td class="ps-4">
                          <div class="d-flex align-items-center gap-2">
                            <span class="text-muted"><i class="bi bi-arrow-return-right"></i></span>
                            <span><?= e($childItem['label']) ?></span>
                          </div>
                        </td>
                        <td>
                          <code class="text-muted text-break"><?= e($childItem['url']) ?></code>
                        </td>
                        <td class="text-center">
                          <span class="badge bg-white text-muted border">Submenu</span>
                        </td>
                        <td class="text-center">
                          <?php if (!empty($childItem['open_in_new_tab'])): ?>
                            <span class="badge bg-secondary-subtle text-secondary" title="Opens in new tab"><i class="bi bi-box-arrow-up-right me-1"></i> New Tab</span>
                          <?php else: ?>
                            <span class="text-muted small">Same Tab</span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <form method="post" action="navbar-management.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= (int)$childItem['id'] ?>">
                            <button type="submit" class="btn btn-sm rounded-pill px-2 py-0 border-0 <?= $isChildActive ? 'btn-success' : 'btn-secondary' ?>" title="Click to toggle status">
                              <small><?= $isChildActive ? 'Active' : 'Inactive' ?></small>
                            </button>
                          </form>
                        </td>
                        <td class="text-end pe-3">
                          <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" title="Edit Child" onclick='prepareEditModal(<?= json_encode($childItem, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                              <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" title="Delete Child" onclick="confirmDelete(<?= (int)$childItem['id'] ?>, '<?= e(addslashes($childItem['label'])) ?>', false)">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add / Edit Menu Item -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="navbar-management.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_item">
        <input type="hidden" name="id" id="modalItemId" value="0">

        <div class="modal-header bg-light">
          <h5 class="modal-title" id="itemModalLabel">Add Menu Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Parent Selection -->
          <div class="mb-3">
            <label for="modalParentId" class="form-label fw-semibold">Menu Level / Parent Item</label>
            <select name="parent_id" id="modalParentId" class="form-select">
              <option value="">Top-Level Navbar Item (No Parent)</option>
              <?php foreach ($parentOptions as $parent): ?>
                <option value="<?= (int)$parent['id'] ?>"><?= e($parent['label']) ?> (Dropdown Parent)</option>
              <?php endforeach; ?>
            </select>
            <div class="form-text small">Select "Top-Level" for main menu tabs, or choose a parent item to place inside a dropdown submenu.</div>
          </div>

          <!-- Label -->
          <div class="mb-3">
            <label for="modalLabel" class="form-label fw-semibold">Item Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="modalLabel" class="form-control" placeholder="e.g. Skin Care, How It Works, Certificates" required>
          </div>

          <!-- URL -->
          <div class="mb-3">
            <label for="modalUrl" class="form-label fw-semibold">Target URL / Route <span class="text-danger">*</span></label>
            <input type="text" name="url" id="modalUrl" class="form-control" placeholder="e.g. shop.php, about.php#who-we-are, https://example.com" required>
            <div class="form-text small">Relative URL (e.g. <code>about.php</code>) or full external URL.</div>
          </div>

          <!-- Sort Order & Status -->
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label for="modalSortOrder" class="form-label fw-semibold">Sort Order</label>
              <input type="number" name="sort_order" id="modalSortOrder" class="form-control" value="0" min="0">
              <div class="form-text small">Lower numbers appear first.</div>
            </div>
            <div class="col-6">
              <label for="modalStatus" class="form-label fw-semibold">Status</label>
              <select name="status" id="modalStatus" class="form-select">
                <option value="active">Active (Visible)</option>
                <option value="inactive">Inactive (Hidden)</option>
              </select>
            </div>
          </div>

          <!-- Toggles -->
          <div class="mb-3 p-3 bg-light rounded-3 border">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="has_dropdown" id="modalHasDropdown" value="1">
              <label class="form-check-label fw-semibold" for="modalHasDropdown">Enable Dropdown Menu / Chevron</label>
              <div class="form-text small">Forces the dropdown chevron to show even before children are added.</div>
            </div>
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" name="open_in_new_tab" id="modalOpenNewTab" value="1">
              <label class="form-check-label fw-semibold" for="modalOpenNewTab">Open in New Tab (<code>target="_blank"</code>)</label>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Confirm Single Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="post" action="navbar-management.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteItemId" value="0">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <p class="mb-2">Are you sure you want to delete <strong id="deleteItemLabel">this item</strong>?</p>
          <div id="deleteWarningAlert" class="alert alert-warning d-none mb-0">
            <i class="bi bi-exclamation-circle me-1"></i> <strong>Warning:</strong> This is a top-level item with submenus. Deleting it will also remove all its child links.
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Confirm Bulk Delete -->
<div class="modal fade" id="bulkDeleteConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Bulk Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="mb-0">Are you sure you want to permanently delete the <strong id="bulkDeleteCountText">0</strong> selected menu item(s)?</p>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="document.getElementById('bulkActionForm').submit()"><i class="bi bi-trash me-1"></i> Delete Selected</button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden Order Move Form -->
<form id="orderMoveForm" method="post" action="navbar-management.php" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="move_order">
  <input type="hidden" name="id" id="moveOrderId" value="0">
  <input type="hidden" name="direction" id="moveOrderDir" value="">
</form>

<script>
function prepareAddModal(parentId) {
  document.getElementById('itemModalLabel').textContent = parentId ? 'Add Submenu Child Item' : 'Add Top-Level Navbar Item';
  document.getElementById('modalItemId').value = '0';
  document.getElementById('modalParentId').value = parentId ? String(parentId) : '';
  document.getElementById('modalLabel').value = '';
  document.getElementById('modalUrl').value = '';
  document.getElementById('modalSortOrder').value = '0';
  document.getElementById('modalStatus').value = 'active';
  document.getElementById('modalHasDropdown').checked = false;
  document.getElementById('modalOpenNewTab').checked = false;
  
  const modal = new bootstrap.Modal(document.getElementById('itemModal'));
  modal.show();
}

function prepareEditModal(item) {
  document.getElementById('itemModalLabel').textContent = 'Edit Menu Item: ' + item.label;
  document.getElementById('modalItemId').value = item.id;
  document.getElementById('modalParentId').value = item.parent_id ? String(item.parent_id) : '';
  document.getElementById('modalLabel').value = item.label || '';
  document.getElementById('modalUrl').value = item.url || '';
  document.getElementById('modalSortOrder').value = item.sort_order || 0;
  document.getElementById('modalStatus').value = item.status || 'active';
  document.getElementById('modalHasDropdown').checked = Boolean(item.has_dropdown);
  document.getElementById('modalOpenNewTab').checked = Boolean(item.open_in_new_tab);
  
  const modal = new bootstrap.Modal(document.getElementById('itemModal'));
  modal.show();
}

function confirmDelete(id, label, hasChildren) {
  document.getElementById('deleteItemId').value = id;
  document.getElementById('deleteItemLabel').textContent = '"' + label + '"';
  const warning = document.getElementById('deleteWarningAlert');
  if (hasChildren) {
    warning.classList.remove('d-none');
  } else {
    warning.classList.add('d-none');
  }
  const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
  modal.show();
}

function submitMoveOrder(id, direction) {
  document.getElementById('moveOrderId').value = id;
  document.getElementById('moveOrderDir').value = direction;
  document.getElementById('orderMoveForm').submit();
}

// Bulk Selection Handlers
function updateBulkToolbar() {
  const checkboxes = document.querySelectorAll('.item-checkbox:checked');
  const count = checkboxes.length;
  const toolbar = document.getElementById('bulkToolbar');
  const countBadge = document.getElementById('selectedCountBadge');
  
  if (count > 0) {
    toolbar.classList.remove('d-none');
    toolbar.classList.add('d-flex');
    countBadge.textContent = count;
  } else {
    toolbar.classList.add('d-none');
    toolbar.classList.remove('d-flex');
  }
}

function clearAllSelections() {
  document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = false);
  document.getElementById('selectAllCheckbox').checked = false;
  updateBulkToolbar();
}

function confirmBulkDelete() {
  const count = document.querySelectorAll('.item-checkbox:checked').length;
  document.getElementById('bulkDeleteCountText').textContent = count;
  const modal = new bootstrap.Modal(document.getElementById('bulkDeleteConfirmModal'));
  modal.show();
}

document.getElementById('selectAllCheckbox')?.addEventListener('change', function() {
  const isChecked = this.checked;
  document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = isChecked);
  updateBulkToolbar();
});

// Dropdown Collapse Toggles
document.querySelectorAll('.toggle-children-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const targetId = this.getAttribute('data-target');
    const targetGroup = document.getElementById(targetId);
    const icon = this.querySelector('.toggle-icon');
    if (targetGroup) {
      if (targetGroup.classList.contains('d-none')) {
        targetGroup.classList.remove('d-none');
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
      } else {
        targetGroup.classList.add('d-none');
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
      }
    }
  });
});
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
