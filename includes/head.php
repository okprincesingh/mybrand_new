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
    $slugCandidate = 'home';
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
    $meta['title'] = (string) (($seoRow['meta_title'] ?? '') !== '' ? $seoRow['meta_title'] : ($seoRow['title'] ?? 'Mybrandplease'));
    $meta['description'] = (string) ($seoRow['meta_description'] ?? '');
    $meta['canonical'] = (string) (($seoRow['canonical_url'] ?? '') !== '' ? $seoRow['canonical_url'] : (($seoRow['slug'] ?? '') . '.php'));
    $meta['keywords'] = (string) ($seoRow['meta_keywords'] ?? '');
}

$title = $meta['title'] ?? 'Mybrandplease';
$description = $meta['description'] ?? 'Private label personal care manufacturing with premium formulations.';
$keywords = $meta['keywords'] ?? '';
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

// Add AOS CSS/JS on animation-enabled pages
$currentPhpPage = basename($_SERVER['PHP_SELF']);
$isAosPage = in_array($currentPhpPage, ['index.php', 'shop.php'], true);
$isHomepage = $currentPhpPage === 'index.php';
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
<?php foreach ($styles as $href): ?>
  <link rel="stylesheet" href="<?php echo url($href); ?>">
<?php endforeach; ?>
<?php if ($isAosPage): ?>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js" defer></script>
<?php endif; ?>
<?php if ($isHomepage): ?>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js" defer></script>
<?php endif; ?>
<style>
  .breadcumb-wrapper,
  .breadcumb2 {
    position: relative;
    background-image: url('<?php echo htmlspecialchars($breadcrumbBackgroundUrl, ENT_QUOTES, 'UTF-8'); ?>') !important;
    background-position: center center !important;
    background-repeat: no-repeat !important;
    background-size: cover !important;
    overflow: hidden;
  }

  .breadcumb-wrapper::before,
  .breadcumb2::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(rgba(255, 249, 252, 0.46), rgba(255, 241, 246, 0.46));
    z-index: 0;
    pointer-events: none;
  }

  .breadcumb-wrapper > *,
  .breadcumb2 > * {
    position: relative;
    z-index: 1;
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
</head>
<body>
