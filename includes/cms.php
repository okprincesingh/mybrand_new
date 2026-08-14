<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/draft.php';

function cms_cache_key(string $bucket, string $key): string
{
    return 'cms:' . $bucket . ':' . $key;
}

function cms_invalidate_page_cache(?string $slug = null): void
{
    if ($slug !== null && $slug !== '') {
        cache_delete(cms_cache_key('page_slug', $slug));
        return;
    }
    cache_clear_prefix('cms:page_slug:');
}

function cms_invalidate_menu_cache(?string $locationKey = null): void
{
    if ($locationKey !== null && $locationKey !== '') {
        cache_delete(cms_cache_key('menu', $locationKey));
        return;
    }
    cache_clear_prefix('cms:menu:');
}

function cms_invalidate_settings_cache(?string $settingKey = null): void
{
    if ($settingKey !== null && $settingKey !== '') {
        cache_delete(cms_cache_key('setting', $settingKey));
        return;
    }
    cache_clear_prefix('cms:setting:');
}

function cms_invalidate_home_slides_cache(): void
{
    cache_delete(cms_cache_key('home', 'slides'));
}

function cms_invalidate_home_hero_videos_cache(): void
{
    cache_delete(cms_cache_key('home', 'hero_videos'));
}

function cms_invalidate_home_cta_cards_cache(): void
{
    cache_delete(cms_cache_key('home', 'cta_cards'));
}

function cms_ensure_home_hero_videos_table(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS home_hero_videos (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(180) NULL,
                desktop_video_url VARCHAR(500) NULL,
                desktop_light_video_url VARCHAR(500) NULL,
                mobile_video_url VARCHAR(500) NULL,
                desktop_video_file VARCHAR(255) NULL,
                desktop_light_video_file VARCHAR(255) NULL,
                mobile_video_file VARCHAR(255) NULL,
                poster_image VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_home_hero_videos_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Ensure new file columns exist on existing installs.
        $columns = db_fetch_all($pdo, 'SHOW COLUMNS FROM home_hero_videos');
        $existing = [];
        foreach ($columns as $column) {
            $existing[strtolower((string) ($column['Field'] ?? ''))] = true;
        }
        if (empty($existing['desktop_video_file'])) {
            $pdo->exec('ALTER TABLE home_hero_videos ADD COLUMN desktop_video_file VARCHAR(255) NULL AFTER desktop_video_url');
        }
        if (empty($existing['desktop_light_video_file'])) {
            $pdo->exec('ALTER TABLE home_hero_videos ADD COLUMN desktop_light_video_file VARCHAR(255) NULL AFTER desktop_light_video_url');
        }
        if (empty($existing['mobile_video_file'])) {
            $pdo->exec('ALTER TABLE home_hero_videos ADD COLUMN mobile_video_file VARCHAR(255) NULL AFTER mobile_video_url');
        }

        $checked = true;
        return true;
    } catch (Throwable $e) {
        $checked = false;
        return false;
    }
}

function cms_get_home_hero_videos(): array
{
    $cacheKey = cms_cache_key('home', 'hero_videos');
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallbackDesktop = 'https://jaikvik.in/lab/mybrand_video/mybrandvideo';
    $fallbackDesktopLight = 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo';
    $fallbackMobile = 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo';
    $fallback = [
        [
            'id' => 0,
            'label' => 'Default Hero Video',
            'desktop_video_url' => $fallbackDesktop,
            'desktop_light_video_url' => $fallbackDesktopLight,
            'mobile_video_url' => $fallbackMobile,
            'poster_image' => '',
        ],
    ];

    $pdo = db();
    if (!$pdo || !cms_ensure_home_hero_videos_table($pdo)) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, label, desktop_video_url, desktop_light_video_url, mobile_video_url, desktop_video_file, desktop_light_video_file, mobile_video_file, poster_image FROM home_hero_videos' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    $videos = [];
    foreach ($rows as $row) {
        $merged = draft_merge_row((array) $row, 'home_hero_video', (int) $row['id']);

        // Resolve each video source: prefer uploaded file, then URL, then fallback.
        $desktopFile = trim((string) ($merged['desktop_video_file'] ?? ''));
        $desktopLightFile = trim((string) ($merged['desktop_light_video_file'] ?? ''));
        $mobileFile = trim((string) ($merged['mobile_video_file'] ?? ''));

        $desktopUrl = trim((string) ($merged['desktop_video_url'] ?? ''));
        $desktopLightUrl = trim((string) ($merged['desktop_light_video_url'] ?? ''));
        $mobileUrl = trim((string) ($merged['mobile_video_url'] ?? ''));

        $videos[] = [
            'id' => (int) ($merged['id'] ?? $row['id']),
            'label' => (string) ($merged['label'] ?? ''),
            'desktop_video_url' => $desktopFile !== '' ? url($desktopFile) : ($desktopUrl !== '' ? $desktopUrl : $fallbackDesktop),
            'desktop_light_video_url' => $desktopLightFile !== '' ? url($desktopLightFile) : ($desktopLightUrl !== '' ? $desktopLightUrl : $fallbackDesktopLight),
            'mobile_video_url' => $mobileFile !== '' ? url($mobileFile) : ($mobileUrl !== '' ? $mobileUrl : $fallbackMobile),
            'poster_image' => (string) ($merged['poster_image'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $videos, 300);
    }
    return $videos;
}

function cms_get_setting(string $key, ?string $default = null): ?string
{
    $cacheKey = cms_cache_key('setting', $key);
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if ($cached !== null) {
            return (string) $cached;
        }
    }

    $pdo = db();
    if (!$pdo) {
        return $default;
    }

    $value = db_fetch_value($pdo, 'SELECT setting_value FROM site_settings WHERE setting_key = :k LIMIT 1', [
        ':k' => $key,
    ]);

    $resolved = $value !== false ? (string) $value : $default;
    if ($resolved !== null && !preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $resolved, 600);
    }

    return $resolved;
}

function cms_get_breadcrumb_background_path(): string
{
    static $resolved = null;
    if (is_string($resolved)) {
        return $resolved;
    }

    $candidateKeys = [
        'breadcrumb_background_image',
        'breadcrumb_bg_image',
        'breadcumb_background_image',
        'breadcumb_bg_image',
        'inner_banner_image',
        'inner_banner_bg',
        'page_banner_image',
        'page_banner_bg',
    ];

    foreach ($candidateKeys as $key) {
        $value = trim((string) (cms_get_setting($key, '') ?? ''));
        if ($value !== '') {
            $resolved = $value;
            return $resolved;
        }
    }

    $resolved = 'assets/imgs/breadcumbBg.jpg';
    return $resolved;
}

