<?php
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/user.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$headerMenuItems = cms_get_resolved_header_menu();
foreach ($headerMenuItems as &$headerMenuItem) {
    if (cms_header_menu_key($headerMenuItem) === 'our-product') {
        $headerMenuItem['title'] = 'Sample';
    }
    if (cms_header_menu_key($headerMenuItem) === 'additional-services') {
        $headerMenuItem['title'] = 'Services';
    }
}
unset($headerMenuItem);
$headerMenuItems = array_values(array_filter($headerMenuItems, static function (array $headerMenuItem): bool {
    return cms_header_menu_key($headerMenuItem) !== 'home';
}));
$headerMenuPriority = [
    'our-product' => 0,
    'how-it-works' => 1,
    'why-choose-us' => 2,
    'about-us' => 3,
    'additional-services' => 4,
    'resources' => 5,
];
usort($headerMenuItems, static function (array $a, array $b) use ($headerMenuPriority): int {
    $aKey = cms_header_menu_key($a);
    $bKey = cms_header_menu_key($b);
    $aPriority = $headerMenuPriority[$aKey] ?? 100;
    $bPriority = $headerMenuPriority[$bKey] ?? 100;

    if ($aPriority === $bPriority) {
        return ((int) ($a['sort_order'] ?? $a['id'] ?? 0)) <=> ((int) ($b['sort_order'] ?? $b['id'] ?? 0));
    }

    return $aPriority <=> $bPriority;
});
$headerLogo = url('uploads/logo/mybrandplease-1.gif');
$headerCartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $headerCartCount += max(1, (int) $qty);
    }
}
// Also try to load from database if session is empty
if ($headerCartCount === 0 && session_id()) {
    $pdo = db();
    if ($pdo) {
        $sessionId = session_id();
        $total = $pdo->prepare('SELECT SUM(quantity) as total FROM cart WHERE session_id = ?');
        $total->execute([$sessionId]);
        $result = $total->fetch();
        if ($result && !empty($result['total'])) {
            $headerCartCount = (int) $result['total'];
        }
    }
}

