<?php
require_once __DIR__ . '/../includes/catalog.php';

$meta = [
  'title' => 'Mybrandplease | Product',
  'description' => 'Explore private label categories and product collections.',
  'canonical' => 'product/index.php'
];
include '../includes/head.php';
include '../includes/header.php';

$categories = catalog_categories();
?>

<section class="product-showcase-page section-spacing-120">
  <div class="container container-1352">
    <div class="product-showcase-head text-center">
      <h2 class="theme-color-font">Build Your Own Private Label Personal Care Products</h2>
      <div class="product-showcase-line"></div>
      <p class="text-muted lh-base fs-17 word-spacing-6">
        Indulge in our extensive range of products crafted with naturally derived ingredients. Choose a category to preview the full collection flow and open filtered products instantly.
      </p>
      <h3>Explore Our Private Label Products Online Below!</h3>
    </div>

    <div class="product-showcase-grid">
      <?php foreach ($categories as $category): ?>
        <a class="product-showcase-card" href="<?php echo htmlspecialchars(catalog_shop_link($category['slug']), ENT_QUOTES, 'UTF-8'); ?>">
          <img src="<?php echo htmlspecialchars(url($category['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>">
          <span class="product-showcase-overlay">
            <strong><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <small><?php echo htmlspecialchars($category['description'], ENT_QUOTES, 'UTF-8'); ?></small>
            <span class="product-showcase-cta">Explore Collection</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
