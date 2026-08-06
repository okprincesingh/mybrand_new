<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';
$adminUser = admin_require_auth();
$title = 'Manage Categories';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = $_POST['action'] ?? '';

  // Save section heading
  if ($action === 'save_section') {
    $titleText = trim((string) ($_POST['title_text'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($titleText === '') {
      $errorMessage = 'Section heading is required';
    } else {
      $pdo = db();
      $stmt = $pdo->prepare("
        INSERT INTO home_category_section (section_key, title_text, is_active)
        VALUES (:section_key, :title_text, :is_active)
        ON DUPLICATE KEY UPDATE
          title_text = VALUES(title_text),
          is_active = VALUES(is_active),
          updated_at = CURRENT_TIMESTAMP
      ");
      $stmt->execute([
        ':section_key' => 'main',
        ':title_text'  => $titleText,
        ':is_active'   => $isActive,
      ]);
      cms_invalidate_home_category_section_cache();
      $successMessage = 'Section heading saved successfully';
    }
  }

  // Save category selections (which catalog categories/subcategories appear on home)
  if ($action === 'save_selection') {
    $pdo = db();
    $selectedCategoryIds = array_map('intval', (array) ($_POST['category_ids'] ?? []));
    $selectedCategoryIds = array_values(array_filter($selectedCategoryIds, static fn($id) => $id > 0));
    $selectedSubcategoryIds = array_map('intval', (array) ($_POST['subcategory_ids'] ?? []));
    $selectedSubcategoryIds = array_values(array_filter($selectedSubcategoryIds, static fn($id) => $id > 0));

    $categorySortOrders = array_map('intval', (array) ($_POST['category_sort_order'] ?? []));
    $allSubcategorySortOrders = array_map('intval', (array) ($_POST['subcategory_sort_order'] ?? []));

    try {
      $pdo->beginTransaction();

      // Delete existing selections
      $pdo->exec('DELETE FROM home_category_subcategories');
      $pdo->exec('DELETE FROM home_categories');

      // Insert selected main categories
      $homeCategoryIdMap = [];
      $insertCat = $pdo->prepare('INSERT INTO home_categories (category_id, sort_order, is_active) VALUES (:cid, :so, 1)');
      foreach ($selectedCategoryIds as $catId) {
        $sortOrder = $categorySortOrders[$catId] ?? 0;
        $insertCat->execute([':cid' => $catId, ':so' => $sortOrder]);
        $homeCategoryIdMap[$catId] = (int) $pdo->lastInsertId();
      }

      // Insert selected subcategories
      if (!empty($homeCategoryIdMap)) {
        $insertSub = $pdo->prepare('INSERT INTO home_category_subcategories (home_category_id, subcategory_id, sort_order) VALUES (:hid, :sid, :so)');
        foreach ($selectedSubcategoryIds as $subId) {
          $subRow = db_fetch_one($pdo, 'SELECT parent_id FROM categories WHERE id = :id LIMIT 1', [':id' => $subId]);
          if ($subRow && isset($homeCategoryIdMap[(int) $subRow['parent_id']])) {
            $homeId  = $homeCategoryIdMap[(int) $subRow['parent_id']];
            $subSort = $allSubcategorySortOrders[$subId] ?? 0;
            $insertSub->execute([':hid' => $homeId, ':sid' => $subId, ':so' => $subSort]);
          }
        }
      }

      $pdo->commit();
      cms_invalidate_home_categories_cache();
      catalog_invalidate_cache();
      $successMessage = 'Category selections saved successfully';
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errorMessage = 'Failed to save selections: ' . $e->getMessage();
    }
  }

  // Reorder (move up/down) — fallback for standard form post
  if ($action === 'reorder') {
    $id        = (int) ($_POST['id'] ?? 0);
    $direction = (string) ($_POST['direction'] ?? 'up');
    if ($id > 0 && in_array($direction, ['up', 'down'], true)) {
      $pdo     = db();
      $current = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE id = :id LIMIT 1', [':id' => $id]);
      if ($current) {
        $currentOrder = (int) $current['sort_order'];
        $neighbor     = null;
        if ($direction === 'up') {
          $neighbor = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE sort_order < :so OR (sort_order = :so AND id < :id) ORDER BY sort_order DESC, id DESC LIMIT 1', [
            ':so' => $currentOrder, ':id' => $id,
          ]);
        } else {
          $neighbor = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE sort_order > :so OR (sort_order = :so AND id > :id) ORDER BY sort_order ASC, id ASC LIMIT 1', [
            ':so' => $currentOrder, ':id' => $id,
          ]);
        }
        if ($neighbor) {
          $stmt = $pdo->prepare('UPDATE home_categories SET sort_order = :so WHERE id = :id');
          $stmt->execute([':so' => (int) $neighbor['sort_order'], ':id' => $id]);
          $stmt->execute([':so' => $currentOrder,                  ':id' => (int) $neighbor['id']]);
          cms_invalidate_home_categories_cache();
          $successMessage = 'Category reordered';
        }
      }
    }
  }
}

