<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Shop Content';
$pdo = db();

$keys = [
  'shop_landing_title',
  'shop_landing_description',
  'shop_landing_subtitle',
  'shop_category_subtitle',
  'shop_subcategory_fallback_description',
  'shop_related_products_title',
];

$defaults = [
  'shop_landing_title' => 'Build Your Own Private Label Personal Care Products',
  'shop_landing_description' => 'Indulge in our extensive range of 200+ products meticulously crafted with naturally derived and organic ingredients. We take pride in our cruelty-free approach, ensuring none of our products are ever tested on animals. Curious to experience the quality firsthand? Immerse yourself in the world of mybrandplease.com and elevate your personal care line with confidence. Discover the opportunity to purchase samples and embrace the "TRY BEFORE YOU BUY" philosophy.',
  'shop_landing_subtitle' => 'Explore Our Private Label Products Online Below!',
  'shop_category_subtitle' => 'Shop our Product Samples Below!',
  'shop_subcategory_fallback_description' => 'Explore premium products in this sub-category and build your private label range with confidence.',
  'shop_related_products_title' => 'Related Products',
];

$values = $defaults;

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();

  foreach ($keys as $key) {
    $val = trim((string) ($_POST[$key] ?? ''));
    $st = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $st->execute([':k' => $key, ':v' => $val]);
    cms_invalidate_settings_cache($key);
  }

  admin_flash('success', 'Shop content updated.');
  header('Location: shop-content.php');
  exit;
}

if ($pdo) {
  $in = "'" . implode("','", array_map(static fn($v) => str_replace("'", "''", $v), $keys)) . "'";
  $rows = db_fetch_all($pdo, "SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ($in)");
  foreach ($rows as $row) {
    $k = (string) ($row['setting_key'] ?? '');
    if (array_key_exists($k, $values) && (string) ($row['setting_value'] ?? '') !== '') {
      $values[$k] = (string) $row['setting_value'];
    }
  }
}

include __DIR__ . '/_layout_top.php';
?>

<div class="row g-4">
  <div class="col-lg-10">
    <div class="form-section">
      <h5 class="mb-3">Edit Shop Page Content</h5>
      <form method="post" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
          <label class="form-label">Landing Title</label>
          <input type="text" name="shop_landing_title" class="form-control" value="<?= e($values['shop_landing_title']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Landing Description</label>
          <textarea name="shop_landing_description" class="form-control" rows="6" required><?= e($values['shop_landing_description']) ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Landing Subtitle</label>
          <input type="text" name="shop_landing_subtitle" class="form-control" value="<?= e($values['shop_landing_subtitle']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Category Page Subtitle</label>
          <input type="text" name="shop_category_subtitle" class="form-control" value="<?= e($values['shop_category_subtitle']) ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Sub-category Fallback Description</label>
          <textarea name="shop_subcategory_fallback_description" class="form-control" rows="4" required><?= e($values['shop_subcategory_fallback_description']) ?></textarea>
          <small class="text-muted">Used when sub-category description is empty in Categories.</small>
        </div>

        <div class="form-group">
          <label class="form-label">Related Products Heading</label>
          <input type="text" name="shop_related_products_title" class="form-control" value="<?= e($values['shop_related_products_title']) ?>" required>
        </div>

        <div class="form-group">
          <button class="btn btn-primary-modern">Save Content</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
