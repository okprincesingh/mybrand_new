<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Check if admin is authenticated
if (!function_exists('admin_current')) {
    require_once __DIR__ . '/../../includes/cms.php';
}

$admin = admin_current();
if (!$admin) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$pdo = db();

// Get order details with customer info
$stmt = $pdo->prepare('
    SELECT o.*,
           c.first_name as customer_first_name,
           c.last_name as customer_last_name,
           c.email as customer_email
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = :id
');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items
$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id');
$stmt->execute([':order_id' => $orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order status history
$stmt = $pdo->prepare('
    SELECT h.*, a.name as admin_name
    FROM order_status_history h
    LEFT JOIN admins a ON h.created_by = a.id
    WHERE h.order_id = :order_id
    ORDER BY h.created_at DESC
');
$stmt->execute([':order_id' => $orderId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items,
    'history' => $history
]);
