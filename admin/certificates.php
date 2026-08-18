<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/our-certificates-template.php';
$adminUser = admin_require_auth();
$title = 'Certificates';
$pdo = db();

function admin_ensure_certificates_table(?PDO $pdo): bool
{
  if (!$pdo) {
    return false;
  }

  try {
    $pdo->exec("
      CREATE TABLE IF NOT EXISTS certificates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NULL,
        file_type ENUM('image','pdf') NOT NULL DEFAULT 'image',
        category VARCHAR(80) NOT NULL DEFAULT 'quality-standards',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_certificates_active_order (is_active, sort_order, id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columns = db_fetch_all($pdo, 'SHOW COLUMNS FROM certificates');
    $existing = array_fill_keys(array_map(static fn($row) => (string) $row['Field'], $columns), true);
    if (!isset($existing['file_path'])) {
      $pdo->exec("ALTER TABLE certificates ADD file_path VARCHAR(255) NULL AFTER image_path");
    }
    if (!isset($existing['file_type'])) {
      $pdo->exec("ALTER TABLE certificates ADD file_type ENUM('image','pdf') NOT NULL DEFAULT 'image' AFTER file_path");
    }
    if (!isset($existing['created_at'])) {
      $pdo->exec("ALTER TABLE certificates ADD created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
    if (!isset($existing['updated_at'])) {
      $pdo->exec("ALTER TABLE certificates ADD updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    return true;
  } catch (Throwable $e) {
    return false;
  }
}

function admin_store_certificate_file(array $file, string $subdir, int $maxBytes, bool $allowPdf = true): ?array
{
  $GLOBALS['admin_certificate_upload_error'] = '';
  $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($uploadError !== UPLOAD_ERR_OK) {
    $messages = [
      UPLOAD_ERR_INI_SIZE => 'The uploaded file is larger than the live server upload_max_filesize limit.',
      UPLOAD_ERR_FORM_SIZE => 'The uploaded file is larger than the form limit.',
      UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
      UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
      UPLOAD_ERR_NO_TMP_DIR => 'The live server is missing a PHP temporary upload folder.',
      UPLOAD_ERR_CANT_WRITE => 'The live server could not write the uploaded file.',
      UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];
    $GLOBALS['admin_certificate_upload_error'] = $messages[$uploadError] ?? 'Upload failed with error code ' . $uploadError . '.';
    return null;
  }

  if (($file['size'] ?? 0) > $maxBytes) {
    $GLOBALS['admin_certificate_upload_error'] = 'The uploaded file is larger than the allowed ' . (int) ($maxBytes / 1_000_000) . 'MB limit.';
    return null;
  }

  $tmp = $file['tmp_name'] ?? '';
  $name = (string) ($file['name'] ?? '');
  if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp) || $name === '') {
    $GLOBALS['admin_certificate_upload_error'] = 'The live server did not provide a valid uploaded file.';
    return null;
  }

  $base = basename($name);
  $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
  if ($extension === '') {
    $GLOBALS['admin_certificate_upload_error'] = 'The uploaded file has no extension.';
    return null;
  }

  $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
  $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
  if ($finfo) {
    finfo_close($finfo);
  }

  $imageInfo = function_exists('getimagesize') ? @getimagesize($tmp) : false;
  $commonImageExtensions = ['jpg', 'jpeg', 'jpe', 'png', 'webp', 'gif', 'bmp', 'avif', 'heic', 'heif', 'tif', 'tiff'];
  $isPdf = $allowPdf && $extension === 'pdf' && ($mime === '' || $mime === 'application/pdf' || str_starts_with($mime, 'application/'));
  $isImage = $extension !== 'svg' && (
    is_array($imageInfo)
    || str_starts_with($mime, 'image/')
    || ($mime === '' && in_array($extension, $commonImageExtensions, true))
    || in_array($extension, ['jpg', 'jpeg', 'jpe'], true)
  );
  if (!$isPdf && !$isImage) {
    $GLOBALS['admin_certificate_upload_error'] = 'The file does not look like a supported image or PDF. Detected MIME: ' . ($mime !== '' ? $mime : 'unknown') . '.';
    return null;
  }

  $targetDir = upload_storage_dir($subdir, false);
  if (!is_dir($targetDir) || !is_writable($targetDir)) {
    $GLOBALS['admin_certificate_upload_error'] = 'Upload folder is not writable: uploads/' . trim($subdir, "/\\") . '.';
    return null;
  }

  $newFileName = hash('sha256', random_bytes(32) . microtime(true) . $name) . '.' . $extension;
  $targetPath = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR . $newFileName;
  if (!move_uploaded_file($tmp, $targetPath)) {
    $GLOBALS['admin_certificate_upload_error'] = 'The uploaded file could not be moved into uploads/' . trim($subdir, "/\\") . '.';
    return null;
  }

  return [
    'public_path' => 'uploads/' . trim($subdir, "/\\") . '/' . $newFileName,
    'extension' => $extension,
    'file_type' => $isPdf ? 'pdf' : 'image',
    'mime_type' => $mime,
  ];
}

function admin_import_existing_certificate_files(PDO $pdo): void
{
  $existingCount = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM certificates');
  if ($existingCount > 0) {
    return;
  }

  $directory = dirname(__DIR__) . '/assets/imgs/our-certificates';
  $webDirectory = 'assets/imgs/our-certificates';
  if (!is_dir($directory)) {
    return;
  }

  $items = our_certificates_folder_items();
  $sort = 0;
  foreach ($items as $item) {
    $fileUrl = (string) ($item['file'] ?? '');
    $imageUrl = (string) ($item['image'] ?? '');
    $filePath = parse_url($fileUrl, PHP_URL_PATH);
    $imagePath = parse_url($imageUrl, PHP_URL_PATH);
    $fileName = $filePath ? basename(rawurldecode($filePath)) : '';
    $imageName = $imagePath ? basename(rawurldecode($imagePath)) : '';
    if ($fileName === '' || !is_file($directory . '/' . $fileName)) {
      continue;
    }
    $relativeFile = $webDirectory . '/' . rawurlencode($fileName);
    $relativeImage = ($imageName !== '' && is_file($directory . '/' . $imageName)) ? ($webDirectory . '/' . rawurlencode($imageName)) : $relativeFile;

    db_execute(
      $pdo,
      'INSERT INTO certificates (title, image_path, file_path, file_type, category, sort_order, is_active) VALUES (:title,:image_path,:file_path,:file_type,:category,:sort_order,1)',
      [
        ':title' => (string) ($item['title'] ?? our_certificates_title_from_filename($fileName)),
        ':image_path' => $relativeImage,
        ':file_path' => $relativeFile,
        ':file_type' => (string) ($item['type'] ?? 'image') === 'pdf' ? 'pdf' : 'image',
        ':category' => (string) ($item['category'] ?? 'quality-standards'),
        ':sort_order' => $sort,
      ]
    );
    $sort++;
  }
}

$tableReady = admin_ensure_certificates_table($pdo);
if ($tableReady) {
  admin_import_existing_certificate_files($pdo);
}
$defaults = [
  'id' => 0,
  'title' => '',
  'image_path' => '',
  'file_path' => '',
  'file_type' => 'image',
  'category' => 'quality-standards',
  'sort_order' => 0,
  'is_active' => 1,
];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);

if ($tableReady && $editId > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM certificates WHERE id = :id LIMIT 1', [':id' => $editId]);
  if ($row) {
    $formData = array_merge($defaults, $row);
  }
}

if ($tableReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? 'save');

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM certificates WHERE id = :id', [':id' => $id]);
      admin_flash('success', 'Certificate deleted.');
    }
    header('Location: certificates.php');
    exit;
  }

  $id = (int) ($_POST['id'] ?? 0);
  $titleIn = trim((string) ($_POST['title'] ?? ''));
  $category = trim((string) ($_POST['category'] ?? 'quality-standards'));
  $sortOrder = (int) ($_POST['sort_order'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;
  $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));
  $filePath = trim((string) ($_POST['existing_file_path'] ?? ''));
  $fileType = trim((string) ($_POST['existing_file_type'] ?? 'image'));

  if ($titleIn === '') {
    admin_flash('danger', 'Certificate title is required.');
    header('Location: certificates.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit;
  }

  if (!empty($_FILES['certificate_file']['name'])) {
    $stored = admin_store_certificate_file(
      $_FILES['certificate_file'],
      'certificates',
      12_000_000,
      true
    );
    if (!$stored) {
      $uploadError = (string) ($GLOBALS['admin_certificate_upload_error'] ?? '');
      admin_flash('danger', 'Certificate upload failed. ' . ($uploadError !== '' ? $uploadError : 'Use a valid image file or PDF up to 12MB.'));
      header('Location: certificates.php' . ($id > 0 ? '?edit=' . $id : ''));
      exit;
    }
    $filePath = (string) $stored['public_path'];
    $fileType = (string) $stored['file_type'];
    if ($fileType === 'image') {
      $imagePath = $filePath;
    }
  }

  if (!empty($_FILES['preview_image']['name'])) {
    $preview = admin_store_certificate_file($_FILES['preview_image'], 'certificates', 5_000_000, false);
    if (!$preview) {
      $uploadError = (string) ($GLOBALS['admin_certificate_upload_error'] ?? '');
      admin_flash('danger', 'Preview image upload failed. ' . ($uploadError !== '' ? $uploadError : 'Use a valid image file up to 5MB.'));
      header('Location: certificates.php' . ($id > 0 ? '?edit=' . $id : ''));
      exit;
    }
    $imagePath = (string) $preview['public_path'];
  }

  if ($filePath === '' && $imagePath !== '') {
    $filePath = $imagePath;
  }
  if ($imagePath === '' && $fileType === 'pdf') {
    $imagePath = $filePath;
  }
  if ($imagePath === '' || $filePath === '') {
    admin_flash('danger', 'Please upload a certificate file.');
    header('Location: certificates.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit;
  }

  if ($id > 0) {
    db_execute(
      $pdo,
      'UPDATE certificates SET title = :title, image_path = :image_path, file_path = :file_path, file_type = :file_type, category = :category, sort_order = :sort_order, is_active = :is_active WHERE id = :id',
      [
        ':title' => $titleIn,
        ':image_path' => $imagePath,
        ':file_path' => $filePath,
        ':file_type' => $fileType === 'pdf' ? 'pdf' : 'image',
        ':category' => $category,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
        ':id' => $id,
      ]
    );
    admin_flash('success', 'Certificate updated.');
  } else {
    db_execute(
      $pdo,
      'INSERT INTO certificates (title, image_path, file_path, file_type, category, sort_order, is_active) VALUES (:title,:image_path,:file_path,:file_type,:category,:sort_order,:is_active)',
      [
        ':title' => $titleIn,
        ':image_path' => $imagePath,
        ':file_path' => $filePath,
        ':file_type' => $fileType === 'pdf' ? 'pdf' : 'image',
        ':category' => $category,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
      ]
    );
    admin_flash('success', 'Certificate added.');
  }

  header('Location: certificates.php');
  exit;
}

$rows = $tableReady ? db_fetch_all($pdo, 'SELECT * FROM certificates ORDER BY sort_order ASC, id ASC') : [];

$livePreviewUrl = url('our-certificates.php');
include __DIR__ . '/_layout_top.php';
?>
<?php if (!$tableReady): ?>
  <div class="alert alert-danger">Certificates table could not be created. Please check database permissions.</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-4"><?= (int) $formData['id'] > 0 ? 'Edit Certificate' : 'Add New Certificate' ?></h5>
      <form method="post" enctype="multipart/form-data" class="d-grid gap-3" data-section-preview='{"content_type":"certificate","entity_id":<?= (int) ($formData['id'] ?? 0) ?>}'>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">
        <input type="hidden" name="existing_file_path" value="<?= e((string) ($formData['file_path'] ?: $formData['image_path'])) ?>">
        <input type="hidden" name="existing_file_type" value="<?= e((string) $formData['file_type']) ?>">

        <div>
          <label class="form-label">Certificate Title</label>
          <input class="form-control" name="title" value="<?= e((string) $formData['title']) ?>" required>
        </div>

        <div>
          <label class="form-label">Certificate File</label>
          <input class="form-control" type="file" name="certificate_file" accept="image/*,application/pdf" <?= (int) $formData['id'] > 0 ? '' : 'required' ?>>
          <small class="text-muted">Allowed: any valid image file or PDF. Max 12MB.</small>
        </div>

        <div>
          <label class="form-label">Preview Image for PDF (optional)</label>
          <input class="form-control" type="file" name="preview_image" accept="image/*">
        </div>

        <?php if ((string) $formData['image_path'] !== ''): ?>
          <div>
            <label class="form-label">Current Preview</label>
            <?php if ((string) $formData['file_type'] === 'pdf' && (string) $formData['image_path'] === (string) ($formData['file_path'] ?: $formData['image_path'])): ?>
              <div class="border rounded p-3 text-center"><i class="bi bi-filetype-pdf fs-1 text-danger"></i><div class="small text-muted">PDF without preview image</div></div>
            <?php else: ?>
              <img src="<?= e(url((string) $formData['image_path'])) ?>" alt="" style="width:140px;max-height:170px;object-fit:contain;border:1px solid var(--border);border-radius:10px;background:#fff;">
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div>
          <label class="form-label">Category</label>
          <select name="category" class="form-select">
            <option value="quality-standards" <?= $formData['category'] === 'quality-standards' ? 'selected' : '' ?>>Quality Standards</option>
            <option value="business-registration" <?= $formData['category'] === 'business-registration' ? 'selected' : '' ?>>Business Registration</option>
            <option value="regulatory" <?= $formData['category'] === 'regulatory' ? 'selected' : '' ?>>Regulatory</option>
          </select>
        </div>

        <div>
          <label class="form-label">Sort Order</label>
          <input class="form-control" type="number" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
        </div>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int) $formData['is_active']) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="isActive">Active</label>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-primary-modern"><?= (int) $formData['id'] > 0 ? 'Update Certificate' : 'Add Certificate' ?></button>
          <?php if ((int) $formData['id'] > 0): ?>
            <a href="certificates.php" class="btn btn-secondary-modern">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Certificates (<?= count($rows) ?>)</h5>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Preview</th>
              <th>Title</th>
              <th>Type</th>
              <th>Status</th>
              <th>Order</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td>
                  <?php if ((string) ($row['file_type'] ?? 'image') === 'pdf' && (string) ($row['image_path'] ?? '') === (string) (($row['file_path'] ?? '') ?: ($row['image_path'] ?? ''))): ?>
                    <i class="bi bi-filetype-pdf fs-2 text-danger"></i>
                  <?php else: ?>
                    <img src="<?= e(url((string) $row['image_path'])) ?>" alt="" style="width:74px;height:88px;object-fit:contain;border:1px solid var(--border);border-radius:8px;background:#fff;">
                  <?php endif; ?>
                </td>
                <td><?= e((string) $row['title']) ?></td>
                <td><?= e(strtoupper((string) ($row['file_type'] ?? 'image'))) ?></td>
                <td>
                  <span class="status-badge <?= ((int) $row['is_active']) === 1 ? 'status-active' : 'status-inactive' ?>">
                    <?= ((int) $row['is_active']) === 1 ? 'Active' : 'Inactive' ?>
                  </span>
                </td>
                <td><?= (int) $row['sort_order'] ?></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="certificates.php?edit=<?= (int) $row['id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this certificate?');">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                      <button class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No certificates added yet. The public page will use the existing folder certificates until you add records here.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
