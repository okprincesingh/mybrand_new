<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Navbar Management';
$pdo = db();

function build_menu_tree(array $rows): array
{
    $tree = [];
    $itemsById = [];

    foreach ($rows as $row) {
        $id = (string) ($row['id'] ?? '');
        $parentId = $row['parent_id'] !== null ? (string) $row['parent_id'] : null;
        $itemsById[$id] = [
            'id' => $id,
            'parent_id' => $parentId,
            'title' => (string) $row['title'],
            'url' => (string) $row['url'],
            'sort_order' => (int) $row['sort_order'],
            'is_active' => (int) $row['is_active'],
            'children' => [],
        ];
    }

    foreach ($itemsById as $id => &$item) {
        if ($item['parent_id'] !== null && isset($itemsById[$item['parent_id']])) {
            $itemsById[$item['parent_id']]['children'][] = &$item;
        } else {
            $tree[] = &$item;
        }
    }
    unset($item);

    usort($tree, static fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order'] ?: strcmp($a['id'], $b['id']));

    $sortChildren = function (array &$items) use (&$sortChildren): void {
        usort($items, static fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order'] ?: strcmp($a['id'], $b['id']));
        foreach ($items as &$item) {
            if (!empty($item['children'])) {
                $sortChildren($item['children']);
            }
        }
        unset($item);
    };
    $sortChildren($tree);

    return $tree;
}

function assign_temp_menu_ids(array $items, int &$counter = 1, ?string $parentId = null): array
{
    $result = [];
    foreach ($items as $index => $item) {
        $tempId = 'temp-' . $counter++;
        $result[] = [
            'id' => $tempId,
            'parent_id' => $parentId,
            'title' => (string) ($item['title'] ?? ''),
            'url' => (string) ($item['url'] ?? ''),
            'sort_order' => $index,
            'is_active' => 1,
            'children' => assign_temp_menu_ids($item['children'] ?? [], $counter, $tempId),
        ];
    }
    return $result;
}

$rawRows = $pdo ? cms_get_menu_items('header_main', true) : [];
$menuItems = [];
$menuExists = cms_has_menu_rows('header_main');
$usesFallbackDisplay = false;

if ($menuExists) {
    $menuItems = cms_build_header_menu_editor_model($rawRows, cms_build_default_header_menu());
} elseif (!$menuExists) {
    $menuItems = assign_temp_menu_ids(cms_build_default_header_menu());
    $usesFallbackDisplay = true;
}

include __DIR__ . '/_layout_top.php';
?>
<style>
.navbar-management-info {
  margin-bottom: 1rem;
}
.navbar-management-list {
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 0.75rem;
  overflow: hidden;
}
.navbar-management-list .header-row,
.navbar-management-list .navbar-item-row {
  display: grid;
  grid-template-columns: 48px minmax(220px, 1.8fr) minmax(220px, 2.4fr) 140px 120px 1fr;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
}
.navbar-management-list .header-row {
  background: #f8f9ff;
  font-weight: 600;
  color: #1f2937;
}
.navbar-management-list .navbar-item-row {
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  background: #ffffff;
}
.navbar-management-list .navbar-item-row.dragging {
  opacity: 0.5;
}
.navbar-management-list .navbar-item-row[data-level="1"] {
  padding-left: 2rem;
}
.navbar-management-list .navbar-item-row[data-level="2"] {
  padding-left: 3.5rem;
}
.navbar-management-list .drag-handle {
  cursor: grab;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  font-size: 1.1rem;
  color: #6b7280;
}
.navbar-management-list .title-cell .form-control,
.navbar-management-list .status-cell .form-check-input,
.navbar-management-list .url-cell a {
  width: 100%;
}
.navbar-management-list .url-cell a {
  color: #0d6efd;
  text-decoration: none;
  word-break: break-all;
}
.navbar-management-list .url-cell a:hover {
  text-decoration: underline;
}
.navbar-management-children {
  padding-left: 0.5rem;
}
.navbar-management-child-section {
  border-left: 2px solid rgba(13, 110, 253, 0.14);
  margin: 0 0 0 0.5rem;
}
.navbar-management-children.collapsed {
  display: none;
}
.navbar-management-save-row {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}
.navbar-management-save-row .btn {
  white-space: nowrap;
}
</style>
<div class="navbar-management-save-row">
  <div>
    <h2 class="admin-title" style="margin-bottom:.25rem;">Navbar Management</h2>
    <p class="text-muted mb-0">Manage the website navbar structure, labels, visibility, and order using the existing header menu configuration.</p>
  </div>
  <div class="d-flex gap-2">
    <button id="addNavbarItemButton" type="button" class="btn btn-outline-success">Add Item</button>
    <button id="saveNavbarButton" class="btn btn-primary">Save Changes</button>
    <button id="refreshNavbarButton" type="button" class="btn btn-outline-secondary">Refresh</button>
  </div>
</div>
<?php if ($usesFallbackDisplay): ?>
  <div class="alert alert-warning navbar-management-info">
    No existing navbar menu configuration was found in the database. The preview below is generated from the site's current default header menu. Saving will create the <code>header_main</code> menu entries in the database without changing existing page URLs or routes.
  </div>
<?php endif; ?>
<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">Header Menu Items</h5>
  </div>
  <div class="widget-body">
    <div class="navbar-management-list" id="navbarManagementList">
      <div class="header-row">
        <span>Order</span>
        <span>Heading</span>
        <span>URL</span>
        <span>Dropdown</span>
        <span>Status</span>
        <span>Actions</span>
      </div>
      <div id="navbarItemsContainer"></div>
    </div>
  </div>
</div>
<script>
const initialMenuItems = <?php echo json_encode($menuItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const csrfToken = '<?php echo e(csrf_token()); ?>';
let menuState = JSON.parse(JSON.stringify(initialMenuItems));
let dragSourceId = null;
let dragSourceParent = null;
const expandedSectionIds = new Set();

function escapeHtml(text) {
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function getExpandedSectionIds() {
  const expanded = new Set();
  document.querySelectorAll('.navbar-management-children:not(.collapsed)').forEach(panel => {
    const parentId = panel.getAttribute('data-parent-id');
    if (parentId) {
      expanded.add(parentId);
    }
  });
  return expanded;
}

function renderMenu() {
  const container = document.getElementById('navbarItemsContainer');
  const currentExpanded = getExpandedSectionIds();
  expandedSectionIds.clear();
  currentExpanded.forEach(id => expandedSectionIds.add(id));
  container.innerHTML = menuState.map(item => renderItemRow(item, 0)).join('');
  attachRowEvents();
}

function renderItemRow(item, level) {
  const hasChildren = Array.isArray(item.children) && item.children.length > 0;
  const levelClass = `data-level="${level}"`;
  const isExpanded = expandedSectionIds.has(String(item.id));
  const childrenClass = hasChildren ? `navbar-management-children${isExpanded ? '' : ' collapsed'}` : '';
  const toggleLabel = hasChildren
    ? (isExpanded ? 'Hide' : `Manage (${item.children.length})`)
    : 'Add child';

  return `
    <div class="navbar-item-row" ${levelClass} data-item-id="${escapeHtml(item.id)}" data-parent-id="${escapeHtml(item.parent_id ?? '')}" draggable="true">
      <div class="drag-handle" aria-label="Drag to reorder">☰</div>
      <div class="title-cell">
        <input type="text" class="form-control form-control-sm title-input" data-item-id="${escapeHtml(item.id)}" value="${escapeHtml(item.title)}" aria-label="Heading label">
      </div>
      <div class="url-cell">
        <input type="text" class="form-control form-control-sm url-input" data-item-id="${escapeHtml(item.id)}" value="${escapeHtml(item.url)}" aria-label="Item URL">
      </div>
      <div class="dropdown-cell">
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle-button" data-item-id="${escapeHtml(item.id)}">${toggleLabel}</button>
      </div>
      <div class="status-cell">
        <div class="form-check form-switch">
          <input class="form-check-input status-toggle" type="checkbox" data-item-id="${escapeHtml(item.id)}" ${item.is_active ? 'checked' : ''}>
        </div>
      </div>
      <div class="actions-cell text-end">
        <div class="d-flex justify-content-end gap-1 flex-wrap">
          <button type="button" class="btn btn-sm btn-outline-success add-child-btn" data-item-id="${escapeHtml(item.id)}">Add child</button>
          <button type="button" class="btn btn-sm btn-outline-danger delete-item-btn" data-item-id="${escapeHtml(item.id)}">Delete</button>
        </div>
        <small class="text-muted d-block mt-1">ID ${escapeHtml(item.id)}</small>
      </div>
    </div>
    ${hasChildren ? `<div class="${childrenClass}" data-parent-id="${escapeHtml(item.id)}">${item.children.map(child => renderItemRow(child, level + 1)).join('')}</div>` : ''}
  `;
}

function attachRowEvents() {
  document.querySelectorAll('.title-input').forEach(input => {
    input.addEventListener('input', function () {
      const itemId = this.dataset.itemId;
      const item = findItemById(menuState, itemId);
      if (item) {
        item.title = this.value;
      }
    });
  });

  document.querySelectorAll('.status-toggle').forEach(toggle => {
    toggle.addEventListener('change', function () {
      const itemId = this.dataset.itemId;
      const item = findItemById(menuState, itemId);
      if (item) {
        item.is_active = this.checked ? 1 : 0;
      }
    });
  });

  document.querySelectorAll('.url-input').forEach(input => {
    input.addEventListener('input', function () {
      const itemId = this.dataset.itemId;
      const item = findItemById(menuState, itemId);
      if (item) {
        item.url = this.value;
      }
    });
  });

  document.querySelectorAll('.dropdown-toggle-button').forEach(button => {
    button.addEventListener('click', function () {
      const itemId = this.dataset.itemId;
      const childrenPanel = document.querySelector(`.navbar-management-children[data-parent-id="${CSS.escape(itemId)}"]`);
      if (childrenPanel) {
        const isCollapsed = childrenPanel.classList.toggle('collapsed');
        if (isCollapsed) {
          expandedSectionIds.delete(itemId);
          this.textContent = `Manage (${childrenPanel.querySelectorAll('.navbar-item-row').length})`;
        } else {
          expandedSectionIds.add(itemId);
          this.textContent = 'Hide';
        }
      }
    });
  });

  document.querySelectorAll('.add-child-btn').forEach(button => {
    button.addEventListener('click', function () {
      const itemId = this.dataset.itemId;
      addItem(itemId);
    });
  });

  document.querySelectorAll('.delete-item-btn').forEach(button => {
    button.addEventListener('click', function () {
      const itemId = this.dataset.itemId;
      if (confirm('Delete this item and its children?')) {
        deleteItemById(menuState, itemId);
        normalizeSortOrder(menuState);
        renderMenu();
      }
    });
  });

  document.querySelectorAll('.navbar-item-row').forEach(row => {
    row.addEventListener('dragstart', handleDragStart);
    row.addEventListener('dragover', handleDragOver);
    row.addEventListener('dragend', handleDragEnd);
    row.addEventListener('drop', handleDrop);
  });
}

function handleDragStart(event) {
  dragSourceId = event.currentTarget.dataset.itemId;
  dragSourceParent = event.currentTarget.dataset.parentId || null;
  event.dataTransfer.effectAllowed = 'move';
}

function handleDragOver(event) {
  event.preventDefault();
  const target = event.currentTarget;
  if (target.dataset.itemId === dragSourceId) {
    return;
  }
  if ((target.dataset.parentId || null) !== dragSourceParent) {
    return;
  }
  target.classList.add('drag-over');
  event.dataTransfer.dropEffect = 'move';
}

function handleDrop(event) {
  event.preventDefault();
  const target = event.currentTarget;
  target.classList.remove('drag-over');
  const targetId = target.dataset.itemId;
  const targetParent = target.dataset.parentId || null;
  if (!dragSourceId || targetId === dragSourceId || targetParent !== dragSourceParent) {
    return;
  }
  moveItemInSiblingList(dragSourceId, targetId, dragSourceParent);
  renderMenu();
}

function handleDragEnd(event) {
  document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
}

function moveItemInSiblingList(sourceId, targetId, parentId) {
  const siblings = parentId ? findItemById(menuState, parentId)?.children : menuState;
  if (!Array.isArray(siblings)) {
    return;
  }
  const sourceIndex = siblings.findIndex(item => item.id === sourceId);
  const targetIndex = siblings.findIndex(item => item.id === targetId);
  if (sourceIndex === -1 || targetIndex === -1) {
    return;
  }
  const [moved] = siblings.splice(sourceIndex, 1);
  siblings.splice(targetIndex, 0, moved);
  normalizeSortOrder(menuState);
}

function findItemById(items, itemId) {
  for (const item of items) {
    if (item.id === itemId) {
      return item;
    }
    if (item.children && item.children.length) {
      const found = findItemById(item.children, itemId);
      if (found) {
        return found;
      }
    }
  }
  return null;
}

function normalizeSortOrder(items) {
  items.forEach((item, index) => {
    item.sort_order = index;
    if (item.children && item.children.length) {
      normalizeSortOrder(item.children);
    }
  });
}

function deleteItemById(items, itemId) {
  for (let index = 0; index < items.length; index++) {
    if (items[index].id === itemId) {
      items.splice(index, 1);
      return true;
    }
    if (items[index].children && items[index].children.length) {
      if (deleteItemById(items[index].children, itemId)) {
        return true;
      }
    }
  }
  return false;
}

function generateTempId() {
  if (typeof window.navbarTempIdCounter === 'undefined') {
    window.navbarTempIdCounter = 1;
  }
  return 'temp-' + window.navbarTempIdCounter++;
}

function addItem(parentId = null) {
  const newItem = {
    id: generateTempId(),
    parent_id: parentId,
    title: 'New Menu',
    url: '#',
    sort_order: 0,
    is_active: 1,
    children: [],
  };

  if (parentId) {
    const parent = findItemById(menuState, parentId);
    if (parent) {
      parent.children.push(newItem);
    }
  } else {
    menuState.push(newItem);
  }

  normalizeSortOrder(menuState);
  renderMenu();
}

function collectState(items, parentId = null) {
  const data = [];
  items.forEach((item, index) => {
    const copy = {
      id: item.id,
      parent_id: parentId,
      title: item.title,
      url: item.url,
      sort_order: index,
      is_active: item.is_active ? 1 : 0,
    };
    if (Array.isArray(item.children) && item.children.length) {
      copy.children = collectState(item.children, item.id);
    } else {
      copy.children = [];
    }
    data.push(copy);
  });
  return data;
}

async function saveMenu() {
  normalizeSortOrder(menuState);
  const payload = {
    action: 'save_menu',
    items: collectState(menuState),
  };

  const response = await fetch('api/navbar-menu.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const text = await response.text();
    alert('Save failed. Please try again.\n' + text);
    return;
  }

  const body = await response.json();
  if (!body.success) {
    alert('Save failed: ' + (body.message || 'Unknown error'));
    return;
  }

  alert('Navbar configuration saved successfully.');
  renderMenu();
}

function refreshPage() {
  window.location.reload();
}

window.addEventListener('DOMContentLoaded', function () {
  renderMenu();
  document.getElementById('saveNavbarButton').addEventListener('click', saveMenu);
  document.getElementById('refreshNavbarButton').addEventListener('click', refreshPage);
});
</script>
