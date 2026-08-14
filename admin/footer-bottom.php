<?php
require_once __DIR__ . '/_init.php';

$adminUser = admin_require_auth();
$title = 'Footer — Bottom Bar';
$pdo = db();

if ($pdo) {
    cms_ensure_footer_and_social_tables($pdo);
}

// Handle Form Submissions
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_bottom') {
        $copyrightText = trim((string) ($_POST['copyright_text'] ?? ''));
        $developerCreditText = trim((string) ($_POST['developer_credit_text'] ?? 'Developed and Maintained by'));
        $developerCreditLabel = trim((string) ($_POST['developer_credit_label'] ?? 'JTPL'));
        $developerCreditUrl = trim((string) ($_POST['developer_credit_url'] ?? 'https://jaikviktechnology.com/'));

        if ($copyrightText === '') {
            admin_flash('danger', 'Copyright text is required.');
            header('Location: footer-bottom.php');
            exit;
        }

        $exists = db_fetch_value($pdo, 'SELECT COUNT(*) FROM footer_bottom WHERE id = 1');
        if ($exists > 0) {
            $stmt = $pdo->prepare('UPDATE footer_bottom SET copyright_text = :copyright_text, developer_credit_text = :developer_credit_text, developer_credit_label = :developer_credit_label, developer_credit_url = :developer_credit_url, updated_at = NOW() WHERE id = 1');
            $stmt->execute([
                ':copyright_text' => $copyrightText,
                ':developer_credit_text' => $developerCreditText,
                ':developer_credit_label' => $developerCreditLabel,
                ':developer_credit_url' => $developerCreditUrl,
            ]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO footer_bottom (id, copyright_text, developer_credit_text, developer_credit_label, developer_credit_url) VALUES (1, :copyright_text, :developer_credit_text, :developer_credit_label, :developer_credit_url)');
            $stmt->execute([
                ':copyright_text' => $copyrightText,
                ':developer_credit_text' => $developerCreditText,
                ':developer_credit_label' => $developerCreditLabel,
                ':developer_credit_url' => $developerCreditUrl,
            ]);
        }

        cms_invalidate_footer_cache();
        admin_flash('success', 'Footer bottom bar updated successfully.');
        header('Location: footer-bottom.php');
        exit;
    }
}

// Fetch current bottom bar data
$bottom = cms_get_footer_bottom(true);
$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4">
  <div class="col-12 col-xl-8">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-layout-text-window-reverse text-primary"></i> Bottom Bar & Copyright Settings
        </h5>
        <div class="btn-group btn-group-sm">
          <a href="footer-brand.php" class="btn btn-outline-secondary"><i class="bi bi-shop me-1"></i> Brand & Contact</a>
          <a href="footer-links.php" class="btn btn-outline-secondary"><i class="bi bi-link-45deg me-1"></i> Footer Links</a>
          <a href="social-media.php" class="btn btn-outline-secondary"><i class="bi bi-share me-1"></i> Social Media</a>
        </div>
      </div>
      <div class="card-body p-4">
        <form method="post" action="footer-bottom.php">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_bottom">

          <!-- Copyright Text -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Copyright Line Text <span class="text-danger">*</span></label>
            <input type="text" name="copyright_text" class="form-control" value="<?= e($bottom['copyright_text'] ?? '') ?>" placeholder="&copy; 2005-2026 NIMISHA IMPEX WORLDWIDE (P) LIMITED | All rights reserved" required>
            <div class="form-text small">HTML entities such as <code>&amp;copy;</code> are fully supported.</div>
          </div>

          <!-- Developer Credit Line -->
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Developer Credit Prefix</label>
              <input type="text" name="developer_credit_text" class="form-control" value="<?= e($bottom['developer_credit_text'] ?? 'Developed and Maintained by') ?>" placeholder="Developed and Maintained by">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Developer Name / Label</label>
              <input type="text" name="developer_credit_label" class="form-control" value="<?= e($bottom['developer_credit_label'] ?? 'JTPL') ?>" placeholder="JTPL">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold">Developer Website Link</label>
              <input type="text" name="developer_credit_url" class="form-control" value="<?= e($bottom['developer_credit_url'] ?? 'https://jaikviktechnology.com/') ?>" placeholder="https://jaikviktechnology.com/">
            </div>
          </div>

          <!-- Live Preview Box -->
          <div class="p-3 bg-dark text-white rounded mb-4">
            <div class="small text-muted mb-2 text-uppercase fw-semibold" style="letter-spacing:1px;">Frontend Render Preview:</div>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 small">
              <div><?= $bottom['copyright_text'] ?? '' ?></div>
              <div>
                <?= e($bottom['developer_credit_text'] ?? 'Developed and Maintained by') ?>
                <a href="<?= e($bottom['developer_credit_url'] ?? '#') ?>" target="_blank" class="text-info text-decoration-none fw-semibold">
                  <?= e($bottom['developer_credit_label'] ?? 'JTPL') ?>
                </a>
              </div>
            </div>
          </div>

          <div class="pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Bottom Bar</button>
            <a href="footer-bottom.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white py-3">
        <h6 class="card-title mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-info-circle text-primary"></i> Module Details
        </h6>
      </div>
      <div class="card-body">
        <p class="small text-muted mb-3">
          This controls the very bottom line of the footer below the trust badges. It displays copyright legal ownership and technical maintenance attribution.
        </p>
        <ul class="list-group list-group-flush small">
          <li class="list-group-item px-0 d-flex justify-content-between">
            <span class="text-muted">Last Updated</span>
            <strong><?= !empty($bottom['updated_at']) ? date('M d, Y h:i A', strtotime($bottom['updated_at'])) : 'Never' ?></strong>
          </li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