function cms_get_menu(string $locationKey): array
{
    $cacheKey = cms_cache_key('menu', $locationKey);
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = db_fetch_all($pdo, 'SELECT mi.id, mi.parent_id, mi.title, mi.url, mi.sort_order FROM menus m INNER JOIN menu_items mi ON mi.menu_id = m.id WHERE m.location_key = :loc AND mi.is_active = 1 ORDER BY mi.sort_order ASC, mi.id ASC', [
        ':loc' => $locationKey,
    ]);
    if (!$rows) {
        return [];
    }

    $byParent = [];
    foreach ($rows as $row) {
        $parent = $row['parent_id'] ? (int) $row['parent_id'] : 0;
        $byParent[$parent][] = $row;
    }

    $build = function (int $parentId) use (&$build, $byParent): array {
        $list = [];
        foreach ($byParent[$parentId] ?? [] as $row) {
            $list[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'url' => (string) $row['url'],
                'children' => $build((int) $row['id']),
            ];
        }
        return $list;
    };

    $menu = $build(0);
    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $menu, 600);
    }
    return $menu;
}

function cms_header_menu_make_item(int &$nextId, string $title, string $url, array $children = []): array
{
    return [
        'id' => $nextId++,
        'title' => $title,
        'url' => $url,
        'children' => $children,
    ];
}

function cms_build_default_header_menu(): array
{
    static $menu = null;
    if (is_array($menu)) {
        return $menu;
    }

    $nextId = 100000;

    $aboutChildren = [
        cms_header_menu_make_item($nextId, 'Who We Are', 'about.php#who-we-are'),
        cms_header_menu_make_item($nextId, 'What We Offer', 'about.php#what-we-offer'),
        cms_header_menu_make_item($nextId, 'How We Formulate', 'about.php#how-we-formulate'),
        cms_header_menu_make_item($nextId, 'Key Benefits', 'about.php#key-benifits'),
        cms_header_menu_make_item($nextId, 'Our Certificates', 'our-certificates.php'),
    ];

    $howItWorksChildren = [
        cms_header_menu_make_item($nextId, 'Product Components', 'how-it-works.php#product-components'),
        cms_header_menu_make_item($nextId, 'Define Offerings', 'how-it-works.php#define-offerings'),
        cms_header_menu_make_item($nextId, 'Design & Printing', 'how-it-works.php#design-and-printing'),
        cms_header_menu_make_item($nextId, 'Finishing Touches', 'how-it-works.php#finishing-touches'),
    ];

    $productChildren = [];
    $productMenuConfig = [
        ['label' => 'Skin Care', 'aliases' => ['skin-care']],
        ['label' => 'Body Care', 'aliases' => ['body-care']],
        ['label' => 'Hair Care', 'aliases' => ['hair-care']],
        ['label' => 'Bathing Soaps', 'aliases' => ['bathing-soaps']],
        ['label' => 'Especially For Men', 'aliases' => ['especially-for-men', 'men-s-care']],
        ['label' => 'Aerosols & Perfumes', 'aliases' => ['aerosols-perfumes']],
    ];
    foreach ($productMenuConfig as $menuEntry) {
        $entryAliases = (array) ($menuEntry['aliases'] ?? []);
        $resolvedLabel = (string) ($menuEntry['label'] ?? 'Products');
        $resolvedLink = catalog_shop_link((string) ($entryAliases[0] ?? 'shop'));
        $resolvedCategory = in_array('aerosols-perfumes', $entryAliases, true)
            ? null
            : catalog_find_category_by_aliases($entryAliases);

        if ($resolvedCategory) {
            $resolvedLink = catalog_shop_link((string) ($resolvedCategory['slug'] ?? ''));
        }

        $productChildren[] = cms_header_menu_make_item($nextId, $resolvedLabel, $resolvedLink);
    }

    $whyChildren = [];
    foreach (cms_get_why_choose_pages(true) as $page) {
        $slug = trim((string) ($page['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $whyChildren[] = cms_header_menu_make_item(
            $nextId,
            (string) ($page['title'] ?? 'Why Choose Us'),
            why_page_url($slug)
        );
    }
    if (!$whyChildren) {
        $whyChildren[] = cms_header_menu_make_item($nextId, 'Private Label Skin Care Manufacturer', why_page_url('private-label-skin-care-manufacturer'));
    }

    $resourcesChildren = [
        cms_header_menu_make_item($nextId, 'Blog', 'blog.php'),
        cms_header_menu_make_item($nextId, "FAQ's", 'faq.php'),
        cms_header_menu_make_item($nextId, 'Contact', 'contact.php'),
        cms_header_menu_make_item($nextId, 'Form Center', 'form-center.php'),
        cms_header_menu_make_item($nextId, 'Product Catalog', 'product-catalog.php'),
        cms_header_menu_make_item($nextId, 'Material Safety Data Sheets', 'data-sheets.php'),
    ];

    $additionalServicesChildren = [
        cms_header_menu_make_item($nextId, 'Design & Print Services', 'services.php#design-print-services'),
        cms_header_menu_make_item($nextId, 'Product & Offering Development', 'services.php#product-offering-development'),
        cms_header_menu_make_item($nextId, 'Finishing Touches', 'services.php#finishing-touches'),
        cms_header_menu_make_item($nextId, 'Logistics Support', 'services.php#logistics-support'),
        cms_header_menu_make_item($nextId, 'Build Your Own Brand', 'services.php#build-your-own-brand'),
    ];

   $menu = [
        cms_header_menu_make_item($nextId, 'Sample', 'shop.php', $productChildren),
        cms_header_menu_make_item($nextId, 'How it Works', 'how-it-works.php', $howItWorksChildren),
        cms_header_menu_make_item($nextId, 'Why Choose Us', 'our-services.php', $whyChildren),
        cms_header_menu_make_item($nextId, 'About Us', 'about.php', $aboutChildren),
        cms_header_menu_make_item($nextId, 'Services', 'services.php', $additionalServicesChildren),
        cms_header_menu_make_item($nextId, 'Resources', 'blog.php', $resourcesChildren),
    ];

    return $menu;
}

function cms_header_menu_key(array $item): string
{
    $title = strtolower(trim((string) ($item['title'] ?? '')));
    $url = strtolower(trim((string) ($item['url'] ?? '')));

    if ($title === 'home' || $url === 'index.php') {
        return 'home';
    }
    if (str_contains($title, 'about') || str_contains($url, 'about.php')) {
        return 'about-us';
    }
    if (str_contains($title, 'how it works') || str_contains($url, 'how-it-works.php')) {
        return 'how-it-works';
    }
    if (str_contains($title, 'product') || str_contains($url, 'shop.php')) {
        return 'our-product';
    }
    if (str_contains($title, 'why choose') || str_contains($url, 'our-services.php')) {
        return 'why-choose-us';
    }
    if (str_contains($title, 'additional service') || str_contains($url, 'services.php')) {
        return 'additional-services';
    }
    if (str_contains($title, 'resource') || str_contains($url, 'blog.php')) {
        return 'resources';
    }

    return preg_replace('/[^a-z0-9]+/', '-', $title . '-' . $url) ?: 'menu-item';
}

function cms_header_menu_has_required_sections(array $items): bool
{
    $required = [
        
        'about-us',
        'how-it-works',
        'our-product',
        'why-choose-us',
        'additional-services',
        'resources',
    ];

    $keys = [];
    foreach ($items as $item) {
        $keys[] = cms_header_menu_key($item);
    }

    foreach ($required as $requiredKey) {
        if (!in_array($requiredKey, $keys, true)) {
            return false;
        }
    }

    return true;
}

function cms_merge_header_menu(array $currentMenu, array $defaultMenu): array
{
    $defaultsByKey = [];
    foreach ($defaultMenu as $defaultItem) {
        $defaultsByKey[cms_header_menu_key($defaultItem)] = $defaultItem;
    }

    $merged = [];
    foreach ($currentMenu as $item) {
        $key = cms_header_menu_key($item);
        if (isset($defaultsByKey[$key])) {
            $defaultItem = $defaultsByKey[$key];
            $alwaysSyncChildren = in_array($key, [
                'our-product',
                'why-choose-us',
            ], true);

            if ($alwaysSyncChildren && !empty($defaultItem['children'])) {
                $item['children'] = $defaultItem['children'];
            } elseif (empty($item['children']) && !empty($defaultItem['children'])) {
                $item['children'] = $defaultItem['children'];
            }
            $merged[] = $item;
            unset($defaultsByKey[$key]);
            continue;
        }

        $merged[] = $item;
    }

    foreach ($defaultMenu as $defaultItem) {
        $key = cms_header_menu_key($defaultItem);
        if (isset($defaultsByKey[$key])) {
            $merged[] = $defaultItem;
        }
    }

    return $merged;
}

function cms_get_resolved_header_menu(): array
{
    $dbMenu = cms_get_menu('header_main');
    $defaultMenu = cms_build_default_header_menu();

    if (!$dbMenu || !cms_header_menu_has_required_sections($dbMenu)) {
        return $defaultMenu;
    }

    return cms_merge_header_menu($dbMenu, $defaultMenu);
}

function cms_get_footer_sections(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = $pdo->query('SELECT fs.id, fs.title, fl.label, fl.url FROM footer_sections fs LEFT JOIN footer_links fl ON fl.section_id = fs.id ORDER BY fs.sort_order ASC, fs.id ASC, fl.sort_order ASC, fl.id ASC')->fetchAll();
    if (!$rows) {
        return [];
    }

    $result = [];
    foreach ($rows as $row) {
        $sid = (int) $row['id'];
        if (!isset($result[$sid])) {
            $result[$sid] = [
                'id' => $sid,
                'title' => (string) $row['title'],
                'links' => [],
            ];
        }

        if ($row['label'] !== null) {
            $result[$sid]['links'][] = [
                'label' => (string) $row['label'],
                'url' => (string) $row['url'],
            ];
        }
    }

    return array_values($result);
}

function get_page_by_slug(string $slug): ?array
{
    if (!validate_slug_value($slug)) {
        return null;
    }

    $cacheKey = cms_cache_key('page_slug', $slug);
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached) || $cached === false) {
            return $cached === false ? null : $cached;
        }
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $statusClause = preview_mode_include_drafts() ? '' : ' AND p.status = "published"';
    $page = db_fetch_one($pdo, 'SELECT p.*, pm.meta_title, pm.meta_description, pm.meta_keywords, pm.canonical_url FROM pages p LEFT JOIN page_meta pm ON pm.page_id = p.id WHERE p.slug = :slug' . $statusClause . ' LIMIT 1', [
        ':slug' => $slug,
    ]);
    if ($page) {
        if (preview_mode_include_drafts()) {
            $page = draft_merge_row((array) $page, 'page', (int) ($page['id'] ?? 0));
        }
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $page, 300);
        }
        return $page;
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, false, 120);
    }
    return null;
}

