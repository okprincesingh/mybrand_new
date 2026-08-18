<?php
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

echo "========================================\n";
echo " Brand Builder Items CMS Test\n";
echo "========================================\n\n";

$items = cms_get_home_brand_builder_items();
echo "Total Active Rotating Items Fetched: " . count($items) . "\n\n";

foreach (array_slice($items, 0, 5) as $idx => $item) {
    echo sprintf(
        "Item #%d: ID=%d | Word='%s' | Image='%s' | Alt='%s' | Sort=%d | Active=%d\n",
        $idx + 1,
        $item['id'] ?? 0,
        $item['word_text'] ?? '',
        $item['image_path'] ?? '',
        $item['image_alt'] ?? '',
        $item['sort_order'] ?? 0,
        $item['is_active'] ?? 1
    );
}

echo "\nTest Completed Successfully!\n";
