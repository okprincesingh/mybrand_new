<?php
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

$meta = $meta ?? [];

$currentPageId = (int) ($_GET['page_id'] ?? 0);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptBase = basename((string) $requestPath);
$slugCandidate = strtolower(pathinfo($scriptBase, PATHINFO_FILENAME));
if ($slugCandidate === 'index') {
    $pathParts = array_values(array_filter(explode('/', trim((string) $requestPath, '/'))));
    $slugCandidate = count($pathParts) > 1 ? strtolower((string) $pathParts[count($pathParts) - 2]) : 'home';
}

$seoRow = null;
$pdo = db();
if ($pdo) {
    if ($currentPageId > 0) {
        $seoRow = db_fetch_one($pdo, 'SELECT p.id,p.slug,p.title,p.status,pm.meta_title,pm.meta_description,pm.meta_keywords,pm.canonical_url FROM pages p LEFT JOIN page_meta pm ON pm.page_id = p.id WHERE p.id = :id LIMIT 1', [':id' => $currentPageId]);
    }

    if (!$seoRow && $slugCandidate !== '') {
        $seoRow = db_fetch_one($pdo, 'SELECT p.id,p.slug,p.title,p.status,pm.meta_title,pm.meta_description,pm.meta_keywords,pm.canonical_url FROM pages p LEFT JOIN page_meta pm ON pm.page_id = p.id WHERE p.slug = :slug LIMIT 1', [':slug' => $slugCandidate]);
    }
}

if (is_array($seoRow) && (($seoRow['status'] ?? 'draft') === 'published')) {
    $meta['title'] = (string) (($seoRow['meta_title'] ?? '') !== '' ? $seoRow['meta_title'] : ($seoRow['title'] ?? 'mybrandplease'));
    $meta['description'] = (string) ($seoRow['meta_description'] ?? '');
    $seoCanonical = (string) ($seoRow['canonical_url'] ?? '');
    if ($seoCanonical === '') {
        $seoSlug = (string) ($seoRow['slug'] ?? '');
        $seoCanonical = $seoSlug === 'home' ? 'index.php' : ($seoSlug !== '' ? ($seoSlug . '.php') : '');
    }
    if ($seoCanonical !== '') {
        $meta['canonical'] = $seoCanonical;
    }
    $meta['keywords'] = (string) ($seoRow['meta_keywords'] ?? '');
}

$title = $meta['title'] ?? 'mybrandplease';
$description = $meta['description'] ?? 'Private label personal care manufacturing with premium formulations.';
$keywords = $meta['keywords'] ?? '';
$brandSearch = ['mybrandplease', 'my brandplease'];
$title = str_ireplace($brandSearch, 'mybrandplease', (string) $title);
$description = str_ireplace($brandSearch, 'mybrandplease', (string) $description);
$keywords = str_ireplace($brandSearch, 'mybrandplease', (string) $keywords);
$robots = $meta['robots'] ?? 'index,follow';
$favicon = $meta['favicon'] ?? 'assets/imgs/logo/favicon-white.png';
$canonical = $meta['canonical'] ?? ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$breadcrumbBackgroundPath = function_exists('cms_get_breadcrumb_background_path')
    ? cms_get_breadcrumb_background_path()
    : 'assets/imgs/breadcumbBg.jpg';
$breadcrumbBackgroundUrl = preg_match('#^(https?:)?//#i', (string) $breadcrumbBackgroundPath)
    ? (string) $breadcrumbBackgroundPath
    : url((string) $breadcrumbBackgroundPath);
if (!preg_match('#^(https?:)?//#i', (string) $canonical)) {
    $canonical = url((string) $canonical);
}

$currentPhpPage = basename($_SERVER['PHP_SELF']);
$isHomepage = $currentPhpPage === 'index.php';
if ($isHomepage) {
    if ($title === 'mybrandplease | Home' || $title === 'mybrandplease') {
        $title = 'mybrandplease | Private Label Cosmetics Manufacturer';
    }
    if ($description === 'mybrandplease - Home page' || $description === 'Private label personal care manufacturing with premium formulations.') {
        $description = 'Launch premium skin care, hair care, body care, bathing soaps, and personal care products with mybrandplease private label manufacturing.';
    }
}