function get_page_sections(int $pageId): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $rows = db_fetch_all($pdo, 'SELECT ps.id AS section_id, ps.page_id, ps.section_key, ps.title AS section_title, ps.body AS section_body, ps.sort_order AS section_sort_order, psi.id AS item_id, psi.item_key, psi.title AS item_title, psi.body AS item_body, psi.image_path, psi.link_url, psi.sort_order AS item_sort_order FROM page_sections ps LEFT JOIN page_section_items psi ON psi.section_id = ps.id WHERE ps.page_id = :page_id ORDER BY ps.sort_order ASC, ps.id ASC, psi.sort_order ASC, psi.id ASC', [
        ':page_id' => $pageId,
    ]);

    $sections = [];
    foreach ($rows as $row) {
        $sid = (int) $row['section_id'];
        if (!isset($sections[$sid])) {
            $sections[$sid] = [
                'id' => $sid,
                'page_id' => (int) $row['page_id'],
                'section_key' => (string) $row['section_key'],
                'title' => (string) ($row['section_title'] ?? ''),
                'body' => (string) ($row['section_body'] ?? ''),
                'sort_order' => (int) $row['section_sort_order'],
                'items' => [],
            ];
        }

        if ($row['item_id'] !== null) {
            $sections[$sid]['items'][] = [
                'id' => (int) $row['item_id'],
                'section_id' => $sid,
                'item_key' => (string) ($row['item_key'] ?? ''),
                'title' => (string) ($row['item_title'] ?? ''),
                'body' => (string) ($row['item_body'] ?? ''),
                'image_path' => (string) ($row['image_path'] ?? ''),
                'link_url' => (string) ($row['link_url'] ?? ''),
                'sort_order' => (int) $row['item_sort_order'],
            ];
        }
    }

    return array_values($sections);
}

