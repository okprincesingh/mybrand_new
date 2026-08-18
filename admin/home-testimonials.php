<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

$adminUser = admin_require_auth();
$title = 'Home Testimonials Management';
$pdo = db();

$successMessage = '';
$errorMessage = '';

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_header_content') {
        $eyebrowText = trim((string) ($_POST['eyebrow_text'] ?? 'Verified Reviews'));
        $headingText = trim((string) ($_POST['heading_text'] ?? "Here's what our customers say"));
        $ratingPrefix = trim((string) ($_POST['rating_prefix'] ?? 'mybrandplease.com is rated'));
        $ratingHighlight = trim((string) ($_POST['rating_highlight'] ?? 'Excellent'));
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $pdo->prepare("
            INSERT INTO home_testimonials_content
                (section_key, eyebrow_text, heading_text, rating_prefix, rating_highlight, is_active)
            VALUES
                (:section_key, :eyebrow_text, :heading_text, :rating_prefix, :rating_highlight, :is_active)
            ON DUPLICATE KEY UPDATE
                eyebrow_text = VALUES(eyebrow_text),
                heading_text = VALUES(heading_text),
                rating_prefix = VALUES(rating_prefix),
                rating_highlight = VALUES(rating_highlight),
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            ':section_key' => 'main',
            ':eyebrow_text' => $eyebrowText,
            ':heading_text' => $headingText,
            ':rating_prefix' => $ratingPrefix,
            ':rating_highlight' => $ratingHighlight,
            ':is_active' => $isActive,
        ]);
        cms_invalidate_home_testimonials_content_cache();
        admin_flash('success', 'Testimonials section header updated successfully.');
        header('Location: home-testimonials.php');
        exit;
    }

    if ($action === 'save_testimonial_card') {
        $id = (int) ($_POST['id'] ?? 0);
        $platform = trim((string) ($_POST['platform'] ?? 'tp'));
        $name = trim((string) ($_POST['name'] ?? ''));
        $reviewDate = trim((string) ($_POST['review_date'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));
        if (!empty($_FILES['image']['name'])) {
            $stored = store_uploaded_image($_FILES['image'], 'testimonials', 5_000_000, false);
            if ($stored) {
                $imagePath = (string) $stored['public_path'];
            }
        }

        if ($name === '' || $content === '') {
            admin_flash('danger', 'Reviewer Name and Review Message are required.');
            header('Location: home-testimonials.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE home_testimonials SET platform = :platform, name = :name, content = :content, rating = :rating, review_date = :review_date, image_path = :image_path, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':platform' => $platform,
                ':name' => $name,
                ':content' => $content,
                ':rating' => $rating,
                ':review_date' => $reviewDate,
                ':image_path' => $imagePath,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]);
            admin_flash('success', 'Testimonial review updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO home_testimonials (platform, name, content, rating, review_date, image_path, sort_order, is_active) VALUES (:platform, :name, :content, :rating, :review_date, :image_path, :sort_order, :is_active)');
            $stmt->execute([
                ':platform' => $platform,
                ':name' => $name,
                ':content' => $content,
                ':rating' => $rating,
                ':review_date' => $reviewDate,
                ':image_path' => $imagePath,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]);
            admin_flash('success', 'Testimonial review added successfully.');
        }

        cms_invalidate_home_testimonials_cache();
        header('Location: home-testimonials.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM home_testimonials WHERE id = :id', [':id' => $id]);
            cms_invalidate_home_testimonials_cache();
            admin_flash('success', 'Testimonial review deleted successfully.');
        }
        header('Location: home-testimonials.php');
        exit;
    }
}

$headerContent = cms_get_home_testimonials_content();