$socialTitle = (string) ($meta['og_title'] ?? $meta['social_title'] ?? $title);
$socialDescription = (string) ($meta['og_description'] ?? $meta['social_description'] ?? $description);
$socialImagePath = (string) ($meta['og_image'] ?? $meta['social_image'] ?? 'assets/imgs/logo/footer.png');
$socialImage = preg_match('#^(https?:)?//#i', $socialImagePath) ? $socialImagePath : url($socialImagePath);
$socialUrl = (string) ($meta['og_url'] ?? $canonical);
$socialType = (string) ($meta['og_type'] ?? ($isHomepage ? 'website' : 'article'));
$socialImageWidth = (string) ($meta['og_image_width'] ?? $meta['social_image_width'] ?? '3125');
$socialImageHeight = (string) ($meta['og_image_height'] ?? $meta['social_image_height'] ?? '875');

$styles = $meta['styles'] ?? [
    'assets/vandor/bootstrap/bootstrap.min.css',
    'assets/vandor/fontawesome/fontawesome-pro.min.css',
    'assets/vandor/swiper/swiper-bundle.min.css',
    'assets/vandor/menu/meanmenu.min.css',
    'assets/vandor/popup/magnific-popup.css',
    'assets/vandor/nice-select/nice-select.css',
    'assets/vandor/wow/animate.css',
    'assets/vandor/odometer/odometer-theme-default.css',
    'assets/css/style.css',
    'assets/css/user-dropdown.css',
];

if ($isHomepage && !isset($meta['styles'])) {
    $homepageUnusedStyles = [
        'assets/vandor/popup/magnific-popup.css',
        'assets/vandor/nice-select/nice-select.css',
        'assets/vandor/odometer/odometer-theme-default.css',
    ];
    $styles = array_values(array_filter($styles, static function (string $href) use ($homepageUnusedStyles): bool {
        return !in_array($href, $homepageUnusedStyles, true);
    }));
}