function cms_get_home_slides(): array
{
    $cacheKey = cms_cache_key('home', 'slides');
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'badge_text' => 'PRIVATE LABEL IS NOW SIMPLIFIED',
            'title' => 'Unleash your custom personal care line effortlessly We made it easier than you ever imagined',
            'description' => 'Launch Your Own Cosmetic Line & Amplify Your Brand With Our Expert Formulations, Empowering Your Success!',
            'button_text' => 'Explore Collection',
            'button_url' => 'shop.php',
            'image_path' => 'assets/imgs/hero/hero-img.png',
            'image_alt' => 'Beauty model',
        ],
        [
            'badge_text' => 'We Blend Science & Nature',
            'title' => 'Premium Ingredients with Effective Formulations',
            'description' => 'We offer 200+ products formulated with naturally derived and organic ingredients.',
            'button_text' => 'Try Our Products',
            'button_url' => 'about.php',
            'image_path' => 'assets/imgs/hero/hero_img2.png',
            'image_alt' => 'Private label product range',
        ],
        [
            'badge_text' => 'VITAMIN C FACIAL SERUM',
            'title' => 'Smoother & Brighter Skin',
            'description' => '',
            'button_text' => 'Learn More',
            'button_url' => 'contact.php',
            'image_path' => 'assets/imgs/hero/hero_img3.png',
            'image_alt' => 'Manufacturing and formulation',
        ],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, badge_text, title, description, button_text, button_url, image_path, image_alt FROM home_slides' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    $slides = [];
    foreach ($rows as $row) {
        $merged = draft_merge_row((array) $row, 'home_slide', (int) $row['id']);
        $slides[] = [
            'id' => (int) ($merged['id'] ?? $row['id']),
            'badge_text' => (string) ($merged['badge_text'] ?? ''),
            'title' => (string) ($merged['title'] ?? ''),
            'description' => (string) ($merged['description'] ?? ''),
            'button_text' => (string) ($merged['button_text'] ?? ''),
            'button_url' => (string) ($merged['button_url'] ?? ''),
            'image_path' => (string) ($merged['image_path'] ?? ''),
            'image_alt' => (string) ($merged['image_alt'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $slides, 300);
    }
    return $slides;
}

function cms_invalidate_home_offices_cache(): void
{
    cache_delete(cms_cache_key('home', 'offices'));
}

function cms_ensure_home_offices_registration_columns(PDO $pdo): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    try {
        $columns = db_fetch_all($pdo, 'SHOW COLUMNS FROM home_offices');
        $existing = [];
        foreach ($columns as $column) {
            $existing[strtolower((string) ($column['Field'] ?? ''))] = true;
        }

        if (empty($existing['company_name'])) {
            $afterColumn = !empty($existing['country']) ? ' AFTER country' : '';
            $pdo->exec('ALTER TABLE home_offices ADD COLUMN company_name VARCHAR(180) NULL' . $afterColumn);
            $existing['company_name'] = true;
        }
        if (empty($existing['registration_label'])) {
            $pdo->exec('ALTER TABLE home_offices ADD COLUMN registration_label VARCHAR(40) NULL AFTER phone');
            $existing['registration_label'] = true;
        }
        if (empty($existing['registration_number'])) {
            $pdo->exec('ALTER TABLE home_offices ADD COLUMN registration_number VARCHAR(120) NULL AFTER registration_label');
            $existing['registration_number'] = true;
        }
        if (empty($existing['tax_label'])) {
            $pdo->exec('ALTER TABLE home_offices ADD COLUMN tax_label VARCHAR(40) NULL AFTER registration_number');
            $existing['tax_label'] = true;
        }
        if (empty($existing['tax_number'])) {
            $pdo->exec('ALTER TABLE home_offices ADD COLUMN tax_number VARCHAR(120) NULL AFTER tax_label');
            $existing['tax_number'] = true;
        }
        $available = true;
        return true;
    } catch (Throwable $e) {
        // Keep the public page available even when the DB user cannot alter schema.
        $available = false;
        return false;
    }
}

function cms_invalidate_home_instagram_reels_cache(): void
{
    cache_delete(cms_cache_key('home', 'instagram_reels'));
}

