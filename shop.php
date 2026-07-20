<?php
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/cms.php';
require_once __DIR__ . '/includes/content-loader.php';

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$subcategory = isset($_GET['subcategory']) ? rawurldecode(trim((string) $_GET['subcategory'])) : '';
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$isSearchResults = $search !== '';

$renderShopIntroHtml = static function (string $html, array $skipPhrases = []): string {
    $html = sanitize_rich_html($html);
    if ($html === '') {
        return '';
    }

    $html = preg_replace('/^\s*<h[1-6]\b[^>]*>[\s\S]*?<\/h[1-6]>\s*/i', '', $html) ?? $html;
    $blockContains = static function (string $block, string $needle): bool {
        $text = html_entity_decode(strip_tags($block), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return stripos($text, $needle) !== false;
    };

    foreach ($skipPhrases as $phrase) {
        $phrase = trim((string) $phrase);
        if ($phrase === '') {
            continue;
        }

        $html = preg_replace_callback('/\s*<h[1-6]\b[^>]*>[\s\S]*?<\/h[1-6]>\s*/i', static function (array $match) use ($phrase, $blockContains): string {
            return $blockContains($match[0], $phrase) ? ' ' : $match[0];
        }, $html) ?? $html;
    }

    $html = preg_replace_callback('/\s*<p\b[^>]*>[\s\S]*?<\/p>\s*/i', static function (array $match) use ($blockContains): string {
        return $blockContains($match[0], 'order samples') ? ' ' : $match[0];
    }, $html) ?? $html;
    $html = preg_replace_callback('/\s*<h[1-6]\b[^>]*>[\s\S]*?<\/h[1-6]>\s*/i', static function (array $match) use ($blockContains): string {
        return $blockContains($match[0], 'shop') && $blockContains($match[0], 'below') ? ' ' : $match[0];
    }, $html) ?? $html;

    return trim($html);
};

$categories = catalog_categories();
$shopLandingTitle = cms_get_setting('shop_landing_title', 'Build Your Own Private Label Personal Care Products') ?? 'Build Your Own Private Label Personal Care Products';
$shopLandingDescription = cms_get_setting('shop_landing_description', 'Indulge in our extensive range of 200+ products meticulously crafted with naturally derived and organic ingredients. We take pride in our cruelty-free approach, ensuring none of our products are ever tested on animals. Curious to experience the quality firsthand? Immerse yourself in the world of mybrandplease.com and elevate your personal care line with confidence. Discover the opportunity to purchase samples and embrace the "TRY BEFORE YOU BUY" philosophy.') ?? '';
$shopLandingSubtitle = cms_get_setting('shop_landing_subtitle', 'Explore Our Private Label Products Online Below!') ?? 'Explore Our Private Label Products Online Below!';
$shopCategorySubtitle = cms_get_setting('shop_category_subtitle', 'Shop our Product Samples Below!') ?? 'Shop our Product Samples Below!';
$shopRelatedTitle = cms_get_setting('shop_related_products_title', 'Related Products') ?? 'Related Products';
$shopSubcategoryFallbackDescription = cms_get_setting('shop_subcategory_fallback_description', 'Explore premium products in this sub-category and build your private label range with confidence.') ?? 'Explore premium products in this sub-category and build your private label range with confidence.';
$categoryCatalogAliases = [
    'especially-for-men' => 'men-s-care',
];
$categoryLandingContent = [
    'skin-care' => [
        'aliases' => ['skin-care', 'private-label-skin-care-products', 'private-label-skin-care'],
        'title' => 'Private Label Skin Care Products',
        'description' => 'mybrandplease.com offers a wide range of natural and organic private label skin care products that exfoliate, provides whitening & lightening, moisturise, recondition and repair the skin. Choose from a variety of skin care lines that offer specific solutions such as Environmental Defense, Age Defying and care for Blemish Prone skin. Our private label skin care products are made from professional, high-quality ingredients such as Vitamin C, Retinol, Glycolic Acid, Kojic Acid and Peptides.',
        'subtitle' => 'Find Natural Private Label Skin Care Products for your Clients by Shopping our Samples Below!',
        'children' => [
            'skin-care-environmental-defense',
            'skin-care-advanced',
            'skin-care-age-defying',
            'skin-care-peptides',
            'vitamin-c',
            'skin-care-brightening',
            'skin-care-super-fruits',
            'skin-care-marine-complex',
            'skin-care-blemish-prone-skin',
            'skin-care-botanical',
        ],
    ],
    'especially-for-men' => [
        'title' => 'Men’s Grooming And Skin Care Products.',
        'description' => 'mybrandplease.com has a specifically tailored line designed to meet the needs of men. Our private label men’s grooming and skin care products include shaving cream, after shave balm and lotions, and skin cream. These products clean and treat skin with herbal extracts and essential oils that infuse the skin with vitamins and minerals. Our products are pure and gentle for everyday use, but powerful enough for even the toughest guys.',
        'subtitle' => 'Create a private label men’s skin care and grooming product line for your brand – order samples today!',
    ],
    'hair-care' => [
        'aliases' => ['hair-care', 'private-label-hair-care-products', 'private-label-hair-care'],
        'children' => [
            'hair-care-bars',
            'hair-care-shampoo',
            'hair-care-conditioner',
            'hair-care-styling-products',
            'hair-care-treatment-products',
        ],
    ],
];

$categoryLandingAliases = [
    'skin-care' => ['private-label-skin-care-products', 'private-label-skin-care'],
    'hair-care' => ['private-label-hair-care-products', 'private-label-hair-care'],
    'body-care' => ['private-label-body-care-products', 'private-label-body-care'],
    'bathing-soaps' => ['private-label-bathing-soaps', 'private-label-soaps', 'bathing-soap'],
    'fragrances' => ['private-label-fragrances', 'private-label-perfumes'],
    'perfumes' => ['private-label-perfumes', 'private-label-fragrances'],
    'packaging' => ['cosmetic-packaging', 'private-label-packaging'],
    'especially-for-men' => ['men-s-care', 'private-label-men-care-products', 'private-label-mens-care-products'],
];

$resolveCategoryLandingKey = static function (?array $category) use ($categoryLandingContent, $categoryLandingAliases): ?string {
    if (!$category) {
        return null;
    }

    $categorySlug = (string) ($category['slug'] ?? '');
    if (isset($categoryLandingContent[$categorySlug])) {
        return $categorySlug;
    }

    $landingKeys = array_unique(array_merge(array_keys($categoryLandingContent), array_keys($categoryLandingAliases)));
    foreach ($landingKeys as $landingKey) {
        $aliases = array_merge(
            [(string) $landingKey],
            (array) ($categoryLandingContent[$landingKey]['aliases'] ?? []),
            (array) ($categoryLandingAliases[$landingKey] ?? [])
        );
        if (catalog_item_matches_aliases($category, $aliases)) {
            return (string) $landingKey;
        }
    }

    return null;
};

$resolveCategoryLandingContent = static function (?array $category) use ($categoryLandingContent, $resolveCategoryLandingKey): ?array {
    $landingKey = $resolveCategoryLandingKey($category);
    return $landingKey !== null ? ($categoryLandingContent[$landingKey] ?? null) : null;
};

$activeCategory = $category !== '' ? catalog_find_category($category) : null;
$activeSubcategory = null;
$categorySpecificHeading = '';
$categorySpecificSubtitle = '';
$activeCategoryLanding = null;
$catalogSourceCategory = $activeCategory;
$activeCategoryLandingKey = null;
if ($activeCategory && !empty($activeCategory['id'])) {
    $cid = (int) $activeCategory['id'];
    $categorySpecificHeading = (string) (cms_get_setting('category_shop_heading_' . $cid, '') ?? '');
    $categorySpecificSubtitle = (string) (cms_get_setting('category_shop_subtitle_' . $cid, '') ?? '');
    $activeCategoryLandingKey = $resolveCategoryLandingKey($activeCategory);
    $activeCategoryLanding = $resolveCategoryLandingContent($activeCategory);
    $catalogSourceSlug = $categoryCatalogAliases[(string) ($activeCategory['slug'] ?? '')] ?? ($activeCategoryLandingKey ?? '');
    if ($catalogSourceSlug !== '') {
        $resolvedSourceCategory = catalog_find_category($catalogSourceSlug);
        if ($resolvedSourceCategory) {
            $catalogSourceCategory = $resolvedSourceCategory;
        }
    }
}

if ($catalogSourceCategory && $subcategory !== '') {
    foreach ((array) ($catalogSourceCategory['subcategories'] ?? []) as $sub) {
        if (catalog_item_matches_aliases((array) $sub, [$subcategory])) {
            $activeSubcategory = $sub;
            break;
        }
    }
}

$filteredProducts = catalog_filtered_products(
    $catalogSourceCategory ? (string) $catalogSourceCategory['slug'] : null,
    $activeSubcategory ? (string) $activeSubcategory['slug'] : null,
    $search !== '' ? $search : null
);

if ($activeCategory && $activeSubcategory && !$filteredProducts) {
    $categorySlug = strtolower((string) ($activeCategory['slug'] ?? ''));
    $subcategoryName = trim((string) ($activeSubcategory['name'] ?? ''));
    if ($categorySlug === 'packaging' && $subcategoryName !== '') {
        $prefix = strtolower(strtok($subcategoryName, ' '));
        if ($prefix !== '') {
            $parentProducts = catalog_filtered_products(
                (string) $activeCategory['slug'],
                null,
                $search !== '' ? $search : null
            );
            $filteredProducts = array_values(array_filter($parentProducts, static function (array $product) use ($prefix): bool {
                return str_starts_with(strtolower((string) ($product['name'] ?? '')), $prefix);
            }));
        }
    }
}

$displayProducts = $filteredProducts;

if ($catalogSourceCategory && !$activeSubcategory && $activeCategoryLanding && !empty($catalogSourceCategory['subcategories']) && !empty($activeCategoryLanding['children'])) {
    $childOrder = array_flip((array) $activeCategoryLanding['children']);
    $preferredChildren = array_values(array_filter(
        (array) $catalogSourceCategory['subcategories'],
        static function (array $sub) use ($childOrder): bool {
            return isset($childOrder[(string) ($sub['slug'] ?? '')]);
        }
    ));

    usort($preferredChildren, static function (array $a, array $b) use ($childOrder): int {
        return ($childOrder[(string) ($a['slug'] ?? '')] ?? PHP_INT_MAX) <=> ($childOrder[(string) ($b['slug'] ?? '')] ?? PHP_INT_MAX);
    });

    $catalogSourceCategory['subcategories'] = $preferredChildren;
}

$pageTitle = 'Shop';
if ($isSearchResults) {
    $pageTitle = 'Search: ' . $search;
} elseif ($activeSubcategory) {
    $pageTitle = (string) $activeSubcategory['name'];
} elseif ($activeCategory) {
    $pageTitle = (string) $activeCategory['name'];
}

$meta = [
    'title' => 'Mybrandplease | ' . $pageTitle,
    'description' => 'Browse private label products by category and subcategory.',
    'canonical' => 'shop.php' . ($_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : ''),
];

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="<?php echo htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="breadcumb-wrapper__items-list-title">Home</a></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2">Products</span></li>
        <?php if ($activeCategory): ?>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="<?php echo htmlspecialchars(catalog_shop_link((string) $activeCategory['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="breadcumb-wrapper__items-list-title"><?php echo htmlspecialchars((string) $activeCategory['name'], ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php endif; ?>
        <?php if ($activeSubcategory): ?>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2"><?php echo htmlspecialchars((string) $activeSubcategory['name'], ENT_QUOTES, 'UTF-8'); ?></span></li>
        <?php endif; ?>
      </ul>
      <div class="breadcumb-wrapper__title">Products</div>
    </div>
  </div>
</div>

<?php if ($isSearchResults && !$activeCategory): ?>
  <section class="shop section-spacing-120 rr-ov-hidden pt-5">
    <div class="container rr-container-1350">
      <div class="section-heading text-center">
        <h2 class="section-heading__title">Search Results for "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"</h2>
      </div>

      <div class="row g-4">
        <?php if (!$displayProducts): ?>
          <div class="col-12">
            <div class="alert alert-light border">No products found for "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>".</div>
          </div>
        <?php endif; ?>

        <?php $delay = 0.2; ?>
        <?php foreach ($displayProducts as $product): ?>
          <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?php echo number_format($delay, 1); ?>s">
            <div class="shop-card">
              <div class="shop-card__thumb">
                <img src="<?php echo htmlspecialchars(url((string) $product['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!empty($product['badge'])): ?>
                  <div class="shop-card__thumb-offer"><?php echo htmlspecialchars((string) $product['badge'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <div class="shop-card__thumb-btn-wrapper">
                  <a href="<?php echo htmlspecialchars(catalog_product_link((string) $product['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="rr-btn-button4">
                    <span class="text">View Product</span>
                    <span class="icon">
                      <svg width="11" height="7" viewBox="0 0 11 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.419678 3.21674H10.2098M10.2098 3.21674L7.41265 6.01393M10.2098 3.21674L7.41265 0.419556" stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </div>
              <div class="shop-card__content">
                <div class="shop-card__content-title">
                  <a href="<?php echo htmlspecialchars(catalog_product_link((string) $product['slug']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
                <ul class="shop-card__content-list">
                  <li class="shop-card__content-list-start"><i class="fa-solid fa-star fa-fw"></i></li>
                  <li class="shop-card__content-list-point"><?php echo htmlspecialchars(number_format((float) $product['rating'], 1), ENT_QUOTES, 'UTF-8'); ?></li>
                  <li class="shop-card__content-list-text">(<?php echo htmlspecialchars((string) $product['reviews'], ENT_QUOTES, 'UTF-8'); ?> Reviews)</li>
                </ul>
                <h4 class="shop-card__content-dollar">$<?php echo htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8'); ?></h4>
              </div>
            </div>
          </div>
          <?php $delay += 0.1; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php elseif (!$activeCategory): ?>
  <section class="category-section is-visible section-spacing-120 rr-ov-hidden pt-5">
    <div class="container rr-container-1350">
      <div class="shop-showcase__head text-center">
        <h2 class="shop-showcase__title"><?php echo htmlspecialchars((string) $shopLandingTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="shop-showcase__divider"></div>
        <p class="shop-showcase__desc"><?php echo nl2br(htmlspecialchars((string) $shopLandingDescription, ENT_QUOTES, 'UTF-8')); ?></p>
        <h3 class="shop-showcase__subhead"><?php echo htmlspecialchars((string) $shopLandingSubtitle, ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>

      <div class="cat-grid">
        <?php foreach ($categories as $idx => $cat): ?>
          <?php $catImage = (string) ($cat['image'] ?? 'assets/imgs/product/skin-care.webp'); ?>
          <?php
            $aosDirections = ['fade-right', 'fade-left', 'fade-up', 'fade-down'];
            $cardAos = $aosDirections[$idx % 4];
            $imgAos = $aosDirections[($idx + 1) % 4];
            $delay = 80 * ($idx % 6);
          ?>
          <a
            href="<?php echo htmlspecialchars(catalog_shop_link((string) $cat['slug']), ENT_QUOTES, 'UTF-8'); ?>"
            class="cat-card"
            data-aos="<?php echo htmlspecialchars($cardAos, ENT_QUOTES, 'UTF-8'); ?>"
            data-aos-delay="<?php echo (int) $delay; ?>"
            data-aos-duration="800">
            <img
              src="<?php echo htmlspecialchars(url($catImage), ENT_QUOTES, 'UTF-8'); ?>"
              class="cat-image"
              alt="<?php echo htmlspecialchars((string) $cat['name'], ENT_QUOTES, 'UTF-8'); ?>"
              data-aos="<?php echo htmlspecialchars($imgAos, ENT_QUOTES, 'UTF-8'); ?>"
              data-aos-delay="<?php echo (int) ($delay + 60); ?>"
              data-aos-duration="900">
            <div class="cat-overlay">
              <h3 class="cat-title"><?php echo htmlspecialchars((string) $cat['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <span class="cat-link">Explore Collection <i class="fa-solid fa-arrow-right"></i></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php elseif (!$activeSubcategory): ?>
  <section class="category-section is-visible section-spacing-120 rr-ov-hidden pt-5">
    <div class="container rr-container-1350">
      <div class="shop-showcase__head text-center">
        <?php
          $categoryHeading = (string) ($categorySpecificHeading !== '' ? $categorySpecificHeading : (($activeCategoryLanding['title'] ?? '') !== '' ? $activeCategoryLanding['title'] : $activeCategory['name']));
          $categorySubhead = (string) ($categorySpecificSubtitle !== '' ? $categorySpecificSubtitle : (($activeCategoryLanding['subtitle'] ?? '') !== '' ? $activeCategoryLanding['subtitle'] : $shopCategorySubtitle));
          $categoryPageImage = (string) ($activeCategory['page_image'] ?? $activeCategory['image'] ?? 'assets/imgs/product/skin-care.webp');
        ?>
        <h2 class="shop-showcase__title"><?php echo htmlspecialchars($categoryHeading, ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="shop-showcase__divider"></div>
        <div class="shop-showcase__desc">
          <?php
            $catDesc = trim((string) ($activeCategory['description'] ?? ''));
            $resolvedCategoryDescription = (string) ($activeCategoryLanding['description'] ?? '');
            $categoryDescription = $catDesc !== ''
              ? $catDesc
              : ($resolvedCategoryDescription !== '' ? $resolvedCategoryDescription : ('Explore our complete ' . $activeCategory['name'] . ' range and build your private label catalog with confidence.'));
            echo $renderShopIntroHtml($categoryDescription, [$categoryHeading, $categorySubhead]);
          ?>
        </div>
        <h3 class="shop-showcase__subhead"><?php echo htmlspecialchars($categorySubhead, ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>

      <?php if (!empty($catalogSourceCategory['subcategories'])): ?>
        <div class="cat-grid">
          <?php foreach ((array) $catalogSourceCategory['subcategories'] as $sub): ?>
            <?php $subImage = (string) ($sub['image'] ?? $catalogSourceCategory['image'] ?? $activeCategory['image']); ?>
            <a href="<?php echo htmlspecialchars(catalog_subcategory_page_link((string) $activeCategory['slug'], (string) ($sub['name'] ?? $sub['slug'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" class="cat-card">
              <img src="<?php echo htmlspecialchars(url($subImage), ENT_QUOTES, 'UTF-8'); ?>" class="cat-image" alt="<?php echo htmlspecialchars((string) $sub['name'], ENT_QUOTES, 'UTF-8'); ?>">
              <div class="cat-overlay">
                <h3 class="cat-title"><?php echo htmlspecialchars((string) $sub['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <span class="cat-link">Explore Collection <i class="fa-solid fa-arrow-right"></i></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php else: ?>
  <section class="shop-sub-detail section-spacing-120 rr-ov-hidden pt-5">
    <div class="container rr-container-1350">
      <div class="shop-sub-detail__top row g-4 align-items-center">
        <div class="col-lg-5" data-aos="zoom-in">
          <div class="shop-sub-detail__thumb">
            <img src="<?php echo htmlspecialchars(url((string) ($activeSubcategory['page_image'] ?? $activeSubcategory['image'] ?? $activeCategory['page_image'] ?? $activeCategory['image'] ?? 'assets/imgs/product/skin-care.webp')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $activeSubcategory['name'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>
        <div class="col-lg-7" data-aos="fade-left">
          <div class="shop-sub-detail__content">
            <h2><?php echo htmlspecialchars((string) $activeSubcategory['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="shop-sub-detail__description">
              <?php
                $subDesc = trim((string) ($activeSubcategory['description'] ?? ''));
                echo $renderShopIntroHtml($subDesc !== '' ? $subDesc : (string) $shopSubcategoryFallbackDescription, [(string) $activeSubcategory['name']]);
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($activeCategory): ?>
  <section class="shop section-spacing-120 rr-ov-hidden pt-0">
    <div class="container rr-container-1350">
      <div class="section-heading">
        <h2 class="section-heading__title"><?php echo htmlspecialchars((string) $shopRelatedTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
      </div>

      <div class="row g-4">
        <?php if (!$displayProducts): ?>
          <div class="col-12">
            <div class="alert alert-light border">No products found for this selection.</div>
          </div>
        <?php endif; ?>

        <?php $delay = 0.2; ?>
        <?php foreach ($displayProducts as $product): ?>
          <div class="col-xl-3 col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="<?php echo number_format($delay, 1); ?>s">
            <div class="shop-card">
              <div class="shop-card__thumb">
                <img src="<?php echo htmlspecialchars(url((string) $product['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (!empty($product['badge'])): ?>
                  <div class="shop-card__thumb-offer"><?php echo htmlspecialchars((string) $product['badge'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <div class="shop-card__thumb-btn-wrapper">
                  <a href="<?php echo htmlspecialchars(catalog_product_link((string) $product['slug']), ENT_QUOTES, 'UTF-8'); ?>" class="rr-btn-button4">
                    <span class="text">View Product</span>
                    <span class="icon">
                      <svg width="11" height="7" viewBox="0 0 11 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.419678 3.21674H10.2098M10.2098 3.21674L7.41265 6.01393M10.2098 3.21674L7.41265 0.419556" stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round"></path>
                      </svg>
                    </span>
                  </a>
                </div>
              </div>
              <div class="shop-card__content">
                <div class="shop-card__content-title">
                  <a href="<?php echo htmlspecialchars(catalog_product_link((string) $product['slug']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
                <ul class="shop-card__content-list">
                  <li class="shop-card__content-list-start"><i class="fa-solid fa-star fa-fw"></i></li>
                  <li class="shop-card__content-list-point"><?php echo htmlspecialchars(number_format((float) $product['rating'], 1), ENT_QUOTES, 'UTF-8'); ?></li>
                  <li class="shop-card__content-list-text">(<?php echo htmlspecialchars((string) $product['reviews'], ENT_QUOTES, 'UTF-8'); ?> Reviews)</li>
                </ul>
                <h4 class="shop-card__content-dollar">$<?php echo htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8'); ?></h4>
              </div>
            </div>
          </div>
          <?php $delay += 0.1; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<style>
  .shop-sub-detail {
    background: linear-gradient(180deg, #fff 0%, #fff7fa 100%);
  }
  .shop-sub-detail__top {
    row-gap: 34px;
  }
  .shop-sub-detail__thumb {
    border-radius: 8px;

  }
  .shop-sub-detail__thumb img {
    width: 100%;
    display: block;
    /* object-fit: cover; */
    aspect-ratio: 16 / 10;
  }
  .shop-sub-detail__content {
    max-width: 620px;
  }
  .shop-sub-detail__content h2 {
    margin: 0 0 18px;
    color: #ee4f8a;
    font-family: var(--font_Playfair);
    font-size: clamp(34px, 4vw, 36px);
    font-weight: 800;
    line-height: 1.05;
    letter-spacing: 0;
  }
  .shop-sub-detail__description {
    color: #202020;
    font-family: var(--font_Lato);
    font-size: 17px;
    line-height: 1.82;
    text-align: justify;
  }
  .shop-sub-detail__description h1,
  .shop-sub-detail__description h2,
  .shop-sub-detail__description h3,
  .shop-sub-detail__description h4,
  .shop-sub-detail__description h5,
  .shop-sub-detail__description h6 {
    margin: 22px 0 10px;
    color: #171717;
    font-family: var(--font_Lato);
    font-size: clamp(18px, 2vw, 24px);
    font-weight: 800;
    line-height: 1.25;
    text-transform: none;
  }
  .shop-sub-detail__description p {
    margin: 0 0 14px;
    color: #202020 !important;
    line-height: 1.82;
  }
  .shop-sub-detail__description strong,
  .shop-sub-detail__description b {
    color: #ee4f8a;
    font-weight: 600;
    font-size: 20px;
  }
  .shop-sub-detail__description ul,
  .shop-sub-detail__description ol {
    margin: 12px 0 0;
    padding-left: 0;
    list-style: none;
    color: #202020;
  }
  .shop-sub-detail__description li {
    position: relative;
    margin: 0 0 10px;
    padding-left: 24px;
    color: #303030 !important;
    line-height: 1.65;
  }
  .shop-sub-detail__description li::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0.68em;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ee4f8a;
    box-shadow: 0 0 0 4px rgba(238, 79, 138, 0.12);
  }
  @media (max-width: 991px) {
    .shop-sub-detail__content {
      max-width: none;
    }
  }
  @media (max-width: 575px) {
    .shop-sub-detail__description {
      font-size: 15px;
      line-height: 1.72;
    }
    .shop-sub-detail__content h2 {
      font-size: 26px;
    }
  }
</style>

<?php include 'includes/footer.php'; ?>





