<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/draft.php';

function cms_invalidate_home_working_process_cache(): void
{
    cache_delete('cms:home:working_process');
}

function cms_invalidate_home_working_process_content_cache(): void
{
    cache_delete('cms:home:working_process_content');
}

function cms_invalidate_home_brand_builder_cache(): void
{
    cache_delete('cms:home:brand_builder');
    cache_delete('cms:home:brand_builder_items');
}

function cms_invalidate_home_brand_builder_items_cache(): void
{
    cache_delete('cms:home:brand_builder_items');
}

function cms_invalidate_home_getting_started_cache(): void
{
    cache_delete('cms:home:getting_started');
}

function cms_invalidate_home_getting_started_content_cache(): void
{
    cache_delete('cms:home:getting_started_content');
}

function cms_invalidate_home_milestones_cache(): void
{
    cache_delete('cms:home:milestones');
}

function cms_invalidate_home_milestones_content_cache(): void
{
    cache_delete('cms:home:milestones_content');
}

function cms_invalidate_home_marquee_strips_cache(): void
{
    cache_delete('cms:home:marquee_strips');
}

function cms_invalidate_home_partner_logos_cache(): void
{
    cache_delete('cms:home:partner_logos');
}

function cms_invalidate_home_certification_logos_cache(): void
{
    cache_delete('cms:home:certification_logos');
}

function cms_invalidate_home_category_section_cache(): void
{
    cache_delete('cms:home:category_section');
}

function cms_invalidate_home_categories_cache(): void
{
    cache_delete('cms:home:categories');
    cache_delete('cms:home:catalog_category_tree');
}