// Add AOS CSS/JS on animation-enabled pages
$isAosPage = in_array($currentPhpPage, ['index.php', 'shop.php'], true);
if ($isAosPage) {
    $styles[] = 'https://unpkg.com/aos@next/dist/aos.css';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
  <?php if ($keywords !== ''): ?><meta name="keywords" content="<?php echo htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
  <meta name="robots" content="<?php echo htmlspecialchars($robots, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8'); ?>">
  <link rel="icon" type="image/x-icon" href="<?php echo url($favicon); ?>">
  <meta property="og:locale" content="en_US">
  <meta property="og:type" content="<?php echo htmlspecialchars($socialType, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:site_name" content="mybrandplease">
  <meta property="og:title" content="<?php echo htmlspecialchars($socialTitle, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($socialDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:url" content="<?php echo htmlspecialchars($socialUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image" content="<?php echo htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="<?php echo htmlspecialchars($socialImageWidth, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image:height" content="<?php echo htmlspecialchars($socialImageHeight, ENT_QUOTES, 'UTF-8'); ?>">
  <meta property="og:image:alt" content="mybrandplease logo and private label cosmetics manufacturing">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo htmlspecialchars($socialTitle, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="twitter:description" content="<?php echo htmlspecialchars($socialDescription, ENT_QUOTES, 'UTF-8'); ?>">
  <meta name="twitter:image" content="<?php echo htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach ($styles as $href): ?>
  <link rel="stylesheet" href="<?php echo url($href); ?>"<?php echo (str_contains((string) $href, '/wow/') || str_contains((string) $href, 'aos@')) ? ' media="(min-width: 992px)"' : ''; ?>>
<?php endforeach; ?>
<?php if ($isAosPage): ?>
  <script>
    if (window.matchMedia('(min-width: 992px)').matches) {
      var aosScript = document.createElement('script');
      aosScript.src = 'https://unpkg.com/aos@2.3.4/dist/aos.js';
      aosScript.defer = true;
      document.head.appendChild(aosScript);
    }
  </script>
<?php endif; ?>
<?php if ($isHomepage): ?>
  <link rel="preconnect" href="https://jaikvik.in" crossorigin>
  <link rel="dns-prefetch" href="//jaikvik.in">
<?php endif; ?>
  <meta name="google-site-verification" content="gL1f34T2493WB69KLImJg503rTBPfBHjJzvzJ-r57dY">
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-C0L7Y4STGF"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-C0L7Y4STGF');
  </script>

<style>
@media (max-width: 991.98px) {
  [data-aos],
  .wow {
    opacity: 1 !important;
    visibility: visible !important;
    transform: none !important;
    animation: none !important;
    transition: none !important;
  }

  .hero-marquee__track,
  .working-process-section__strip-services,
  .partners-carousel-list,
  .auto-scroll-content,
  .map-marker__dot {
    animation: none !important;
    transform: none !important;
  }
}
</style>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var bgUrl = '<?php echo htmlspecialchars($breadcrumbBackgroundUrl, ENT_QUOTES, 'UTF-8'); ?>';
    document.querySelectorAll('.breadcumb-wrapper[data-bg-src], .breadcumb2[data-bg-src]').forEach(function (el) {
      var requestedBg = el.getAttribute('data-bg-src') || bgUrl;
      el.style.backgroundImage = 'url("' + requestedBg + '")';
      el.style.backgroundPosition = 'center center';
      el.style.backgroundRepeat = 'no-repeat';
      el.style.backgroundSize = 'cover';
    });
  });
</script>

<!-- User Panel Styles -->
<?php if (basename($_SERVER['PHP_SELF']) === 'user-dashboard.php' || basename($_SERVER['PHP_SELF']) === 'user-orders.php' || basename($_SERVER['PHP_SELF']) === 'user-wishlist.php' || basename($_SERVER['PHP_SELF']) === 'user-addresses.php' || basename($_SERVER['PHP_SELF']) === 'user-profile.php' || basename($_SERVER['PHP_SELF']) === 'user-settings.php'): ?>
<style>
    /* Dashboard Layout */
    .dashboard-layout {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 24px;
    }
    .dashboard-sidebar {
        position: sticky;
        top: 20px;
        align-self: start;
    }
    .sidebar-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #eee;
    }
    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        font-size: 24px;
    }
    .user-details h4 {
        margin: 0 0 4px;
        font-size: 18px;
        font-weight: 600;
        color: #0C0C0C;
    }
    .user-details p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }
    .sidebar-nav ul {
        list-style: none;
        padding: 0;
        margin: 0 0 24px;
    }
    .nav-item {
        margin-bottom: 8px;
    }
    .nav-item a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .nav-item a:hover {
        background: #f8f9fa;
        color: #EE2D7A;
    }
    .nav-item.active a {
        background: #EE2D7A;
        color: #fff;
    }
    .nav-item i {
        font-size: 16px;
    }
    .sidebar-actions {
        padding-top: 16px;
        border-top: 1px solid #eee;
    }
    .btn-secondary {
        width: 100%;
        padding: 12px 16px;
        background: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    .btn-secondary:hover {
        background: #e0e0e0;
    }
    .dashboard-content {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .dashboard-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #0C0C0C;
        margin: 0 0 8px;
    }
    .dashboard-header p {
        color: #666;
        margin: 0;
    }
    
    /* Responsive */
    @media (max-width: 991px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
        }
        .dashboard-sidebar {
            position: static;
            margin-bottom: 24px;
        }
    }
</style>
<?php endif; ?>

