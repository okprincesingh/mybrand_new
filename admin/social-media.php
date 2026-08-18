<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Social Media Links';
$pdo = db();

if ($pdo) {
    cms_ensure_footer_and_social_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    // 1. Save Platform Link (Add or Edit)
    if ($action === 'save_social') {
        $id = (int) ($_POST['id'] ?? 0);
        $platform = trim((string) ($_POST['platform'] ?? ''));
        $iconClass = trim((string) ($_POST['icon_class'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $openInNewTab = !empty($_POST['open_in_new_tab']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $existingIconImage = trim((string) ($_POST['existing_icon_image'] ?? ''));

        if ($platform === '' || $url === '') {
            admin_flash('danger', 'Platform name and URL are required.');
            header('Location: social-media.php' . ($id > 0 ? '?edit=' . $id : '?action=add'));
            exit;
        }

        $iconImage = $existingIconImage;
        if (!empty($_FILES['icon_image']['name'])) {
            $stored = store_uploaded_image($_FILES['icon_image'], 'social', 5_000_000, false);
            if ($stored) {
                $iconImage = (string) $stored['public_path'];
            }
        }

        if ($iconClass === '' && $iconImage === '') {
            $iconClass = 'fa-solid fa-link';
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE social_media_links SET platform = :platform, icon_class = :icon_class, icon_image = :icon_image, url = :url, open_in_new_tab = :open_in_new_tab, sort_order = :sort_order, status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':platform' => $platform,
                ':icon_class' => $iconClass,
                ':icon_image' => $iconImage,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
                ':id' => $id,
            ]);
            admin_flash('success', 'Social media link updated successfully.');
        } else {
            if ($sortOrder === 0) {
                $maxOrder = (int) db_fetch_value($pdo, 'SELECT MAX(sort_order) FROM social_media_links');
                $sortOrder = $maxOrder + 1;
            }
            $stmt = $pdo->prepare('INSERT INTO social_media_links (platform, icon_class, icon_image, url, open_in_new_tab, sort_order, status) VALUES (:platform, :icon_class, :icon_image, :url, :open_in_new_tab, :sort_order, :status)');
            $stmt->execute([
                ':platform' => $platform,
                ':icon_class' => $iconClass,
                ':icon_image' => $iconImage,
                ':url' => $url,
                ':open_in_new_tab' => $openInNewTab,
                ':sort_order' => $sortOrder,
                ':status' => $status,
            ]);
            admin_flash('success', 'New social media link added successfully.');
        }

        cms_invalidate_social_cache();
        header('Location: social-media.php');
        exit;
    }

    // 2. Toggle Status (Active / Inactive)
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $currentStatus = db_fetch_value($pdo, 'SELECT status FROM social_media_links WHERE id = :id', [':id' => $id]);
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            db_execute($pdo, 'UPDATE social_media_links SET status = :status, updated_at = NOW() WHERE id = :id', [
                ':status' => $newStatus,
                ':id' => $id,
            ]);
            cms_invalidate_social_cache();
            admin_flash('success', 'Social link status updated to ' . ucfirst($newStatus) . '.');
        }
        header('Location: social-media.php');
        exit;
    }

    // 3. Delete Single Platform
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM social_media_links WHERE id = :id', [':id' => $id]);
            cms_invalidate_social_cache();
            admin_flash('success', 'Social media link deleted successfully.');
        }
        header('Location: social-media.php');
        exit;
    }

    // 4. Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $ids = array_filter($ids, fn($i) => $i > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM social_media_links WHERE id IN ({$placeholders})");
            $stmt->execute(array_values($ids));
            cms_invalidate_social_cache();
            admin_flash('success', count($ids) . ' social media links deleted successfully.');
        } else {
            admin_flash('warning', 'No items selected for deletion.');
        }
        header('Location: social-media.php');
        exit;
    }
}

// Fetch all social links
$socialLinks = $pdo ? db_fetch_all($pdo, 'SELECT * FROM social_media_links ORDER BY sort_order ASC, id ASC') : [];

// Form Mode (Edit or Add)
$editId = (int) ($_GET['edit'] ?? 0);
$isAdding = isset($_GET['action']) && $_GET['action'] === 'add';
$formData = [
    'id' => 0,
    'platform' => '',
    'icon_class' => '',
    'icon_image' => '',
    'url' => '',
    'open_in_new_tab' => 1,
    'sort_order' => count($socialLinks) + 1,
    'status' => 'active',
];

if ($editId > 0 && $pdo) {
    $row = db_fetch_one($pdo, 'SELECT * FROM social_media_links WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($formData, $row);
    }
}

