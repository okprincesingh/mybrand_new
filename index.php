<?php
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/cms.php';

$homeProducts = array_slice(catalog_products(), 0, 8);
$allHomeCategories = catalog_categories();
$homeCategoryConfigs = [
  [
    'label' => 'SKIN CARE',
    'aliases' => ['skin-care'],
    'children' => [
      ['aliases' => ['skin-care-environmental-defense'], 'label' => 'Environmental Defense', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Environmental-Defense-888x1024.webp'],
      ['aliases' => ['skin-care-advanced'], 'label' => 'Advanced', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Advance-888x1024.webp'],
      ['aliases' => ['skin-care-age-defying'], 'label' => 'Age Defying', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Age-Defying-888x1024.webp'],
      ['aliases' => ['skin-care-peptides'], 'label' => 'Peptides', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Peptides-888x1024.webp'],
      ['aliases' => ['vitamin-c', 'skin-care-vitamin-c'], 'label' => 'Vitamin C', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Vitamin-C-888x1024.webp'],
      ['aliases' => ['skin-care-brightening'], 'label' => 'Brightening', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Brightening-888x1024.webp'],
      ['aliases' => ['skin-care-super-fruits'], 'label' => 'Super Fruits', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Super-Fruits-888x1024.webp'],
      ['aliases' => ['skin-care-marine-complex'], 'label' => 'Marine Complex', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Marine-Complex-1-888x1024.webp'],
      ['aliases' => ['skin-care-blemish-prone-skin'], 'label' => 'Blemish Prone Skin', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Blemish-Prone-Skin-888x1024.webp'],
      ['aliases' => ['skin-care-botanical'], 'label' => 'Botanical', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Botanical-1-888x1024.webp'],
    ],
  ],
  [
    'label' => 'BODY CARE',
    'aliases' => ['body-care'],
    'children' => [
      ['aliases' => ['body-care-specialty-products'], 'label' => 'Specialty Products', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Specialty-Products-scaled.webp'],
      ['aliases' => ['body-care-body-wash-shower-gel'], 'label' => 'Body Wash & Shower Gel', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Body-Wash-Shower-Gel-scaled.webp'],
      ['aliases' => ['body-care-lotions'], 'label' => 'Lotions', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Lotion-scaled.webp'],
      ['aliases' => ['body-care-body-butters'], 'label' => 'Body Butters', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Body-Butter-scaled.webp'],
      ['aliases' => ['body-care-salts-soaks'], 'label' => 'Salt & Soaks', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/salt-Soaks-scaled.webp'],
      ['aliases' => ['body-care-lip-balms-lip-scrubs'], 'label' => 'Lip Balms & Scrubs', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Lip-Balm-Scrubs-scaled.webp'],
      ['aliases' => ['body-care-body-scrubs'], 'label' => 'Bath & Body Scrub', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Body-Scrubs-scaled.webp'],
      ['aliases' => ['body-care-manicure-pedicure'], 'label' => 'Manicure & Pedicure', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Pedi-Meni-scaled.webp'],
    ],
  ],
  [
    'label' => 'HAIR CARE',
    'aliases' => ['hair-care'],
    'children' => [
      ['aliases' => ['hair-care-bars', 'bars'], 'label' => 'Shampoo & Conditioner Bars', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Shampoo-Conditioner-Bars-888x1024.webp'],
      ['aliases' => ['hair-care-shampoo', 'shampoo'], 'label' => 'Shampoo', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Shampoo-888x1024.webp'],
      ['aliases' => ['hair-care-conditioner'], 'label' => 'Conditioner', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Conditioner-888x1024.webp'],
      ['aliases' => ['hair-care-styling-products', 'styling-products'], 'label' => 'Styling Products', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Styling-Products-888x1024.webp'],
      ['aliases' => ['hair-care-treatment-products', 'treatment-products'], 'label' => 'Treatment Products', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Treatment-Products-888x1024.webp'],
    ],
  ],
  [
    'label' => 'BATHING SOAPS',
    'aliases' => ['bathing-soaps'],
    'children' => [
      ['aliases' => ['bathing-soaps-beauty-soaps'], 'label' => 'Beauty Soaps', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Beauty-Soaps-scaled.webp'],
      ['aliases' => ['bathing-soaps-mens-soap'], 'label' => 'Mens Soap', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Mens-Soap-scaled.webp'],
      ['aliases' => ['bathing-soaps-medicated-soaps'], 'label' => 'Medicated Soap', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Medicated-Soap-scaled.webp'],
      ['aliases' => ['bathing-soaps-hotel-soap'], 'label' => 'Hotel Soaps', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Hotel-Soaps-scaled.webp'],
      ['aliases' => ['bathing-soaps-novelty-soaps'], 'label' => 'Novelty Soaps', 'image' => 'https://mybrandplease.com/wp-content/uploads/2023/07/Novelty-Soaps-scaled.webp'],
    ],
  ],
  [
    'label' => "FOR MEN'S",
    'aliases' => ['men-s-care', 'especially-for-men'],
    'image' => 'https://mybrandplease.com/wp-content/uploads/2023/05/Mens-min-1024x621.png',
  ],
];

$homeCategories = [];
$selectedCategoryIds = [];
$selectedCategorySlugs = [];
foreach ($homeCategoryConfigs as $config) {
  $category = catalog_find_category_by_aliases((array) ($config['aliases'] ?? []));
  $categoryResolved = (bool) $category;
  if (!$category) {
    $categorySlug = (string) (($config['aliases'][0] ?? '') ?: catalog_normalize_identity((string) ($config['label'] ?? 'category')));
    $category = [
      'id' => 0,
      'slug' => $categorySlug,
      'name' => (string) ($config['label'] ?? $categorySlug),
      'description' => '',
      'image' => (string) ($config['image'] ?? 'assets/imgs/product/skin-care.webp'),
      'subcategories' => [],
    ];
  }

  $category['display_name'] = (string) ($config['label'] ?? strtoupper((string) ($category['name'] ?? '')));
  $selectedCategoryIds[] = (int) ($category['id'] ?? 0);
  $selectedCategorySlugs[] = (string) ($category['slug'] ?? '');
  $cards = [];

  $children = [];
  $seenChildKeys = [];
  foreach ((array) ($config['children'] ?? []) as $childConfig) {
    $childAliases = (array) ($childConfig['aliases'] ?? []);
    $resolvedChild = catalog_find_subcategory_by_aliases($category, $childAliases);
    $childSlug = (string) ($resolvedChild['slug'] ?? ($childAliases[0] ?? catalog_normalize_identity((string) ($childConfig['label'] ?? ''))));
    if ($childSlug === '') {
      continue;
    }
    $seenChildKeys[] = $childSlug;
    $children[] = [
      'id' => (int) ($resolvedChild['id'] ?? 0),
      'slug' => $childSlug,
      'name' => (string) ($childConfig['label'] ?? $resolvedChild['name'] ?? $childSlug),
      'display_name' => (string) ($childConfig['label'] ?? $resolvedChild['name'] ?? $childSlug),
      'url_name' => (string) ($resolvedChild['name'] ?? $childConfig['label'] ?? $childSlug),
      'description' => (string) ($resolvedChild['description'] ?? ''),
      'image' => (string) ($resolvedChild['image'] ?? $childConfig['image'] ?? $category['image'] ?? 'assets/imgs/product/skin-care.webp'),
      'fit' => (string) ($childConfig['fit'] ?? 'cover'),
    ];
  }

  if (empty($config['children'])) {
    foreach ((array) ($category['subcategories'] ?? []) as $child) {
      $childSlug = (string) ($child['slug'] ?? '');
      if ($childSlug !== '' && in_array($childSlug, $seenChildKeys, true)) {
        continue;
      }
      $children[] = (array) $child;
    }
  }

  $category['subcategories'] = $children;
  if (!empty($children)) {
    $cards = array_map(static function (array $child) use ($category): array {
      return [
        'name' => (string) ($child['display_name'] ?? $child['name'] ?? ''),
        'image' => (string) ($child['image'] ?? $category['image'] ?? 'assets/imgs/product/skin-care.webp'),
        'href' => catalog_subcategory_page_link((string) ($category['slug'] ?? ''), (string) ($child['url_name'] ?? $child['name'] ?? $child['display_name'] ?? $child['slug'] ?? '')),
        'fit' => (string) ($child['fit'] ?? 'cover'),
      ];
    }, array_slice($children, 0, 10));
  }

  if (empty($cards) && $categoryResolved) {
    $productCards = array_slice(catalog_filtered_products((string) $category['slug'], null, null), 0, 10);
    foreach ($productCards as $product) {
      $cards[] = [
        'name' => (string) ($product['name'] ?? ''),
        'image' => (string) ($product['image'] ?? $category['image'] ?? 'assets/imgs/product/skin-care.webp'),
        'href' => url('product-details.php') . '?slug=' . rawurlencode((string) ($product['slug'] ?? '')),
      ];
    }
  }

  if (empty($cards)) {
    $cards[] = [
      'name' => (string) $category['display_name'],
      'image' => (string) ($category['image'] ?? 'assets/imgs/product/skin-care.webp'),
      'href' => url('shop.php') . '?category=' . rawurlencode((string) $category['slug']),
    ];
  }

  $category['home_cards'] = $cards;
  $homeCategories[] = $category;
}

foreach ($allHomeCategories as $category) {
  if (count($homeCategories) >= 5) {
    break;
  }
  $categoryId = (int) ($category['id'] ?? 0);
  $categorySlug = (string) ($category['slug'] ?? '');
  if (($categoryId > 0 && in_array($categoryId, $selectedCategoryIds, true)) || ($categorySlug !== '' && in_array($categorySlug, $selectedCategorySlugs, true))) {
    continue;
  }

  $category['display_name'] = strtoupper((string) ($category['name'] ?? ''));
  $cards = [];
  $productCards = array_slice(catalog_filtered_products((string) ($category['slug'] ?? ''), null, null), 0, 10);
  foreach ($productCards as $product) {
    $cards[] = [
      'name' => (string) ($product['name'] ?? ''),
      'image' => (string) ($product['image'] ?? $category['image'] ?? 'assets/imgs/product/skin-care.webp'),
      'href' => url('product-details.php') . '?slug=' . rawurlencode((string) ($product['slug'] ?? '')),
    ];
  }
  if (empty($cards)) {
    $cards[] = [
      'name' => (string) $category['display_name'],
      'image' => (string) ($category['image'] ?? 'assets/imgs/product/skin-care.webp'),
      'href' => url('shop.php') . '?category=' . rawurlencode((string) ($category['slug'] ?? '')),
    ];
  }
  $category['home_cards'] = $cards;
  $homeCategories[] = $category;
}

$homeInitialCategory = $homeCategories[0] ?? null;
$homeInitialItems = [];
if ($homeInitialCategory) {
  $homeInitialItems = (array) ($homeInitialCategory['home_cards'] ?? []);
}
$homeSlides = cms_get_home_slides();
$homeTestimonials = cms_get_home_testimonials();
$homeOffices = cms_get_home_offices();
$homeInstagramReels = cms_get_home_instagram_reels();
$meta = [
  'title' => 'Mybrandplease | Home',
  'description' => 'Mybrandplease - Home page',
  'canonical' => 'index.php'
];
include 'includes/head.php';
include 'includes/header.php';
?>

<!-- Logout Success Message -->
<?php if (isset($_GET['logout']) && $_GET['logout'] === 'success' && isset($_SESSION['logout_message'])): ?>
<div class="logout-success-message" id="logout-message">
    <div class="logout-success-content">
        <div class="logout-success-icon">
            <i class="fa-solid fa-check-circle"></i>
        </div>
        <div class="logout-success-text">
            <h3>Successfully Logged Out</h3>
            <p><?php echo htmlspecialchars($_SESSION['logout_message'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <button class="logout-success-close" onclick="closeLogoutMessage()" aria-label="Close message">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
</div>
<?php unset($_SESSION['logout_message']); ?>
<?php endif; ?>

<script>
// Auto-hide logout message after 5 seconds
setTimeout(function() {
    const message = document.getElementById('logout-message');
    if (message) {
        message.classList.add('hide');
        setTimeout(function() {
            message.remove();
        }, 300);
    }
}, 5000);

// Manual close function
function closeLogoutMessage() {
    const message = document.getElementById('logout-message');
    if (message) {
        message.classList.add('hide');
        setTimeout(function() {
            message.remove();
        }, 300);
    }
}
</script>



<section class="hero-video-section container-fluid p-0">

    <!-- Desktop Video -->
    <video
        class="hero-video hero-video-desktop"
        data-hero-video="desktop"
        data-src="<?php echo htmlspecialchars(url('https://jaikvik.in/lab/mybrand_video/mybrandvideo'), ENT_QUOTES, 'UTF-8'); ?>"
        data-src-light="<?php echo htmlspecialchars(url('https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo'), ENT_QUOTES, 'UTF-8'); ?>"
        autoplay
        muted
        loop
        playsinline
        preload="none"></video>

    <!-- Mobile Video -->
    <video
        class="hero-video hero-video-mobile"
        data-hero-video="mobile"
        data-src="<?php echo htmlspecialchars(url('https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo'), ENT_QUOTES, 'UTF-8'); ?>"
        autoplay
        muted
        loop
        playsinline
        preload="none"></video>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const desktopVideo = document.querySelector('[data-hero-video="desktop"]');
    const mobileVideo = document.querySelector('[data-hero-video="mobile"]');
    const mobileQuery = window.matchMedia('(max-width: 1024px)');
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

    function stopVideo(video) {
        if (!video) return;
        video.pause();
        video.removeAttribute('src');
        video.load();
        video.preload = 'none';
    }

    function shouldUseLightHeroVideo() {
        if (connection) {
            if (connection.saveData) return true;
            if (typeof connection.effectiveType === 'string' && /(^|-)2g|3g/.test(connection.effectiveType)) return true;
            if (typeof connection.downlink === 'number' && connection.downlink > 0 && connection.downlink < 10) return true;
        }

        if (typeof navigator.deviceMemory === 'number' && navigator.deviceMemory < 4) {
            return true;
        }

        return !connection;
    }

    function getVideoSource(video) {
        if (!video) return '';
        if (video === desktopVideo && shouldUseLightHeroVideo()) {
            return video.getAttribute('data-src-light') || video.getAttribute('data-src') || '';
        }

        return video.getAttribute('data-src') || '';
    }

    function startVideo(video) {
        if (!video) return;
        const source = getVideoSource(video);
        if (!source) return;
        if (video.getAttribute('src') === source) return;
        video.preload = 'auto';
        video.setAttribute('src', source);
        video.load();
        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {});
        }
    }

    function loadCurrentHeroVideo() {
        if (mobileQuery.matches) {
            stopVideo(desktopVideo);
            startVideo(mobileVideo);
        } else {
            stopVideo(mobileVideo);
            startVideo(desktopVideo);
        }
    }

    loadCurrentHeroVideo();
    if (typeof mobileQuery.addEventListener === 'function') {
        mobileQuery.addEventListener('change', loadCurrentHeroVideo);
    } else if (typeof mobileQuery.addListener === 'function') {
        mobileQuery.addListener(loadCurrentHeroVideo);
    }

    if (connection && typeof connection.addEventListener === 'function') {
        connection.addEventListener('change', loadCurrentHeroVideo);
    }
});
</script>





        <section class="category1 section-spacing-120 rr-ov-hidden">
          <div class="category1-wrapper">
            <div class="container rr-container-1350">
              <!-- <div class="section-heading wow fadeInRight" data-wow-delay="0.3s">
                <h2 class="section-heading__title">OUR CATEGORY</h2>
              </div> -->
              <div class="row g-4">
                <div class="col-md-3 col-xl-3">
                  <div class="category1-item wow fadeInRight" data-wow-delay="0.2s">
                    <div class="category1-item__thumb">
                      <img src="<?php echo url('assets/imgs/category/category_thumb1_2.png'); ?>" alt="thumb">
                    </div>
                    <div class="category1-item__content2">
                      </h2>
                      <div class="category1-item__button">
                        <a href="shop.php" class="rr-btn-button2">
                          <span class="text">Explore now</span>
                          <span class="icon">
                            <svg width="11" height="7" viewBox="0 0 11 7" fill="none"
                              xmlns="http://www.w3.org/2000/svg">
                              <path
                                d="M0.419556 3.21674H10.2097M10.2097 3.21674L7.41253 6.01393M10.2097 3.21674L7.41253 0.419556"
                                stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round">
                              </path>
                            </svg>
                          </span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-xl-6">
                  <div class="category1-item wow fadeInRight" data-wow-delay="0.3s">
                    <div class="category1-item__thumb">
                      <img src="<?php echo url('assets/imgs/category/category_thumb1_1.png'); ?>" alt="thumb">
                    </div>
                    <div class="category1-item__content2">
                      </h2>
                      <div class="category1-item__button">
                        <a href="shop.php" class="rr-btn-button2">
                          <span class="text">Try Our Products</span>
                          <span class="icon">
                            <svg width="11" height="7" viewBox="0 0 11 7" fill="none"
                              xmlns="http://www.w3.org/2000/svg">
                              <path
                                d="M0.419556 3.21674H10.2097M10.2097 3.21674L7.41253 6.01393M10.2097 3.21674L7.41253 0.419556"
                                stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round">
                              </path>
                            </svg>
                          </span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-3 col-xl-3">
                  <div class="category1-item wow fadeInRight" data-wow-delay="0.5s">
                    <div class="category1-item__thumb">
                      <img src="<?php echo url('assets/imgs/category/category_thumb1_3.png'); ?>" alt="thumb">
                    </div>
                    <div class="category1-item__content2">
                      </h2>
                      <div class="category1-item__button">
                        <a href="shop.php" class="rr-btn-button2">
                          <span class="text">Contact Us</span>
                          <span class="icon">
                            <svg width="11" height="7" viewBox="0 0 11 7" fill="none"
                              xmlns="http://www.w3.org/2000/svg">
                              <path
                                d="M0.419556 3.21674H10.2097M10.2097 3.21674L7.41253 6.01393M10.2097 3.21674L7.41253 0.419556"
                                stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round">
                              </path>
                            </svg>
                          </span>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="intro1-slider__dots"></div>
            </div>
          </div>
        </section>

        <style>
          .category-section .cat-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 24px 32px;
            max-width: 1360px;
            margin: 0 auto;
          }
          .category-section .cat-card {
            position: relative;
            display: block;
            border-radius: 8px;
            aspect-ratio: 216 / 291;
            perspective: 768px;
            text-decoration: none;
            box-shadow: 0 14px 30px rgba(34, 34, 34, 0.12);
          }
          .category-section .cat-card__flip {
            position: relative;
            display: block;
            width: 100%;
            height: 100%;
            min-height: 250px;
            border-radius: 8px;
            transform-style: preserve-3d;
            transition: transform 0.7s ease;
          }
          .category-section .cat-card__face {
            position: absolute;
            inset: 0;
            border-radius: 8px;
            overflow: hidden;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
          }
          .category-section .cat-card__face--front {
            background: #f7f7f7;
          }
          .category-section .cat-card__face--back {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: #ebebeb;
            transform: rotateY(180deg);
            text-align: center;
          }
          .category-section .cat-card__back-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
          }
          .category-section .cat-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
          }
          .category-section .cat-overlay {
            display: none;
          }
          .category-section .cat-title {
            margin: 0;
            color: #ff508b;
            font-family: "Abril Fatface", serif;
            font-size: 22px;
            font-weight: 400;
            line-height: 1.25;
            text-align: center;
            max-width: 130px;
          }
          .category-section .cat-title--back {
            color: #ff508b;
          }
          @media (hover: hover) and (pointer: fine) {
            .category-section .cat-card:hover .cat-card__flip,
            .category-section .cat-card:focus-visible .cat-card__flip {
              transform: rotateY(180deg);
            }
            .category-section .cat-card:hover .cat-image,
            .category-section .cat-card:focus-visible .cat-image {
              transform: scale(1.03);
            }
            .category-section .cat-card:hover,
            .category-section .cat-card:focus-visible {
              box-shadow: 0 18px 38px rgba(34, 34, 34, 0.16);
            }
          }
          @media (max-width: 1199px) {
            .category-section .cat-grid {
              grid-template-columns: repeat(4, minmax(0, 1fr));
              gap: 22px 24px;
            }
          }
          @media (max-width: 991px) {
            .category-section .cat-grid {
              grid-template-columns: repeat(3, minmax(0, 1fr));
              gap: 20px;
            }
          }
          @media (max-width: 767px) {
            .category-section .cat-grid {
              grid-template-columns: repeat(2, minmax(0, 1fr));
              gap: 16px;
            }
            .category-section .cat-card__flip {
              min-height: 210px;
            }
            .category-section .cat-title--back {
              font-size: 20px;
            }
          }
        </style>

        <section class="category-section section-spacing-120 rr-ov-hidden pt-0 js-category-showcase">
          <div class="container rr-container-1350">
            <div class="section-heading category-section__heading wow fadeInUp" data-wow-delay=".2s">
              <h2 class="section-heading__title">Nature Powered Ingredients</h2>
            </div>
            <div class="nav-tabs-modern" id="homeCategoryTabs">
              <span class="nav-tabs-modern__indicator" aria-hidden="true"></span>
              <?php foreach ($homeCategories as $idx => $cat): ?>
                <a href="#" class="nav-tab-item <?= $idx === 0 ? 'active' : '' ?>" data-cat="<?= htmlspecialchars((string)$cat['slug'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars((string)($cat['display_name'] ?? $cat['name']), ENT_QUOTES, 'UTF-8') ?>
                </a>
              <?php endforeach; ?>
            </div>

            <div class="cat-grid" id="homeCategoryGrid">
              <?php foreach ($homeInitialItems as $item): ?>
                <?php
                  $itemName = (string) ($item['name'] ?? '');
                  $itemImage = (string) ($item['image'] ?? ($homeInitialCategory['image'] ?? 'assets/imgs/product/skin-care.webp'));
                  $itemHref = (string) ($item['href'] ?? url('shop.php') . '?category=' . rawurlencode((string) ($homeInitialCategory['slug'] ?? '')));
                ?>
                <a href="<?= htmlspecialchars($itemHref, ENT_QUOTES, 'UTF-8') ?>" class="cat-card" aria-label="<?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?>">
                  <span class="cat-card__flip">
                    <span class="cat-card__face cat-card__face--front">
                      <img src="<?= htmlspecialchars(url($itemImage), ENT_QUOTES, 'UTF-8') ?>" class="cat-image" alt="<?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?>">
                    </span>
                    <span class="cat-card__face cat-card__face--back" aria-hidden="true">
                      <span class="cat-card__back-inner">
                        <h3 class="cat-title cat-title--back"><?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?></h3>
                      </span>
                    </span>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <script>
            (function(){
              const categories = <?= json_encode($homeCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
              const section = document.querySelector('.js-category-showcase');
              const tabsWrap = document.getElementById('homeCategoryTabs');
              const grid = document.getElementById('homeCategoryGrid');
              const indicator = tabsWrap ? tabsWrap.querySelector('.nav-tabs-modern__indicator') : null;
              if (!tabsWrap || !grid || !Array.isArray(categories) || categories.length === 0) return;

              const esc = (v) => String(v ?? '').replace(/[&<>'"]/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[m]));
              const appBase = <?= json_encode(rtrim(dirname(url('shop.php')), '/\\'), JSON_UNESCAPED_SLASHES) ?>;
              let switchTimer = null;
              let animationTimer = null;
              const toUrl = (path) => {
                const raw = String(path || '').trim();
                if (raw === '') return '';
                if (/^(https?:)?\/\//i.test(raw)) return raw;
                const normalized = raw.replace(/^\/+/, '').replace(/^mybrand\//i, '');
                return appBase + '/' + normalized;
              };

              function moveIndicator(target) {
                if (!indicator || !target) return;
                indicator.style.width = target.offsetWidth + 'px';
                indicator.style.height = target.offsetHeight + 'px';
                indicator.style.transform = 'translate(' + target.offsetLeft + 'px,' + target.offsetTop + 'px)';
                indicator.style.opacity = '1';
              }

              function attachCardHoverEffects() {
                grid.querySelectorAll('.cat-card').forEach((card) => {
                  card.addEventListener('pointermove', (event) => {
                    if (window.innerWidth < 992) return;
                    const rect = card.getBoundingClientRect();
                    const x = event.clientX - rect.left;
                    const y = event.clientY - rect.top;
                    const rotateY = ((x / rect.width) - 0.5) * 8;
                    const rotateX = (0.5 - (y / rect.height)) * 8;
                    card.style.transform = 'perspective(1000px) rotateX(' + rotateX.toFixed(2) + 'deg) rotateY(' + rotateY.toFixed(2) + 'deg) translateY(-8px)';
                  });
                  card.addEventListener('pointerleave', () => {
                    card.style.transform = '';
                  });
                });
              }

              function renderCards(slug, animate = false) {
                const active = categories.find((c) => c.slug === slug) || categories[0];
                const items = Array.isArray(active.home_cards) && active.home_cards.length
                  ? active.home_cards
                  : [{
                      name: active.display_name || active.name,
                      image: active.image,
                      href: <?= json_encode(url('shop.php'), JSON_UNESCAPED_SLASHES) ?> + '?category=' + encodeURIComponent(active.slug)
                    }];

                const cards = items.map((item) => `
                  <a href="${item.href}" class="cat-card" aria-label="${esc(item.name)}">
                    <span class="cat-card__flip">
                      <span class="cat-card__face cat-card__face--front">
                        <img src="${toUrl(item.image)}" class="cat-image" alt="${esc(item.name)}">
                      </span>
                      <span class="cat-card__face cat-card__face--back" aria-hidden="true">
                        <span class="cat-card__back-inner">
                          <h3 class="cat-title cat-title--back">${esc(item.name)}</h3>
                        </span>
                      </span>
                    </span>
                  </a>
                `).join('');

                const finishRender = () => {
                  grid.innerHTML = cards;
                  requestAnimationFrame(() => {
                    grid.classList.remove('is-switching');
                    grid.classList.add('is-animating');
                    attachCardHoverEffects();
                    if (animationTimer) window.clearTimeout(animationTimer);
                    animationTimer = window.setTimeout(() => {
                      grid.classList.remove('is-animating');
                    }, 500);
                  });
                  if (window.AOS && typeof window.AOS.refreshHard === 'function') {
                    window.AOS.refreshHard();
                  } else if (window.AOS && typeof window.AOS.refresh === 'function') {
                    window.AOS.refresh();
                  }
                };

                if (!animate) {
                  finishRender();
                  return;
                }

                grid.classList.add('is-switching');
                if (switchTimer) window.clearTimeout(switchTimer);
                switchTimer = window.setTimeout(finishRender, 180);
              }

              tabsWrap.addEventListener('click', function(e){
                const link = e.target.closest('.nav-tab-item');
                if (!link) return;
                e.preventDefault();
                tabsWrap.querySelectorAll('.nav-tab-item').forEach((el) => el.classList.remove('active'));
                link.classList.add('active');
                moveIndicator(link);
                renderCards(link.getAttribute('data-cat'), true);
              });

              const first = tabsWrap.querySelector('.nav-tab-item.active') || tabsWrap.querySelector('.nav-tab-item');
              if (first) moveIndicator(first);
              renderCards(first ? first.getAttribute('data-cat') : categories[0].slug);

              window.addEventListener('resize', function () {
                const activeTab = tabsWrap.querySelector('.nav-tab-item.active');
                if (activeTab) moveIndicator(activeTab);
              });

              if (section && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                  entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                      section.classList.add('is-visible');
                    }
                  });
                }, { threshold: 0.18 });
                observer.observe(section);
              } else if (section) {
                section.classList.add('is-visible');
              }
            })();
          </script>
        </section>

        <section class="py-5 bg-light js-why-business">
          <div class="container">
            <div class="section-heading text-center mb-5">
              <h2 class="section-heading__title">Why launch your own brand</h2>
              <p class="text-muted word-spacing-6 lh-base mx-auto fs-18" >
                Enhance your brand reputation and profitability by leveraging our specialised private label cosmetic products, with low minimums order quantity and competitive prices, our high-quality offerings foster customer loyalty, robust margins, and substantial returns. Maximize your brand's potential with our premium cosmetics solutions.
              </p>
            </div>

            <div class="row g-4">
              
              <div class="col-md-6 col-lg-3 ">
                <div class="card h-100 align-items-center border-0 shadow-sm overflow-hidden rounded-4 js-why-card">
                  <img src="<?php echo url('assets/imgs/home/1.png'); ?>" class="card-img-top img-80" alt="Profit growth">
                  <div class="card-body p-4 py-1 text-center">
                    <h5 class="fw-bold mb-2 theme-color-font">Higher Profits</h5>
                    <p class=" text-muted py-3 text-justify">Our high-quality natural and organic-based skin and hair care products are offered at costs comparable to or lower than leading brands, but you set the price.</p>
                  </div>
                  <div class="card-footer bg-white border-0 pb-4 text-center">
                    <span class="fs-15 theme-color-font rounded-pill bg-success-subark px-3">No More MSRP!</span>
                  </div>
                </div>
              </div>

              <div class="col-md-6 col-lg-3">
                <div class="card h-100 align-items-center border-0 shadow-sm overflow-hidden rounded-4 js-why-card">
                  <img src="<?php echo url('assets/imgs/home/2.png'); ?>" class="card-img-top img-80" alt="Increased sales">
                  <div class="card-body p-4 py-1 text-center">
                    <h5 class="fw-bold mb-2 theme-color-font">Increased Sales</h5>
                    <p class=" text-muted py-3 text-justify">Engaging your self in marketing your own brand where margin and product sale price in your absolute control where you take better marketing approach and decisions.</p>
                  </div>
                  <div class="card-footer bg-white border-0 pb-4 text-center">
                    <span class="fs-15 theme-color-font rounded-pill bg-primary-subark px-3">Manage with flexibility.</span>
                  </div>
                </div>
              </div>

              <div class="col-md-6 col-lg-3">
                <div class="card h-100 align-items-center border-0 shadow-sm overflow-hidden rounded-4 js-why-card">
                  <img src="<?php echo url('assets/imgs/home/3.png'); ?>" class="card-img-top img-80" alt="Customer loyalty">
                  <div class="card-body p-4 py-1 text-center">
                    <h5 class="fw-bold mb-2 theme-color-font">Client Retention</h5>
                    <p class=" text-muted py-3 text-justify">Retain your customers with you with your brand. We are committed to offer you rock bottom price and yet the premium products experience. Create a BRAND LOYALTY.</p>
                  </div>
                  <div class="card-footer bg-white border-0 pb-4 text-center">
                    <span class="fs-15 theme-color-font rounded-pill bg-warning-subark px-3">Your Success Is Our Success.</span>
                  </div>
                </div>
              </div>

              <div class="col-md-6 col-lg-3">
                <div class="card h-100 align-items-center border-0 shadow-sm overflow-hidden rounded-4 js-why-card">
                  <img src="<?php echo url('assets/imgs/home/4.png'); ?>" class="card-img-top img-80" alt="Brand equity">
                  <div class="card-body p-4 py-1 text-center">
                    <h5 class="fw-bold mb-2 theme-color-font">Brand Equity</h5>
                    <p class=" text-muted py-3 text-justify">Building sales of your own brand of skin and hair care products not only builds your prestige in the mind of your customers but also in the market and leads to BRAND LOYALTY.</p>
                  </div>
                  <div class="card-footer bg-white border-0 pb-4 text-center">
                    <span class="fs-15 theme-color-font rounded-pill bg-info-subtle px-3">Give Your Work Deeper Meaning.</span>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </section>

        <section class="offer1 section-spacing-120  rr-ov-hidden pb-0">
          <div class="container rr-container-1350">
            <div class="offer1-wrapper background-image wow fadeInUp"
              style="background-image: url(assets/imgs/offer/offer-banner.jpeg);" data-wow-delay=".3s">
              <div class="row">
                <div class="col-xl-12 d-flex justify-content-end">
                  <div class="offer1__content">
                    <!-- <span class="offer1__content-text">A nature`s touch</span> -->
                    <h2 class="offer1__content-title"><span class="subtitle">Get 15%</span> Off All Private Label Work </h2>
                    <p class="offer1__content-subtext">Unlock quantity discounts for your private label work and maximize your savings with us. </p>
                    <div class="offer1__content-button">
                      <a href="shop.php" class="rr-btn-button2">
                        <span class="text">Browse product</span>
                        <span class="icon">
                          <svg width="11" height="7" viewBox="0 0 11 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                              d="M0.419556 3.21674H10.2097M10.2097 3.21674L7.41253 6.01393M10.2097 3.21674L7.41253 0.419556"
                              stroke="#0C0C0C" stroke-width="0.839157" stroke-linecap="round" stroke-linejoin="round">
                            </path>
                          </svg>
                        </span>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- new section -->
        <section class="gs-process gs-section section-spacing-120 mt-md-4">
          <div class="petal petal--1"></div>
          <div class="petal petal--2"></div>
          <div class="petal petal--3"></div>
          <div class="petal petal--4"></div>

          <div class="gs-process__inner gs-inner">
            <h1 class="gs-process__title gs-title">~ <em>Here's How To Get Started</em> ~</h1>

            <p class="gs-process__subtitle gs-subtitle">
              You know your brand and customers best. Let us help you build a custom private label line of offerings that are as unique as your brand.
            </p>

            <div class="gs-process__grid gs-grid">
              <div class="gs-process__card gs-card">
                <div class="gs-process__card-inner">
                  <div class="gs-process__card-face gs-process__card-front">
                    <div class="gs-process__icon gs-icon">
                      <span class="gs-process__icon-glyph" aria-hidden="true">🎨</span>
                      <span class="gs-process__step gs-step">01</span>
                    </div>
                    <div class="gs-process__card-title gs-card-title">Order Sample &amp; Determine Products</div>
                    <p class="gs-process__card-text gs-card-text">
                      We offer over 200 formulations in body, skin, and hair care. Choose your favourites that you know your clients will love and order samples online.
                    </p>
                    <a href="how-it-works.php#define-offerings" class="gs-process__card-link gs-card-link">Learn more</a>
                  </div>
                  <div class="gs-process__card-face gs-process__card-back">
                    <img src="<?php echo url('assets/imgs/how-it-works/1.png'); ?>" alt="Order samples and determine products">
                  </div>
                </div>
              </div>

              <div class="gs-process__card gs-card">
                <div class="gs-process__card-inner">
                  <div class="gs-process__card-face gs-process__card-front">
                    <div class="gs-process__icon gs-icon">
                      <span class="gs-process__icon-glyph" aria-hidden="true">🧴</span>
                      <span class="gs-process__step gs-step">02</span>
                    </div>
                    <div class="gs-process__card-title gs-card-title">Consult with Us on Packaging</div>
                    <p class="gs-process__card-text gs-card-text">
                      Focus on your message and the details of your opening order. Identify which packaging works best with your products and your brand.
                    </p>
                    <a href="how-it-works.php#product-components" class="gs-process__card-link gs-card-link">Learn more</a>
                  </div>
                  <div class="gs-process__card-face gs-process__card-back">
                    <img src="<?php echo url('assets/imgs/how-it-works/2.png'); ?>" alt="Choose product packaging components">
                  </div>
                </div>
              </div>

              <div class="gs-process__card gs-card">
                <div class="gs-process__card-inner">
                  <div class="gs-process__card-face gs-process__card-front">
                    <div class="gs-process__icon gs-icon">
                      <span class="gs-process__icon-glyph" aria-hidden="true">✨</span>
                      <span class="gs-process__step gs-step">03</span>
                    </div>
                    <div class="gs-process__card-title gs-card-title">Get Your Label Designed</div>
                    <p class="gs-process__card-text gs-card-text">
                      With the help of our label designing experts, see your brand come to life. We can also assist your designer on label designing of your choice.
                    </p>
                    <a href="how-it-works.php#design-and-printing" class="gs-process__card-link gs-card-link">Learn more</a>
                  </div>
                  <div class="gs-process__card-face gs-process__card-back">
                    <img src="<?php echo url('assets/imgs/how-it-works/3.png'); ?>" alt="Label design and printing">
                  </div>
                </div>
              </div>

              <div class="gs-process__card gs-card">
                <div class="gs-process__card-inner">
                  <div class="gs-process__card-face gs-process__card-front">
                    <div class="gs-process__icon gs-icon">
                      <span class="gs-process__icon-glyph" aria-hidden="true">📦</span>
                      <span class="gs-process__step gs-step">04</span>
                    </div>
                    <div class="gs-process__card-title gs-card-title">Consider Finishing Touches</div>
                    <p class="gs-process__card-text gs-card-text">
                      Details are everything. We can assist you with product boxes, shrink wrap, inserts, and much more to perfect your presentation.
                    </p>
                    <a href="how-it-works.php#finishing-touches" class="gs-process__card-link gs-card-link">Learn more</a>
                  </div>
                  <div class="gs-process__card-face gs-process__card-back">
                    <img src="<?php echo url('assets/imgs/how-it-works/4.png'); ?>" alt="Finishing touches for private label packaging">
                  </div>
                </div>
              </div>
            </div>

            <div class="gs-process__cta gs-cta">
              <a href="how-it-works.php" class="gs-process__btn gs-btn">Start Your Journey <span class="gs-process__dot dot"></span></a>
            </div>
          </div>
        </section>

        <!-- Milestone Section Start -->
        <section class="milestone-highlight section-spacing-120 rr-ov-hidden">
          <div class="milestone-highlight__overlay"></div>
          <div class="container">
            <div class="milestone-highlight__intro wow fadeInUp" data-wow-delay=".2s">
              <span class="milestone-highlight__eyebrow">Growth Snapshot</span>
              <h2 class="milestone-highlight__title">~Our Milestones~</h2>
              <p class="milestone-highlight__lead">A quick look at the scale, consistency, and trust we keep building with every private label partnership.</p>
            </div>
            <div class="milestone-grid">
              <div class="milestone-card wow fadeInUp" data-wow-delay=".1s">
                <div class="milestone-card__icon-wrap">
                  <img src="<?php echo url('assets/imgs/home/milestone/4381dcfc16-300x254.webp'); ?>" alt="Monthly worldwide inquiries">
                </div>
                <span class="milestone-card__kicker">Monthly Avg.</span>
                <h3 class="milestone-card__number js-milestone-number" data-target="1075">0+</h3>
                <p class="milestone-card__text">Monthly Worldwide Inquiries</p>
              </div>
              <div class="milestone-card wow fadeInUp" data-wow-delay=".2s">
                <div class="milestone-card__icon-wrap">
                  <img src="<?php echo url('assets/imgs/home/milestone/f99c232e29-2-300x202.webp'); ?>" alt="Customers served monthly">
                </div>
                <span class="milestone-card__kicker">Monthly Avg.</span>
                <h3 class="milestone-card__number js-milestone-number" data-target="950">0+</h3>
                <p class="milestone-card__text">Customer's Served Monthly</p>
              </div>
              <div class="milestone-card wow fadeInUp" data-wow-delay=".3s">
                <div class="milestone-card__icon-wrap">
                  <img src="<?php echo url('assets/imgs/home/milestone/ec2ce0607f-150x150.webp'); ?>" alt="Contract manufacturing for brands">
                </div>
                <span class="milestone-card__kicker">Brand Scale</span>
                <h3 class="milestone-card__number js-milestone-number" data-target="650">0+</h3>
                <p class="milestone-card__text">Contract Manufacturing for Brands</p>
              </div>
              <div class="milestone-card wow fadeInUp" data-wow-delay=".4s">
                <div class="milestone-card__icon-wrap">
                  <img src="<?php echo url('assets/imgs/home/milestone/b3099fe017-150x150.webp'); ?>" alt="Ayurvedic personal care formulations">
                </div>
                <span class="milestone-card__kicker">Formula Library</span>
                <h3 class="milestone-card__number js-milestone-number" data-target="525">0+</h3>
                <p class="milestone-card__text">Ayurvedic Personal Care Formulations</p>
              </div>
            </div>
          </div>
        </section>
        <!-- Milestone Section End -->

        <!-- Global Presence Map Section Start -->
        <section class="global-presence section-spacing-120 rr-ov-hidden js-global-presence">
          <div class="container-fluid" id="section-map">
            <div class="section-heading wow fadeInUp" data-wow-delay=".2s">
              <h2 class="section-heading__title">~ Our Global Presence ~</h2>
            </div>
            <div class="global-map-stage wow fadeInUp" data-wow-delay=".3s">
              <div class="map-wrapper">
                <img decoding="async" src="<?php echo url('assets/imgs/home/globe.png'); ?>" alt="Clean Map" class="map-image map-clean">
                <div class="map-interactive-layer">
                  <img decoding="async" src="<?php echo url('assets/imgs/home/globe.png'); ?>" alt="Shaded Map" class="map-image">
                  <div id="pinsContainer"></div>
                </div>
              </div>
            </div>
          </div>
        </section>
        <!-- Global Presence Map Section End -->

        <section class="reviews-section">
          <div class="reviews-section__grid">
            <div class="reviews-section__intro" id="rvIntroPanel">
              <p class="rv-label"><span><i class="fa-solid fa-shield-halved"></i></span> Verified Reviews</p>
              <h2 class="rv-heading" id="rvHeading">mybrandplease.com is rated <b>Excellent</b></h2>
              <div class="rv-score-card">
                <div class="rv-score-card__brand">
                  <img src="<?php echo url('uploads/logo/trusp.png'); ?>" alt="Trustpilot" id="rvScoreLogo">
                  <span id="rvScoreName">Trustpilot</span>
                </div>
                <div class="rv-score-card__blocks rv-score-card__blocks--tp" id="rvScoreBlocks" aria-label="4.4 out of 5 stars">
                  <span><i class="fa-solid fa-star"></i></span>
                  <span><i class="fa-solid fa-star"></i></span>
                  <span><i class="fa-solid fa-star"></i></span>
                  <span><i class="fa-solid fa-star"></i></span>
                  <span class="is-half"><i class="fa-solid fa-star"></i></span>
                </div>
                <p>Rated <strong id="rvScoreValue">4.4</strong> &bull; <span id="rvScoreText">Excellent</span></p>
                <div class="rv-score-card__verified"><i class="fa-solid fa-shield-halved"></i> Verified reviews from real customers</div>
              </div>
              <div class="rv-trust-strip">
                <span><i class="fa-solid fa-shield-halved"></i><b>1000+</b> businesses</span>
                <span><i class="fa-solid fa-users"></i><b>98%</b> satisfaction</span>
                <span><i class="fa-solid fa-star"></i>Quality you can rely on</span>
              </div>
              <div class="rv-platform-badge" id="rvPlatformBadge" aria-hidden="true">
                <img src="" alt="" id="rvPlatformBadgeLogo">
              </div>
            </div>
            <div class="reviews-section__content">
              <div class="rv-tabs">
            <button class="rv-tab active-all" onclick="filterPlat('all', this)">All Reviews</button>
            <button class="rv-tab" onclick="filterPlat('tp', this)"> <img src="<?php echo url('uploads/logo/trusp.png'); ?>" alt="Trustpilot"> Trustpilot</button>
            <button class="rv-tab" onclick="filterPlat('goog', this)"> <img src="<?php echo url('uploads/logo/goo.png'); ?>" alt="Google"> Google</button>
            <button class="rv-tab" onclick="filterPlat('ali', this)"> <img src="<?php echo url('uploads/logo/ali.png'); ?>" alt="Alibaba"> Alibaba</button>
          </div>

          <div class="rv-outer">
            <div class="rv-progress"><div class="rv-pbar" id="pbar"></div></div>
            <div class="rv-wrap"><div class="rv-track" id="track"></div></div>
            <div class="rv-bottom">
              <div class="rv-dots" id="dots"></div>
              <div class="rv-arrows">
                <button class="rv-arr" onclick="prev()">&#8592;</button>
                <button class="rv-arr" onclick="next()">&#8594;</button>
              </div>
            </div>
          </div>
            </div>
          </div>
          

          
        </section>

       
        <section class="social-reels rr-ov-hidden" id="video-showcase">
          <div class="container rr-container-1350">
            <div class="social-reels__intro">
              <span class="social-reels__eyebrow">Video Showcase</span>
              <h2 class="social-reels__title">Watch it ! Love it! Build it !</h2>
              <p class="social-reels__lead">We don’t just manufacture products. We manufacture dominance.</p>
            </div>
          </div>
          <div class="social-reels__viewport">
            <button class="social-reels__nav social-reels__nav--prev" type="button" aria-label="Previous video">
              <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <div class="social-reels__slider swiper js-video-showcase-slider" aria-label="Customer social reels">
              <div class="swiper-wrapper">
              <?php $renderedReels = 0; ?>
              <?php foreach ($homeInstagramReels as $idx => $reel): ?>
                <?php
                  if ($renderedReels >= 8) {
                    break;
                  }
                  $reelUrl = (string) ($reel['reel_url'] ?? '');
                  $videoPath = trim((string) ($reel['video_path'] ?? ''));
                  $videoUrl = '';
                  if ($videoPath !== '') {
                    $videoAbsolutePath = __DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $videoPath), DIRECTORY_SEPARATOR);
                    if (is_file($videoAbsolutePath)) {
                      $videoUrl = url($videoPath);
                    } else {
                      $videoPath = '';
                    }
                  }
                  $cleanUrl = preg_replace('/\?.*$/', '', $reelUrl) ?: $reelUrl;
                  $cleanUrl = rtrim($cleanUrl, '/');
                  $embedUrl = str_ends_with($cleanUrl, '/embed') ? $cleanUrl : ($cleanUrl !== '' ? ($cleanUrl . '/embed') : '');
                  if ($videoUrl === '' && $embedUrl === '') {
                    continue;
                  }
                  $renderedReels++;
                ?>
                <div
                  class="social-reels__card js-reel-card swiper-slide"
                  aria-label="Open reel <?php echo $renderedReels; ?>"
                  <?php if ($videoUrl !== ''): ?>
                    data-video-src="<?php echo htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                  <?php elseif ($embedUrl !== ''): ?>
                    data-embed-src="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                  <?php endif; ?>>
                  <?php if ($videoUrl !== ''): ?>
                    <video
                      src="<?php echo htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                      class="social-reels__video"
                      playsinline
                      muted
                      autoplay
                      loop
                      preload="metadata"></video>
                    <button class="social-reels__volume-btn" type="button" aria-label="Unmute reel" aria-pressed="false">
                      <i class="fa-solid fa-volume-xmark" aria-hidden="true"></i>
                    </button>
                  <?php elseif ($embedUrl !== ''): ?>
                    <iframe
                      src="<?php echo htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                      class="social-reels__iframe"
                      title="Instagram reel <?php echo $renderedReels; ?>"
                      loading="lazy"
                      allow="autoplay; encrypted-media; picture-in-picture; clipboard-write"
                      allowfullscreen>
                    </iframe>
                  <?php endif; ?>
                  <?php if ($reelUrl !== ''): ?>
                    <a href="<?php echo htmlspecialchars((string) $reelUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="social-reels__badge social-reels__badge--link" aria-label="Open Instagram reel <?php echo $renderedReels; ?>">
                      <i class="fa-brands fa-instagram"></i>
                    </a>
                  <?php else: ?>
                    <span class="social-reels__badge"><i class="fa-brands fa-instagram"></i></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
              </div>
            </div>
            <button class="social-reels__nav social-reels__nav--next" type="button" aria-label="Next video">
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="social-reels__scrollbar swiper-scrollbar" aria-label="Video showcase slider"></div>
          </div>
          <div class="container rr-container-1350">
            <div class="social-reels__outro">
              <p class="social-reels__tagline">mybrandplease.com - turns your ambition into artistry, and your brand into a lasting legacy.</p>
            </div>
          </div>
        </section>

        <div class="social-reels__modal" id="social-reels-modal" aria-hidden="true">
          <div class="social-reels__modal-backdrop" data-social-reels-close></div>
          <div class="social-reels__modal-dialog" role="dialog" aria-modal="true" aria-label="Video showcase">
            <button class="social-reels__modal-close" type="button" data-social-reels-close aria-label="Close video">&times;</button>
            <div class="social-reels__modal-shell">
              <div class="social-reels__modal-bar">
                <span class="social-reels__modal-pill">Video Showcase</span>
                <span class="social-reels__modal-meta">mybrandplease.com</span>
              </div>
              <video class="social-reels__modal-video" controls playsinline></video>
              <iframe class="social-reels__modal-video social-reels__modal-iframe" title="Video showcase reel" allow="autoplay; encrypted-media; picture-in-picture; clipboard-write" allowfullscreen></iframe>
            </div>
          </div>
        </div>

        <script>
          window.addEventListener('load', function () {
            const section = document.getElementById('video-showcase');
            if (!section) return;

            const swiperEl = section.querySelector('.js-video-showcase-slider');
            if (!swiperEl || typeof Swiper === 'undefined') return;

            const cards = Array.from(swiperEl.querySelectorAll('.social-reels__card'));
            if (cards.length === 0) return;

            function prepareCard(card) {
              const video = card.querySelector('video');
              if (!video) {
                card.classList.add('is-ready');
                return;
              }
              video.muted = true;
              video.autoplay = true;
              video.loop = true;
              video.addEventListener('loadeddata', function () {
                card.classList.add('is-ready');
              }, { once: true });
              if (video.readyState >= 2) {
                card.classList.add('is-ready');
              }
              const playPromise = video.play();
              if (playPromise && typeof playPromise.catch === 'function') {
                playPromise.catch(function () {});
              }
            }

            cards.forEach(prepareCard);

            function playCardVideos() {
              section.querySelectorAll('.social-reels__video').forEach(function (video) {
                video.muted = true;
                const playPromise = video.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                  playPromise.catch(function () {});
                }
              });
            }

            function updateVolumeButton(button, video) {
              const isMuted = video.muted;
              button.setAttribute('aria-pressed', isMuted ? 'false' : 'true');
              button.setAttribute('aria-label', isMuted ? 'Unmute reel' : 'Mute reel');
              button.classList.toggle('is-unmuted', !isMuted);
              button.innerHTML = isMuted
                ? '<i class="fa-solid fa-volume-xmark" aria-hidden="true"></i>'
                : '<i class="fa-solid fa-volume-high" aria-hidden="true"></i>';
            }

            section.querySelectorAll('.social-reels__volume-btn').forEach(function (button) {
              button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                const card = button.closest('.social-reels__card');
                const video = card ? card.querySelector('video') : null;
                if (!video) return;
                video.muted = !video.muted;
                if (!video.muted) {
                  const playPromise = video.play();
                  if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(function () {});
                  }
                }
                updateVolumeButton(button, video);
              });
            });

            const modal = document.getElementById('social-reels-modal');
            const modalVideo = modal ? modal.querySelector('video') : null;
            const modalIframe = modal ? modal.querySelector('iframe') : null;

            function closeModal() {
              if (!modal || !modalVideo || !modalIframe) return;
              modal.classList.remove('is-open');
              modal.setAttribute('aria-hidden', 'true');
              document.body.style.overflow = '';
              modalVideo.pause();
              modalVideo.removeAttribute('src');
              modalVideo.load();
              modalIframe.removeAttribute('src');
              modalVideo.style.display = '';
              modalIframe.style.display = 'none';
            }

            function openModal(card) {
              if (!modal || !modalVideo || !modalIframe) return;
              const videoSrc = card.getAttribute('data-video-src') || '';
              const embedSrc = card.getAttribute('data-embed-src') || '';
              if (videoSrc !== '') {
                modalIframe.style.display = 'none';
                modalIframe.removeAttribute('src');
                modalVideo.style.display = 'block';
                modalVideo.src = videoSrc;
                modalVideo.currentTime = 0;
                modalVideo.muted = false;
                const playPromise = modalVideo.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                  playPromise.catch(function () {});
                }
              } else if (embedSrc !== '') {
                modalVideo.pause();
                modalVideo.removeAttribute('src');
                modalVideo.style.display = 'none';
                modalIframe.style.display = 'block';
                modalIframe.src = embedSrc;
              } else {
                return;
              }
              modal.classList.add('is-open');
              modal.setAttribute('aria-hidden', 'false');
              document.body.style.overflow = 'hidden';
            }

            cards.forEach(function (card) {
              card.setAttribute('role', 'button');
              card.setAttribute('tabindex', '0');
            });

            section.addEventListener('click', function (event) {
              if (event.target.closest('a, button')) return;
              const card = event.target.closest('.social-reels__card');
              if (card) {
                openModal(card);
              }
            });

            section.addEventListener('keydown', function (event) {
              if (event.key !== 'Enter' && event.key !== ' ') return;
              const card = event.target.closest('.social-reels__card');
              if (!card) return;
              event.preventDefault();
              openModal(card);
            });

            if (modal) {
              modal.querySelectorAll('[data-social-reels-close]').forEach(function (closeButton) {
                closeButton.addEventListener('click', closeModal);
              });
              document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                  closeModal();
                }
              });
            }

            if (swiperEl.swiper) {
              swiperEl.swiper.destroy(true, true);
            }

            const reelsSwiper = new Swiper(swiperEl, {
              loop: cards.length > 1,
              speed: 700,
              grabCursor: true,
              watchOverflow: false,
              slidesPerView: 'auto',
              spaceBetween: 24,
              centeredSlides: false,
              observer: true,
              observeParents: true,
              loopAdditionalSlides: cards.length,
              autoplay: {
                delay: 3000,
                disableOnInteraction: false,
                pauseOnMouseEnter: false
              },
              navigation: {
                prevEl: section.querySelector('.social-reels__nav--prev'),
                nextEl: section.querySelector('.social-reels__nav--next')
              },
              scrollbar: {
                el: section.querySelector('.social-reels__scrollbar'),
                draggable: true,
                dragSize: 90
              },
              on: {
                init: playCardVideos,
                slideChangeTransitionEnd: playCardVideos
              },
              breakpoints: {
                0: {
                  spaceBetween: 16
                },
                768: {
                  spaceBetween: 24
                }
              }
            });

            if (reelsSwiper.autoplay && typeof reelsSwiper.autoplay.start === 'function') {
              reelsSwiper.autoplay.start();
            }

            document.addEventListener('visibilitychange', function () {
              if (!document.hidden && reelsSwiper.autoplay && typeof reelsSwiper.autoplay.start === 'function') {
                reelsSwiper.autoplay.start();
              }
            });
          });
        </script>

        <!-- Office Section Start -->
        <section class="office-showcase section-spacing-120 rr-ov-hidden">
          <div class="container">
            <div class="office-showcase__intro wow fadeInUp" data-wow-delay=".3s">
              <span class="office-showcase__eyebrow">Global Presence</span>
              <h2 class="office-showcase__title">~ Our Offices ~</h2>
              <p class="office-showcase__lead">Meet the teams supporting our private label partners across key markets with direct access to local guidance, production coordination, and faster communication.</p>
            </div>
            <div class="office-grid">
              <?php
                $displayOffices = array_values($homeOffices);
                if (count($displayOffices) < 2) {
                  $displayOffices[] = [
                    'country' => 'United Kingdom',
                    'company_name' => 'Mybrandplease UK',
                    'address' => 'Unit 1, Durham Way South, Newton Aycliffe, DL5 6ZF, UNITED KINGDOM',
                    'email' => 'info@mybrandplease.com',
                    'phone' => '+44 7940 359995',
                    'image_path' => 'assets/imgs/home/office/Flag-United-Kingdom.webp',
                    'website' => 'https://www.mybrandplease.com',
                  ];
                }
                usort($displayOffices, function ($a, $b) {
                  $aIsAustralia = strcasecmp(trim((string) ($a['country'] ?? '')), 'Australia') === 0;
                  $bIsAustralia = strcasecmp(trim((string) ($b['country'] ?? '')), 'Australia') === 0;
                  return $aIsAustralia <=> $bIsAustralia;
                });
                $officeDelay = 0.1;
                foreach ($displayOffices as $office):
                  $officeCountry = trim((string) ($office['country'] ?? 'Office'));
                  $officeCompanyName = trim((string) ($office['company_name'] ?? ''));
                  $officeAddress = trim((string) ($office['address'] ?? ''));
                  $officeEmail = trim((string) ($office['email'] ?? ''));
                  $officePhone = trim((string) ($office['phone'] ?? ''));
                  $officeRegistrationLabel = trim((string) ($office['registration_label'] ?? ''));
                  $officeRegistrationNumber = trim((string) ($office['registration_number'] ?? ''));
                  $officeWebsite = trim((string) ($office['website'] ?? ''));
                  if ($officeWebsite === '') {
                    $officeWebsite = 'https://www.mybrandplease.com';
                  }
                  $officeImage = trim((string) ($office['image_path'] ?? ''));
                  $officeImageUrl = $officeImage !== '' ? url($officeImage) : url('assets/imgs/home/office/Flag-United-Kingdom.webp');
                  $officePhoneHref = preg_replace('/\D+/', '', $officePhone);
                ?>
                <article class="office-card wow fadeInUp" data-wow-delay=".<?php echo (int) round($officeDelay * 10); ?>s">
                  <div class="office-card__topline"></div>
                  <div class="office-card__flag">
                    <img src="<?php echo htmlspecialchars($officeImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($officeCountry, ENT_QUOTES, 'UTF-8'); ?> Office">
                  </div>
                  <div class="office-card__body">
                    <h3 class="office-card__title"><?php echo htmlspecialchars($officeCountry, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if ($officeCompanyName !== ''): ?>
                      <p class="office-card__company"><?php echo htmlspecialchars($officeCompanyName, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <p class="office-card__address"><?php echo nl2br(htmlspecialchars($officeAddress, ENT_QUOTES, 'UTF-8')); ?></p>
                    <div class="office-card__meta-list">
                      <?php if ($officeRegistrationLabel !== '' || $officeRegistrationNumber !== ''): ?>
                        <div class="office-card__meta office-card__meta--plain">
                          <span class="office-card__meta-icon"><i class="fa-regular fa-building"></i></span>
                          <span>
                            <?php if ($officeRegistrationLabel !== ''): ?>
                              <strong><?php echo htmlspecialchars($officeRegistrationLabel, ENT_QUOTES, 'UTF-8'); ?>:</strong>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($officeRegistrationNumber, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        </div>
                      <?php endif; ?>
                      <?php if ($officePhone !== ''): ?>
                        <a class="office-card__meta" href="https://wa.me/<?php echo htmlspecialchars($officePhoneHref ?: $officePhone, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                          <span class="office-card__meta-icon"><i class="fa-brands fa-whatsapp"></i></span>
                          <span><?php echo htmlspecialchars($officePhone, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                      <?php endif; ?>
                      <?php if ($officeEmail !== ''): ?>
                        <a class="office-card__meta" href="mailto:<?php echo htmlspecialchars($officeEmail, ENT_QUOTES, 'UTF-8'); ?>">
                          <span class="office-card__meta-icon"><i class="fa-regular fa-envelope"></i></span>
                          <span><?php echo htmlspecialchars($officeEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                      <?php endif; ?>
                      <a class="office-card__meta" href="<?php echo htmlspecialchars($officeWebsite, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <span class="office-card__meta-icon"><i class="fa-solid fa-globe"></i></span>
                        <span><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $officeWebsite), ENT_QUOTES, 'UTF-8'); ?></span>
                      </a>
                    </div>
                  </div>
                </article>
              <?php $officeDelay += 0.1; endforeach; ?>
            </div>
          </div>
        </section>
        <!-- Office Section End -->

        <!-- CTA Section Start -->
        <section class="cta-section section-spacing-120 rr-ov-hidden">
          <div class="container rr-container-1350">
            <div class="cta-wrapper">
              <div class="row align-items-center bg-white rounded-4 text-center">
                
                <div class="col-lg-6 ">
                  <div class="cta-form">
                    <div class="">
                      <h3 class="cta-form-title">Request a Free Consultation</h3>
                      <p class="cta-form-subtitle">Fill out the form below and our team will get back to you within 24 hours.</p>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <button class="cta-enquiry-btn" id="open-enquiry-btn" type="button">
                    <span class="cta-btn-text">Get Free Enquiry</span>
                    <span class="cta-btn-icon">
                      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 10H15M15 10L10 5M15 10L10 15" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
        <!-- CTA Section End -->

<script>
  window.addEventListener('load', function () {
    const root = document;

    const imageTargets = root.querySelectorAll(
      '.intro1__thumb img, .category1-item__thumb img, .cat-image, .card-img-top, .milestone-card__icon-wrap img, .office-card__flag img, .auto-scroll-item img, .partners-carousel-card img, .social-reels__video'
    );
      imageTargets.forEach(function (el) {
        if (!el.hasAttribute('data-aos')) el.setAttribute('data-aos', 'zoom-in');
        if (!el.hasAttribute('data-aos-duration')) el.setAttribute('data-aos-duration', '800');
      });

    const textTargets = root.querySelectorAll(
      '.section-heading__title, .section-heading__subtitle, .intro1__content-title, .intro1__content-desc, .cat-title, .milestone-card__text, .office-card__title, .office-card__address, .cta-form-title, .cta-form-subtitle'
    );
    textTargets.forEach(function (el, i) {
      if (!el.hasAttribute('data-aos')) el.setAttribute('data-aos', i % 2 === 0 ? 'fade-up' : 'fade-left');
      if (!el.hasAttribute('data-aos-duration')) el.setAttribute('data-aos-duration', '700');
    });

    const cardTargets = root.querySelectorAll(
      '.js-why-card, .milestone-card, .office-card, .cat-card, .social-reels__card'
    );
    cardTargets.forEach(function (el, i) {
      if (!el.hasAttribute('data-aos')) el.setAttribute('data-aos', 'fade-up');
      if (!el.hasAttribute('data-aos-delay')) el.setAttribute('data-aos-delay', String((i % 6) * 80));
      if (!el.hasAttribute('data-aos-duration')) el.setAttribute('data-aos-duration', '850');
    });

    if (window.AOS && typeof window.AOS.init === 'function') {
      window.AOS.init({
        once: false,
        mirror: true,
        offset: 60,
        duration: 800,
        easing: 'ease-out-cubic'
      });
    }

      if (window.gsap && window.ScrollTrigger) {
        window.gsap.registerPlugin(window.ScrollTrigger);

        const parallaxImages = root.querySelectorAll(
          '.intro1__thumb img, .category1-item__thumb img, .card-img-top, .office-card__flag img'
        );
        window.gsap.set(parallaxImages, { willChange: 'transform' });
        parallaxImages.forEach(function (img) {
          window.gsap.fromTo(img, { y: -12 }, {
            y: 12,
            ease: 'none',
            scrollTrigger: {
              trigger: img.closest('section') || img,
              start: 'top bottom',
              end: 'bottom top',
              scrub: true
            }
          });
        });
      }

      (function initGlobalMapSequence() {
        const stage = root.querySelector('.global-map-stage');
        const pinsContainer = root.getElementById('pinsContainer');
        if (!stage || !pinsContainer) return;

        const locations = [
          { name: 'North America', top: 44, left: 15, height: 20 },
          { name: 'Canada', top: 32, left: 18, height: 40 },
          { name: 'Africa', top: 54, left: 52, height: 80 },
          { name: 'United Kingdom', top: 32, left: 45, height: 100 },
          { name: 'Europe', top: 38, left: 50, height: 45 },
          { name: 'Asia', top: 45, left: 70, height: 80 },
          { name: 'South America', top: 68, left: 27, height: 60 },
          { name: 'Australia', top: 82, left: 89, height: 60 }
        ];

        const pinElements = [];

        locations.forEach(function (loc, index) {
          const pin = root.createElement('div');
          pin.className = 'map-pin';
          if (loc.top >= 50) {
            pin.classList.add('southern');
          }
          pin.style.top = loc.top + '%';
          pin.style.left = loc.left + '%';
          pin.style.animationDelay = (index * 0.1) + 's';
          pin.innerHTML =
            '<div class="pin-label">' + loc.name + '</div>' +
            '<div class="pin-stick" style="height: ' + loc.height + 'px;"></div>' +
            '<div class="pin-dot"></div>';
          pinsContainer.appendChild(pin);
          pinElements.push(pin);
        });

        function adjustStickHeights() {
          const scaleFactor = window.innerWidth <= 768 ? 0.5 : 1;
          pinElements.forEach(function (pin, index) {
            const stick = pin.querySelector('.pin-stick');
            stick.style.height = (locations[index].height * scaleFactor) + 'px';
          });
        }

        function activateMap() {
          if (!stage.classList.contains('active')) {
            stage.classList.add('active');
            pinElements.forEach(function (pin) {
              pin.classList.add('pop-up');
            });
          }
        }

        function deactivateMap() {
          if (stage.classList.contains('active')) {
            stage.classList.remove('active');
            pinElements.forEach(function (pin) {
              pin.classList.remove('pop-up');
            });
          }
        }

        adjustStickHeights();
        window.addEventListener('resize', adjustStickHeights);
        stage.addEventListener('mouseenter', activateMap);
        stage.addEventListener('mouseleave', deactivateMap);
        stage.addEventListener('focusin', activateMap);
        stage.addEventListener('focusout', deactivateMap);
      })();
    });
  </script>


 <!-- Auto-scroll Section Start -->
        <section class="partners-carousel-section section-spacing-120 rr-ov-hidden">
          <div class="container">
            <div class="section-heading wow fadeInUp" data-wow-delay=".3s">
              <h2 class="section-heading__title partners-carousel-section__title">~ Our Manufactured Products are Sold at ~</h2>
            </div>
            <?php
              $partnerCompanies = [
                ['src' => 'assets/imgs/home/Amazon-logo-min-300x126.jpg', 'alt' => 'Amazon'],
                ['src' => 'assets/imgs/home/Costco_Wholesale_logo-min-300x108.jpg', 'alt' => 'Costco'],
                ['src' => 'assets/imgs/home/desertcart-logo-min-300x74.jpg', 'alt' => 'Desert Cart'],
                ['src' => 'assets/imgs/home/EBay_logo-min-300x120.jpg', 'alt' => 'eBay'],
                ['src' => 'assets/imgs/home/Etsy-min-300x171.jpg', 'alt' => 'Etsy'],
                ['src' => 'assets/imgs/home/final_logo_1_37ee31bf-a041-4af1-9b0e-d86fd4b2da83-300x85.jpg', 'alt' => 'MyBrand'],
                ['src' => 'assets/imgs/home/iherb-min-300x117.jpg', 'alt' => 'iHerb'],
                ['src' => 'assets/imgs/home/Macys_Logo-min-300x86.jpg', 'alt' => 'Macy\'s'],
                ['src' => 'assets/imgs/home/Nordstrom-logo-min-300x169.jpg', 'alt' => 'Nordstrom'],
                ['src' => 'assets/imgs/home/Saks_Fifth_Avenue_Logo_-min-300x225.jpg', 'alt' => 'Saks Fifth Avenue'],
                ['src' => 'assets/imgs/home/target-min-300x83.jpg', 'alt' => 'Target'],
                ['src' => 'assets/imgs/home/the-detox-market-min-300x28.jpg', 'alt' => 'The Detox Market'],
                ['src' => 'assets/imgs/home/TJ_Maxx-min-300x96.jpg', 'alt' => 'TJ Maxx'],
                ['src' => 'assets/imgs/home/Walmart_logo.svg-min-300x72.jpg', 'alt' => 'Walmart'],
              ];
            ?>
            <div class="partners-carousel-track">
              <div class="partners-carousel-list">
                <?php for ($loop = 0; $loop < 2; $loop++): ?>
                  <?php foreach ($partnerCompanies as $company): ?>
                    <div class="partners-carousel-card"<?php echo $loop === 1 ? ' aria-hidden="true"' : ''; ?>>
                      <img src="<?php echo htmlspecialchars($company['src'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($company['alt'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                  <?php endforeach; ?>
                <?php endfor; ?>
              </div>
            </div>
          </div>
        </section>
        <!-- Auto-scroll Section End -->


<?php
  $partnerLogos = [
    ['src' => 'assets/imgs/partner-logos/31.png', 'alt' => 'TÜV Rheinland'],
    ['src' => 'assets/imgs/partner-logos/CLEAN%20LABEL.png', 'alt' => 'Clean Label'],
    ['src' => 'assets/imgs/partner-logos/COSMOS.png', 'alt' => 'Cosmos'],
    ['src' => 'assets/imgs/partner-logos/CPNP.png', 'alt' => 'CPNP Registered'],
    ['src' => 'assets/imgs/partner-logos/CREDO%20New.png', 'alt' => 'Credo'],
    ['src' => 'assets/imgs/partner-logos/Cruelty%20Free.png', 'alt' => 'Cruelty Free'],
    ['src' => 'assets/imgs/partner-logos/EWG.png', 'alt' => 'EWG Verified'],
    ['src' => 'assets/imgs/partner-logos/GLP.png', 'alt' => 'GLP Certified'],
    ['src' => 'assets/imgs/partner-logos/GMP.png', 'alt' => 'GMP Certified'],
    ['src' => 'assets/imgs/partner-logos/LOW%20MOQ.png', 'alt' => 'Low MOQ'],
    ['src' => 'assets/imgs/partner-logos/MADE%20SAFE.png', 'alt' => 'Made Safe'],
    ['src' => 'assets/imgs/partner-logos/MOCRA.png', 'alt' => 'MOCRA Compliant'],
    ['src' => 'assets/imgs/partner-logos/SEPHORA.png', 'alt' => 'Sephora'],
    ['src' => 'assets/imgs/partner-logos/USDA%20ORGANIC.png', 'alt' => 'USDA Organic'],
    ['src' => 'assets/imgs/partner-logos/USFDA.png', 'alt' => 'FDA Registered'],
    ['src' => 'assets/imgs/partner-logos/VEGAN.png', 'alt' => 'Vegan'],
  ];
?>
<section class="auto-scroll-section auto-scroll-section--footer rr-ov-hidden">
  <div class="auto-scroll-wrapper">
    <div class="auto-scroll-content">
      <?php for ($loop = 0; $loop < 2; $loop++): ?>
        <?php foreach ($partnerLogos as $logo): ?>
          <div class="auto-scroll-item"<?php echo $loop === 1 ? ' aria-hidden="true"' : ''; ?>>
            <img src="<?php echo htmlspecialchars($logo['src'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($logo['alt'], ENT_QUOTES, 'UTF-8'); ?>">
            <span class="auto-scroll-label"><?php echo htmlspecialchars($logo['alt'], ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
        <?php endforeach; ?>
      <?php endfor; ?>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