function cms_get_home_offices(): array
{
    $cacheKey = cms_cache_key('home', 'offices');
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'country' => 'India',
            'company_name' => 'NIMISHA IMPEX WORLDWIDE (P) LIMITED',
            'address' => 'D-226, 10th Avenue, Gaur City 2,\nGr. Noida West, UP - 201301, IN',
            'email' => 'customersupport@nimishaimpex.com',
            'phone' => '+91 (971) 700 4615',
            'registration_label' => 'CIN',
            'registration_number' => 'U20237UP2025PTC234476',
            'tax_label' => 'GST',
            'tax_number' => '09AAKCN9231H1Z4',
            'image_path' => 'assets/imgs/home/office/INDIAN.webp',
        ],
        [
            'country' => 'United States',
            'company_name' => 'Nimisha Impex inc',
            'address' => '30 N Gould St, Ste R,\nSheridan, WY 82801, USA',
            'email' => 'customersupport@nimishaimpex.com',
            'phone' => '+1 (343) 322 5866',
            'registration_label' => 'EIN',
            'registration_number' => '41-4152316',
            'tax_label' => 'TAX ID',
            'tax_number' => '2026-001890284',
            'image_path' => 'assets/imgs/home/office/USA-FLAG.webp',
        ],
        [
            'country' => 'United Kingdom',
            'company_name' => 'Nimisha Impex Worldwide Limited',
            'address' => '128, City Rd, London,\nEC1V 2NX, UNITED KINGDOM',
            'email' => 'customersupport@nimishaimpex.com',
            'phone' => '+91 (120) 518 5637',
            'registration_label' => 'Company No',
            'registration_number' => '17263045',
            'tax_label' => 'UK VAT',
            'tax_number' => '522 9730 88',
            'image_path' => 'assets/imgs/home/office/Flag-United-Kingdom.webp',
        ],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $hasExtendedOfficeColumns = cms_ensure_home_offices_registration_columns($pdo);

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, $hasExtendedOfficeColumns
        ? 'SELECT id, country, company_name, address, email, phone, registration_label, registration_number, tax_label, tax_number, image_path FROM home_offices' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
        : 'SELECT id, country, address, email, phone, image_path FROM home_offices' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );
    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    $out = [];
    foreach ($rows as $row) {
        $merged = draft_merge_row((array) $row, 'home_office', (int) $row['id']);
        $out[] = [
            'id' => (int) ($merged['id'] ?? $row['id']),
            'country' => (string) ($merged['country'] ?? ''),
            'company_name' => (string) ($merged['company_name'] ?? ''),
            'address' => (string) ($merged['address'] ?? ''),
            'email' => (string) ($merged['email'] ?? ''),
            'phone' => (string) ($merged['phone'] ?? ''),
            'registration_label' => (string) ($merged['registration_label'] ?? ''),
            'registration_number' => (string) ($merged['registration_number'] ?? ''),
            'tax_label' => (string) ($merged['tax_label'] ?? ''),
            'tax_number' => (string) ($merged['tax_number'] ?? ''),
            'image_path' => (string) ($merged['image_path'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_instagram_reels(): array
{
    $cacheKey = cms_cache_key('home', 'instagram_reels');
    $fallback = [];

    $folderReels = [];
    $seenVideoPaths = [];
    $seenFileFingerprints = [];
    $reelsDir = __DIR__ . '/../uploads/instagram-reels';
    $folderSignature = 'no-folder';
    if (is_dir($reelsDir)) {
        $files = glob($reelsDir . '/*.{mp4,mov,webm,m4v}', GLOB_BRACE) ?: [];
        usort($files, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        $signatureParts = [];
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $signatureParts[] = basename($file) . '|' . (string) @filesize($file) . '|' . (string) @filemtime($file);
        }
        if ($signatureParts) {
            $folderSignature = sha1(implode(';', $signatureParts));
        }

        $sortOrder = 1;
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            $relativePath = 'uploads/instagram-reels/' . basename($file);
            $normalizedPath = strtolower(str_replace('\\', '/', $relativePath));
            if (isset($seenVideoPaths[$normalizedPath])) {
                continue;
            }
            $fingerprint = @sha1_file($file);
            if ($fingerprint === false) {
                $fingerprint = strtolower(basename($file)) . '|' . (string) @filesize($file);
            }
            if (isset($seenFileFingerprints[$fingerprint])) {
                continue;
            }
            $seenVideoPaths[$normalizedPath] = true;
            $seenFileFingerprints[$fingerprint] = true;
            $folderReels[] = [
                'id' => 0,
                'reel_url' => '',
                'video_path' => $relativePath,
                'sort_order' => $sortOrder++,
                'is_active' => 1,
            ];
        }
    }

    $effectiveCacheKey = $cacheKey . ':' . $folderSignature;
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($effectiveCacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    if ($folderReels) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($effectiveCacheKey, $folderReels, 300);
        }
        return $folderReels;
    }

    $pdo = db();
    if (!$pdo) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($effectiveCacheKey, $fallback, 300);
        }
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, reel_url, video_path, sort_order, is_active FROM home_instagram_reels' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    $out = [];
    foreach ($rows as $row) {
        $merged = draft_merge_row((array) $row, 'home_instagram_reel', (int) ($row['id'] ?? 0));
        $out[] = [
            'id' => (int) ($merged['id'] ?? $row['id'] ?? 0),
            'reel_url' => (string) ($merged['reel_url'] ?? ''),
            'video_path' => (string) ($merged['video_path'] ?? ''),
            'sort_order' => (int) ($merged['sort_order'] ?? $row['sort_order'] ?? 0),
            'is_active' => (int) ($merged['is_active'] ?? $row['is_active'] ?? 1),
        ];
    }

    if (!$out) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($effectiveCacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($effectiveCacheKey, $out, 300);
    }
    return $out;
}

function cms_get_why_choose_pages(bool $publishedOnly = true): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $sql = 'SELECT p.id, p.title, p.slug, p.status, pm.meta_title, pm.meta_description, pm.meta_keywords, pm.canonical_url
            FROM pages p
            LEFT JOIN page_meta pm ON pm.page_id = p.id
            WHERE p.page_group = :grp';
    $params = [':grp' => 'why_choose_us'];

    if ($publishedOnly && !preview_mode_include_drafts()) {
        $sql .= ' AND p.status = :st';
        $params[':st'] = 'published';
    }

    $sql .= ' ORDER BY p.updated_at DESC, p.id DESC';
    $pages = db_fetch_all($pdo, $sql, $params);

    $preferredOrder = [
        'private-label-skin-care-manufacturer', 
        'private-label-hair-care-manufacturer',
        'private-label-mens-grooming-products',
        'private-label-essential-oil-supplier',
        'white-label-makeup',
        'luxury-private-label-cosmetics',
        'private-label-spa-product',
        'private-label-salon-products',
        'third-party-cosmetic',
        'private-label-cosmetics-brand',
        'bathing-soap-manufacturer',
        'contract-manufacturer-for-cosmetics-products',
    ];
    $rank = array_flip($preferredOrder);

    usort($pages, static function (array $a, array $b) use ($rank): int {
        $slugA = (string) ($a['slug'] ?? '');
        $slugB = (string) ($b['slug'] ?? '');
        $rankA = $rank[$slugA] ?? PHP_INT_MAX;
        $rankB = $rank[$slugB] ?? PHP_INT_MAX;
        if ($rankA !== $rankB) {
            return $rankA <=> $rankB;
        }
        return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });

    return $pages;
}

function cms_ensure_home_cta_cards_table(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS home_cta_cards (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              card_key VARCHAR(50) NULL UNIQUE,
              title VARCHAR(120) NOT NULL,
              button_text VARCHAR(120) NOT NULL,
              button_url VARCHAR(255) NOT NULL,
              image_path VARCHAR(255) NOT NULL,
              image_alt VARCHAR(255) NULL,
              sort_order INT NOT NULL DEFAULT 0,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX idx_home_cta_cards_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $count = (int) ($pdo->query("SELECT COUNT(*) FROM home_cta_cards")->fetchColumn() ?: 0);
        if ($count === 0) {
            $pdo->exec("
                INSERT INTO home_cta_cards (card_key, title, button_text, button_url, image_path, image_alt, sort_order, is_active) VALUES
                ('explore_now', 'Explore Now', 'Explore now', 'shop.php', 'assets/imgs/category/category_thumb2.jpeg', 'Explore now thumb', 1, 1),
                ('try_products', 'Try Our Products', 'Try Our Products', 'shop.php', 'assets/imgs/category/category_thumb3.jpeg', 'Try Our Products thumb', 2, 1),
                ('contact_us', 'Contact Us', 'Contact Us', 'shop.php', 'assets/imgs/category/category_thumb1.jpeg', 'Contact Us thumb', 3, 1)
            ");
        }
        $checked = true;
        return true;
    } catch (Throwable $e) {
        $checked = false;
        return false;
    }
}

function cms_get_home_cta_cards(): array
{
    $cacheKey = cms_cache_key('home', 'cta_cards');
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'id' => 1,
            'card_key' => 'explore_now',
            'title' => 'Explore Now',
            'button_text' => 'Explore now',
            'button_url' => 'shop.php',
            'image_path' => 'assets/imgs/category/category_thumb2.jpeg',
            'image_alt' => 'Explore now thumb',
            'sort_order' => 1,
            'is_active' => 1
        ],
        [
            'id' => 2,
            'card_key' => 'try_products',
            'title' => 'Try Our Products',
            'button_text' => 'Try Our Products',
            'button_url' => 'shop.php',
            'image_path' => 'assets/imgs/category/category_thumb3.jpeg',
            'image_alt' => 'Try Our Products thumb',
            'sort_order' => 2,
            'is_active' => 1
        ],
        [
            'id' => 3,
            'card_key' => 'contact_us',
            'title' => 'Contact Us',
            'button_text' => 'Contact Us',
            'button_url' => 'shop.php',
            'image_path' => 'assets/imgs/category/category_thumb1.jpeg',
            'image_alt' => 'Contact Us thumb',
            'sort_order' => 3,
            'is_active' => 1
        ]
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    cms_ensure_home_cta_cards_table($pdo);

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM home_cta_cards' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (empty($rows)) {
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_cta_card');
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $rows, 300);
    }
    return $rows;
}

