<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

$adminUser = admin_require_auth();
$title = 'Home Offices Management';
$pdo = db();
if ($pdo) {
  cms_ensure_home_offices_registration_columns($pdo);
}

$officesContent = cms_get_home_offices_content();

$defaults = ['id'=>0,'country'=>'','company_name'=>'','address'=>'','email'=>'','phone'=>'','registration_label'=>'','registration_number'=>'','tax_label'=>'','tax_number'=>'','sort_order'=>0,'is_active'=>1,'image_path'=>''];
$formData = $defaults;
$editId = (int) ($_GET['edit'] ?? 0);
if ($pdo && $editId > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM home_offices WHERE id=:id LIMIT 1', [':id'=>$editId]);
  if ($row) { $formData = array_merge($defaults, $row); }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? 'save');

  if ($action === 'save_header_content') {
    $eyebrowText = trim((string) ($_POST['eyebrow_text'] ?? 'GLOBAL PRESENCE'));
    $headingText = trim((string) ($_POST['heading_text'] ?? 'Our Global Network'));
    $subheadingText = trim((string) ($_POST['subheading_text'] ?? 'Our Group of Companies & Global Registered Offices'));
    $introText = trim((string) ($_POST['intro_text'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("
      INSERT INTO home_offices_content
        (section_key, eyebrow_text, heading_text, subheading_text, intro_text, is_active)
      VALUES
        (:section_key, :eyebrow_text, :heading_text, :subheading_text, :intro_text, :is_active)
      ON DUPLICATE KEY UPDATE
        eyebrow_text = VALUES(eyebrow_text),
        heading_text = VALUES(heading_text),
        subheading_text = VALUES(subheading_text),
        intro_text = VALUES(intro_text),
        is_active = VALUES(is_active),
        updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
      ':section_key' => 'main',
      ':eyebrow_text' => $eyebrowText,
      ':heading_text' => $headingText,
      ':subheading_text' => $subheadingText,
      ':intro_text' => $introText,
      ':is_active' => $isActive,
    ]);
    cms_invalidate_home_offices_content_cache();
    admin_flash('success', 'Home Offices section headers updated successfully.');
    header('Location: home-offices.php');
    exit;
  }

  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM home_offices WHERE id=:id', [':id'=>$id]);
      cms_invalidate_home_offices_cache();
      admin_flash('success', 'Office deleted.');
    }
    header('Location: home-offices.php'); exit;
  }

  $id = (int) ($_POST['id'] ?? 0);
  $country = trim((string) ($_POST['country'] ?? ''));
  $companyName = trim((string) ($_POST['company_name'] ?? ''));
  $address = trim((string) ($_POST['address'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phone'] ?? ''));
  $registrationLabel = trim((string) ($_POST['registration_label'] ?? ''));
  $registrationNumber = trim((string) ($_POST['registration_number'] ?? ''));
  $taxLabel = trim((string) ($_POST['tax_label'] ?? ''));
  $taxNumber = trim((string) ($_POST['tax_number'] ?? ''));
  $sortOrder = (int) ($_POST['sort_order'] ?? 0);
  $isActive = isset($_POST['is_active']) ? 1 : 0;
  $imagePath = trim((string) ($_POST['existing_image_path'] ?? ''));

  if (!empty($_FILES['image']['name'])) {
    $stored = store_uploaded_image($_FILES['image'], 'offices', 5_000_000, false);
    if ($stored) {
      $imagePath = (string) $stored['public_path'];
    }
  }

  if ($country === '' || $address === '') {
    admin_flash('danger', 'Country and address are required.');
    header('Location: home-offices.php' . ($id > 0 ? '?edit=' . $id : '')); exit;
  }

  if ($id > 0) {
    db_execute($pdo, 'UPDATE home_offices SET country=:country, company_name=:company_name, address=:address, email=:email, phone=:phone, registration_label=:registration_label, registration_number=:registration_number, tax_label=:tax_label, tax_number=:tax_number, image_path=:image, sort_order=:sort_order, is_active=:is_active WHERE id=:id', [
      ':country'=>$country, ':company_name'=>$companyName, ':address'=>$address, ':email'=>$email, ':phone'=>$phone, ':registration_label'=>$registrationLabel, ':registration_number'=>$registrationNumber, ':tax_label'=>$taxLabel, ':tax_number'=>$taxNumber, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive, ':id'=>$id
    ]);
    admin_flash('success', 'Office updated.');
  } else {
    db_execute($pdo, 'INSERT INTO home_offices (country, company_name, address, email, phone, registration_label, registration_number, tax_label, tax_number, image_path, sort_order, is_active) VALUES (:country,:company_name,:address,:email,:phone,:registration_label,:registration_number,:tax_label,:tax_number,:image,:sort_order,:is_active)', [
      ':country'=>$country, ':company_name'=>$companyName, ':address'=>$address, ':email'=>$email, ':phone'=>$phone, ':registration_label'=>$registrationLabel, ':registration_number'=>$registrationNumber, ':tax_label'=>$taxLabel, ':tax_number'=>$taxNumber, ':image'=>$imagePath, ':sort_order'=>$sortOrder, ':is_active'=>$isActive
    ]);
    admin_flash('success', 'Office added.');
  }

  cms_invalidate_home_offices_cache();
  header('Location: home-offices.php'); exit;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM home_offices ORDER BY sort_order ASC, id ASC') : [];
$livePreviewUrl = url('index.php');
include __DIR__ . '/_layout_top.php';
?>

<!-- Section Header & Subheading Settings -->
<div class="widget-card mb-4">
  <div class="widget-header">
    <h5 class="widget-title"><i class="bi bi-gear me-2"></i>Our Offices Section Header Settings</h5>
  </div>
  <div class="widget-body p-3">
    <form method="post" action="" data-section-preview='{"content_type":"home_offices_content","entity_id":<?= (int) ($officesContent['id'] ?? 0) ?>}'>
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save_header_content">

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Eyebrow Text <span class="text-danger">*</span></label>
          <input class="form-control" name="eyebrow_text" value="<?= e((string) ($officesContent['eyebrow_text'] ?? 'GLOBAL PRESENCE')) ?>" required placeholder="e.g. GLOBAL PRESENCE">
          <small class="text-muted">Small badge text above title (e.g. "GLOBAL PRESENCE").</small>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Main Heading <span class="text-danger">*</span></label>
          <input class="form-control" name="heading_text" value="<?= e((string) ($officesContent['heading_text'] ?? 'Our Global Network')) ?>" required placeholder="e.g. Our Global Network">
          <small class="text-muted">Admin enters title text only. Automatically rendered as <code>~ Heading ~</code> on frontend.</small>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Sub Heading <span class="text-danger">*</span></label>
        <input class="form-control" name="subheading_text" value="<?= e((string) ($officesContent['subheading_text'] ?? 'Our Group of Companies & Global Registered Offices')) ?>" required placeholder="e.g. Our Group of Companies & Global Registered Offices">
        <small class="text-muted">Subheading text below main heading.</small>
      </div>

      <div class="mb-3">
        <label class="form-label">Intro Text <span class="text-danger">*</span></label>
        <textarea class="form-control" rows="2" name="intro_text" required placeholder="e.g. Our registered offices across key markets..."><?= e((string) ($officesContent['intro_text'] ?? '')) ?></textarea>
        <small class="text-muted">Paragraph text below subheading.</small>
      </div>

      <div class="mb-3 form-check">
        <input class="form-check-input" type="checkbox" name="is_active" id="headerActive" <?= ((int) ($officesContent['is_active'] ?? 1)) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="headerActive">Active</label>
      </div>

      <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Header Settings</button>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-5">
    <div class="form-section">
      <h5 class="mb-3"><?= $formData['id'] ? 'Edit Office' : 'Add Office' ?></h5>
      <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:0.5rem;" data-section-preview='{"content_type":"home_office","entity_id":<?= (int) $formData['id'] ?>}'>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $formData['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string) $formData['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Country</label>
          <input class="form-control" name="country" value="<?= e((string) $formData['country']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Company Name</label>
          <input class="form-control" name="company_name" value="<?= e((string) $formData['company_name']) ?>" placeholder="Company / registered entity name">
        </div>
        
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea class="form-control" rows="4" name="address" required><?= e((string) $formData['address']) ?></textarea>
        </div>
        
        <div class="form-group">
          <label class="form-label">Email</label>
          <input class="form-control" name="email" value="<?= e((string) $formData['email']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input class="form-control" name="phone" value="<?= e((string) $formData['phone']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Registration Label</label>
          <input class="form-control" name="registration_label" value="<?= e((string) $formData['registration_label']) ?>" placeholder="CIN, EIN, Company No">
        </div>

        <div class="form-group">
          <label class="form-label">Registration Number</label>
          <input class="form-control" name="registration_number" value="<?= e((string) $formData['registration_number']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Tax Label</label>
          <input class="form-control" name="tax_label" value="<?= e((string) $formData['tax_label']) ?>" placeholder="GST, TAX ID, UK VAT">
        </div>

        <div class="form-group">
          <label class="form-label">Tax Number</label>
          <input class="form-control" name="tax_number" value="<?= e((string) $formData['tax_number']) ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" class="form-control" name="sort_order" value="<?= (int) $formData['sort_order'] ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Status</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= ((int)$formData['is_active'])===1?'checked':'' ?>>
            <label class="form-check-label" for="isActive">Active</label>
          </div>
        </div>
        
        <div class="form-group">
          <label class="form-label">Flag / Image</label>
          <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp">
          <?php if((string)$formData['image_path']!==''): ?>
            <img src="<?= e(url((string)$formData['image_path'])) ?>" style="margin-top:10px;width:80px;height:80px;object-fit:cover;border-radius:50%;">
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <div class="d-flex gap-2">
            <button class="btn btn-primary-modern"><?= $formData['id']?'Update':'Add' ?></button>
            <?php if($formData['id']): ?>
              <a href="home-offices.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Office Locations</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">Export</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table" style="width: 100%;">
          <thead>
            <tr>
              <th>Country</th>
              <th>Company</th>
              <th>Email</th>
              <th>Registration</th>
              <th>Tax</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td><?= e((string)$r['country']) ?></td>
                <td><?= e((string)($r['company_name'] ?? '')) ?></td>
                <td><?= e((string)$r['email']) ?></td>
                <td><?= e(trim((string)($r['registration_label'] ?? '') . ' ' . (string)($r['registration_number'] ?? ''))) ?></td>
                <td><?= e(trim((string)($r['tax_label'] ?? '') . ' ' . (string)($r['tax_number'] ?? ''))) ?></td>
                <td>
                  <span class="status-badge <?= ((int)$r['is_active'])===1?'status-active':'status-inactive' ?>">
                    <?= ((int)$r['is_active'])===1?'Active':'Inactive' ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="home-offices.php?edit=<?= (int)$r['id'] ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" onsubmit="return confirm('Delete this office?');" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if(!$rows): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">No offices found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