<!-- Logout Success Message Styles -->
<style>
.logout-success-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transform: translateX(120%);
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    max-width: 350px;
    animation: slideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideIn {
    from {
        transform: translateX(120%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.logout-success-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
}

.logout-success-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #d1fae5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #065f46;
    font-size: 20px;
}

.logout-success-text {
    flex: 1;
}

.logout-success-text h3 {
    margin: 0 0 4px;
    font-size: 16px;
    font-weight: 600;
    color: #0C0C0C;
}

.logout-success-text p {
    margin: 0;
    font-size: 14px;
    color: #666;
}

.logout-success-close {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    width: 28px;
    height: 28px;
}

.logout-success-close:hover {
    background: #f3f4f6;
    color: #333;
}

.logout-success-close i {
    font-size: 16px;
}

/* Auto-hide after 5 seconds */
.logout-success-message {
    animation: slideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* Smooth exit animation */
.logout-success-message.hide {
    animation: slideOut 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes slideOut {
    to {
        transform: translateX(120%);
        opacity: 0;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .logout-success-message {
        right: 16px;
        left: 16px;
        max-width: none;
    }
}
</style>

<script type="application/ld+json">
    
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Who are mybrandplease's private label clients?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "mybrandplease works with a diverse range of private label skin care and cosmetics clients, from luxury spas to online retailers. Client names are kept confidential to protect each brand's identity."
      }
    },
    {
      "@type": "Question",
      "name": "What are mybrandplease's response times?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "mybrandplease typically responds to inquiries within 24-48 hours. The team operates Monday to Saturday, 9 am to 6 pm IST. For urgent matters, customers can contact the sales office directly by phone or WhatsApp."
      }
    },
    {
      "@type": "Question",
      "name": "Can I visit the private label manufacturing facility?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "To protect client confidentiality, mybrandplease does not offer facility tours."
      }
    },
    {
      "@type": "Question",
      "name": "Does mybrandplease help with Health Ministry and FDA registration?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, mybrandplease offers FDA and Health Ministry registration assistance for a fee of $200 per product."
      }
    },
    {
      "@type": "Question",
      "name": "How can I order product samples?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Samples can be ordered directly from the Products section of the website, where customers can browse and select from mybrandplease's full product range."
      }
    },
    {
      "@type": "Question",
      "name": "What are the sample sizes for private label skin care products?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Product samples are available in 1/2 oz, 1 oz, and 2 oz sizes."
      }
    },
    {
      "@type": "Question",
      "name": "How long does a sample order take to arrive?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Sample orders are typically fulfilled within 3-4 business days, with a tracking number emailed once the order ships."
      }
    },
    {
      "@type": "Question",
      "name": "What are the minimum order quantities for private label products?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "The minimum order requirement is 500 units per product, with flexible options for both smaller and larger orders."
      }
    },
    {
      "@type": "Question",
      "name": "How much does private label manufacturing cost?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Pricing depends on the product, packaging, and quantity selected. Customers can contact info@mybrandplease.com for a full pricing catalogue in US Dollars."
      }
    },
    {
      "@type": "Question",
      "name": "Does mybrandplease offer quantity discounts?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, quantity discounts are available for both bulk and retail-size orders. Contact the sales team at info@mybrandplease.com for pricing catalogues."
      }
    },
    {
      "@type": "Question",
      "name": "What are the typical lead times for private label orders?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Standard lead times are 6-8 weeks for new orders, and approximately 3-6 weeks once an order is approved and the deposit is received, though this can vary by season and product availability."
      }
    },
    {
      "@type": "Question",
      "name": "Are mybrandplease products vegan and gluten-free?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Most products are vegan and gluten-free, though customers should check the full ingredients list of each specific product to confirm."
      }
    },
    {
      "@type": "Question",
      "name": "Are mybrandplease products tested on animals?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "No, mybrandplease is a certified cruelty-free company and does not test any products on animals."
      }
    },
    {
      "@type": "Question",
      "name": "Does mybrandplease offer custom formulations?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, custom formulations start at $675, with pricing depending on ingredients and testing requirements. Lead time is 8-12 weeks with a minimum purchase of 25 gallons."
      }
    },
    {
      "@type": "Question",
      "name": "Does mybrandplease help with logo and label design?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, professional graphic design services are available: logo design for $300 and label design for $350, one-time fees covering current and future products."
      }
    }
  ]
}

