<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/catalog.php';

$slug = rawurldecode(trim((string) ($_GET['slug'] ?? ''), "/ \t\n\r\0\x0B"));

if (!validate_slug_value($slug)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$combinedAerosolPerfumeSlugs = ['aerosols-perfumes', 'aerosols-parfumes', 'aerosols-and-perfumes', 'aerosol-perfume', 'aerosol-and-perfume'];
if (in_array(catalog_normalize_identity($slug), $combinedAerosolPerfumeSlugs, true)) {
    $_GET['category'] = 'aerosols-perfumes';
    unset($_GET['slug']);
    require __DIR__ . '/shop.php';
    exit;
}

$category = catalog_find_category($slug);
if ($category) {
    $_GET['category'] = (string) ($category['slug'] ?? $slug);
    unset($_GET['slug']);
    require __DIR__ . '/shop.php';
    exit;
}

$_GET['slug'] = $slug;
require __DIR__ . '/why-page.php';