// ── Handle reorder AJAX (fetch-based, returns JSON) ──────────────────────────
if (isset($_GET['ajax_reorder'])) {
  header('Content-Type: application/json');
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
  }
  $payload = json_decode(file_get_contents('php://input'), true) ?? [];
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)) {
    echo json_encode(['ok' => false, 'error' => 'CSRF mismatch']);
    exit;
  }
  $id        = (int) ($payload['id'] ?? 0);
  $direction = (string) ($payload['direction'] ?? '');
  if ($id > 0 && in_array($direction, ['up', 'down'], true)) {
    $pdo     = db();
    $current = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE id = :id LIMIT 1', [':id' => $id]);
    if ($current) {
      $currentOrder = (int) $current['sort_order'];
      $neighbor     = null;
      if ($direction === 'up') {
        $neighbor = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE sort_order < :so OR (sort_order = :so AND id < :id) ORDER BY sort_order DESC, id DESC LIMIT 1', [':so' => $currentOrder, ':id' => $id]);
      } else {
        $neighbor = db_fetch_one($pdo, 'SELECT id, sort_order FROM home_categories WHERE sort_order > :so OR (sort_order = :so AND id > :id) ORDER BY sort_order ASC, id ASC LIMIT 1', [':so' => $currentOrder, ':id' => $id]);
      }
      if ($neighbor) {
        $stmt = $pdo->prepare('UPDATE home_categories SET sort_order = :so WHERE id = :id');
        $stmt->execute([':so' => (int) $neighbor['sort_order'], ':id' => $id]);
        $stmt->execute([':so' => $currentOrder,                  ':id' => (int) $neighbor['id']]);
        cms_invalidate_home_categories_cache();
        echo json_encode(['ok' => true]);
        exit;
      }
    }
  }
  echo json_encode(['ok' => false, 'error' => 'Nothing to reorder']);
  exit;
}

// ── Page data ─────────────────────────────────────────────────────────────────
$pdo         = db();
$section     = cms_get_home_category_section();
$catalogTree = cms_get_catalog_category_tree();

$selectedHomeCategories        = [];
$selectedSubcategoryIds        = [];
$selectedSubcategorySortOrders = [];

