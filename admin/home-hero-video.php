<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Hero Video Section';
$pdo = db();

if ($pdo) {
    cms_ensure_home_hero_videos_table($pdo);
    draft_ensure_table($pdo);
}

$defaults = [
    'id' => 0,
    'label' => '',
    'desktop_video_url' => '',
    'desktop_light_video_url' => '',
    'mobile_video_url' => '',
    'desktop_video_file' => '',
    'desktop_light_video_file' => '',
    'mobile_video_file' => '',
    'poster_image' => '',
    'sort_order' => 0,
    'is_active' => 1,
];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);

if ($pdo && $editId > 0) {
    $row = db_fetch_one($pdo, 'SELECT * FROM home_hero_videos WHERE id = :id LIMIT 1', [':id' => $editId]);
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
            db_execute($pdo, 'DELETE FROM home_hero_videos WHERE id = :id', [':id' => $id]);
            cms_invalidate_home_hero_videos_cache();
            admin_flash('success', 'Hero video deleted.');
        }
        header('Location: home-hero-video.php');
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $label = trim((string) ($_POST['label'] ?? ''));
    $desktopUrl = trim((string) ($_POST['desktop_video_url'] ?? ''));
    $desktopLightUrl = trim((string) ($_POST['desktop_light_video_url'] ?? ''));
    $mobileUrl = trim((string) ($_POST['mobile_video_url'] ?? ''));
    $desktopFile = trim((string) ($_POST['existing_desktop_video_file'] ?? ''));
    $desktopLightFile = trim((string) ($_POST['existing_desktop_light_video_file'] ?? ''));
    $mobileFile = trim((string) ($_POST['existing_mobile_video_file'] ?? ''));
    $posterImage = trim((string) ($_POST['existing_poster_image'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($_FILES['poster_image']['name'])) {
        $stored = store_uploaded_image($_FILES['poster_image'], 'home-hero', 5_000_000, false);
        if ($stored) {
            $posterImage = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Poster image upload failed. Please upload jpg, jpeg, png or webp (max 5MB).');
            header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }

    // Handle local video file uploads (mp4, webm, mov - max 50MB each).
    if (!empty($_FILES['desktop_video_file']['name'])) {
        $stored = store_uploaded_video($_FILES['desktop_video_file'], 'home-hero', 50_000_000, false);
        if ($stored) {
            $desktopFile = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Desktop video upload failed. Please upload mp4, webm or mov (max 50MB).');
            header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }
    if (!empty($_FILES['desktop_light_video_file']['name'])) {
        $stored = store_uploaded_video($_FILES['desktop_light_video_file'], 'home-hero', 50_000_000, false);
        if ($stored) {
            $desktopLightFile = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Desktop light video upload failed. Please upload mp4, webm or mov (max 50MB).');
            header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }
    if (!empty($_FILES['mobile_video_file']['name'])) {
        $stored = store_uploaded_video($_FILES['mobile_video_file'], 'home-hero', 50_000_000, false);
        if ($stored) {
            $mobileFile = (string) $stored['public_path'];
        } else {
            admin_flash('danger', 'Mobile video upload failed. Please upload mp4, webm or mov (max 50MB).');
            header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }
    }

    // At least one desktop source (URL or file) is required.
    if ($desktopUrl === '' && $desktopFile === '') {
        admin_flash('danger', 'Please provide a Desktop video URL or upload a Desktop video file.');
        header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id : ''));
        exit;
    }

    if ($desktopUrl !== '' && !preg_match('#^(https?:)?//#i', $desktopUrl)) {
        $desktopUrl = url($desktopUrl);
    }
    if ($desktopLightUrl !== '' && !preg_match('#^(https?:)?//#i', $desktopLightUrl)) {
        $desktopLightUrl = url($desktopLightUrl);
    }
    if ($mobileUrl !== '' && !preg_match('#^(https?:)?//#i', $mobileUrl)) {
        $mobileUrl = url($mobileUrl);
    }

    // Draft / Publish / Discard actions
    $draftAction = (string) ($_POST['draft_action'] ?? '');

    if ($draftAction === 'save_draft') {
        $draftData = [
            'label' => $label,
            'desktop_video_url' => $desktopUrl,
            'desktop_light_video_url' => $desktopLightUrl,
            'mobile_video_url' => $mobileUrl,
            'desktop_video_file' => $desktopFile,
            'desktop_light_video_file' => $desktopLightFile,
            'mobile_video_file' => $mobileFile,
            'poster_image' => $posterImage,
        ];
        $saved = draft_save('home_hero_video', $id, $draftData, (int) ($adminUser['id'] ?? 0));
        admin_flash($saved ? 'success' : 'danger', $saved ? 'Draft saved. Open Live Preview to see it. The live site is unchanged until you Publish.' : 'Failed to save draft.');
        header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id . '&draft_reload=1' : '?draft_reload=1'));
        exit;
    }

    if ($draftAction === 'discard_draft') {
        draft_discard('home_hero_video', $id);
        admin_flash('warning', 'Draft discarded. Preview reverted to the published state.');
        header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id . '&draft_reload=1' : '?draft_reload=1'));
        exit;
    }

    if ($draftAction === 'publish_draft') {
        $published = draft_publish('home_hero_video', $id, function ($pdo, int $entityId, array $draftData): bool {
            $entityId = max(0, $entityId);
            if ($entityId > 0) {
                return db_execute(
                    $pdo,
                    'UPDATE home_hero_videos SET label = :label, desktop_video_url = :desktop_url, desktop_light_video_url = :desktop_light_url, mobile_video_url = :mobile_url, desktop_video_file = :desktop_file, desktop_light_video_file = :desktop_light_file, mobile_video_file = :mobile_file, poster_image = :poster_image WHERE id = :id',
                    [
                        ':label' => (string) ($draftData['label'] ?? ''),
                        ':desktop_url' => (string) ($draftData['desktop_video_url'] ?? ''),
                        ':desktop_light_url' => (string) ($draftData['desktop_light_video_url'] ?? ''),
                        ':mobile_url' => (string) ($draftData['mobile_video_url'] ?? ''),
                        ':desktop_file' => (string) ($draftData['desktop_video_file'] ?? ''),
                        ':desktop_light_file' => (string) ($draftData['desktop_light_video_file'] ?? ''),
                        ':mobile_file' => (string) ($draftData['mobile_video_file'] ?? ''),
                        ':poster_image' => (string) ($draftData['poster_image'] ?? ''),
                        ':id' => $entityId,
                    ]
                );
            }
            return false;
        });

        cms_invalidate_home_hero_videos_cache();
        admin_flash($published ? 'success' : 'danger', $published ? 'Draft published to the live site.' : 'Publish failed. No draft exists for this record.');
        header('Location: home-hero-video.php' . ($id > 0 ? '?edit=' . $id . '&draft_reload=1' : '?draft_reload=1'));
        exit;
    }

    if ($id > 0) {
        db_execute(
            $pdo,
            'UPDATE home_hero_videos SET label = :label, desktop_video_url = :desktop_url, desktop_light_video_url = :desktop_light_url, mobile_video_url = :mobile_url, desktop_video_file = :desktop_file, desktop_light_video_file = :desktop_light_file, mobile_video_file = :mobile_file, poster_image = :poster_image, sort_order = :sort_order, is_active = :is_active WHERE id = :id',
            [
                ':label' => $label,
                ':desktop_url' => $desktopUrl,
                ':desktop_light_url' => $desktopLightUrl,
                ':mobile_url' => $mobileUrl,
                ':desktop_file' => $desktopFile,
                ':desktop_light_file' => $desktopLightFile,
                ':mobile_file' => $mobileFile,
                ':poster_image' => $posterImage,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
                ':id' => $id,
            ]
        );
        admin_flash('success', 'Hero video updated.');
    } else {
        db_execute(
            $pdo,
            'INSERT INTO home_hero_videos (label, desktop_video_url, desktop_light_video_url, mobile_video_url, desktop_video_file, desktop_light_video_file, mobile_video_file, poster_image, sort_order, is_active) VALUES (:label, :desktop_url, :desktop_light_url, :mobile_url, :desktop_file, :desktop_light_file, :mobile_file, :poster_image, :sort_order, :is_active)',
            [
                ':label' => $label,
                ':desktop_url' => $desktopUrl,
                ':desktop_light_url' => $desktopLightUrl,
                ':mobile_url' => $mobileUrl,
                ':desktop_file' => $desktopFile,
                ':desktop_light_file' => $desktopLightFile,
                ':mobile_file' => $mobileFile,
                ':poster_image' => $posterImage,
                ':sort_order' => $sortOrder,
                ':is_active' => $isActive,
            ]
        );
        admin_flash('success', 'Hero video added.');
    }

    cms_invalidate_home_hero_videos_cache();
    header('Location: home-hero-video.php');
    exit;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_hero_videos ORDER BY sort_order ASC, id ASC') : [];

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-4"><?= $formData['id'] ? 'Edit Hero Video' : 'Add New Hero Video' ?></h5>
      <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:0.5rem;" data-section-preview='{"content_type":"home_hero_video","entity_id":<?= (int) $formData['id'] ?>}'>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_poster_image" value="<?= e((string) $formData['poster_image']) ?>">
        <input type="hidden" name="existing_desktop_video_file" value="<?= e((string) $formData['desktop_video_file']) ?>">
        <input type="hidden" name="existing_desktop_light_video_file" value="<?= e((string) $formData['desktop_light_video_file']) ?>">
        <input type="hidden" name="existing_mobile_video_file" value="<?= e((string) $formData['mobile_video_file']) ?>">

        <div class="form-group">
          <label class="form-label">Label (optional)</label>
          <input type="text" name="label" class="form-control" value="<?= e((string) $formData['label']) ?>" placeholder="e.g. Main Hero Video">
        </div>

        <div class="form-group">
          <label class="form-label">Desktop Video URL (optional)</label>
          <input type="text" name="desktop_video_url" class="form-control" value="<?= e((string) $formData['desktop_video_url']) ?>" placeholder="https://example.com/video.mp4">
          <small class="text-muted">Default: https://jaikvik.in/lab/mybrand_video/mybrandvideo</small>
        </div>

        <div class="form-group">
          <label class="form-label">Upload Desktop Video File (optional)</label>
          <input type="file" name="desktop_video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
          <small class="text-muted">Allowed: mp4, webm, mov (max 50MB). Uploaded file takes priority over the URL.</small>
          <?php if ((string) $formData['desktop_video_file'] !== ''): ?>
            <div class="mt-2">
              <video src="<?= e(url((string) $formData['desktop_video_file'])) ?>" muted controls playsinline preload="metadata" style="width:100%;max-height:160px;border-radius:12px;border:1px solid var(--border);"></video>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Desktop Light / Low-Data Video URL (optional)</label>
          <input type="text" name="desktop_light_video_url" class="form-control" value="<?= e((string) $formData['desktop_light_video_url']) ?>" placeholder="https://example.com/video-light.mp4">
          <small class="text-muted">Used when the visitor has a slow connection. Default: mobile video URL.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Upload Desktop Light Video File (optional)</label>
          <input type="file" name="desktop_light_video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
          <small class="text-muted">Allowed: mp4, webm, mov (max 50MB). Uploaded file takes priority over the URL.</small>
          <?php if ((string) $formData['desktop_light_video_file'] !== ''): ?>
            <div class="mt-2">
              <video src="<?= e(url((string) $formData['desktop_light_video_file'])) ?>" muted controls playsinline preload="metadata" style="width:100%;max-height:160px;border-radius:12px;border:1px solid var(--border);"></video>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Mobile Video URL (optional)</label>
          <input type="text" name="mobile_video_url" class="form-control" value="<?= e((string) $formData['mobile_video_url']) ?>" placeholder="https://example.com/video-mobile.mp4">
        </div>

        <div class="form-group">
          <label class="form-label">Upload Mobile Video File (optional)</label>
          <input type="file" name="mobile_video_file" class="form-control" accept="video/mp4,video/webm,video/quicktime">
          <small class="text-muted">Allowed: mp4, webm, mov (max 50MB). Uploaded file takes priority over the URL.</small>
          <?php if ((string) $formData['mobile_video_file'] !== ''): ?>
            <div class="mt-2">
              <video src="<?= e(url((string) $formData['mobile_video_file'])) ?>" muted controls playsinline preload="metadata" style="width:100%;max-height:160px;border-radius:12px;border:1px solid var(--border);"></video>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int) $formData['sort_order'] ?>">
          <small class="text-muted">The first active record (lowest sort order) is used on the homepage hero.</small>
        </div>

        <div class="form-group">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int) $formData['is_active']) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Poster Image (optional)</label>
          <input type="file" name="poster_image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if ((string) $formData['poster_image'] !== ''): ?>
            <div class="mt-3">
              <img src="<?= e(url((string) $formData['poster_image'])) ?>" alt="" style="width:100%;max-height:160px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
            </div>
          <?php endif; ?>
        </div>

        <?php if ((string) $formData['desktop_video_url'] !== ''): ?>
          <div class="form-group">
            <label class="form-label">Current Video Preview</label>
            <video src="<?= e(url((string) $formData['desktop_video_url'])) ?>" muted controls playsinline preload="metadata" style="width:100%;max-height:200px;border-radius:12px;border:1px solid var(--border);"></video>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <div class="d-flex gap-3 flex-wrap">
            <button class="btn btn-primary-modern"><?= $formData['id'] ? 'Update Hero Video' : 'Add Hero Video' ?></button>
            <?php if ($formData['id']): ?>
              <button type="submit" name="draft_action" value="save_draft" class="btn btn-warning">
                <i class="bi bi-pencil-square"></i> Save Draft
              </button>
              <?php if (draft_has('home_hero_video', (int) $formData['id'])): ?>
                <button type="submit" name="draft_action" value="publish_draft" class="btn btn-success">
                  <i class="bi bi-rocket-takeoff"></i> Publish
                </button>
                <button type="submit" name="draft_action" value="discard_draft" class="btn btn-outline-danger" onclick="return confirm('Discard this draft? The preview will revert to the published state.');">
                  <i class="bi bi-arrow-counterclockwise"></i> Discard Draft
                </button>
              <?php endif; ?>
              <a href="home-hero-video.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Hero Videos (<?= count($rows) ?>)</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">Export</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Preview</th>
              <th>Label</th>
              <th>Status</th>
              <th>Order</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td>
                <?php if ((string) ($row['desktop_video_url'] ?? '') !== ''): ?>
                  <video src="<?= e(url((string) $row['desktop_video_url'])) ?>" muted playsinline preload="metadata" style="width:130px;height:70px;object-fit:cover;border-radius:10px;border:1px solid var(--border);"></video>
                <?php else: ?>
                  <span class="text-muted">No video</span>
                <?php endif; ?>
              </td>
              <td><?= e((string) ($row['label'] ?: 'Untitled')) ?></td>
              <td>
                <span class="status-badge <?= ((int) $row['is_active']) === 1 ? 'status-active' : 'status-inactive' ?>">
                  <?= ((int) $row['is_active']) === 1 ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td><?= (int) $row['sort_order'] ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a href="home-hero-video.php?edit=<?= (int) $row['id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete this hero video?');">
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
              <td colspan="5" class="text-center text-muted py-4">No hero videos found. Add your first hero video.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>