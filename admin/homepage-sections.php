<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '../../includes/cms_homepage_sections.php';

$activeTab = $_GET['tab'] ?? 'working_process';

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $tab = $_POST['tab'] ?? 'working_process';
  $action = $_POST['action'] ?? '';
  
  if ($action === 'save_working_process' && $tab === 'working_process') {
    $id = (int) ($_POST['id'] ?? 0);
    $titleSmall = trim((string) ($_POST['title_small'] ?? ''));
    $titleLarge = trim((string) ($_POST['title_large'] ?? ''));
    $text = trim((string) ($_POST['text'] ?? ''));
    $href = trim((string) ($_POST['href'] ?? 'contact.php'));
    $altText = trim((string) ($_POST['alt_text'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $imagePath = '';
    if (!empty($_FILES['image_path']['name'])) {
      $stored = store_uploaded_image($_FILES['image_path'], 'home/working-process', 5_000_000, false);
      if ($stored) {
        $imagePath = $stored['public_path'];
      }
    } elseif (!empty($_POST['existing_image_path'])) {
      $imagePath = $_POST['existing_image_path'];
    }
    
    if ($imagePath === '') {
      $errorMessage = 'Image is required';
    } else {
      $pdo = db();
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE home_working_process SET title_small = :title_small, title_large = :title_large, text = :text, href = :href, image_path = :image_path, alt_text = :alt_text, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
        $stmt->execute([
          ':title_small' => $titleSmall,
          ':title_large' => $titleLarge,
          ':text' => $text,
          ':href' => $href,
          ':image_path' => $imagePath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
          ':id' => $id,
        ]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO home_working_process (title_small, title_large, text, href, image_path, alt_text, sort_order, is_active) VALUES (:title_small, :title_large, :text, :href, :image_path, :alt_text, :sort_order, :is_active)');
        $stmt->execute([
          ':title_small' => $titleSmall,
          ':title_large' => $titleLarge,
          ':text' => $text,
          ':href' => $href,
          ':image_path' => $imagePath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
        ]);
      }
      cms_invalidate_home_working_process_cache();
      $successMessage = 'Working process step saved successfully';
    }
  }
  
  if ($action === 'save_working_process_content' && $tab === 'working_process') {
    $eyebrowText = trim((string) ($_POST['eyebrow_text'] ?? ''));
    $titleSpanText = trim((string) ($_POST['title_span_text'] ?? ''));
    $titleText = trim((string) ($_POST['title_text'] ?? ''));
    $descriptionText = trim((string) ($_POST['description_text'] ?? ''));
    $animationMode = trim((string) ($_POST['animation_mode'] ?? 'default'));
    $allowedAnimationModes = ['default', 'top', 'bottom', 'fade_in', 'zoom', 'spin'];
    if (!in_array($animationMode, $allowedAnimationModes, true)) {
      $animationMode = 'default';
    }
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $pdo = db();
    $stmt = $pdo->prepare("
INSERT INTO home_working_process_content
    (section_key, eyebrow_text, title_span_text, title_text, description_text, animation_mode, is_active)
VALUES
    (:section_key, :eyebrow_text, :title_span_text, :title_text, :description_text, :animation_mode, :is_active)
ON DUPLICATE KEY UPDATE
    eyebrow_text = VALUES(eyebrow_text),
    title_span_text = VALUES(title_span_text),
    title_text = VALUES(title_text),
    description_text = VALUES(description_text),
    animation_mode = VALUES(animation_mode),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP
");
    $stmt->execute([
      ':section_key' => 'main',
      ':eyebrow_text' => $eyebrowText,
      ':title_span_text' => $titleSpanText,
      ':title_text' => $titleText,
      ':description_text' => $descriptionText,
      ':animation_mode' => $animationMode,
      ':is_active' => $isActive,
    ]);
    cms_invalidate_home_working_process_content_cache();
    $successMessage = 'Working process content updated successfully';
  }
  
  if ($action === 'save_brand_builder' && $tab === 'brand_builder') {
    $sectionKey = trim((string) ($_POST['section_key'] ?? 'main'));
    $kickerText = trim((string) ($_POST['kicker_text'] ?? ''));
    $titleText = trim((string) ($_POST['title_text'] ?? ''));
    $subtitleText = trim((string) ($_POST['subtitle_text'] ?? ''));
    $primaryBtnText = trim((string) ($_POST['primary_btn_text'] ?? ''));
    $primaryBtnUrl = trim((string) ($_POST['primary_btn_url'] ?? ''));
    $secondaryBtnText = trim((string) ($_POST['secondary_btn_text'] ?? ''));
    $secondaryBtnUrl = trim((string) ($_POST['secondary_btn_url'] ?? ''));
    $stat1Number = trim((string) ($_POST['stat_1_number'] ?? ''));
    $stat1Label = trim((string) ($_POST['stat_1_label'] ?? ''));
    $stat2Number = trim((string) ($_POST['stat_2_number'] ?? ''));
    $stat2Label = trim((string) ($_POST['stat_2_label'] ?? ''));
    $stat3Number = trim((string) ($_POST['stat_3_number'] ?? ''));
    $stat3Label = trim((string) ($_POST['stat_3_label'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $pdo = db();
$stmt = $pdo->prepare("
INSERT INTO home_brand_builder (
    section_key,
    kicker_text,
    title_text,
    subtitle_text,
    primary_btn_text,
    primary_btn_url,
    secondary_btn_text,
    secondary_btn_url,
    stat_1_number,
    stat_1_label,
    stat_2_number,
    stat_2_label,
    stat_3_number,
    stat_3_label,
    is_active
)
VALUES (
    :section_key,
    :kicker_text,
    :title_text,
    :subtitle_text,
    :primary_btn_text,
    :primary_btn_url,
    :secondary_btn_text,
    :secondary_btn_url,
    :stat_1_number,
    :stat_1_label,
    :stat_2_number,
    :stat_2_label,
    :stat_3_number,
    :stat_3_label,
    :is_active
)
ON DUPLICATE KEY UPDATE
    kicker_text = VALUES(kicker_text),
    title_text = VALUES(title_text),
    subtitle_text = VALUES(subtitle_text),
    primary_btn_text = VALUES(primary_btn_text),
    primary_btn_url = VALUES(primary_btn_url),
    secondary_btn_text = VALUES(secondary_btn_text),
    secondary_btn_url = VALUES(secondary_btn_url),
    stat_1_number = VALUES(stat_1_number),
    stat_1_label = VALUES(stat_1_label),
    stat_2_number = VALUES(stat_2_number),
    stat_2_label = VALUES(stat_2_label),
    stat_3_number = VALUES(stat_3_number),
    stat_3_label = VALUES(stat_3_label),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP
");
    $stmt->execute([
      ':section_key' => $sectionKey,
      ':kicker_text' => $kickerText,
      ':title_text' => $titleText,
      ':subtitle_text' => $subtitleText,
      ':primary_btn_text' => $primaryBtnText,
      ':primary_btn_url' => $primaryBtnUrl,
      ':secondary_btn_text' => $secondaryBtnText,
      ':secondary_btn_url' => $secondaryBtnUrl,
      ':stat_1_number' => $stat1Number,
      ':stat_1_label' => $stat1Label,
      ':stat_2_number' => $stat2Number,
      ':stat_2_label' => $stat2Label,
      ':stat_3_number' => $stat3Number,
      ':stat_3_label' => $stat3Label,
      ':is_active' => $isActive,
    ]);
    cms_invalidate_home_brand_builder_cache();
    $successMessage = 'Brand builder section saved successfully';
  }
  
  if ($action === 'save_brand_builder_item' && $tab === 'brand_builder') {
    $id = (int) ($_POST['id'] ?? 0);
    $wordText = trim((string) ($_POST['word_text'] ?? ''));
    $imageAlt = trim((string) ($_POST['image_alt'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $imagePath = '';
    if (!empty($_FILES['image_path']['name'])) {
      $stored = store_uploaded_image($_FILES['image_path'], 'home/brand-builder', 5_000_000, false);
      if ($stored) {
        $imagePath = $stored['public_path'];
      }
    } elseif (!empty($_POST['existing_image_path'])) {
      $imagePath = $_POST['existing_image_path'];
    }
    
    if ($wordText === '') {
      $errorMessage = 'Span / Word text is required';
    } elseif ($imagePath === '') {
      $errorMessage = 'Hero image is required';
    } else {
      $pdo = db();
      $section = db_fetch_one($pdo, 'SELECT id FROM home_brand_builder WHERE section_key = :k LIMIT 1', [':k' => 'main']);
      $sectionId = (int) ($section['id'] ?? 0);
      if ($sectionId <= 0) {
        $pdo->exec("INSERT INTO home_brand_builder (section_key, kicker_text, title_text, is_active) VALUES ('main', 'Just add your brand.', 'The modern way to build a brand', 1)");
        $sectionId = (int) $pdo->lastInsertId();
      }
      
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE home_brand_builder_items SET word_text = :word_text, image_path = :image_path, image_alt = :image_alt, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
        $stmt->execute([
          ':word_text' => $wordText,
          ':image_path' => $imagePath,
          ':image_alt' => $imageAlt,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
          ':id' => $id,
        ]);
        cms_invalidate_home_brand_builder_items_cache();
        $successMessage = 'Rotating word & image item updated successfully';
      } else {
        $stmt = $pdo->prepare('INSERT INTO home_brand_builder_items (section_id, word_text, image_path, image_alt, sort_order, is_active) VALUES (:section_id, :word_text, :image_path, :image_alt, :sort_order, :is_active)');
        $stmt->execute([
          ':section_id' => $sectionId,
          ':word_text' => $wordText,
          ':image_path' => $imagePath,
          ':image_alt' => $imageAlt,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
        ]);
        cms_invalidate_home_brand_builder_items_cache();
        $successMessage = 'Rotating word & image item added successfully';
      }
    }
  }
  
  if ($action === 'save_getting_started_content' && $tab === 'getting_started') {
    $headingText = trim((string) ($_POST['heading_text'] ?? "Here's How To Get Started"));
    $descriptionText = trim((string) ($_POST['description_text'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $pdo = db();
    $stmt = $pdo->prepare("
INSERT INTO home_getting_started_content
    (section_key, heading_text, description_text, is_active)
VALUES
    (:section_key, :heading_text, :description_text, :is_active)
ON DUPLICATE KEY UPDATE
    heading_text = VALUES(heading_text),
    description_text = VALUES(description_text),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP
");
    $stmt->execute([
      ':section_key' => 'main',
      ':heading_text' => $headingText,
      ':description_text' => $descriptionText,
      ':is_active' => $isActive,
    ]);
    cms_invalidate_home_getting_started_content_cache();
    $successMessage = 'Getting started section header & intro text updated successfully';
  }
  
  if ($action === 'save_getting_started' && $tab === 'getting_started') {
    $id = (int) ($_POST['id'] ?? 0);
    $stepNumber = trim((string) ($_POST['step_number'] ?? ''));
    $iconEmoji = trim((string) ($_POST['icon_emoji'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $learnMoreUrl = trim((string) ($_POST['learn_more_url'] ?? ''));
    $backImageAlt = trim((string) ($_POST['back_image_alt'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $backImagePath = '';
    if (!empty($_FILES['back_image_path']['name'])) {
      $stored = store_uploaded_image($_FILES['back_image_path'], 'home/getting-started', 5_000_000, false);
      if ($stored) {
        $backImagePath = $stored['public_path'];
      }
    } elseif (!empty($_POST['existing_back_image_path'])) {
      $backImagePath = $_POST['existing_back_image_path'];
    }
    
    if ($backImagePath === '') {
      $errorMessage = 'Back image is required';
    } else {
      $pdo = db();
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE home_getting_started SET step_number = :step_number, icon_emoji = :icon_emoji, title = :title, description = :description, learn_more_url = :learn_more_url, back_image_path = :back_image_path, back_image_alt = :back_image_alt, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
        $stmt->execute([
          ':step_number' => $stepNumber,
          ':icon_emoji' => $iconEmoji,
          ':title' => $title,
          ':description' => $description,
          ':learn_more_url' => $learnMoreUrl,
          ':back_image_path' => $backImagePath,
          ':back_image_alt' => $backImageAlt,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
          ':id' => $id,
        ]);
        cms_invalidate_home_getting_started_cache();
        $successMessage = 'Getting started step updated successfully';
      } else {
        $stmt = $pdo->prepare('INSERT INTO home_getting_started (step_number, icon_emoji, title, description, learn_more_url, back_image_path, back_image_alt, sort_order, is_active) VALUES (:step_number, :icon_emoji, :title, :description, :learn_more_url, :back_image_path, :back_image_alt, :sort_order, :is_active)');
        $stmt->execute([
          ':step_number' => $stepNumber,
          ':icon_emoji' => $iconEmoji,
          ':title' => $title,
          ':description' => $description,
          ':learn_more_url' => $learnMoreUrl,
          ':back_image_path' => $backImagePath,
          ':back_image_alt' => $backImageAlt,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
        ]);
        cms_invalidate_home_getting_started_cache();
        $successMessage = 'Getting started step added successfully';
      }
    }
  }
  
  if ($action === 'save_marquee' && $tab === 'marquee') {
    $stripKey = trim((string) ($_POST['strip_key'] ?? ''));
    $items = trim((string) ($_POST['items'] ?? ''));
    $brandText = trim((string) ($_POST['brand_text'] ?? ''));
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $pdo = db();
$stmt = $pdo->prepare("
INSERT INTO home_marquee_strips
(
    strip_key,
    items,
    brand_text,
    is_active
)
VALUES
(
    :strip_key,
    :items,
    :brand_text,
    :is_active
)
ON DUPLICATE KEY UPDATE
    items = VALUES(items),
    brand_text = VALUES(brand_text),
    is_active = VALUES(is_active),
    updated_at = CURRENT_TIMESTAMP
");
    $stmt->execute([
      ':strip_key' => $stripKey,
      ':items' => $items,
      ':brand_text' => $brandText,
      ':is_active' => $isActive,
    ]);
    cms_invalidate_home_marquee_strips_cache();
    $successMessage = 'Marquee strip saved successfully';
  }
  
  if ($action === 'save_partner_logo' && $tab === 'partner_logos') {
    $id = (int) ($_POST['id'] ?? 0);
    $altText = trim((string) ($_POST['alt_text'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $logoPath = '';
    if (!empty($_FILES['logo_path']['name'])) {
      $stored = store_uploaded_image($_FILES['logo_path'], 'home/partner-logos', 5_000_000, false);
      if ($stored) {
        $logoPath = $stored['public_path'];
      }
    } elseif (!empty($_POST['existing_logo_path'])) {
      $logoPath = $_POST['existing_logo_path'];
    }
    
    if ($logoPath === '') {
      $errorMessage = 'Logo image is required';
    } else {
      $pdo = db();
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE home_partner_logos SET logo_path = :logo_path, alt_text = :alt_text, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
        $stmt->execute([
          ':logo_path' => $logoPath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
          ':id' => $id,
        ]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO home_partner_logos (logo_path, alt_text, sort_order, is_active) VALUES (:logo_path, :alt_text, :sort_order, :is_active)');
        $stmt->execute([
          ':logo_path' => $logoPath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
        ]);
      }
      cms_invalidate_home_partner_logos_cache();
      $successMessage = 'Partner logo saved successfully';
    }
  }
  
  if ($action === 'save_certification_logo' && $tab === 'certification_logos') {
    $id = (int) ($_POST['id'] ?? 0);
    $altText = trim((string) ($_POST['alt_text'] ?? ''));
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $logoPath = '';
    if (!empty($_FILES['logo_path']['name'])) {
      $stored = store_uploaded_image($_FILES['logo_path'], 'home/certification-logos', 5_000_000, false);
      if ($stored) {
        $logoPath = $stored['public_path'];
      }
    } elseif (!empty($_POST['existing_logo_path'])) {
      $logoPath = $_POST['existing_logo_path'];
    }
    
    if ($logoPath === '') {
      $errorMessage = 'Logo image is required';
    } else {
      $pdo = db();
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE home_certification_logos SET logo_path = :logo_path, alt_text = :alt_text, sort_order = :sort_order, is_active = :is_active WHERE id = :id');
        $stmt->execute([
          ':logo_path' => $logoPath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
          ':id' => $id,
        ]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO home_certification_logos (logo_path, alt_text, sort_order, is_active) VALUES (:logo_path, :alt_text, :sort_order, :is_active)');
        $stmt->execute([
          ':logo_path' => $logoPath,
          ':alt_text' => $altText,
          ':sort_order' => $sortOrder,
          ':is_active' => $isActive,
        ]);
      }
      cms_invalidate_home_certification_logos_cache();
      $successMessage = 'Certification logo saved successfully';
    }
  }
  
  if ($action === 'delete' && $tab === 'working_process') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      $pdo = db();
      $stmt = $pdo->prepare('DELETE FROM home_working_process WHERE id = :id');
      $stmt->execute([':id' => $id]);
      cms_invalidate_home_working_process_cache();
      $successMessage = 'Working process step deleted';
    }
  }
  
  if ($action === 'delete' && $tab === 'partner_logos') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      $pdo = db();
      $stmt = $pdo->prepare('DELETE FROM home_partner_logos WHERE id = :id');
      $stmt->execute([':id' => $id]);
      cms_invalidate_home_partner_logos_cache();
      $successMessage = 'Partner logo deleted';
    }
  }
  
  if ($action === 'delete' && $tab === 'certification_logos') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      $pdo = db();
      $stmt = $pdo->prepare('DELETE FROM home_certification_logos WHERE id = :id');
      $stmt->execute([':id' => $id]);
      cms_invalidate_home_certification_logos_cache();
      $successMessage = 'Certification logo deleted';
    }
  }
  
  if ($action === 'delete' && ($tab === 'getting_started')) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      $pdo = db();
      $stmt = $pdo->prepare('DELETE FROM home_getting_started WHERE id = :id');
      $stmt->execute([':id' => $id]);
      cms_invalidate_home_getting_started_cache();
      $successMessage = 'Getting started step deleted';
    }
  }
  
  if ($action === 'delete' && ($tab === 'brand_builder_item' || ($tab === 'brand_builder' && ($_POST['item_type'] ?? '') === 'brand_builder_item'))) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      $pdo = db();
      $stmt = $pdo->prepare('DELETE FROM home_brand_builder_items WHERE id = :id');
      $stmt->execute([':id' => $id]);
      cms_invalidate_home_brand_builder_items_cache();
      $successMessage = 'Rotating word & image item deleted successfully';
    }
  }
}

$pdo = db();
$workingProcessSteps = db_fetch_all($pdo, 'SELECT * FROM home_working_process ORDER BY sort_order ASC, id ASC');
$workingProcessContent = cms_get_home_working_process_content();
$brandBuilder = db_fetch_one($pdo, 'SELECT * FROM home_brand_builder WHERE section_key = :k LIMIT 1', [':k' => 'main']);
$brandBuilderItemsAdmin = db_fetch_all($pdo, 'SELECT bi.* FROM home_brand_builder_items bi INNER JOIN home_brand_builder bb ON bb.id = bi.section_id WHERE bb.section_key = :k ORDER BY bi.sort_order ASC, bi.id ASC', [':k' => 'main']);
if (empty($brandBuilderItemsAdmin)) {
  $brandBuilderItemsAdmin = db_fetch_all($pdo, 'SELECT * FROM home_brand_builder_items ORDER BY sort_order ASC, id ASC');
}
$gettingStartedSteps = db_fetch_all($pdo, 'SELECT * FROM home_getting_started ORDER BY sort_order ASC, id ASC');
$gettingStartedContent = cms_get_home_getting_started_content();
$marqueeStrip = db_fetch_one($pdo, 'SELECT * FROM home_marquee_strips WHERE strip_key = :k LIMIT 1', [':k' => 'working_process_services']);
$partnerLogos = db_fetch_all($pdo, 'SELECT * FROM home_partner_logos ORDER BY sort_order ASC, id ASC');
$certificationLogos = db_fetch_all($pdo, 'SELECT * FROM home_certification_logos ORDER BY sort_order ASC, id ASC');

$editBrandBuilderItem = null;
if ($activeTab === 'brand_builder' && isset($_GET['edit_item_id'])) {
  $editItemId = (int) $_GET['edit_item_id'];
  $editBrandBuilderItem = db_fetch_one($pdo, 'SELECT * FROM home_brand_builder_items WHERE id = :id LIMIT 1', [':id' => $editItemId]);
}

$editWorkingProcess = null;
if ($activeTab === 'working_process' && isset($_GET['edit_id'])) {
  $editId = (int) $_GET['edit_id'];
  $editWorkingProcess = db_fetch_one($pdo, 'SELECT * FROM home_working_process WHERE id = :id LIMIT 1', [':id' => $editId]);
}

$editPartnerLogo = null;
if ($activeTab === 'partner_logos' && isset($_GET['edit_id'])) {
  $editId = (int) $_GET['edit_id'];
  $editPartnerLogo = db_fetch_one($pdo, 'SELECT * FROM home_partner_logos WHERE id = :id LIMIT 1', [':id' => $editId]);
}

$editCertificationLogo = null;
if ($activeTab === 'certification_logos' && isset($_GET['edit_id'])) {
  $editId = (int) $_GET['edit_id'];
  $editCertificationLogo = db_fetch_one($pdo, 'SELECT * FROM home_certification_logos WHERE id = :id LIMIT 1', [':id' => $editId]);
}

$editGettingStarted = null;
$showGettingStartedForm = false;
if ($activeTab === 'getting_started') {
  if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    $editGettingStarted = db_fetch_one($pdo, 'SELECT * FROM home_getting_started WHERE id = :id LIMIT 1', [':id' => $editId]);
    if ($editGettingStarted) {
      $showGettingStartedForm = true;
    }
  } elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
    $showGettingStartedForm = true;
  }
}

$title = 'Homepage Sections Management';
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

<div class="tab-content" id="homepageTabContent">

  <!-- ===================== WORKING PROCESS ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'working_process' ? 'show active' : ''; ?>" id="working-process" role="tabpanel">

    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Working Process Content</h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" data-section-preview='{"content_type":"home_working_process_content","entity_id":0}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_working_process_content">
          <input type="hidden" name="tab" value="working_process">
          <input type="hidden" name="section_key" value="main">

          <div class="mb-3">
            <label class="form-label">Span Text (Eyebrow) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="eyebrow_text" value="<?php echo htmlspecialchars($workingProcessContent['eyebrow_text'] ?? 'Private Label', ENT_QUOTES, 'UTF-8'); ?>" required>
            <small class="text-muted">Small label text above the heading (e.g. "Private Label").</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Span Text (Heading) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title_span_text" value="<?php echo htmlspecialchars($workingProcessContent['title_span_text'] ?? 'Why launch', ENT_QUOTES, 'UTF-8'); ?>" required>
            <small class="text-muted">Span text at the start of the heading (e.g. "Why launch"). Rendered before the line break.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Main Heading <span class="text-danger">*</span></label>
            <textarea class="form-control" name="title_text" rows="2" required><?php echo htmlspecialchars($workingProcessContent['title_text'] ?? 'your own brand', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="text-muted">Main heading text after the span (e.g. "your own brand"). HTML allowed for line breaks.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description_text" rows="3" required><?php echo htmlspecialchars($workingProcessContent['description_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="text-muted">Paragraph text below the heading.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Card Animation <span class="text-danger">*</span></label>
            <select class="form-select" name="animation_mode" required>
              <?php
              $animationModes = [
                'default' => 'Default',
                'top' => 'Top',
                'bottom' => 'Bottom',
                'fade_in' => 'Fade In',
                'zoom' => 'Zoom',
                'spin' => 'Spin',
              ];
              $currentAnimationMode = $workingProcessContent['animation_mode'] ?? 'default';
              foreach ($animationModes as $modeKey => $modeLabel): ?>
                <option value="<?php echo htmlspecialchars($modeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentAnimationMode === $modeKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($modeLabel, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Choose the animation style for the Working Process cards.</small>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="wpc_active" <?php echo ($workingProcessContent['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="wpc_active">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Update Content</button>
        </form>
      </div>
    </div>

    <div class="widget-card mt-4">
      <div class="widget-header">
        <h5 class="widget-title"><?php echo $editWorkingProcess ? 'Edit Working Process Step' : 'Add New Working Process Step'; ?></h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" enctype="multipart/form-data" data-section-preview='{"content_type":"home_working_process","entity_id":<?= (int) ($editWorkingProcess['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_working_process">
          <input type="hidden" name="tab" value="working_process">
          <?php if ($editWorkingProcess): ?>
            <input type="hidden" name="id" value="<?php echo $editWorkingProcess['id']; ?>">
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Title Small <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title_small" value="<?php echo htmlspecialchars($editWorkingProcess['title_small'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Title Large <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="title_large" value="<?php echo htmlspecialchars($editWorkingProcess['title_large'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="text" rows="3" required><?php echo htmlspecialchars($editWorkingProcess['text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Link URL</label>
              <input type="text" class="form-control" name="href" value="<?php echo htmlspecialchars($editWorkingProcess['href'] ?? 'contact.php', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Alt Text <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="alt_text" value="<?php echo htmlspecialchars($editWorkingProcess['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Image <?php if (!$editWorkingProcess): ?><span class="text-danger">*</span><?php endif; ?></label>
              <input type="file" class="form-control" name="image_path" <?php echo $editWorkingProcess ? '' : 'required'; ?> accept="image/*">
              <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($editWorkingProcess['image_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($editWorkingProcess && !empty($editWorkingProcess['image_path'])): ?>
                <img src="<?php echo url($editWorkingProcess['image_path']); ?>" alt="" class="img-thumbnail mt-2" style="max-height: 100px;">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?php echo $editWorkingProcess['sort_order'] ?? 0; ?>">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="wp_active" <?php echo ($editWorkingProcess['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="wp_active">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Save Step</button>
          <a href="?tab=working_process" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>

    <div class="widget-card mt-4">
      <div class="widget-header">
        <h5 class="widget-title">Working Process Steps</h5>
      </div>
      <div class="widget-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Order</th>
                <th>Title</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($workingProcessSteps as $step): ?>
                <tr>
                  <td><?php echo $step['sort_order']; ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($step['title_small'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($step['title_large'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($step['text'], 0, 100), ENT_QUOTES, 'UTF-8'); ?>...</small>
                  </td>
                  <td><img src="<?php echo url($step['image_path']); ?>" alt="" style="max-height: 50px;"></td>
                  <td><?php echo $step['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                  <td>
                    <a href="?tab=working_process&edit_id=<?php echo $step['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="tab" value="working_process">
                      <input type="hidden" name="id" value="<?php echo $step['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <!-- ===================== END WORKING PROCESS ===================== -->

  <!-- ===================== BRAND BUILDER ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'brand_builder' ? 'show active' : ''; ?>" id="brand-builder" role="tabpanel">

    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Brand Builder Section</h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" data-section-preview='{"content_type":"home_brand_builder","entity_id":<?= (int) ($brandBuilder['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_brand_builder">
          <input type="hidden" name="tab" value="brand_builder">

          <div class="mb-3">
            <label class="form-label">Kicker Text</label>
            <textarea class="form-control" name="kicker_text" rows="2"><?php echo htmlspecialchars($brandBuilder['kicker_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="text-muted">HTML allowed</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Title Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="title_text" rows="3" required><?php echo htmlspecialchars($brandBuilder['title_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="text-muted">HTML allowed. Use <code><span class="brand-builder__changing-word" data-brand-builder-word>word</span></code> for rotating words.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Subtitle Text</label>
            <input type="text" class="form-control" name="subtitle_text" value="<?php echo htmlspecialchars($brandBuilder['subtitle_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Primary Button Text</label>
              <input type="text" class="form-control" name="primary_btn_text" value="<?php echo htmlspecialchars($brandBuilder['primary_btn_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Primary Button URL</label>
              <input type="text" class="form-control" name="primary_btn_url" value="<?php echo htmlspecialchars($brandBuilder['primary_btn_url'] ?? 'shop.php', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Secondary Button Text</label>
              <input type="text" class="form-control" name="secondary_btn_text" value="<?php echo htmlspecialchars($brandBuilder['secondary_btn_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Secondary Button URL</label>
              <input type="text" class="form-control" name="secondary_btn_url" value="<?php echo htmlspecialchars($brandBuilder['secondary_btn_url'] ?? 'services.php', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <h5 class="mt-4 mb-3">Statistics</h5>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 1 Number</label>
              <input type="text" class="form-control" name="stat_1_number" value="<?php echo htmlspecialchars($brandBuilder['stat_1_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 1 Label</label>
              <input type="text" class="form-control" name="stat_1_label" value="<?php echo htmlspecialchars($brandBuilder['stat_1_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 2 Number</label>
              <input type="text" class="form-control" name="stat_2_number" value="<?php echo htmlspecialchars($brandBuilder['stat_2_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 2 Label</label>
              <input type="text" class="form-control" name="stat_2_label" value="<?php echo htmlspecialchars($brandBuilder['stat_2_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 3 Number</label>
              <input type="text" class="form-control" name="stat_3_number" value="<?php echo htmlspecialchars($brandBuilder['stat_3_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Stat 3 Label</label>
              <input type="text" class="form-control" name="stat_3_label" value="<?php echo htmlspecialchars($brandBuilder['stat_3_label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="bb_active" <?php echo ($brandBuilder['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="bb_active">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Save Brand Builder Section</button>
        </form>
      </div>
    </div>

    <!-- Rotating Words & Images Section -->
    <div class="widget-card mt-4" id="brandBuilderItemFormCard">
      <div class="widget-header">
        <h5 class="widget-title"><?php echo $editBrandBuilderItem ? 'Edit Rotating Word & Image Pair' : 'Add New Rotating Word & Image Pair'; ?></h5>
        <?php if ($editBrandBuilderItem): ?>
          <div class="widget-actions">
            <a href="?tab=brand_builder#brandBuilderItemsTableCard" class="btn btn-secondary btn-sm">Cancel Edit</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_brand_builder_item">
          <input type="hidden" name="tab" value="brand_builder">
          <?php if ($editBrandBuilderItem): ?>
            <input type="hidden" name="id" value="<?php echo $editBrandBuilderItem['id']; ?>">
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Span / Rotating Word Text <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="word_text" value="<?php echo htmlspecialchars($editBrandBuilderItem['word_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. skin care" required>
              <small class="text-muted">This text appears highlighted in the rotating title span</small>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Image Alt Text</label>
              <input type="text" class="form-control" name="image_alt" value="<?php echo htmlspecialchars($editBrandBuilderItem['image_alt'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Skin care product category">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Hero Image <?php if (!$editBrandBuilderItem): ?><span class="text-danger">*</span><?php endif; ?></label>
              <input type="file" class="form-control" name="image_path" <?php echo $editBrandBuilderItem ? '' : 'required'; ?> accept="image/*">
              <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($editBrandBuilderItem['image_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($editBrandBuilderItem && !empty($editBrandBuilderItem['image_path'])): ?>
                <div class="mt-2">
                  <small class="text-muted d-block mb-1">Current Image:</small>
                  <img src="<?php echo url($editBrandBuilderItem['image_path']); ?>" alt="" class="img-thumbnail" style="max-height: 90px; object-fit: cover;">
                </div>
              <?php endif; ?>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?php echo $editBrandBuilderItem['sort_order'] ?? (count($brandBuilderItemsAdmin) + 1); ?>">
            </div>
            <div class="col-md-3 mb-3 d-flex align-items-end">
              <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" name="is_active" id="bb_item_active" <?php echo ($editBrandBuilderItem['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="bb_item_active">Active</label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary"><?php echo $editBrandBuilderItem ? 'Update Pair' : 'Add Pair'; ?></button>
          <?php if ($editBrandBuilderItem): ?>
            <a href="?tab=brand_builder#brandBuilderItemsTableCard" class="btn btn-secondary">Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Table of Rotating Pairs -->
    <div class="widget-card mt-4" id="brandBuilderItemsTableCard">
      <div class="widget-header">
        <h5 class="widget-title">Rotating Words & Images Sequence</h5>
      </div>
      <div class="widget-body p-3">
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th style="width: 60px;">#</th>
                <th style="width: 100px;">Image</th>
                <th>Span / Word Text</th>
                <th>Alt Text</th>
                <th style="width: 100px;">Sort Order</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($brandBuilderItemsAdmin)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    No rotating words & images found. Add a new pair using the form above.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($brandBuilderItemsAdmin as $index => $item): ?>
                  <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td>
                      <img src="<?php echo url($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['image_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="img-thumbnail" style="height: 50px; width: 50px; object-fit: cover;">
                    </td>
                    <td>
                      <strong><span class="badge bg-light text-dark fs-6 font-monospace">&lt;span&gt;<?php echo htmlspecialchars($item['word_text'], ENT_QUOTES, 'UTF-8'); ?>&lt;/span&gt;</span></strong>
                    </td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($item['image_alt'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                    <td><?php echo (int) $item['sort_order']; ?></td>
                    <td>
                      <?php if ($item['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="?tab=brand_builder&edit_item_id=<?php echo $item['id']; ?>#brandBuilderItemFormCard" class="btn btn-sm btn-primary me-1"><i class="bi bi-pencil"></i> Edit</a>
                      <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this rotating word & image pair?')">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="tab" value="brand_builder">
                        <input type="hidden" name="item_type" value="brand_builder_item">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <!-- ===================== END BRAND BUILDER ===================== -->

  <!-- ===================== GETTING STARTED ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'getting_started' ? 'show active' : ''; ?>" id="getting-started" role="tabpanel">

    <!-- Getting Started Section Header & Intro (Update-Only) -->
    <div class="widget-card mb-4">
      <div class="widget-header">
        <h5 class="widget-title">Getting Started Section Header & Intro Text</h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" data-section-preview='{"content_type":"home_getting_started_content","entity_id":<?= (int) ($gettingStartedContent['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_getting_started_content">
          <input type="hidden" name="tab" value="getting_started">

          <div class="mb-3">
            <label class="form-label">Heading Text <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="heading_text" value="<?php echo htmlspecialchars($gettingStartedContent['heading_text'] ?? "Here's How To Get Started", ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Here's How To Get Started" required>
            <small class="text-muted">Enter the heading text. The formatting (<code>~ &lt;em&gt;Heading&lt;/em&gt; ~</code>) will be automatically applied on the homepage.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Intro Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description_text" rows="3" placeholder="Enter intro description..." required><?php echo htmlspecialchars($gettingStartedContent['description_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="gs_content_active" <?php echo ($gettingStartedContent['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="gs_content_active">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Save Section Header & Intro</button>
        </form>
      </div>
    </div>

    <div class="widget-card <?php echo $showGettingStartedForm ? '' : 'd-none'; ?>" id="gettingStartedFormCard">
      <div class="widget-header">
        <h5 class="widget-title" id="gettingStartedFormTitle"><?php echo $editGettingStarted ? 'Edit Getting Started Step' : 'Add New Getting Started Step'; ?></h5>
        <?php if ($editGettingStarted): ?>
          <div class="widget-actions">
            <a href="?tab=getting_started" class="btn btn-secondary btn-sm">Cancel Edit</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" enctype="multipart/form-data" data-section-preview='{"content_type":"home_getting_started","entity_id":<?= (int) ($editGettingStarted['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_getting_started">
          <input type="hidden" name="tab" value="getting_started">
          <?php if ($editGettingStarted): ?>
            <input type="hidden" name="id" value="<?php echo $editGettingStarted['id']; ?>">
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Step Number <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="step_number" value="<?php echo htmlspecialchars($editGettingStarted['step_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. 01" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Icon Emoji <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="icon_emoji" value="<?php echo htmlspecialchars($editGettingStarted['icon_emoji'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. 🎨" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($editGettingStarted['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Order Sample & Determine Products" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description" rows="3" placeholder="Describe this step..." required><?php echo htmlspecialchars($editGettingStarted['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Learn More URL <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="learn_more_url" value="<?php echo htmlspecialchars($editGettingStarted['learn_more_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. how-it-works.php#define-offerings" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Back Image <?php if (!$editGettingStarted): ?><span class="text-danger">*</span><?php endif; ?></label>
              <input type="file" class="form-control" name="back_image_path" <?php echo $editGettingStarted ? '' : 'required'; ?> accept="image/*">
              <input type="hidden" name="existing_back_image_path" value="<?php echo htmlspecialchars($editGettingStarted['back_image_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($editGettingStarted && !empty($editGettingStarted['back_image_path'])): ?>
                <img src="<?php echo url($editGettingStarted['back_image_path']); ?>" alt="" class="img-thumbnail mt-2" style="max-height: 100px;">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Back Image Alt Text <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="back_image_alt" value="<?php echo htmlspecialchars($editGettingStarted['back_image_alt'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Order samples and determine products" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?php echo $editGettingStarted['sort_order'] ?? 0; ?>">
            </div>
            <div class="col-md-6 mb-3">
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="gs_active" <?php echo ($editGettingStarted['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="gs_active">Active</label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary" id="gettingStartedSubmitBtn"><?php echo $editGettingStarted ? 'Update Step' : 'Add Step'; ?></button>
          <?php if ($editGettingStarted): ?>
            <a href="?tab=getting_started" class="btn btn-secondary">Cancel</a>
          <?php else: ?>
            <button type="button" class="btn btn-secondary" id="gettingStartedCancelBtn">Cancel</button>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="widget-card mt-4">
      <div class="widget-header">
        <h5 class="widget-title">Getting Started Steps</h5>
        <div class="widget-actions">
          <button type="button" class="btn btn-primary-modern btn-sm" id="addGettingStartedBtn"><i class="bi bi-plus-circle me-1"></i>Add New Step</button>
        </div>
      </div>
      <div class="widget-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Step</th>
                <th>Title</th>
                <th>Image</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($gettingStartedSteps)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-rocket-takeoff" style="font-size:1.5rem;display:block;margin-bottom:0.3rem;opacity:0.3;"></i>
                    No getting started steps yet. Click <strong>Add New Step</strong> to create one.
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($gettingStartedSteps as $step): ?>
                <tr>
                  <td><?php echo htmlspecialchars($step['step_number'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($step['icon_emoji'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <strong><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($step['description'], 0, 100), ENT_QUOTES, 'UTF-8'); ?>...</small>
                  </td>
                  <td><img src="<?php echo url($step['back_image_path']); ?>" alt="" style="max-height: 50px;"></td>
                  <td><?php echo $step['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                  <td>
                    <a href="?tab=getting_started&edit_id=<?php echo $step['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="tab" value="getting_started">
                      <input type="hidden" name="id" value="<?php echo $step['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <!-- ===================== END GETTING STARTED ===================== -->

  <!-- ===================== MARQUEE STRIP ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'marquee' ? 'show active' : ''; ?>" id="marquee" role="tabpanel">

    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Marquee Strip Settings</h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" data-section-preview='{"content_type":"home_marquee_strip","entity_id":<?= (int) ($marqueeStrip['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_marquee">
          <input type="hidden" name="tab" value="marquee">
          <input type="hidden" name="strip_key" value="working_process_services">

          <div class="mb-3">
            <label class="form-label">Items (comma-separated) <span class="text-danger">*</span></label>
            <textarea class="form-control" name="items" rows="2" required><?php echo htmlspecialchars($marqueeStrip['items'] ?? 'Skin Care,Hair Care,Body Care,Fragrances,Cosmetic Packaging', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="text-muted">Enter items separated by commas</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Brand Text</label>
            <input type="text" class="form-control" name="brand_text" value="<?php echo htmlspecialchars($marqueeStrip['brand_text'] ?? 'mybrandplease.com', ENT_QUOTES, 'UTF-8'); ?>">
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="is_active" id="marquee_active" <?php echo ($marqueeStrip['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="marquee_active">Active</label>
          </div>

          <button type="submit" class="btn btn-primary">Save Marquee Strip</button>
        </form>
      </div>
    </div>

  </div>
  <!-- ===================== END MARQUEE STRIP ===================== -->

  <!-- ===================== PARTNER LOGOS ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'partner_logos' ? 'show active' : ''; ?>" id="partner-logos" role="tabpanel">

    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title"><?php echo $editPartnerLogo ? 'Edit Partner Logo' : 'Add New Partner Logo'; ?></h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" enctype="multipart/form-data" data-section-preview='{"content_type":"home_partner_logo","entity_id":<?= (int) ($editPartnerLogo['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_partner_logo">
          <input type="hidden" name="tab" value="partner_logos">
          <?php if ($editPartnerLogo): ?>
            <input type="hidden" name="id" value="<?php echo $editPartnerLogo['id']; ?>">
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Logo Image <?php if (!$editPartnerLogo): ?><span class="text-danger">*</span><?php endif; ?></label>
              <input type="file" class="form-control" name="logo_path" <?php echo $editPartnerLogo ? '' : 'required'; ?> accept="image/*">
              <input type="hidden" name="existing_logo_path" value="<?php echo htmlspecialchars($editPartnerLogo['logo_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($editPartnerLogo && !empty($editPartnerLogo['logo_path'])): ?>
                <img src="<?php echo url($editPartnerLogo['logo_path']); ?>" alt="" class="img-thumbnail mt-2" style="max-height: 100px;">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Alt Text <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="alt_text" value="<?php echo htmlspecialchars($editPartnerLogo['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?php echo $editPartnerLogo['sort_order'] ?? 0; ?>">
            </div>
            <div class="col-md-6 mb-3">
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="pl_active" <?php echo ($editPartnerLogo['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="pl_active">Active</label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save Logo</button>
          <a href="?tab=partner_logos" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>

    <div class="widget-card mt-4">
      <div class="widget-header">
        <h5 class="widget-title">Partner Logos</h5>
      </div>
      <div class="widget-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Logo</th>
                <th>Alt Text</th>
                <th>Order</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($partnerLogos as $logo): ?>
                <tr>
                  <td><img src="<?php echo url($logo['logo_path']); ?>" alt="" style="max-height: 50px;"></td>
                  <td><?php echo htmlspecialchars($logo['alt_text'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo $logo['sort_order']; ?></td>
                  <td><?php echo $logo['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                  <td>
                    <a href="?tab=partner_logos&edit_id=<?php echo $logo['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="tab" value="partner_logos">
                      <input type="hidden" name="id" value="<?php echo $logo['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <!-- ===================== END PARTNER LOGOS ===================== -->

  <!-- ===================== CERTIFICATION LOGOS ===================== -->
  <div class="tab-pane fade <?php echo $activeTab === 'certification_logos' ? 'show active' : ''; ?>" id="certification-logos" role="tabpanel">

    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title"><?php echo $editCertificationLogo ? 'Edit Certification Logo' : 'Add New Certification Logo'; ?></h5>
      </div>
      <div class="widget-body p-3">
        <form method="POST" action="" enctype="multipart/form-data" data-section-preview='{"content_type":"home_certification_logo","entity_id":<?= (int) ($editCertificationLogo['id'] ?? 0) ?>}'>
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_certification_logo">
          <input type="hidden" name="tab" value="certification_logos">
          <?php if ($editCertificationLogo): ?>
            <input type="hidden" name="id" value="<?php echo $editCertificationLogo['id']; ?>">
          <?php endif; ?>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Logo Image <?php if (!$editCertificationLogo): ?><span class="text-danger">*</span><?php endif; ?></label>
              <input type="file" class="form-control" name="logo_path" <?php echo $editCertificationLogo ? '' : 'required'; ?> accept="image/*">
              <input type="hidden" name="existing_logo_path" value="<?php echo htmlspecialchars($editCertificationLogo['logo_path'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($editCertificationLogo && !empty($editCertificationLogo['logo_path'])): ?>
                <img src="<?php echo url($editCertificationLogo['logo_path']); ?>" alt="" class="img-thumbnail mt-2" style="max-height: 100px;">
              <?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Alt Text <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="alt_text" value="<?php echo htmlspecialchars($editCertificationLogo['alt_text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Sort Order</label>
              <input type="number" class="form-control" name="sort_order" value="<?php echo $editCertificationLogo['sort_order'] ?? 0; ?>">
            </div>
            <div class="col-md-6 mb-3">
              <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="cl_active" <?php echo ($editCertificationLogo['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="cl_active">Active</label>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save Logo</button>
          <a href="?tab=certification_logos" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>

    <div class="widget-card mt-4">
      <div class="widget-header">
        <h5 class="widget-title">Certification Logos</h5>
      </div>
      <div class="widget-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Logo</th>
                <th>Alt Text</th>
                <th>Order</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($certificationLogos as $logo): ?>
                <tr>
                  <td><img src="<?php echo url($logo['logo_path']); ?>" alt="" style="max-height: 50px;"></td>
                  <td><?php echo htmlspecialchars($logo['alt_text'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo $logo['sort_order']; ?></td>
                  <td><?php echo $logo['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                  <td>
                    <a href="?tab=certification_logos&edit_id=<?php echo $logo['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="tab" value="certification_logos">
                      <input type="hidden" name="id" value="<?php echo $logo['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
  <!-- ===================== END CERTIFICATION LOGOS ===================== -->

</div>

<script>
(function () {
  'use strict';
  var formCard = document.getElementById('gettingStartedFormCard');
  var addBtn = document.getElementById('addGettingStartedBtn');
  var cancelBtn = document.getElementById('gettingStartedCancelBtn');

  function showForm() {
    if (!formCard) return;
    formCard.classList.remove('d-none');
    formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function hideForm() {
    if (!formCard) return;
    formCard.classList.add('d-none');
  }

  if (addBtn) {
    addBtn.addEventListener('click', function (e) {
      e.preventDefault();
      // Reset the form to a clean "Add" state (no hidden id, empty fields)
      var form = formCard.querySelector('form');
      if (form) {
        form.reset();
        var idInput = form.querySelector('input[name="id"]');
        if (idInput) idInput.remove();
        var existingPath = form.querySelector('input[name="existing_back_image_path"]');
        if (existingPath) existingPath.value = '';
        var fileInput = form.querySelector('input[name="back_image_path"]');
        if (fileInput) {
          fileInput.required = true;
          fileInput.value = '';
        }
        var titleEl = document.getElementById('gettingStartedFormTitle');
        if (titleEl) titleEl.textContent = 'Add New Getting Started Step';
        var submitBtn = document.getElementById('gettingStartedSubmitBtn');
        if (submitBtn) submitBtn.textContent = 'Add Step';
        var previewAttr = form.getAttribute('data-section-preview');
        if (previewAttr) {
          try {
            var cfg = JSON.parse(previewAttr);
            cfg.entity_id = 0;
            form.setAttribute('data-section-preview', JSON.stringify(cfg));
          } catch (err) {}
        }
      }
      showForm();
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      hideForm();
    });
  }
})();
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