if ($pdo) {
  try {
    $selectedHomeCategories = db_fetch_all($pdo, 'SELECT * FROM home_categories ORDER BY sort_order ASC, id ASC') ?: [];
    if ($selectedHomeCategories) {
      $homeIds      = array_map(static fn($row) => (int) $row['id'], $selectedHomeCategories);
      $placeholders = implode(',', array_fill(0, count($homeIds), '?'));
      $subRows      = db_fetch_all($pdo, "SELECT subcategory_id, sort_order FROM home_category_subcategories WHERE home_category_id IN ($placeholders)", $homeIds) ?: [];
      $selectedSubcategoryIds = array_map(static fn($row) => (int) $row['subcategory_id'], $subRows);
      foreach ($subRows as $subRow) {
        $selectedSubcategorySortOrders[(int) $subRow['subcategory_id']] = (int) $subRow['sort_order'];
      }
    }
  } catch (Throwable $e) {
    $selectedHomeCategories        = [];
    $selectedSubcategoryIds        = [];
    $selectedSubcategorySortOrders = [];
  }
}

$selectedCategoryIds        = array_map(static fn($row) => (int) ($row['category_id'] ?? 0), $selectedHomeCategories);
$selectedCategorySortOrders = [];
foreach ($selectedHomeCategories as $shc) {
  $selectedCategorySortOrders[(int) ($shc['category_id'] ?? 0)] = (int) ($shc['sort_order'] ?? 0);
}

// ── Build JS saved-state (string keys for reliable JS object lookup) ──────────
$jsSavedCategoryIds      = array_map('strval', $selectedCategoryIds);
$jsSavedCategorySortMap  = [];
foreach ($selectedCategorySortOrders as $catId => $so) {
  $jsSavedCategorySortMap[(string) $catId] = $so;
}
$jsSavedSubcategoryIds     = array_map('strval', $selectedSubcategoryIds);
$jsSavedSubcategorySortMap = [];
foreach ($selectedSubcategorySortOrders as $subId => $so) {
  $jsSavedSubcategorySortMap[(string) $subId] = $so;
}