function cms_get_home_working_process(): array
{
    $cacheKey = 'cms:home:working_process';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'title_small' => 'Brand',
            'title_large' => 'Equity',
            'text' => 'Building sales of your own skin and hair care brand strengthens your prestige with customers and in the market.',
            'href' => 'contact.php',
            'image_path' => 'assets/imgs/home/4.png',
            'alt_text' => 'Brand equity',
        ],
        [
            'title_small' => 'Client',
            'title_large' => 'Retention',
            'text' => 'Retain customers with your own brand while offering premium product experiences at strong pricing, helping you create brand loyalty.',
            'href' => 'contact.php',
            'image_path' => 'assets/imgs/home/3.png',
            'alt_text' => 'Customer loyalty',
        ],
        [
            'title_small' => 'Increased',
            'title_large' => 'Sales',
            'text' => 'Market your own brand with margin and product sale price in your control, giving you stronger flexibility in marketing approach and decisions.',
            'href' => 'contact.php',
            'image_path' => 'assets/imgs/home/2.png',
            'alt_text' => 'Increased sales',
        ],
        [
            'title_small' => 'Higher',
            'title_large' => 'Profits',
            'text' => 'Our high-quality natural and organic-based skin and hair care products are offered at costs comparable to or lower than leading brands, while you set the sale price.',
            'href' => 'contact.php',
            'image_path' => 'assets/imgs/home/1.png',
            'alt_text' => 'Profit growth',
        ],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, title_small, title_large, text, href, image_path, alt_text FROM home_working_process' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_working_process');
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'title_small' => (string) ($row['title_small'] ?? ''),
            'title_large' => (string) ($row['title_large'] ?? ''),
            'text' => (string) ($row['text'] ?? ''),
            'href' => (string) ($row['href'] ?? 'contact.php'),
            'image_path' => (string) ($row['image_path'] ?? ''),
            'alt_text' => (string) ($row['alt_text'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_category_section(): array
{
    $cacheKey = 'cms:home:category_section';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'section_key' => 'main',
        'title_text' => 'Nature Powered Ingredients',
        'is_active' => 1,
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    try {
        $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
        $row = db_fetch_one($pdo, 'SELECT * FROM home_category_section WHERE section_key = :k' . $activeClause . ' LIMIT 1', [
            ':k' => 'main',
        ]);
    } catch (Throwable $e) {
        $row = null;
    }

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_category_section', (int) ($row['id'] ?? 0));
    }

    $out = [
        'section_key' => (string) ($row['section_key'] ?? 'main'),
        'title_text' => (string) ($row['title_text'] ?? 'Nature Powered Ingredients'),
        'is_active' => (int) ($row['is_active'] ?? 1),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_catalog_category_tree(): array
{
    $cacheKey = 'cms:home:catalog_category_tree';
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

    try {
        $hasPageImagePath = (bool) db_fetch_one($pdo, "SHOW COLUMNS FROM categories LIKE 'page_image_path'");
    } catch (Throwable $e) {
        $hasPageImagePath = false;
    }

    $pageImageSelect = $hasPageImagePath ? 'page_image_path' : 'NULL AS page_image_path';
    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, parent_id, name, slug, description, image_path, ' . $pageImageSelect . ', sort_order FROM categories' . $activeClause . ' ORDER BY parent_id ASC, sort_order ASC, name ASC'
    );

    if (!$rows) {
        return [];
    }

    $top = [];
    $subs = [];
    foreach ($rows as $r) {
        if (empty($r['parent_id'])) {
            $top[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'slug' => (string) $r['slug'],
                'name' => (string) $r['name'],
                'description' => (string) ($r['description'] ?? ''),
                'image' => (string) ($r['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
                'page_image' => (string) ($r['page_image_path'] ?: $r['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
                'subcategories' => [],
            ];
        } else {
            $subs[] = $r;
        }
    }

    foreach ($subs as $s) {
        $pid = (int) $s['parent_id'];
        if (isset($top[$pid])) {
            $top[$pid]['subcategories'][] = [
                'id' => (int) $s['id'],
                'parent_id' => $pid,
                'slug' => (string) $s['slug'],
                'name' => (string) $s['name'],
                'description' => (string) ($s['description'] ?? ''),
                'image' => (string) ($s['image_path'] ?: $top[$pid]['image']),
                'page_image' => (string) ($s['page_image_path'] ?: $s['image_path'] ?: $top[$pid]['page_image']),
            ];
        }
    }

    $tree = array_values($top);
    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $tree, 600);
    }
    return $tree;
}

function cms_get_home_categories(): array
{
    $cacheKey = 'cms:home:categories';
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

    try {
        $hasPageImagePath = (bool) db_fetch_one($pdo, "SHOW COLUMNS FROM categories LIKE 'page_image_path'");
    } catch (Throwable $e) {
        $hasPageImagePath = false;
    }
    $pageImageSelect = $hasPageImagePath ? 'c.page_image_path' : 'NULL AS page_image_path';

    try {
        $activeClause = preview_mode_include_drafts() ? '' : ' WHERE hc.is_active = 1';
        $rows = db_fetch_all(
            $pdo,
            'SELECT hc.id AS home_id, hc.category_id, hc.sort_order, hc.is_active,
                    c.id, c.slug, c.name, c.description, c.image_path, ' . $pageImageSelect . '
             FROM home_categories hc
             INNER JOIN categories c ON c.id = hc.category_id
             ' . $activeClause . '
             ORDER BY hc.sort_order ASC, hc.id ASC'
        );
    } catch (Throwable $e) {
        $rows = [];
    }

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, [], 300);
        }
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $homeId = (int) ($row['home_id'] ?? 0);
        $categoryId = (int) ($row['category_id'] ?? 0);

        // Fetch selected subcategories for this home category
        $subRows = [];
        try {
            $subActiveClause = preview_mode_include_drafts() ? '' : ' AND c.is_active = 1';
            $subPageImageSelect = $hasPageImagePath ? 'c.page_image_path' : 'NULL AS page_image_path';
            $subRows = db_fetch_all(
                $pdo,
                'SELECT c.id, c.slug, c.name, c.description, c.image_path, ' . $subPageImageSelect . '
                 FROM home_category_subcategories hcs
                 INNER JOIN categories c ON c.id = hcs.subcategory_id
                 WHERE hcs.home_category_id = :hid' . $subActiveClause . '
                 ORDER BY hcs.sort_order ASC, hcs.id ASC',
                [':hid' => $homeId]
            );
        } catch (Throwable $e) {
            $subRows = [];
        }

        $subcategories = [];
        foreach ($subRows as $sub) {
            $subcategories[] = [
                'id' => (int) ($sub['id'] ?? 0),
                'slug' => (string) ($sub['slug'] ?? ''),
                'name' => (string) ($sub['name'] ?? ''),
                'description' => (string) ($sub['description'] ?? ''),
                'image' => (string) ($sub['image_path'] ?: $row['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
                'page_image' => (string) ($sub['page_image_path'] ?: $sub['image_path'] ?: $row['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
            ];
        }

        $out[] = [
            'id' => $categoryId,
            'home_id' => $homeId,
            'slug' => (string) ($row['slug'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'image' => (string) ($row['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
            'page_image' => (string) ($row['page_image_path'] ?: $row['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (int) ($row['is_active'] ?? 1),
            'subcategories' => $subcategories,
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_working_process_content(): array
{
    $cacheKey = 'cms:home:working_process_content';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'section_key' => 'main',
        'eyebrow_text' => 'Private Label',
        'title_span_text' => 'Why launch',
        'title_text' => 'your own brand',
        'description_text' => 'Enhance your brand reputation and profitability with premium private label cosmetic products, low minimum order quantity, and competitive pricing.',
        'animation_mode' => 'default',
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
    $row = db_fetch_one($pdo, 'SELECT * FROM home_working_process_content WHERE section_key = :k' . $activeClause . ' LIMIT 1', [
        ':k' => 'main',
    ]);

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_working_process_content', (int) ($row['id'] ?? 0));
    }

    $out = [
        'section_key' => (string) ($row['section_key'] ?? 'main'),
        'eyebrow_text' => (string) ($row['eyebrow_text'] ?? ''),
        'title_span_text' => (string) ($row['title_span_text'] ?? ''),
        'title_text' => (string) ($row['title_text'] ?? ''),
        'description_text' => (string) ($row['description_text'] ?? ''),
        'animation_mode' => (string) ($row['animation_mode'] ?? 'default'),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_brand_builder(): array
{
    $cacheKey = 'cms:home:brand_builder';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'section_key' => 'main',
        'kicker_text' => 'Just add your brand.<br>mybrandplease.com handles the rest.',
        'title_text' => 'The modern<br>way to build a<br><span class="brand-builder__changing-word" data-brand-builder-word>skin care</span> <br>brand',
        'subtitle_text' => 'Start Free Today! - Lowest MOQ | Premium Packaging | World-Class Manufacturing',
        'primary_btn_text' => 'Explore Private Label',
        'primary_btn_url' => 'shop.php',
        'secondary_btn_text' => 'Explore Custom Formulation',
        'secondary_btn_url' => 'services.php',
        'stat_1_number' => '100K+',
        'stat_1_label' => 'Brands built',
        'stat_2_number' => '4.9 ★',
        'stat_2_label' => 'Over 400 reviews',
        'stat_3_number' => '1M+',
        'stat_3_label' => 'Orders shipped',
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
    $row = db_fetch_one($pdo, 'SELECT * FROM home_brand_builder WHERE section_key = :k' . $activeClause . ' LIMIT 1', [
        ':k' => 'main',
    ]);

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_brand_builder', (int) ($row['id'] ?? 0));
    }

    $out = [
        'section_key' => (string) ($row['section_key'] ?? 'main'),
        'kicker_text' => (string) ($row['kicker_text'] ?? ''),
        'title_text' => (string) ($row['title_text'] ?? ''),
        'subtitle_text' => (string) ($row['subtitle_text'] ?? ''),
        'primary_btn_text' => (string) ($row['primary_btn_text'] ?? ''),
        'primary_btn_url' => (string) ($row['primary_btn_url'] ?? ''),
        'secondary_btn_text' => (string) ($row['secondary_btn_text'] ?? ''),
        'secondary_btn_url' => (string) ($row['secondary_btn_url'] ?? ''),
        'stat_1_number' => (string) ($row['stat_1_number'] ?? ''),
        'stat_1_label' => (string) ($row['stat_1_label'] ?? ''),
        'stat_2_number' => (string) ($row['stat_2_number'] ?? ''),
        'stat_2_label' => (string) ($row['stat_2_label'] ?? ''),
        'stat_3_number' => (string) ($row['stat_3_number'] ?? ''),
        'stat_3_label' => (string) ($row['stat_3_label'] ?? ''),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_brand_builder_items(): array
{
    $cacheKey = 'cms:home:brand_builder_items';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        ['word_text' => 'skin care', 'image_path' => 'assets/imgs/modern/1.jpg', 'image_alt' => 'Skin care product category'],
        ['word_text' => 'hair care', 'image_path' => 'assets/imgs/modern/2.jpg', 'image_alt' => 'Hair care product category'],
        ['word_text' => 'body care', 'image_path' => 'assets/imgs/modern/3.jpg', 'image_alt' => 'Body care product category'],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND bi.is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT bi.id, bi.word_text, bi.image_path, bi.image_alt, bi.sort_order, bi.is_active FROM home_brand_builder_items bi 
         INNER JOIN home_brand_builder bb ON bb.id = bi.section_id 
         WHERE bb.section_key = :k' . $activeClause . ' 
         ORDER BY bi.sort_order ASC, bi.id ASC',
        [':k' => 'main']
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'word_text' => (string) ($row['word_text'] ?? ''),
            'image_path' => (string) ($row['image_path'] ?? ''),
            'image_alt' => (string) ($row['image_alt'] ?? ''),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (int) ($row['is_active'] ?? 1),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_getting_started(): array
{
    $cacheKey = 'cms:home:getting_started';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'step_number' => '01',
            'icon_emoji' => '🎨',
            'title' => 'Order Sample & Determine Products',
            'description' => 'We offer over 200 formulations in body, skin, and hair care. Choose your favourites that you know your clients will love and order samples online.',
            'learn_more_url' => 'how-it-works.php#define-offerings',
            'back_image_path' => 'assets/imgs/how-it-works/1.png',
            'back_image_alt' => 'Order samples and determine products',
        ],
        [
            'step_number' => '02',
            'icon_emoji' => '🧴',
            'title' => 'Consult with Us on Packaging',
            'description' => 'Focus on your message and the details of your opening order. Identify which packaging works best with your products and your brand.',
            'learn_more_url' => 'how-it-works.php#product-components',
            'back_image_path' => 'assets/imgs/how-it-works/2.png',
            'back_image_alt' => 'Choose product packaging components',
        ],
        [
            'step_number' => '03',
            'icon_emoji' => '✨',
            'title' => 'Get Your Label Designed',
            'description' => 'With the help of our label designing experts, see your brand come to life. We can also assist your designer on label designing of your choice.',
            'learn_more_url' => 'how-it-works.php#design-and-printing',
            'back_image_path' => 'assets/imgs/how-it-works/3.png',
            'back_image_alt' => 'Label design and printing',
        ],
        [
            'step_number' => '04',
            'icon_emoji' => '📦',
            'title' => 'Consider Finishing Touches',
            'description' => 'Details are everything. We can assist you with product boxes, shrink wrap, inserts, and much more to perfect your presentation.',
            'learn_more_url' => 'how-it-works.php#finishing-touches',
            'back_image_path' => 'assets/imgs/how-it-works/4.png',
            'back_image_alt' => 'Finishing touches for private label packaging',
        ],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, step_number, icon_emoji, title, description, learn_more_url, back_image_path, back_image_alt FROM home_getting_started' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_getting_started');
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'step_number' => (string) ($row['step_number'] ?? ''),
            'icon_emoji' => (string) ($row['icon_emoji'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'learn_more_url' => (string) ($row['learn_more_url'] ?? ''),
            'back_image_path' => (string) ($row['back_image_path'] ?? ''),
            'back_image_alt' => (string) ($row['back_image_alt'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_getting_started_content(): array
{
    $cacheKey = 'cms:home:getting_started_content';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'section_key' => 'main',
        'heading_text' => "Here's How To Get Started",
        'description_text' => 'You know your brand and customers best. Let us help you build a custom private label line of offerings that are as unique as your brand.',
        'is_active' => 1,
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
    $row = db_fetch_one(
        $pdo,
        'SELECT * FROM home_getting_started_content WHERE section_key = :k' . $activeClause . ' LIMIT 1',
        [':k' => 'main']
    );

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_getting_started_content', (int) ($row['id'] ?? 0));
    }

    $out = [
        'section_key' => (string) ($row['section_key'] ?? 'main'),
        'heading_text' => (string) ($row['heading_text'] ?? "Here's How To Get Started"),
        'description_text' => (string) ($row['description_text'] ?? ''),
        'is_active' => (int) ($row['is_active'] ?? 1),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_marquee_strip(string $stripKey): array
{
    $cacheKey = 'cms:home:marquee_strip:' . $stripKey;
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'strip_key' => $stripKey,
        'items' => ['Skin Care', 'Hair Care', 'Body Care', 'Fragrances', 'Cosmetic Packaging'],
        'brand_text' => 'mybrandplease.com',
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
    $row = db_fetch_one($pdo, 'SELECT id, strip_key, items, brand_text FROM home_marquee_strips WHERE strip_key = :k' . $activeClause . ' LIMIT 1', [
        ':k' => $stripKey,
    ]);

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_marquee_strip', (int) ($row['id'] ?? 0));
    }

    $items = array_filter(array_map('trim', explode(',', (string) ($row['items'] ?? ''))));
    
    $out = [
        'strip_key' => (string) ($row['strip_key'] ?? $stripKey),
        'items' => array_values($items),
        'brand_text' => (string) ($row['brand_text'] ?? ''),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_partner_logos(): array
{
    $cacheKey = 'cms:home:partner_logos';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        ['logo_path' => 'assets/imgs/home/Amazon-logo-min-300x126.jpg', 'alt_text' => 'Amazon'],
        ['logo_path' => 'assets/imgs/home/Costco_Wholesale_logo-min-300x108.jpg', 'alt_text' => 'Costco'],
        ['logo_path' => 'assets/imgs/home/EBay_logo-min-300x120.jpg', 'alt_text' => 'eBay'],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, logo_path, alt_text FROM home_partner_logos' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_partner_logo');
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'logo_path' => (string) ($row['logo_path'] ?? ''),
            'alt_text' => (string) ($row['alt_text'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_certification_logos(): array
{
    $cacheKey = 'cms:home:certification_logos';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        ['logo_path' => 'assets/imgs/partner-logos/31.png', 'alt_text' => 'TÜV Rheinland'],
        ['logo_path' => 'assets/imgs/partner-logos/COSMOS.png', 'alt_text' => 'Cosmos'],
        ['logo_path' => 'assets/imgs/partner-logos/GMP.png', 'alt_text' => 'GMP Certified'],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, logo_path, alt_text FROM home_certification_logos' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_certification_logo');
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'logo_path' => (string) ($row['logo_path'] ?? ''),
            'alt_text' => (string) ($row['alt_text'] ?? ''),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_milestones_content(): array
{
    $cacheKey = 'cms:home:milestones_content';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        'section_key' => 'main',
        'eyebrow_text' => 'Growth Snapshot',
        'heading_text' => 'Our Milestones',
        'description_text' => 'A quick look at the scale, consistency, and trust we keep building with every private label partnership.',
        'is_active' => 1,
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' AND is_active = 1';
    $row = db_fetch_one(
        $pdo,
        'SELECT * FROM home_milestones_content WHERE section_key = :k' . $activeClause . ' LIMIT 1',
        [':k' => 'main']
    );

    if (!$row) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $row = draft_merge_row((array) $row, 'home_milestones_content', (int) ($row['id'] ?? 0));
    }

    $out = [
        'section_key' => (string) ($row['section_key'] ?? 'main'),
        'eyebrow_text' => (string) ($row['eyebrow_text'] ?? 'Growth Snapshot'),
        'heading_text' => (string) ($row['heading_text'] ?? 'Our Milestones'),
        'description_text' => (string) ($row['description_text'] ?? ''),
        'is_active' => (int) ($row['is_active'] ?? 1),
    ];

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}

function cms_get_home_milestones(): array
{
    $cacheKey = 'cms:home:milestones';
    if (!preview_mode_should_bypass_cache()) {
        $cached = cache_get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $fallback = [
        [
            'id' => 1,
            'image_path' => 'assets/imgs/home/milestone/4381dcfc16-300x254.webp',
            'image_alt' => 'Monthly worldwide inquiries',
            'kicker' => 'Monthly Avg.',
            'number_value' => '1075+',
            'title' => 'Monthly Worldwide Inquiries',
            'sort_order' => 1,
            'is_active' => 1,
        ],
        [
            'id' => 2,
            'image_path' => 'assets/imgs/home/milestone/f99c232e29-2-300x202.webp',
            'image_alt' => 'Customers served monthly',
            'kicker' => 'Monthly Avg.',
            'number_value' => '950+',
            'title' => "Customer's Served Monthly",
            'sort_order' => 2,
            'is_active' => 1,
        ],
        [
            'id' => 3,
            'image_path' => 'assets/imgs/home/milestone/ec2ce0607f-150x150.webp',
            'image_alt' => 'Contract manufacturing for brands',
            'kicker' => 'Brand Scale',
            'number_value' => '650+',
            'title' => 'Contract Manufacturing for Brands',
            'sort_order' => 3,
            'is_active' => 1,
        ],
        [
            'id' => 4,
            'image_path' => 'assets/imgs/home/milestone/b3099fe017-150x150.webp',
            'image_alt' => 'Ayurvedic personal care formulations',
            'kicker' => 'Formula Library',
            'number_value' => '525+',
            'title' => 'Ayurvedic Personal Care Formulations',
            'sort_order' => 4,
            'is_active' => 1,
        ],
    ];

    $pdo = db();
    if (!$pdo) {
        return $fallback;
    }

    $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
    $rows = db_fetch_all(
        $pdo,
        'SELECT id, image_path, image_alt, kicker, number_value, title, sort_order, is_active FROM home_milestones' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
    );

    if (!$rows) {
        if (!preview_mode_should_bypass_cache()) {
            cache_set($cacheKey, $fallback, 300);
        }
        return $fallback;
    }

    if (preview_mode_include_drafts()) {
        $rows = draft_merge_rows($rows, 'home_milestones');
    }

    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'image_path' => (string) ($row['image_path'] ?? ''),
            'image_alt' => (string) ($row['image_alt'] ?? ''),
            'kicker' => (string) ($row['kicker'] ?? ''),
            'number_value' => (string) ($row['number_value'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (int) ($row['is_active'] ?? 1),
        ];
    }

    if (!preview_mode_should_bypass_cache()) {
        cache_set($cacheKey, $out, 300);
    }
    return $out;
}