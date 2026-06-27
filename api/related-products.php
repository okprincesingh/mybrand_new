<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/catalog.php';

header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'data' => ['items' => []],
    ]);
    exit;
}

$slug = trim((string) ($_GET['slug'] ?? ''));
$limit = (int) ($_GET['limit'] ?? 4);
$limit = max(1, min(12, $limit));

if ($slug === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Product slug is required',
        'data' => ['items' => []],
    ]);
    exit;
}

$product = catalog_find_product($slug);
if (!$product) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Product not found',
        'data' => ['items' => []],
    ]);
    exit;
}

$items = catalog_related_products(
    (string) ($product['slug'] ?? ''),
    (string) ($product['category'] ?? ''),
    (string) ($product['subcategory'] ?? ''),
    $limit
);

echo json_encode([
    'success' => true,
    'message' => 'Related products fetched',
    'data' => ['items' => $items],
], JSON_UNESCAPED_SLASHES);