include __DIR__ . '/_layout_top.php';
?>
<?php if ($successMessage): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- Section Heading -->
  <div class="col-lg-12">
    <div class="form-section">
      <h5 class="mb-3">Section Heading</h5>
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save_section">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Heading Text</label>
            <input type="text" name="title_text" class="form-control" value="<?= e($section['title_text'] ?? 'Nature Powered Ingredients') ?>" required>
          </div>
          <div class="col-md-4 d-flex align-items-end gap-2">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="is_active" id="sectionActive" value="1" <?= !empty($section['is_active']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="sectionActive">Active</label>
            </div>
            <button type="submit" class="btn btn-primary">Save Heading</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Category Selection — single form, NO nested forms inside -->
  <div class="col-lg-12">
    <div class="form-section">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Select Categories to Display on Home</h5>
        <span class="text-muted small">Categories are managed in <a href="categories.php">Catalog &rarr; Categories</a>. Select which ones appear here.</span>
      </div>

      <?php if (empty($catalogTree)): ?>
        <div class="alert alert-info mb-0">
          No catalog categories found. Please add categories in <a href="categories.php">Catalog &rarr; Categories</a> first.
        </div>
      <?php else: ?>
        <!-- Single form — reorder buttons use JS fetch so no nested forms needed -->
        <form method="POST" action="" id="selectionForm">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" id="csrfToken">
          <input type="hidden" name="action" value="save_selection">

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th style="width:40px;">Select</th>
                  <th>Category</th>
                  <th>Subcategories</th>
                  <th style="width:100px;">Sort Order</th>
                  <th class="text-end" style="width:120px;">Order</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($catalogTree as $idx => $cat): ?>
                  <?php
                    $catId      = (int) $cat['id'];
                    $isSelected = in_array($catId, $selectedCategoryIds, true);
                    $homeRow    = null;
                    foreach ($selectedHomeCategories as $shc) {
                      if ((int) $shc['category_id'] === $catId) {
                        $homeRow = $shc;
                        break;
                      }
                    }
                    $homeId       = $homeRow ? (int) $homeRow['id'] : 0;
                    $catSortOrder = $selectedCategorySortOrders[$catId] ?? $idx;
                    $subs         = (array) ($cat['subcategories'] ?? []);
                    usort($subs, function ($a, $b) use ($selectedSubcategorySortOrders) {
                      $aId   = (int) ($a['id'] ?? 0);
                      $bId   = (int) ($b['id'] ?? 0);
                      $aSort = $selectedSubcategorySortOrders[$aId] ?? 0;
                      $bSort = $selectedSubcategorySortOrders[$bId] ?? 0;
                      return $aSort === $bSort ? ($aId <=> $bId) : ($aSort <=> $bSort);
                    });
                  ?>
                  <tr class="<?= $isSelected ? 'table-active' : '' ?>">
                    <td><?= $idx + 1 ?></td>
                    <td>
                      <input type="checkbox"
                             class="form-check-input category-check"
                             name="category_ids[]"
                             value="<?= $catId ?>"
                             <?= $isSelected ? 'checked' : '' ?>>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($cat['image'])): ?>
                          <img src="<?= e(url($cat['image'])) ?>" alt="" style="height:36px;width:36px;object-fit:cover;border-radius:4px;">
                        <?php endif; ?>
                        <div>
                          <strong><?= e($cat['name']) ?></strong>
                          <div class="text-muted small"><code><?= e($cat['slug']) ?></code></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if (empty($subs)): ?>
                        <span class="text-muted small">No subcategories</span>
                      <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                          <?php foreach ($subs as $sub): ?>
                            <?php $subId = (int) $sub['id']; ?>
                            <label class="form-check form-check-inline sub-check-label" style="margin-right:0;">
                              <input type="checkbox"
                                     class="form-check-input sub-check"
                                     name="subcategory_ids[]"
                                     value="<?= $subId ?>"
                                     data-parent="<?= $catId ?>"
                                     <?= in_array($subId, $selectedSubcategoryIds, true) ? 'checked' : '' ?>>
                              <span class="form-check-label small"><?= e($sub['name']) ?></span>
                              <input type="number"
                                     class="form-control form-control-sm subcategory-sort-order d-inline-block"
                                     data-subcategory-id="<?= $subId ?>"
                                     style="width:60px;height:28px;padding:2px 6px;margin-left:4px;"
                                     name="subcategory_sort_order[<?= $subId ?>]"
                                     value="<?= (int) ($selectedSubcategorySortOrders[$subId] ?? 0) ?>"
                                     min="0"
                                     placeholder="Sort">
                            </label>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <input type="number"
                             class="form-control form-control-sm category-sort-order"
                             data-category-id="<?= $catId ?>"
                             name="category_sort_order[<?= $catId ?>]"
                             value="<?= (int) $catSortOrder ?>"
                             min="0"
                             style="width:80px;">
                    </td>
                    <td class="text-end">
                      <?php if ($homeRow): ?>
                        <!-- Reorder buttons — use JS fetch, no nested <form> -->
                        <div class="btn-group btn-group-sm">
                          <button type="button"
                                  class="btn btn-outline-secondary reorder-btn"
                                  data-home-id="<?= $homeId ?>"
                                  data-direction="up"
                                  title="Move up"
                                  <?= $idx === 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-up"></i>
                          </button>
                          <button type="button"
                                  class="btn btn-outline-secondary reorder-btn"
                                  data-home-id="<?= $homeId ?>"
                                  data-direction="down"
                                  title="Move down"
                                  <?= $idx === count($catalogTree) - 1 ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-down"></i>
                          </button>
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary" id="editSelectionBtn">
              <i class="bi bi-pencil-square me-1"></i>Load Saved
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i>Save Selection
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  // ── Saved state injected by PHP directly from the database ──────────────────
  // String-keyed maps so JS object property access is unambiguous.
  var SAVED_STATE = {
    categoryIds:        <?= json_encode($jsSavedCategoryIds,      JSON_UNESCAPED_UNICODE) ?>,
    categorySortMap:    <?= json_encode($jsSavedCategorySortMap,  JSON_UNESCAPED_UNICODE) ?>,
    subcategoryIds:     <?= json_encode($jsSavedSubcategoryIds,   JSON_UNESCAPED_UNICODE) ?>,
    subcategorySortMap: <?= json_encode($jsSavedSubcategorySortMap, JSON_UNESCAPED_UNICODE) ?>
  };

  // ── Helpers ─────────────────────────────────────────────────────────────────
  function applyState(state) {
    // Restore main category checkboxes + sort orders
    document.querySelectorAll('.category-check').forEach(function (chk) {
      var id            = chk.value;
      var shouldCheck   = state.categoryIds.indexOf(id) !== -1;
      chk.checked       = shouldCheck;
      var row = chk.closest('tr');
      if (row) row.classList.toggle('table-active', shouldCheck);

      var sortInput = document.querySelector('.category-sort-order[data-category-id="' + id + '"]');
      if (sortInput) {
        sortInput.value = (state.categorySortMap[id] !== undefined) ? state.categorySortMap[id] : 0;
      }
    });

    // Restore subcategory checkboxes + sort orders
    document.querySelectorAll('.sub-check').forEach(function (chk) {
      var id          = chk.value;
      var shouldCheck = state.subcategoryIds.indexOf(id) !== -1;
      chk.checked     = shouldCheck;

      var sortInput = document.querySelector('.subcategory-sort-order[data-subcategory-id="' + id + '"]');
      if (sortInput) {
        sortInput.value = (state.subcategorySortMap[id] !== undefined) ? state.subcategorySortMap[id] : 0;
      }
    });
  }

  function flashBtn(btn, label, cls) {
    var orig     = btn.innerHTML;
    var origCls  = btn.classList.contains('btn-outline-secondary') ? 'btn-outline-secondary' : '';
    btn.innerHTML = label;
    btn.classList.remove('btn-outline-secondary', 'btn-success', 'btn-danger');
    btn.classList.add(cls);
    setTimeout(function () {
      btn.innerHTML = orig;
      btn.classList.remove('btn-success', 'btn-danger');
      if (origCls) btn.classList.add(origCls);
    }, 1500);
  }

  // ── Reorder via fetch (avoids nested <form> inside save form) ───────────────
  function reorder(homeId, direction) {
    var csrfEl = document.getElementById('csrfToken');
    var csrf   = csrfEl ? csrfEl.value : '';
    fetch('?ajax_reorder=1', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-Csrf-Token': csrf },
      body:    JSON.stringify({ id: homeId, direction: direction })
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.ok) {
        // Reload page to reflect new order
        window.location.reload();
      } else {
        alert('Reorder failed: ' + (data.error || 'unknown error'));
      }
    })
    .catch(function () { alert('Network error during reorder.'); });
  }

  // ── Wire up once DOM is ready ───────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {

    // Highlight row when category checkbox changes
    document.querySelectorAll('.category-check').forEach(function (chk) {
      chk.addEventListener('change', function () {
        var row = this.closest('tr');
        if (row) row.classList.toggle('table-active', this.checked);
      });
    });

    // Reorder buttons (fetch-based, no nested forms)
    document.querySelectorAll('.reorder-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var homeId    = parseInt(this.dataset.homeId, 10);
        var direction = this.dataset.direction;
        if (homeId && direction) reorder(homeId, direction);
      });
    });

    // Edit / Load Saved button
    var editBtn = document.getElementById('editSelectionBtn');
    if (!editBtn) return;

    editBtn.addEventListener('click', function () {
      if (SAVED_STATE.categoryIds.length === 0) {
        flashBtn(editBtn, '<i class="bi bi-exclamation-triangle me-1"></i>Nothing saved yet', 'btn-danger');
        return;
      }
      applyState(SAVED_STATE);
      flashBtn(editBtn, '<i class="bi bi-check2 me-1"></i>Loaded!', 'btn-success');
    });
  });
})();
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>