// FontAwesome icon presets
$presets = [
    ['name' => 'YouTube', 'icon' => 'fa-brands fa-youtube', 'placeholder' => 'https://www.youtube.com/@mybrandplease'],
    ['name' => 'Facebook', 'icon' => 'fa-brands fa-facebook-f', 'placeholder' => 'https://www.facebook.com/mybrandplease'],
    ['name' => 'Instagram', 'icon' => 'fa-brands fa-instagram', 'placeholder' => 'https://www.instagram.com/mybrandplease_/'],
    ['name' => 'TikTok', 'icon' => 'fa-brands fa-tiktok', 'placeholder' => 'https://www.tiktok.com/@mybrandplease.com'],
    ['name' => 'X (Twitter)', 'icon' => 'fa-brands fa-x-twitter', 'placeholder' => 'https://x.com/mybrandplease'],
    ['name' => 'LinkedIn', 'icon' => 'fa-brands fa-linkedin-in', 'placeholder' => 'https://www.linkedin.com/in/mybrandplease'],
    ['name' => 'Pinterest', 'icon' => 'fa-brands fa-pinterest-p', 'placeholder' => 'https://in.pinterest.com/mybrandplease/'],
    ['name' => 'Threads', 'icon' => 'fa-brands fa-threads', 'placeholder' => 'https://www.threads.net/@mybrandplease'],
    ['name' => 'WhatsApp', 'icon' => 'fa-brands fa-whatsapp', 'placeholder' => 'https://wa.me/919717004615'],
];

$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- FontAwesome icons for admin preview -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-1">Global Social Media Module</h4>
    <p class="text-muted small mb-0">Changes saved here are shared everywhere across the site (Footer, Sidebar, About/Services pages, JSON-LD Schema).</p>
  </div>
  <?php if (!$isAdding && $editId === 0): ?>
    <a href="social-media.php?action=add" class="btn btn-success">
      <i class="bi bi-plus-lg me-1"></i> Add Social Platform
    </a>
  <?php endif; ?>
</div>