</script>

<script type="application/ld+json">
    {
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://mybrandplease.com/#organization",
  "name": "mybrandplease",
  "legalName": "NIMISHA IMPEX WORLDWIDE (P) LIMITED",
  "alternateName": "My Brand Please",
  "url": "https://mybrandplease.com/",
  "logo": {
    "@type": "ImageObject",
    "url": "<?php echo htmlspecialchars(url('assets/imgs/logo/footer.png'), ENT_QUOTES, 'UTF-8'); ?>"
  },
  "description": "mybrandplease is a private label and third-party cosmetics manufacturer offering custom formulations, premium packaging, and full brand-launch support for skin care, hair care, body care, bathing soaps, and men's grooming products. FDA registered, ISO 22716 certified, and MoCRA compliant, with over 21 years of private labelling experience.",
  "foundingDate": "2005",
  "slogan": "Private Label Is Now Simplified",
  "email": "info@mybrandplease.com",
  "telephone": "+91-97170-04615",
  "sameAs": [
    "https://www.facebook.com/mybrandplease",
    "https://www.instagram.com/mybrandplease_/",
    "https://x.com/mybrandplease",
    "https://www.linkedin.com/in/mybrandplease/",
    "https://in.pinterest.com/mybrandplease/",
    "https://www.youtube.com/@mybrandplease",
    "https://www.trustpilot.com/review/mybrandplease.com",
    "https://g.co/kgs/YgaRfYo"
  ],
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "D226, 10th Avenue, Gaur City 2",
    "addressLocality": "Greater Noida West",
    "postalCode": "201301",
    "addressCountry": "IN"
  },
  "contactPoint": [
    {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "telephone": "+91-97170-04615",
      "email": "info@mybrandplease.com",
      "areaServed": "IN",
      "availableLanguage": ["English", "Hindi"]
    },
    {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "telephone": "+1-343-322-5866",
      "email": "info@mybrandplease.com",
      "areaServed": "US"
    },
    {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "telephone": "+1-819-593-8620",
      "email": "barb@mybrandplease.com",
      "areaServed": "CA"
    },
    {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "telephone": "+61-422-833-441",
      "email": "info@mybrandplease.com",
      "areaServed": "AU"
    }
  ],
  "location": [
    {
      "@type": "Place",
      "name": "mybrandplease India Office",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "D226, 10th Avenue, Gaur City 2",
        "addressLocality": "Greater Noida West",
        "postalCode": "201301",
        "addressCountry": "IN"
      }
    },
    {
      "@type": "Place",
      "name": "mybrandplease USA Office",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "59th Terrace SW, West Park",
        "addressLocality": "Florida",
        "postalCode": "33023",
        "addressCountry": "US"
      }
    },
    {
      "@type": "Place",
      "name": "mybrandplease Canada Office",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "McWatters Road",
        "addressLocality": "Ottawa, ON",
        "postalCode": "K2C 3N8",
        "addressCountry": "CA"
      }
    },
    {
      "@type": "Place",
      "name": "mybrandplease Australia Office",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "811 Pacific Highway, Chatswood",
        "addressLocality": "Sydney",
        "addressCountry": "AU"
      }
    }
  ],
  "areaServed": ["IN", "US", "CA", "AU", "Worldwide"],
  "knowsAbout": [
    "Private label cosmetics manufacturing",
    "Third-party skin care manufacturing",
    "Hair care formulation",
    "Contract manufacturing for beauty brands",
    "Natural and organic personal care products"
  ],
  "hasCredential": [
    "FDA Registered",
    "ISO 22716 Certified",
    "MoCRA Compliant",
    "EU CosIng Compliant",
    "Cruelty-Free Compliant",
    "Vegan Certified",
    "CPNP Registered"
  ]
}


</script>
</head>
<body>