$defaults = [
    'id' => 0,
    'platform' => 'tp',
    'name' => '',
    'review_date' => date('d M Y'),
    'content' => '',
    'rating' => 5,
    'sort_order' => 0,
    'is_active' => 1,
    'image_path' => '',
];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);
if ($pdo && $editId > 0) {
    $row = db_fetch_one($pdo, 'SELECT * FROM home_testimonials WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($row) {
        $formData = array_merge($defaults, $row);
    }
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_testimonials ORDER BY sort_order ASC, id ASC') : [];
$platformLabels = [
    'tp' => 'Trustpilot',
    'goog' => 'Google',
    'ali' => 'Alibaba',
];

$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- Section Header & Intro Text Settings -->
<div class="widget-card mb-4">
  <div class="widget-header">
    <h5 class="widget-title"><i class="bi bi-gear me-2"></i>Testimonials Section Header & Title Settings</h5>
  </div>
  <div class="widget-body p-3">
    <form method="post" action="" data-section-preview='{"content_type":"home_testimonials_content","entity_id":<?= (int) ($headerContent['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_header_content">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Verified Reviews Label <span class="text-danger">*</span></label>
          <input class="form-control" name="eyebrow_text" value="<?= e((string) ($headerContent['eyebrow_text'] ?? 'Verified Reviews')) ?>" required placeholder="e.g. Verified Reviews">
          <small class="text-muted">Small badge label above the intro (e.g. "Verified Reviews").</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Main Heading <span class="text-danger">*</span></label>
          <input class="form-control" name="heading_text" value="<?= e((string) ($headerContent['heading_text'] ?? "Here's what our customers say")) ?>" required placeholder="e.g. Here's what our customers say">
          <small class="text-muted">Main headline text below the badge.</small>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Rating Text Prefix <span class="text-danger">*</span></label>
          <input class="form-control" name="rating_prefix" value="<?= e((string) ($headerContent['rating_prefix'] ?? 'mybrandplease.com is rated')) ?>" required placeholder="e.g. mybrandplease.com is rated">
          <small class="text-muted">Prefix text before the highlighted word.</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Rating Highlighted Word <span class="text-danger">*</span></label>
          <input class="form-control" name="rating_highlight" value="<?= e((string) ($headerContent['rating_highlight'] ?? 'Excellent')) ?>" required placeholder="e.g. Excellent">
          <small class="text-muted">Bold/highlighted word (e.g. "Excellent").</small>
        </div>
      </div>

      <div class="mb-3 form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="headerActive" <?= ((int) ($headerContent['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="headerActive">Active</label>
      </div>

      <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Header Settings</button>
    </form>
  </div>
</div>

<!-- Testimonials Form and Cards List -->
<div class="row g-4">
  <div class="col-lg-5">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title"><i class="bi bi-chat-quote me-2"></i><?= $formData['id'] ? 'Edit Testimonial' : 'Add New Testimonial' ?></h5>
      </div>
      <div class="widget-body p-3">
        <form method="post" enctype="multipart/form-data" action="" data-section-preview='{"content_type":"home_testimonial","entity_id":<?= (int) $formData['id'] ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_testimonial_card">
          <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
          <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

          <div class="mb-3">
            <label class="form-label">Review Platform <span class="text-danger">*</span></label>
            <select class="form-select" name="platform" required>
              <option value="tp" <?= $formData['platform'] === 'tp' ? 'selected' : '' ?>>Trustpilot</option>
              <option value="goog" <?= $formData['platform'] === 'goog' ? 'selected' : '' ?>>Google</option>
              <option value="ali" <?= $formData['platform'] === 'ali' ? 'selected' : '' ?>>Alibaba</option>
            </select>
            <small class="text-muted">Determines platform tab assignment & logo pill styling.</small>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Reviewer Name <span class="text-danger">*</span></label>
              <input class="form-control" name="name" value="<?= e((string) $formData['name']) ?>" placeholder="e.g. Steve Marc" required>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Review Date</label>
              <input class="form-control" name="review_date" value="<?= e((string) ($formData['review_date'] ?: date('d M Y'))) ?>" placeholder="e.g. 8 Mar 2026">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Review Message <span class="text-danger">*</span></label>
            <textarea class="form-control" rows="4" name="content" placeholder="Enter review message content..." required><?= e((string) $formData['content']) ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Star Rating (1 - 5) <span class="text-danger">*</span></label>
              <select class="form-select" name="rating" required>
                <option value="5" <?= (int) $formData['rating'] === 5 ? 'selected' : '' ?>>★★★★★ (5 Stars)</option>
                <option value="4" <?= (int) $formData['rating'] === 4 ? 'selected' : '' ?>>★★★★☆ (4 Stars)</option>
                <option value="3" <?= (int) $formData['rating'] === 3 ? 'selected' : '' ?>>★★★☆☆ (3 Stars)</option>
                <option value="2" <?= (int) $formData['rating'] === 2 ? 'selected' : '' ?>>★★☆☆☆ (2 Stars)</option>
                <option value="1" <?= (int) $formData['rating'] === 1 ? 'selected' : '' ?>>★☆☆☆☆ (1 Star)</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int) $formData['is_active']) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>

          <div class="mb-3">
            <label class="form-label">Reviewer Image (Optional)</label>
            <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp">
            <?php if ((string) $formData['image_path'] !== ''): ?>
              <div class="mt-2">
                <small class="text-muted d-block mb-1">Current Image:</small>
                <img src="<?= e(url((string) $formData['image_path'])) ?>" style="width:60px;height:60px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
              </div>
            <?php endif; ?>
            <small class="text-muted">If no file is selected during edit, the existing image will be retained. If left empty, initials will be generated automatically.</small>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><?= $formData['id'] ? 'Update Review' : 'Add Review' ?></button>
            <?php if ($formData['id']): ?>
              <a href="home-testimonials.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">All Testimonials (<?= count($rows) ?>)</h5>
      </div>
      <div class="widget-body p-0">
        <div class="table-responsive">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Order</th>
                <th>Platform</th>
                <th>Reviewer</th>
                <th>Rating</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No testimonials found. Fill the form to create one.</td>
                </tr>
              <?php endif; ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= (int) $r['sort_order'] ?></td>
                  <td>
                    <?php
                      $pCode = (string) ($r['platform'] ?? 'tp');
                      $pBadgeClass = $pCode === 'tp' ? 'bg-success' : ($pCode === 'goog' ? 'bg-primary' : 'bg-warning text-dark');
                      $pName = $platformLabels[$pCode] ?? 'Trustpilot';
                    ?>
                    <span class="badge <?= $pBadgeClass ?>"><?= e($pName) ?></span>
                  </td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if (!empty($r['image_path'])): ?>
                        <img src="<?= e(url((string) $r['image_path'])) ?>" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                      <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light text-dark fw-bold border rounded-circle" style="width:36px;height:36px;font-size:12px;">
                          <?= e(substr((string) $r['name'], 0, 2)) ?>
                        </div>
                      <?php endif; ?>
                      <div>
                        <strong><?= e((string) $r['name']) ?></strong>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="text-warning">★ <?= (int) $r['rating'] ?></span>
                  </td>
                  <td><small class="text-muted"><?= e((string) ($r['review_date'] ?? '')) ?></small></td>
                  <td>
                    <span class="badge <?= ((int) $r['is_active']) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                      <?= ((int) $r['is_active']) === 1 ? 'Active' : 'Inactive' ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <a class="btn btn-sm btn-primary" href="home-testimonials.php?edit=<?= (int) $r['id'] ?>" title="Edit">
                        <i class="bi bi-pencil-square"></i> Edit
                      </a>
                      <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this testimonial?');">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-sm btn-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
