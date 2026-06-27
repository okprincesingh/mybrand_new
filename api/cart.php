<?php
/**
 * Cart API Endpoint
 * Handles all cart operations: add, update, remove, clear, get
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/catalog.php';

header('Content-Type: application/json');

function cart_is_duplicate_add(string $slug, int $quantity): bool
{
    $now = microtime(true);
    $last = $_SESSION['_cart_last_add'] ?? null;
    $_SESSION['_cart_last_add'] = [
        'slug' => $slug,
        'quantity' => $quantity,
        'time' => $now,
    ];
    if (!is_array($last)) {
        return false;
    }
    $lastSlug = (string) ($last['slug'] ?? '');
    $lastQty = (int) ($last['quantity'] ?? 0);
    $lastTime = (float) ($last['time'] ?? 0);
    return $lastSlug === $slug && $lastQty === $quantity && ($now - $lastTime) < 0.8;
}

// Only allow POST and GET requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input or form data
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = isset($input['action']) ? trim((string) $input['action']) : '';

    switch ($action) {
        case 'add':
            echo json_encode(cart_api_add($input));
            break;
        case 'update':
            echo json_encode(cart_api_update($input));
            break;
        case 'remove':
            echo json_encode(cart_api_remove($input));
            break;
        case 'clear':
            echo json_encode(cart_api_clear());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(cart_api_get());
}

/**
 * Add item to cart
 */
function cart_api_add($data) {
    $slug = isset($data['slug']) ? trim((string) $data['slug']) : '';
    $quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;

    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    // Guard against duplicate fast-click / duplicate event dispatch.
    if (cart_is_duplicate_add($slug, $quantity)) {
        return [
            'success' => true,
            'message' => 'Product already added',
            'data' => [
                'slug' => $slug,
                'quantity' => (int) ($_SESSION['cart'][$slug] ?? 0),
                'cart_count' => cart_count_items()
            ]
        ];
    }

    // Get product from catalog
    $product = catalog_find_product($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add or update quantity
    $currentQty = (int) ($_SESSION['cart'][$slug] ?? 0);
    $_SESSION['cart'][$slug] = $currentQty + $quantity;

    // Try to save to database if available
    cart_save_to_database();

    return [
        'success' => true,
        'message' => 'Product added to cart',
        'data' => [
            'slug' => $slug,
            'quantity' => $_SESSION['cart'][$slug],
            'cart_count' => cart_count_items(),
            'product' => [
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image']
            ]
        ]
    ];
}

/**
 * Update item quantity in cart
 */
function cart_api_update($data) {
    $slug = isset($data['slug']) ? trim((string) $data['slug']) : '';
    $quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;

    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    if (!isset($_SESSION['cart'][$slug])) {
        return ['success' => false, 'message' => 'Item not in cart'];
    }

    $_SESSION['cart'][$slug] = $quantity;

    // Try to save to database
    cart_save_to_database();

    return [
        'success' => true,
        'message' => 'Cart updated',
        'data' => [
            'slug' => $slug,
            'quantity' => $quantity,
            'cart_count' => cart_count_items()
        ]
    ];
}

/**
 * Remove item from cart
 */
function cart_api_remove($data) {
    $slug = isset($data['slug']) ? trim((string) $data['slug']) : '';

    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    if (!isset($_SESSION['cart'][$slug])) {
        return ['success' => false, 'message' => 'Item not in cart'];
    }

    unset($_SESSION['cart'][$slug]);

    // Try to save to database
    cart_save_to_database();

    return [
        'success' => true,
        'message' => 'Item removed from cart',
        'data' => [
            'slug' => $slug,
            'cart_count' => cart_count_items()
        ]
    ];
}

/**
 * Clear entire cart
 */
function cart_api_clear() {
    $_SESSION['cart'] = [];

    // Try to clear from database
    cart_clear_database();

    return [
        'success' => true,
        'message' => 'Cart cleared',
        'data' => [
            'cart_count' => 0
        ]
    ];
}

/**
 * Get cart contents
 */
function cart_api_get() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Load from database if session is empty but we have a session ID
    if (empty($_SESSION['cart']) && session_id()) {
        cart_load_from_database();
    }

    $cartRows = [];
    $subtotal = 0.0;

    foreach ($_SESSION['cart'] as $slug => $quantity) {
        $product = catalog_find_product($slug);
        if (!$product) {
            continue;
        }

        $qty = max(1, (int) $quantity);
        $lineTotal = $product['price'] * $qty;
        $subtotal += $lineTotal;

        $cartRows[] = [
            'slug' => $slug,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'link' => catalog_product_link($slug),
            'quantity' => $qty,
            'line_total' => $lineTotal
        ];
    }

    return [
        'success' => true,
        'message' => 'Cart retrieved',
        'data' => [
            'items' => $cartRows,
            'count' => cart_count_items(),
            'subtotal' => $subtotal,
            'total' => $subtotal // Add shipping/tax logic here if needed
        ]
    ];
}

/**
 * Count total items in cart
 */
function cart_count_items() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }

    $count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $count += (int) $qty;
    }
    return $count;
}

/**
 * Save cart to database for persistence
 */
function cart_save_to_database() {
    $pdo = db();
    if (!$pdo || !session_id()) {
        return;
    }

    $sessionId = session_id();

    // Clear existing cart for this session
    $pdo->prepare('DELETE FROM cart WHERE session_id = ?')->execute([$sessionId]);

    if (empty($_SESSION['cart'])) {
        return;
    }

    // Insert new cart items
    $stmt = $pdo->prepare('
        INSERT INTO cart (session_id, product_id, quantity)
        SELECT ?, id, ? FROM products WHERE slug = ?
    ');

    foreach ($_SESSION['cart'] as $slug => $quantity) {
        $stmt->execute([$sessionId, $quantity, $slug]);
    }
}

/**
 * Load cart from database
 */
function cart_load_from_database() {
    $pdo = db();
    if (!$pdo || !session_id()) {
        return;
    }

    $sessionId = session_id();
    $rows = $pdo->prepare('
        SELECT p.slug, c.quantity
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ?
    ');
    $rows->execute([$sessionId]);
    $cartItems = $rows->fetchAll();

    if ($cartItems) {
        $_SESSION['cart'] = [];
        foreach ($cartItems as $item) {
            $_SESSION['cart'][$item['slug']] = (int) $item['quantity'];
        }
    }
}

/**
 * Clear cart from database
 */
function cart_clear_database() {
    $pdo = db();
    if (!$pdo || !session_id()) {
        return;
    }

    $pdo->prepare('DELETE FROM cart WHERE session_id = ?')->execute([session_id()]);
}
?>
