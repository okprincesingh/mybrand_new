<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/shipping.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = trim((string) ($input['action'] ?? 'list'));
$cart = $_SESSION['cart'] ?? [];
if (!is_array($cart)) {
    $cart = [];
}

$subtotal = 0.0;
foreach ($cart as $slug => $quantity) {
    $product = catalog_find_product((string) $slug);
    if (!$product) {
        continue;
    }
    $qty = max(1, (int) $quantity);
    $subtotal += ((float) ($product['price'] ?? 0)) * $qty;
}
$subtotal = round($subtotal, 2);
$cartWeight = shipping_calculate_cart_weight($cart);

if ($action === 'list') {
    $available = getAvailableShippingMethods($subtotal, $cartWeight, $destinationState, $postalCode);
    $selected = shipping_get_session_selection();

    if (!$selected && !empty($available)) {
        $selected = [
            'id' => (int) $available[0]['id'],
            'cost' => (float) $available[0]['cost'],
            'method_name' => (string) $available[0]['method_name'],
        ];
        shipping_save_selection_to_session($available[0]);
    }

    $shippingCost = (float) ($selected['cost'] ?? 0.0);
    $discountAmount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
    $total = round($subtotal + $shippingCost - $discountAmount, 2);

    echo json_encode([
        'success' => true,
        'methods' => array_map(static function (array $method): array {
            return [
                'id' => (int) $method['id'],
                'method_name' => (string) $method['method_name'],
                'label' => (string) ($method['label'] ?? $method['method_name']),
                'shipping_type' => (string) $method['shipping_type'],
                'cost' => (float) $method['cost'],
                'estimated_delivery_days' => (int) ($method['estimated_delivery_days'] ?? 0),
            ];
        }, $available),
        'selected' => $selected,
        'summary' => [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'cart_weight' => $cartWeight,
        ],
    ]);
    exit;
}

if ($action === 'select') {
    $methodId = (int) ($input['method_id'] ?? 0);
    if ($methodId <= 0) {
        shipping_clear_session_selection();
        echo json_encode(['success' => false, 'message' => 'Invalid shipping method']);
        exit;
    }

    $method = shipping_get_method_by_id($methodId, $subtotal, $cartWeight, $destinationState, $postalCode);
    if (!$method) {
        shipping_clear_session_selection();
        echo json_encode(['success' => false, 'message' => 'Shipping method is not available']);
        exit;
    }

    shipping_save_selection_to_session($method);
    $discountAmount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
    $total = round($subtotal + (float) $method['cost'] - $discountAmount, 2);

    echo json_encode([
        'success' => true,
        'selected' => [
            'id' => (int) $method['id'],
            'method_name' => (string) $method['method_name'],
            'cost' => (float) $method['cost'],
        ],
        'summary' => [
            'subtotal' => $subtotal,
            'shipping_cost' => (float) $method['cost'],
            'discount_amount' => $discountAmount,
            'total' => $total,
            'cart_weight' => $cartWeight,
        ],
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);


