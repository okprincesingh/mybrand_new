<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Footer — Brand & Contact';
$pdo = db();

if ($pdo) {
    cms_ensure_footer_and_social_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_brand') {
        $tagline = trim((string) ($_POST['tagline'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $existingLogo = trim((string) ($_POST['existing_logo'] ?? 'uploads/logo/mybrandfooter.gif'));

        if ($tagline === '' || $phone === '' || $email === '') {
            admin_flash('danger', 'Tagline, Phone, and Email are required.');
            header('Location: footer-brand.php');
            exit;
        }

        if (!validate_email_value($email)) {
            admin_flash('danger', 'Please enter a valid email address.');
            header('Location: footer-brand.php');
            exit;
        }

        $logoPath = $existingLogo;
        if (!empty($_FILES['logo']['name'])) {
            $stored = store_uploaded_image($_FILES['logo'], 'logo', 5_000_000, false);
            if ($stored) {
                $logoPath = (string) $stored['public_path'];
            } else {
                admin_flash('danger', 'Failed to upload logo. Allowed formats: JPG, PNG, GIF, WEBP, SVG (max 5MB).');
                header('Location: footer-brand.php');
                exit;
            }
        }

        // Check if row 1 exists
        $exists = db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_brand WHERE id = 1');
        if ($exists > 0) {
            $stmt = $pdo->prepare('UPDATE footer_brand SET logo = :logo, tagline = :tagline, phone = :phone, email = :email, updated_at = NOW() WHERE id = 1');
            $stmt->execute([
                ':logo' => $logoPath,
                ':tagline' => $tagline,
                ':phone' => $phone,
                ':email' => $email,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO footer_brand (id, logo, tagline, phone, email) VALUES (1, :logo, :tagline, :phone, :email)');
            $stmt->execute([
                ':logo' => $logoPath,
                ':tagline' => $tagline,
                ':phone' => $phone,
                ':email' => $email,
            ]);
        }

        cms_invalidate_footer_cache();
        admin_flash('success', 'Footer brand and contact details updated successfully.');
        header('Location: footer-brand.php');
        exit;
    }
}

// Fetch current Brand info
$brand = cms_get_footer_brand(true);
$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4">
  <div class="col-12 col-xl-8">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-shop text-primary"></i> Footer Brand & Contact Information
        </h5>
        <div class="btn-group btn-group-sm">
          <a href="footer-links.php" class="btn btn-outline-secondary"><i class="bi bi-link-45deg me-1"></i> Footer Links</a>
          <a href="footer-bottom.php" class="btn btn-outline-secondary"><i class="bi bi-layout-text-window-reverse me-1"></i> Bottom Bar</a>
          <a href="social-media.php" class="btn btn-outline-secondary"><i class="bi bi-share me-1"></i> Social Media</a>
        </div>
      </div>
      <div class="card-body p-4">
        <form method="post" action="footer-brand.php" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_brand">
          <input type="hidden" name="existing_logo" value="<?= e((string) ($brand['logo'] ?? '')) ?>">

          <!-- Logo Field -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Footer Logo Image</label>
            <div class="d-flex flex-column flex-sm-row align-items-start gap-4 p-3 bg-light rounded-3 border">
              <div class="text-center">
                <?php if (!empty($brand['logo'])): ?>
                  <div class="p-3 bg-dark rounded border d-inline-block">
                    <img src="<?= e(url($brand['logo'])) ?>" alt="Footer Logo Preview" style="max-height: 70px; max-width: 220px; object-fit: contain;">
                  </div>
                  <div class="mt-1 small text-muted text-break" style="max-width: 220px;">
                    <?= e($brand['logo']) ?>
                  </div>
                <?php else: ?>
                  <div class="p-3 bg-secondary text-white rounded">No Logo</div>
                <?php endif; ?>
              </div>
              <div class="flex-grow-1">
                <label for="logoInput" class="form-label small text-muted mb-1">Replace / Upload Logo (Supports JPG, PNG, GIF including animated, WEBP, and SVG):</label>
                <input type="file" id="logoInput" name="logo" class="form-control mb-2" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*">
                <div class="form-text small">
                  <i class="bi bi-info-circle me-1"></i> Recommended format: Transparent PNG, animated GIF, or crisp SVG. Max size: 5MB.
                </div>
              </div>
            </div>
          </div>

          <!-- Tagline Field -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Brand Tagline / Lead Text <span class="text-danger">*</span></label>
            <textarea name="tagline" class="form-control" rows="3" placeholder="e.g. Get in touch with us however is most convenient for you." required><?= e($brand['tagline'] ?? '') ?></textarea>
            <div class="form-text small">This lead sentence is displayed right beneath the footer logo.</div>
          </div>

          <div class="row g-3 mb-4">
            <!-- Phone / WhatsApp Field -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Call / WhatsApp Number <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                <input type="text" name="phone" class="form-control" value="<?= e($brand['phone'] ?? '') ?>" placeholder="+91 (971) 700 4615" required>
              </div>
            </div>

            <!-- Email Field -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Contact Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" value="<?= e($brand['email'] ?? '') ?>" placeholder="info@mybrandplease.com" required>
              </div>
            </div>
          </div>

          <div class="pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Changes</button>
            <a href="footer-brand.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-info-circle text-primary"></i> Module Overview
        </h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">
          The footer brand block renders on every page of the website. Updating these values updates the logo, tagline text, and direct contact details across all views.
        </p>
        <ul class="list-group list-group-flush small">
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Last Updated</span>
            <strong><?= !empty($brand['updated_at']) ? date('M d, Y h:i A', strtotime($brand['updated_at'])) : 'Never' ?></strong>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Social Media</span>
            <a href="social-media.php" class="text-decoration-none">Manage Global Links &rarr;</a>
          </li>
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Footer Columns</span>
            <a href="footer-links.php" class="text-decoration-none">Manage 3 Link Groups &rarr;</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