if (!function_exists('render_header_menu_items')) {
    function render_header_menu_items(array $items): void
    {
        foreach ($items as $item) {
            $hasChildren = !empty($item['children']);
            echo '<li' . ($hasChildren ? ' class="menu-item-has-children"' : '') . '>';
            echo '<a href="' . htmlspecialchars(url((string) $item['url']), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . '</a>';
            if ($hasChildren) {
                echo '<ul class="dp-menu">';
                render_header_menu_items($item['children']);
                echo '</ul>';
            }
            echo '</li>';
        }
    }
}
?>


  <div class="progress-wrap">
    <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
      <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"></path>
    </svg>
  </div>
  <div id="smooth-wrapper">
    <div id="smooth-content">
      <!-- Search Area Start (same as header-area-2) -->
      <!-- Side Panel Start -->
      <aside class="fix">
        <div class="side-info">
          <div class="side-info-content">
            <div class="offset-widget offset-header">
              <div class="offset-logo">
                <a href="<?php echo url('index.php'); ?>">
                  <img src="<?php echo $headerLogo; ?>" alt="MyBrandPlease Logo" />
                </a>
              </div>
              <button id="side-info-close" class="side-info-close">
                <i class="fas fa-times"></i>
              </button>
            </div>
            <div class="moc-offcanvas d-xl-none fix" id="mocOffcanvas">
              <div class="moc-panel" role="dialog" aria-modal="true" aria-labelledby="mocTitle" aria-hidden="true">
                <div class="moc-topbar">
                  <button type="button" class="moc-back" id="mocBack" aria-label="Back to main menu">
                    <span class="moc-back__icon" aria-hidden="true">&#8592;</span>
                    <span class="moc-back__label">Back</span>
                  </button>
                  <div class="moc-title" id="mocTitle">Menu</div>
                  <button type="button" class="moc-close side-info-close" aria-label="Close menu">&times;</button>
                </div>

                <div class="moc-track-wrap">
                  <div
                    class="moc-track"
                    id="mocTrack"
                    data-menu-tree="<?php echo htmlspecialchars(json_encode($headerMenuItems, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"
                  >
                    <nav class="moc-menu-panel moc-menu-panel--root" aria-label="Mobile root menu">
                      <ul class="moc-list">
                        <?php foreach ($headerMenuItems as $menuItem): ?>
                          <?php $hasChildren = !empty($menuItem['children']); ?>
                          <li>
                            <?php if ($hasChildren): ?>
                              <button
                                type="button"
                                class="moc-link moc-parent"
                                data-menu-id="<?php echo (int) ($menuItem['id'] ?? 0); ?>"
                              >
                                <span><?php echo htmlspecialchars((string) ($menuItem['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="moc-chevron" aria-hidden="true">&#8250;</span>
                              </button>
                            <?php else: ?>
                              <a class="moc-link" href="<?php echo htmlspecialchars(url((string) ($menuItem['url'] ?? '#')), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars((string) ($menuItem['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                              </a>
                            <?php endif; ?>
                          </li>
                        <?php endforeach; ?>
                        <li class="moc-cta-item">
                          <a class="moc-cta-btn" href="<?php echo url('meeting-schedule.php'); ?>">Get In Touch</a>
                        </li>
                      </ul>
                    </nav>

                    <nav class="moc-menu-panel moc-menu-panel--sub" aria-label="Mobile submenu">
                      <div class="moc-subhead">Sub Menu</div>
                      <ul class="moc-list" id="mocSubmenuList"></ul>
                    </nav>
                  </div>
                </div>
              </div>
            </div>
            <div class="offset-widget-box">
              <h2 class="title">Social Info</h2>
              <div class="offset-social">
                <a href="<?php echo url('contact.php'); ?>" class="facebook" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="<?php echo url('contact.php'); ?>" class="twitter" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="<?php echo url('contact.php'); ?>" class="linkedin" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="<?php echo url('contact.php'); ?>" class="youtube" aria-label="Youtube"><i class="fab fa-youtube"></i></a>
              </div>
            </div>
          </div>
        </div>
      </aside>
      <div class="offcanvas-overlay"></div>

      <!-- Header start -->
      <header class="header-area header-layoutone header-sticky">
        <div class="header-main">
          <div class="container">
            <div class="header-shell">
            <div class="row align-items-center justify-content-between g-0">
              <!-- Logo Column -->
              <div class="col-auto">
                <div class="header__logo">
                  <a href="<?php echo url('index.php'); ?>">
                    <img src="<?php echo $headerLogo; ?>" class="normal-logo" alt="MyBrandPlease Logo" />
                  </a>
                </div>
              </div>

              <!-- Navigation Column -->
              <div class="col d-none d-xl-flex justify-content-center">
                <nav class="main-menu">
                  <ul>
                    <?php render_header_menu_items($headerMenuItems); ?>
                  </ul>
                </nav>
              </div>

              <!-- Actions Column -->
              <div class="col-auto">
                <div class="header-right d-flex align-items-center gap-3">
                  <!-- Search Button -->
                  <div class="header__search">
                    <button class="search-open-btn" type="button" aria-expanded="false" aria-controls="site-search">
                      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                          d="M23.707 22.293L16.882 15.468C18.204 13.835 19 11.76 19 9.50002C19 4.26202 14.738 0 9.49997 0C4.26197 0 0 4.26197 0 9.49997C0 14.738 4.26202 19 9.50002 19C11.76 19 13.835 18.204 15.468 16.882L22.293 23.707C22.488 23.902 22.744 24 23 24C23.256 24 23.512 23.902 23.707 23.707C24.098 23.316 24.098 22.684 23.707 22.293ZM9.50002 17C5.364 17 2.00002 13.636 2.00002 9.49997C2.00002 5.36395 5.364 1.99997 9.50002 1.99997C13.636 1.99997 17 5.36395 17 9.49997C17 13.636 13.636 17 9.50002 17Z"
                          fill="#070713" />
                      </svg>
                    </button>
                    <div id="site-search" class="search-panel" role="dialog" aria-hidden="true"
                      aria-label="Site search">
                      <div class="search-backdrop"></div>
                      <div class="search-inner" role="document">
                        <button class="search-close" type="button" aria-label="Close search">&times;</button>
                        <form class="search-form" action="<?php echo htmlspecialchars(url('shop.php'), ENT_QUOTES, 'UTF-8'); ?>" method="get" role="search">
                          <input type="search" name="q" class="search-input" placeholder="Search..."
                            autocomplete="off" />
                          <button type="submit" class="search-submit" aria-label="Submit search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>

                  <a href="<?php echo url('meeting-schedule.php'); ?>" class="header-meta-link d-none d-lg-inline-flex">
                    Get Free Consultation
                  </a>

                  <a href="<?php echo url('meeting-schedule.php'); ?>" class="btn-orange d-inline-flex header-get-touch-btn">
                    Get In Touch
                  </a>

                  <a href="<?php echo url('cart.php'); ?>" class="action-btn position-relative header-cart-btn" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span
                      class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light"
                      data-cart-count
                      data-cart-server="1"
                      style="font-size: 10px; padding: 4px 6px; <?php echo $headerCartCount > 0 ? '' : 'display:none;'; ?>">
                      <?php echo (int) $headerCartCount; ?>
                    </span>
                  </a>

                  <div class="header-lang d-none d-lg-flex">
                    <div class="header-lang-switcher" id="header-lang-switcher" aria-expanded="false">
                      <button
                        type="button"
                        class="header-lang-switcher__trigger"
                        id="header-lang-trigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-controls="header-lang-menu"
                      >
                        <span class="header-lang-switcher__current">
                          <span class="header-lang-switcher__flag flag-en" id="header-lang-flag" aria-hidden="true"></span>
                          <span id="header-lang-label">EN</span>
                        </span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                      </button>
                      <div class="header-lang-switcher__menu" id="header-lang-menu" role="listbox" aria-label="Select language">
                        <button type="button" class="header-lang-switcher__option is-active" data-lang="en" aria-selected="true" role="option">
                          <span class="header-lang-switcher__flag flag-en" aria-hidden="true"></span>
                          <span>EN</span>
                        </button>
                        <button type="button" class="header-lang-switcher__option" data-lang="fr" aria-selected="false" role="option">
                          <span class="header-lang-switcher__flag flag-fr" aria-hidden="true"></span>
                          <span>FR</span>
                        </button>
                        <button type="button" class="header-lang-switcher__option" data-lang="es" aria-selected="false" role="option">
                          <span class="header-lang-switcher__flag flag-es" aria-hidden="true"></span>
                          <span>ES</span>
                        </button>
                      </div>
                    </div>
                    <div class="header-lang-google-translate" id="google_translate_element"></div>
                  </div>

                  <!-- Mobile Menu Toggle -->
                  <div class="header__navicon d-xl-none">
                    <div class="side-toggle">
                      <a class="bar-icon" href="javascript:void(0)">
                        <span></span>
                        <span></span>
                        <span></span>
                      </a>
                    </div>
                  </div>

                </div>
              </div>
            </div>
            </div>
          </div>
        </div>
      </header>
      <!-- Header area end -->

      <main>
