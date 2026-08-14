<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Navbar — Logo Management';
$pdo = db();

if ($pdo) {
    cms_ensure_navbar_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_logo') {
        $eyebrowText = trim((string) ($_POST['eyebrow_text'] ?? ''));
        $brandText = trim((string) ($_POST['brand_text'] ?? ''));
        $taglineText = trim((string) ($_POST['tagline_text'] ?? ''));
        $logoLinkUrl = trim((string) ($_POST['logo_link_url'] ?? 'index.php'));
        $existingLogo = trim((string) ($_POST['existing_logo'] ?? 'uploads/logo/mybrandplease-1.gif'));

        if ($logoLinkUrl === '') {
            $logoLinkUrl = 'index.php';
        }

        $logoPath = $existingLogo;
        if (!empty($_FILES['logo']['name'])) {
            $stored = store_uploaded_image($_FILES['logo'], 'logo', 5_000_000, false);
            if ($stored) {
                $logoPath = (string) $stored['public_path'];
            } else {
                admin_flash('danger', 'Failed to upload logo image. Allowed formats: JPG, PNG, GIF (incl. animated), WEBP, SVG (max 5MB).');
                header('Location: navbar-logo.php');
                exit;
            }
        }

        // Check if row 1 exists
        $exists = db_fetch_value($pdo, 'SELECT COUNT(*) FROM nav_logo WHERE id = 1');
        if ($exists > 0) {
            $stmt = $pdo->prepare('UPDATE nav_logo SET logo_image = :logo_image, eyebrow_text = :eyebrow_text, brand_text = :brand_text, tagline_text = :tagline_text, logo_link_url = :logo_link_url, updated_at = NOW() WHERE id = 1');
            $stmt->execute([
                ':logo_image' => $logoPath,
                ':eyebrow_text' => $eyebrowText,
                ':brand_text' => $brandText,
                ':tagline_text' => $taglineText,
                ':logo_link_url' => $logoLinkUrl,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO nav_logo (id, logo_image, eyebrow_text, brand_text, tagline_text, logo_link_url) VALUES (1, :logo_image, :eyebrow_text, :brand_text, :tagline_text, :logo_link_url)');
            $stmt->execute([
                ':logo_image' => $logoPath,
                ':eyebrow_text' => $eyebrowText,
                ':brand_text' => $brandText,
                ':tagline_text' => $taglineText,
                ':logo_link_url' => $logoLinkUrl,
            ]);
        }

        cms_invalidate_nav_cache();
        admin_flash('success', 'Navbar logo and branding settings updated successfully.');
        header('Location: navbar-logo.php');
        exit;
    }
}

// Fetch current Navbar logo info
$logoData = cms_get_nav_logo(true);
$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4">
  <div class="col-12 col-xl-8">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-image text-primary"></i> Main Navigation Logo &amp; Branding
        </h5>
        <div class="btn-group btn-group-sm">
          <a href="navbar-management.php" class="btn btn-outline-secondary"><i class="bi bi-list-nested me-1"></i> Menu Items</a>
          <a href="<?= e($livePreviewUrl) ?>" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i> Live Site</a>
        </div>
      </div>
      <div class="card-body p-4">
        <form method="post" action="navbar-logo.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_logo">
          <input type="hidden" name="existing_logo" value="<?= e((string) ($logoData['logo_image'] ?? '')) ?>">

          <!-- Logo File Upload Field -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Logo Graphic / Animated GIF / SVG</label>
            <div class="d-flex flex-column flex-md-row align-items-start gap-4 p-3 bg-light rounded-3 border">
              <div class="text-center">
                <?php if (!empty($logoData['logo_image'])): ?>
                  <div class="p-3 bg-white rounded border d-inline-block shadow-sm" id="logoPreviewBox">
                    <img src="<?= e(url($logoData['logo_image'])) ?>" alt="Navbar Logo Preview" style="max-height: 80px; max-width: 240px; object-fit: contain;">
                  </div>
                  <div class="mt-2 small text-muted text-break" style="max-width: 240px;">
                    <code><?= e($logoData['logo_image']) ?></code>
                  </div>
                <?php else: ?>
                  <div class="p-3 bg-secondary text-white rounded">No Logo Selected</div>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <label for="logoInput" class="form-label small text-muted mb-1">Replace / Upload New Logo Image:</label>
                <input type="file" id="logoInput" name="logo" class="form-control mb-2" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*">
                <div class="form-text small">
                  <i class="bi bi-info-circle me-1"></i> Accepted formats: <strong>JPG, PNG, GIF (including animated), WEBP, SVG</strong>. Max file size: 5MB.
                </div>
              </div>
            </div>
          </div>

          <!-- Eyebrow Text -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Eyebrow Text (Optional)</label>
            <input type="text" name="eyebrow_text" class="form-control" value="<?= e($logoData['eyebrow_text'] ?? '') ?>" placeholder="e.g. For dedicated Private Label Support">
            <div class="form-text small">Small line above the main wordmark or logo title.</div>
          </div>

          <div class="row g-3 mb-3">
            <!-- Brand Text / Wordmark -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Brand Text / Alt Wordmark</label>
              <input type="text" name="brand_text" class="form-control" value="<?= e($logoData['brand_text'] ?? '') ?>" placeholder="e.g. mybrandplease.com">
              <div class="form-text small">The brand name used for the logo graphic alt text and accessibility labels.</div>
            </div>

            <!-- Logo Link URL -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Logo Link URL <span class="text-danger">*</span></label>
              <input type="text" name="logo_link_url" class="form-control" value="<?= e($logoData['logo_link_url'] ?? 'index.php') ?>" placeholder="index.php or /" required>
              <div class="form-text small">Destination URL when users click the navbar logo (defaults to <code>index.php</code>).</div>
            </div>
          </div>

          <!-- Tagline Text -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Tagline Text (Optional)</label>
            <input type="text" name="tagline_text" class="form-control" value="<?= e($logoData['tagline_text'] ?? '') ?>" placeholder="e.g. Your Vision | Our Expertise | Your Brand">
            <div class="form-text small">Secondary slogan or metadata line associated with the logo brand box.</div>
          </div>

          <div class="pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Logo Settings</button>
            <a href="navbar-logo.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-info-circle text-primary"></i> Format &amp; Specs Reference
        </h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">
          The main navigation logo is displayed in the sticky desktop navbar as well as inside the mobile slide-out drawer.
        </p>
        <ul class="list-group list-group-flush small">
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Supported Formats</span>
            <strong>SVG, GIF, PNG, WEBP, JPG</strong>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Animated GIFs</span>
            <span class="badge bg-success-subtle text-success">Fully Supported</span>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">MIME Type Checking</span>
            <span class="badge bg-primary-subtle text-primary">Active</span>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Last Updated</span>
            <strong><?= !empty($logoData['updated_at']) ? date('M d, Y h:i A', strtotime($logoData['updated_at'])) : 'Initial default' ?></strong>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Navbar Menu Items</span>
            <a href="navbar-management.php" class="text-decoration-none">Manage Links &rarr;</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