function cms_invalidate_how_it_works_cache(): void
{
    cache_delete(cms_cache_key('how_it_works', 'sections'));
    cache_delete(cms_cache_key('how_it_works', 'sections_active'));
    cache_delete(cms_cache_key('how_it_works', 'sections_all'));
    cache_delete(cms_cache_key('how_it_works', 'accordions'));
    cache_delete(cms_cache_key('how_it_works', 'accordions_active'));
    cache_delete(cms_cache_key('how_it_works', 'accordions_all'));
    cache_clear_prefix('cms:how_it_works:');
    cache_delete(cms_cache_key('setting', 'how_it_works_layout'));
    // Hero headline & description are cached separately under cms:setting:*,
    // so they must be invalidated here too, otherwise saved changes to the
    // hero header only appear after the 10-minute cache TTL expires.
    cache_delete(cms_cache_key('setting', 'how_it_works_hero_title'));
    cache_delete(cms_cache_key('setting', 'how_it_works_hero_description'));
}


function cms_ensure_how_it_works_tables(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS how_it_works_sections (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                body_1 TEXT NOT NULL,
                body_2 TEXT NULL,
                image_path VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_how_it_works_sections_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS how_it_works_accordions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_open_default TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_how_it_works_accordions_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $ensureIndex = function (PDO $pdo, string $table, string $indexName, string $columns) {
            try {
                $exists = db_fetch_one($pdo, "SHOW INDEX FROM `{$table}` WHERE Key_name = :k LIMIT 1", [':k' => $indexName]);
                if (!$exists) {
                    $pdo->exec("CREATE INDEX `{$indexName}` ON `{$table}` ({$columns})");
                }
            } catch (Throwable $e) {}
        };

        $ensureIndex($pdo, 'how_it_works_sections', 'idx_how_it_works_sections_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'how_it_works_sections', 'idx_how_it_works_sections_order', 'sort_order, id');
        $ensureIndex($pdo, 'how_it_works_accordions', 'idx_how_it_works_accordions_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'how_it_works_accordions', 'idx_how_it_works_accordions_order', 'sort_order, id');
        $ensureIndex($pdo, 'how_it_works_accordions', 'idx_how_it_works_accordions_open_default', 'is_open_default');

        // Seed sections if empty

        $countSec = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM how_it_works_sections');
        if ($countSec === 0) {
            $pdo->exec("
                INSERT INTO how_it_works_sections (title, body_1, body_2, image_path, sort_order, is_active) VALUES
                ('Choose Your Product Components', 'Unleash your brand\'s potential with <span class=\"theme-color-font fw-bold\">mybrandplease.com</span>. Explore our extensive range of over 200+ formulations across body, skin, and hair care, carefully crafted for professional-grade results. Experience the luxury of high-quality ingredients, including naturally derived and certified organic components. Tailor your products to perfection with our diverse packaging options and captivating fragrances.', 'Handpick your favorites, knowing they will captivate and delight your cherished clients. Embark on a sensory journey and sample our extraordinary products today.', 'assets/imgs/how-it-works/Choose-Your-Product-Components-min-2048x1244.webp', 1, 1),
                ('Define Your Offerings', 'Harness the power of your brand\'s message and fine-tune your opening order. Define product names, quantities, and sizes to perfection. Make key decisions that will shape your product line. Take control and let us bring your vision to reality.', 'Explore our blog for invaluable expert tips and tricks. Seize this opportunity to create a remarkable brand experience. Check out our blog for our expert tips &amp; tricks <a href=\"blog.php\" class=\"theme-color-font\">here</a>.', 'assets/imgs/how-it-works/Define-Your-Offerings-min-2048x1244.webp', 2, 1),
                ('Label Design & Printing', 'Embark on your design journey with meticulous planning and make your labels shine. Our expert Graphic Designers are poised to create stunning logos and labels for your personal care products.', 'Benefit from our comprehensive design services or utilize our templates to collaborate with your own team. Experience the added convenience of our in-house digital print services or explore external options for unique finishes and metallic elements.', 'assets/imgs/how-it-works/Label-Design-Printing-min-2048x1243.webp', 3, 1),
                ('Finishing Touches', 'Elevate your brand with exceptional exterior packaging and exquisite finishing touches. Enhance your marketing presence and create a luxurious impression by adding premium exterior boxes.', 'Ensure optimal protection during shipping and explore options like seals, shrink wrap, inserts, and promotional materials to make your products truly distinctive. Invest in finer details that leave a long-lasting impression.', 'assets/imgs/how-it-works/Finishing-Touches-min-768x467.webp', 4, 1)
            ");
        }

        // Seed accordions if empty
        $countAcc = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM how_it_works_accordions');
        if ($countAcc === 0) {
            $pdo->exec("
                INSERT INTO how_it_works_accordions (title, body, sort_order, is_open_default, is_active) VALUES
                ('Contact our Project Consultants to place your order.', '<p class=\"text-muted lh-base fs-17 word-spacing-6\">Once you have all the elements of your order finalized, get in touch with one of our Project Consultants to place your order. The following details will be required:</p><ul class=\"order-accordion__list\"><li><strong>Products:</strong> The products you\'d like to order</li><li><strong>Fragrance:</strong> If you would like any of your products scented</li><li><strong>Sizes:</strong> The unit size of each product you would like us to produce</li><li><strong>Packaging:</strong> The containers and closures you would like to use</li><li><strong>Quantity:</strong> How many of each unit you would like to order</li><li><strong>Labels:</strong> If you need any assistance with label design and/or label printing</li><li><strong>Finishing Touches:</strong> If you require any exterior elements, such as boxes or seals</li><li><strong>Shipping Details:</strong> Where you will want your products shipped once complete.</li><li><strong>Additional Services:</strong> If you would like to use any of our additional services, such as photography or documentation preparations</li></ul>', 1, 1, 1),
                ('Receive your Production Quote & Make Any Changes!', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">Your Project Consultant will consolidate all of your elements into a final production quote for you to review & view your unit and services pricing. This production quote will be the document that our Production Teams use to manufacture your goods, so it is essential that you make any necessary changes or modifications at this stage!</p>', 2, 0, 1),
                ('Approve your Order & Pay your Deposit.', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">Once you have signed off on all the details of your order, we will require a 50% deposit before we move the order to production. Changes cannot be made after this time.</p>', 3, 0, 1),
                ('Begin your Design Process with our Graphics Team or Share your Designs With us.', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">If you’ve chosen to use our graphic design services to design your labels and/or logo, the design process will begin now, after the order has been placed. You’ll be matched up with a designer and they will walk you through the process of the design. Otherwise, if you will be designing your own labels, we will provide your team with our templates at this time so they can set them up to ensure they will work with our printing presses. It is important to note that we always will need final approval on your order to proceed with any graphic design initiatives.</p>', 4, 0, 1),
                ('Your Order Will Begin Production.', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">Now that your labels are finalized & ready for print, all of the puzzle pieces have come together and your order will go into the final stage of its production process. Our team will manufacture your order per the specifications of your approved production quote. Our standard lead time for opening orders is 8 weeks, once the labels have been finalized, however, these lead times are not guaranteed and can fluctuate to be both shorter and longer depending on a number of factors including component sourcing & seasonality.</p>', 5, 0, 1),
                ('Your Order is Complete & Ready for Shipping! Final Payment is Required.', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">Once your order is complete and ready for shipping, we will require the balance of your order to be paid. Please note that any shipping charges will be added to your final bill, along with any applicable taxes or fees. Once paid, we will ship your products to your desired location, whether that be your personal or business address, or a fulfillment center of your choosing.</p>', 6, 0, 1),
                ('Your Vision has Been Brought to Life & your Products are Ready for your Clients!', '<p class=\"text-muted lh-base fs-17 word-spacing-6 mb-0\">Your finished custom products have arrived! You are now ready to launch and present your personal care line to your customers.</p>', 7, 0, 1)
            ");
        }

        $checked = true;
        return true;
    } catch (Throwable $e) {
        $checked = false;
        return false;
    }
}

function cms_get_how_it_works_sections(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('how_it_works', 'sections' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_how_it_works_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM how_it_works_sections' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'how_it_works_section');
    }

    return $rows;
}

function cms_get_how_it_works_accordions(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('how_it_works', 'accordions' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_how_it_works_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM how_it_works_accordions' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'how_it_works_accordion');
    }

    return $rows;
}

function cms_get_how_it_works_layout(): string
{
    $layout = cms_get_setting('how_it_works_layout', 'default');
    $allowed = ['default', 'left', 'right', 'center'];
    return in_array($layout, $allowed, true) ? $layout : 'default';
}

function cms_invalidate_about_cache(): void
{
    cache_delete(cms_cache_key('about', 'blocks_active'));
    cache_delete(cms_cache_key('about', 'blocks_all'));
    cache_delete(cms_cache_key('about', 'certifications_active'));
    cache_delete(cms_cache_key('about', 'certifications_all'));
    cache_delete(cms_cache_key('about', 'key_benefits_active'));
    cache_delete(cms_cache_key('about', 'key_benefits_all'));
    cache_delete(cms_cache_key('about', 'accreditations_active'));
    cache_delete(cms_cache_key('about', 'accreditations_all'));
    cache_clear_prefix('cms:about:');
    cms_invalidate_settings_cache();
}

function cms_ensure_about_tables(PDO $pdo): bool
{
    static $ensured = false;
    if ($ensured) {
        return true;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS about_blocks (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                section_heading VARCHAR(255) NULL,
                section_intro TEXT NULL,
                block_title VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                image_alt VARCHAR(255) NULL,
                layout ENUM('left', 'right') NOT NULL DEFAULT 'left',
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_about_blocks_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS about_certifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                icon_path VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_about_certs_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS about_key_benefits (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                label VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_about_benefits_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS about_accreditations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                image_path VARCHAR(255) NOT NULL,
                alt_text VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_about_accred_active_order (is_active, sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $ensureIndex = function (PDO $pdo, string $table, string $indexName, string $columns) {
            try {
                $exists = db_fetch_one($pdo, "SHOW INDEX FROM `{$table}` WHERE Key_name = :k LIMIT 1", [':k' => $indexName]);
                if (!$exists) {
                    $pdo->exec("CREATE INDEX `{$indexName}` ON `{$table}` ({$columns})");
                }
            } catch (Throwable $e) {}
        };

        $ensureIndex($pdo, 'about_blocks', 'idx_about_blocks_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'about_blocks', 'idx_about_blocks_order', 'sort_order, id');
        $ensureIndex($pdo, 'about_certifications', 'idx_about_certs_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'about_certifications', 'idx_about_certs_order', 'sort_order, id');
        $ensureIndex($pdo, 'about_key_benefits', 'idx_about_benefits_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'about_key_benefits', 'idx_about_benefits_order', 'sort_order, id');
        $ensureIndex($pdo, 'about_accreditations', 'idx_about_accred_active_order', 'is_active, sort_order, id');
        $ensureIndex($pdo, 'about_accreditations', 'idx_about_accred_order', 'sort_order, id');

        // Seed defaults if empty
        $countBlocks = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM about_blocks');
        if ($countBlocks === 0) {
            $pdo->exec("
                INSERT INTO about_blocks (section_heading, section_intro, block_title, body, image_path, image_alt, layout, sort_order, is_active) VALUES
                (NULL, NULL, 'Who We Are?', '<p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">With an extensive industry experience spanning over two decades, mybrandplease.com has gained the trust of numerous global brands as their preferred manufacturing partner, facilitating the realization of their vision.</p><p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">Our team consists of dedicated personal care experts and enthusiasts who provide comprehensive assistance throughout your Private Label journey</p><p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">We offer an expansive range of product and packaging options, enabling you to craft a distinctive product line that is not only cost-effective and of superior quality but also proudly made in INDIA with unwavering love and passion.</p>', 'assets/imgs/about/Who-we-are-min-2048x1238.jpg', 'Our Products', 'left', 1, 1),
                ('We Located in the vibrant city of <span class=\"theme-color-font h3\">Delhi, India</span>', '<span class=\"theme-color-font\">mybrandplease.com</span> proudly operates as a trusted hub for numerous renowned brands, distinguished Spas, Hotels, Salons, &amp; Retailers across the globe.', 'What We Offer?', '<p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">With an extensive industry experience spanning over two decades, mybrandplease.com has gained the trust of numerous global brands as their preferred manufacturing partner, facilitating the realization of their vision.</p><p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">Our team consists of dedicated personal care experts and enthusiasts who provide comprehensive assistance throughout your Private Label journey</p><p class=\"mb-2 text-muted lh-base fs-17 word-spacing-6\">We offer an expansive range of product and packaging options, enabling you to craft a distinctive product line that is not only cost-effective and of superior quality but also proudly made in INDIA with unwavering love and passion.</p>', 'assets/imgs/about/what-do-we-offer-min-2048x1241.jpg', 'Manufacturing', 'right', 2, 1),
                ('Our relentless pursuit: safety, efficacy, and the essence of natural formulation.', NULL, 'How We Formulate?', '<p class=\"mb-3 text-muted lh-base fs-17 word-spacing-6\">Embracing scientific rigor, our formulations epitomize excellence, with premium ingredients securing robust shelf life and customer safety.</p><p class=\"mb-3 text-muted lh-base fs-17 word-spacing-6\">The alchemy of science and nature converge in formulations that astound with tangible, transformative results. Our goal: ignite customer devotion, fueling repeat purchases and skyrocketing sales.</p><p class=\"mb-0 text-muted lh-base fs-17 word-spacing-6\">We grasp the pivotal role of results-driven formulations, fusing the epitome of scientific innovation with the bounties of nature. Unveiling nature’s finest, we harness the potency of natural and organic ingredients, unveiling a realm of unparalleled beauty and wellness.</p>', 'assets/imgs/about/How-do-we-Formulate-min-2048x1241.jpg', 'How We Formulate', 'left', 3, 1);
            ");
        }

        $countCerts = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM about_certifications');
        if ($countCerts === 0) {
            $pdo->exec("
                INSERT INTO about_certifications (icon_path, title, description, sort_order, is_active) VALUES
                ('assets/imgs/about/Picture-1.png-11-500x497.jpg', 'Vegan Formulas', 'The majority of our formulations offered are Vegan.', 1, 1),
                ('assets/imgs/about/Curelty.jpg', 'Cruelty Free', 'Our formulations are never tested on animals.', 2, 1),
                ('assets/imgs/about/GMP-500x500.jpg', 'GMP Certified', 'The products are manufactured in a GMP Certified Facility.', 3, 1),
                ('assets/imgs/about/Organic-500x500.jpg', '100% ORGANIC', 'Our ingredients are 100% organic and safe from any side effects.', 4, 1),
                ('assets/imgs/about/FDA-scaled-500x502.jpg', 'FDA COMPLIANT', 'Our products are made in a FDA Compliant Facility.', 5, 1),
                ('assets/imgs/about/MOQ-500x500.jpg', 'Low MOQ', 'We strive to make starting your own line accessible to all.', 6, 1),
                ('assets/imgs/about/9001.jpg', 'ISO Certified', 'Our facilities is ISO 9001:2015 Certified.', 7, 1),
                ('assets/imgs/about/premium-500x500.jpg', 'Premium Quality', '100% Premium Quality Products are offered.', 8, 1);
            ");
        }

        $countBenefits = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM about_key_benefits');
        if ($countBenefits === 0) {
            $pdo->exec("
                INSERT INTO about_key_benefits (label, description, sort_order, is_active) VALUES
                ('Higher Profits', 'Unlock the freedom to determine your own pricing with our premium natural and organic-based skin and hair care products, delivering uncompromising quality at costs rivaling or surpassing top brands, Eliminating The Constraints of MSRP.', 1, 1),
                ('Brand Equity', 'Elevate your brand’s reputation and market presence by selling your exclusive private label skin and hair care products, fostering customer loyalty and driving business value growth.', 2, 1),
                ('Increased Sales', 'Empower your staff by involving them in the development of your private label products, igniting their commitment and driving remarkable growth in product sales', 3, 1),
                ('Client Retention', 'Unleash the power of your brand as your clients become ambassadors, carrying your essence and influence straight to their homes.', 4, 1);
            ");
        }

        $countAccred = (int) db_fetch_value($pdo, 'SELECT COUNT(*) FROM about_accreditations');
        if ($countAccred === 0) {
            $pdo->exec("
                INSERT INTO about_accreditations (image_path, alt_text, sort_order, is_active) VALUES
                ('assets/imgs/about/FDA-scaled-500x502.jpg', 'FDA Compliant Facility', 1, 1),
                ('assets/imgs/about/TUV-500x500.jpg', 'TUV Rheinland Certified', 2, 1),
                ('assets/imgs/about/9001.jpg', 'ISO 9001 Certified', 3, 1),
                ('assets/imgs/about/GMP1-500x500.jpg', 'GMP Certified', 4, 1),
                ('assets/imgs/about/PBA-500x189.jpg', 'Professional Beauty Association', 5, 1),
                ('assets/imgs/about/FIEO-500x214.jpg', 'FIEO', 6, 1),
                ('assets/imgs/about/EU.jpg', 'European Standards', 7, 1),
                ('assets/imgs/about/HACCP1-1-500x268.jpg', 'HACCP Certified', 8, 1);
            ");
        }

        $ensured = true;
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function cms_get_about_blocks(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('about', 'blocks' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_about_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM about_blocks' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'about_block');
    }

    return $rows;
}

function cms_get_about_certifications(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('about', 'certifications' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_about_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM about_certifications' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'about_certification');
    }

    return $rows;
}

function cms_get_about_key_benefits(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('about', 'key_benefits' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_about_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM about_key_benefits' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'about_key_benefit');
    }

    return $rows;
}

function cms_get_about_accreditations(bool $includeInactive = false): array
{
    $cacheKey = cms_cache_key('about', 'accreditations' . ($includeInactive ? '_all' : '_active'));
    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $pdo = db();
    if (!$pdo || !cms_ensure_about_tables($pdo)) {
        return [];
    }

    $activeClause = ($includeInactive || preview_mode_include_drafts()) ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all($pdo, 'SELECT * FROM about_accreditations' . $activeClause . ' ORDER BY sort_order ASC, id ASC');

    if (!preview_mode_should_bypass_cache() && !$includeInactive) {
        cache_set($cacheKey, $rows, 300);
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'about_accreditation');
    }

    return $rows;
}


