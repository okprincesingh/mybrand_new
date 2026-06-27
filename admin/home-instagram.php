<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Instagram Reels';
$pdo = db();

$defaults = [
  'id' => 0,
  'reel_url' => '',
  'video_path' => '',
  'sort_order' => 0,
  'is_active' => 1,
];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);

$videoExists = static function (string $path): bool {
  $path = trim($path);
  if ($path === '') {
    return false;
  }
  $absolutePath = __DIR__ . '/../' . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
  return is_file($absolutePath);
};

if ($pdo && $editId > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM home_instagram_reels WHERE id = :id LIMIT 1', [':id' => $editId]);
  if ($row) {
    $formData = array_merge($defaults, $row);
  }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? 'save');

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM home_instagram_reels WHERE id = :id', [':id' => $id]);
      cms_invalidate_home_instagram_reels_cache();
      admin_flash('success', 'Reel deleted.');
    }
    header('Location: home-instagram.php');
    exit;
  }

  $id = (int) ($_POST['id'] ?? 0);
  $reelUrl = trim((string) ($_POST['reel_url'] ?? ''));
  $videoPath = trim((string) ($_POST['existing_video_path'] ?? ''));
  $sortOrder = (int) ($_POST['sort_order'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;

  if (!empty($_FILES['video']['name'])) {
    $stored = store_uploaded_video($_FILES['video'], 'instagram-reels', 50_000_000, false);
    if ($stored) {
      $videoPath = (string) $stored['public_path'];
    } else {
      admin_flash('danger', 'Video upload failed. Please upload mp4, webm or mov (max 50MB).');
      header('Location: home-instagram.php' . ($id > 0 ? '?edit=' . $id : ''));
      exit;
    }
  }

  if ($videoPath === '' && ($reelUrl === '' || !preg_match('#^https?://#i', $reelUrl))) {
    admin_flash('danger', 'Please upload a video file (or provide a valid fallback reel URL).');
    header('Location: home-instagram.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit;
  }

  if ($id > 0) {
    db_execute(
      $pdo,
      'UPDATE home_instagram_reels SET reel_url = :reel_url, video_path = :video_path, sort_order = :sort_order, is_active = :is_active WHERE id = :id',
      [
        ':reel_url' => $reelUrl,
        ':video_path' => $videoPath,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
        ':id' => $id,
      ]
    );
    admin_flash('success', 'Reel updated.');
  } else {
    db_execute(
      $pdo,
      'INSERT INTO home_instagram_reels (reel_url, video_path, sort_order, is_active) VALUES (:reel_url, :video_path, :sort_order, :is_active)',
      [
        ':reel_url' => $reelUrl,
        ':video_path' => $videoPath,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
      ]
    );
    admin_flash('success', 'Reel added.');
  }

  cms_invalidate_home_instagram_reels_cache();
  header('Location: home-instagram.php');
  exit;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_instagram_reels ORDER BY sort_order ASC, id ASC') : [];
$missingVideoCount = 0;
foreach ($rows as $row) {
  $storedPath = (string) ($row['video_path'] ?? '');
  if ($storedPath !== '' && !$videoExists($storedPath)) {
    $missingVideoCount++;
  }
}

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-4"><?= $formData['id'] ? 'Edit Reel' : 'Add New Reel' ?></h5>
      <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_video_path" value="<?= e((string) $formData['video_path']) ?>">

        <div class="form-group">
          <label class="form-label">Upload Reel Video</label>
          <input type="file" name="video" class="form-control" accept="video/mp4,video/webm,video/quicktime" <?= $formData['id'] ? '' : 'required' ?>>
          <small class="text-muted">Allowed: mp4, webm, mov (max 50MB)</small>
          <?php if ((string) $formData['video_path'] !== ''): ?>
            <div class="mt-3">
              <?php if ($videoExists((string) $formData['video_path'])): ?>
                <video src="<?= e(url((string) $formData['video_path'])) ?>" controls muted playsinline style="width:100%;max-height:240px;border-radius:12px;border:1px solid var(--border);"></video>
              <?php else: ?>
                <div class="alert alert-warning mt-2 mb-0">Stored video file is missing. Please re-upload this reel video.</div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Fallback Instagram URL (optional)</label>
          <input type="url" name="reel_url" class="form-control" value="<?= e((string) $formData['reel_url']) ?>" placeholder="https://www.instagram.com/reel/...">
        </div>

        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="form-group">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int) $formData['is_active']) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>

        <div class="form-group">
          <div class="d-flex gap-3">
            <button class="btn btn-primary-modern"><?= $formData['id'] ? 'Update Reel' : 'Add Reel' ?></button>
            <?php if ($formData['id']): ?>
              <a href="home-instagram.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Instagram Reels (<?= count($rows) ?>)</h5>
      </div>
      <?php if ($missingVideoCount > 0): ?>
        <div class="alert alert-warning mx-4 mt-3 mb-0">
          <?= (int) $missingVideoCount ?> reel video file(s) are missing from `uploads/instagram-reels/`. Re-upload those reels to restore the homepage carousel.
        </div>
      <?php endif; ?>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Preview</th>
              <th>Reel URL</th>
              <th>Status</th>
              <th>Order</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <?php
                $rowVideoPath = (string) ($row['video_path'] ?? '');
                $rowVideoExists = $rowVideoPath !== '' && $videoExists($rowVideoPath);
              ?>
              <tr>
                <td>
                  <?php if ($rowVideoExists): ?>
                    <video src="<?= e(url($rowVideoPath)) ?>" muted playsinline controls style="width:110px;height:64px;object-fit:cover;border-radius:10px;border:1px solid var(--border);"></video>
                  <?php elseif ($rowVideoPath !== ''): ?>
                    <span class="badge bg-warning text-dark">Missing file</span>
                  <?php else: ?>
                    <span class="text-muted">No video</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((string) ($row['reel_url'] ?? '') !== ''): ?>
                    <a href="<?= e((string) $row['reel_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $row['reel_url']) ?></a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge <?= ((int) $row['is_active']) === 1 ? 'status-active' : 'status-inactive' ?>">
                    <?= ((int) $row['is_active']) === 1 ? 'Active' : 'Inactive' ?>
                  </span>
                  <?php if (!$rowVideoExists && $rowVideoPath !== ''): ?>
                    <div class="text-warning small mt-1">Re-upload required</div>
                  <?php endif; ?>
                </td>
                <td><?= (int) $row['sort_order'] ?></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="home-instagram.php?edit=<?= (int) $row['id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this reel?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                      <button class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">No reels found. Add your first reel video.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