<?php if ($editId > 0 || $isAdding): ?>
<!-- Add / Edit Form Card -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi <?= $editId > 0 ? 'bi-pencil-square text-warning' : 'bi-plus-circle-fill text-success' ?>"></i>
      <?= $editId > 0 ? 'Edit Social Platform' : 'Add New Social Platform' ?>
    </h5>
    <a href="social-media.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-x-lg"></i> Cancel
    </a>
  </div>
  <div class="card-body p-4">
    <!-- Preset Shortcuts -->
    <div class="mb-4 p-3 bg-light rounded-3 border">
      <label class="form-label small fw-semibold text-muted mb-2"><i class="bi bi-magic me-1"></i> Quick Preset Fill:</label>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($presets as $p): ?>
          <button type="button" class="btn btn-sm btn-outline-dark preset-btn" data-name="<?= e($p['name']) ?>" data-icon="<?= e($p['icon']) ?>" data-placeholder="<?= e($p['placeholder']) ?>">
            <i class="<?= e($p['icon']) ?> me-1"></i> <?= e($p['name']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <form method="post" action="social-media.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_social">
      <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
      <input type="hidden" name="existing_icon_image" value="<?= e((string) $formData['icon_image']) ?>">

      <div class="row g-3">
        <!-- Platform Name -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Platform Name <span class="text-danger">*</span></label>
          <input type="text" id="platformInput" name="platform" class="form-control" value="<?= e($formData['platform']) ?>" placeholder="e.g. YouTube, Instagram, TikTok" required>
        </div>

        <!-- FontAwesome Icon Class -->
        <div class="col-md-5">
          <label class="form-label fw-semibold">Icon Class (FontAwesome)</label>
          <div class="input-group">
            <span class="input-group-text" id="iconPreviewSpan"><i class="<?= e($formData['icon_class'] ?: 'fa-solid fa-link') ?>"></i></span>
            <input type="text" id="iconClassInput" name="icon_class" class="form-control" value="<?= e($formData['icon_class']) ?>" placeholder="e.g. fa-brands fa-youtube">
          </div>
          <div class="form-text small">e.g. <code>fa-brands fa-instagram</code>, <code>fa-brands fa-tiktok</code></div>
        </div>

        <!-- Sort Order -->
        <div class="col-md-3">
          <label class="form-label fw-semibold">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <!-- Destination URL -->
        <div class="col-md-8">
          <label class="form-label fw-semibold">Destination Profile URL <span class="text-danger">*</span></label>
          <input type="text" id="urlInput" name="url" class="form-control" value="<?= e($formData['url']) ?>" placeholder="https://..." required>
        </div>

        <!-- Status -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= $formData['status'] === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
            <option value="inactive" <?= $formData['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
          </select>
        </div>

        <!-- Optional Custom Image Icon -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">Custom Image Icon <small class="text-muted">(Optional upload if not in FontAwesome)</small></label>
          <input type="file" name="icon_image" class="form-control" accept="image/*">
          <?php if (!empty($formData['icon_image'])): ?>
            <div class="mt-2 d-flex align-items-center gap-2">
              <img src="<?= e(url($formData['icon_image'])) ?>" alt="Icon Preview" style="width:32px; height:32px; object-fit:contain;">
              <small class="text-muted"><?= e($formData['icon_image']) ?></small>
            </div>
          <?php endif; ?>
        </div>

        <!-- Open in New Tab -->
        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="open_in_new_tab" id="openTabCheck" value="1" <?= !empty($formData['open_in_new_tab']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="openTabCheck">
              Open in new tab (<code>target="_blank"</code>)
            </label>
          </div>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Platform</button>
        <a href="social-media.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Social Media Links Table Card -->
<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-share text-primary"></i> Active Platforms (<?= count($socialLinks) ?>)
    </h5>
    <span class="text-muted small">Single source of truth for all social icons site-wide</span>
  </div>
  <div class="card-body p-0">
    <form id="bulkForm" method="post" action="social-media.php">
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
              <th style="width: 70px;">Icon</th>
              <th>Platform</th>
              <th>Destination URL</th>
              <th style="width: 100px;">Status</th>
              <th style="width: 140px;" class="text-end pe-3">Actions</th>
            </tr>
          </thead>
          <tbody id="sortableSocial">
            <?php if (empty($socialLinks)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-share fs-1 d-block mb-2 text-secondary"></i>
                  No social media platforms added yet. Click "Add Social Platform" to create one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($socialLinks as $item): ?>
                <tr data-id="<?= (int) $item['id'] ?>">
                  <td class="ps-3">
                    <input type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" class="form-check-input row-select">
                  </td>
                  <td>
                    <span class="drag-handle text-muted" style="cursor:grab;" title="Drag to reorder"><i class="bi bi-grip-vertical fs-5"></i></span>
                  </td>
                  <td>
                    <?php if (!empty($item['icon_image'])): ?>
                      <img src="<?= e(url($item['icon_image'])) ?>" alt="<?= e($item['platform']) ?>" style="width: 28px; height: 28px; object-fit: contain;">
                    <?php else: ?>
                      <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle border" style="width:36px; height:36px;">
                        <i class="<?= e($item['icon_class'] ?: 'fa-solid fa-link') ?> fs-6"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong class="text-dark"><?= e($item['platform']) ?></strong>
                  </td>
                  <td>
                    <a href="<?= e(url($item['url'])) ?>" target="_blank" class="text-decoration-none text-break font-monospace small text-primary">
                      <?= e($item['url']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                  </td>
                  <td>
                    <form method="post" action="social-media.php" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                      <button type="submit" class="btn btn-sm <?= $item['status'] === 'active' ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= ucfirst($item['status']) ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-end pe-3">
                    <a href="social-media.php?edit=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" action="social-media.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this social link?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($socialLinks)): ?>
        <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
          <button type="submit" class="btn btn-sm btn-danger" id="bulkDeleteBtn" disabled onclick="return confirm('Are you sure you want to delete the selected social links?');">
            <i class="bi bi-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
          </button>
          <span class="text-muted small">
            <i class="bi bi-arrows-move me-1"></i> Drag rows to reorder how icons appear site-wide.
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

  // Preset buttons click handler
  document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const name = this.getAttribute('data-name');
      const icon = this.getAttribute('data-icon');
      const placeholder = this.getAttribute('data-placeholder');

      const platformInput = document.getElementById('platformInput');
      const iconClassInput = document.getElementById('iconClassInput');
      const urlInput = document.getElementById('urlInput');
      const iconPreviewSpan = document.getElementById('iconPreviewSpan');

      if (platformInput) platformInput.value = name;
      if (iconClassInput) iconClassInput.value = icon;
      if (urlInput && !urlInput.value) urlInput.value = placeholder;
      if (iconPreviewSpan) iconPreviewSpan.innerHTML = `<i class="${icon}"></i>`;
    });
  });

  // Live update icon preview on text change
  const iconClassInput = document.getElementById('iconClassInput');
  const iconPreviewSpan = document.getElementById('iconPreviewSpan');
  if (iconClassInput && iconPreviewSpan) {
    iconClassInput.addEventListener('input', function() {
      const val = this.value.trim() || 'fa-solid fa-link';
      iconPreviewSpan.innerHTML = `<i class="${val}"></i>`;
    });
  }

  const tbody = document.getElementById('sortableSocial');
  if (tbody && typeof Sortable !== 'undefined') {
    new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150,
      onEnd: function () {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const order = Array.from(rows).map(row => parseInt(row.getAttribute('data-id'), 10));

        fetch('api/social-reorder.php', {
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
