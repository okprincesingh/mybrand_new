<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/user.php';

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

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$pdo = db();

// Get user details
$stmt = $pdo->prepare('
    SELECT u.*,
           (SELECT MAX(created_at) FROM user_sessions WHERE user_id = u.id) as last_login,
           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = (SELECT c.id FROM customers c WHERE c.email COLLATE utf8mb4_unicode_ci = u.email COLLATE utf8mb4_unicode_ci LIMIT 1) OR o.user_id = u.id) as order_count
    FROM users u
    WHERE u.id = :id
');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Get user addresses
$stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC');
$stmt->execute([':user_id' => $userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user orders (via customer email link or user_id)
$stmt = $pdo->prepare('
    SELECT o.* FROM orders o
    WHERE o.user_id = :user_id
       OR o.customer_id = (SELECT c.id FROM customers c WHERE c.email COLLATE utf8mb4_unicode_ci = (SELECT email FROM users WHERE id = :user_id2) COLLATE utf8mb4_unicode_ci LIMIT 1)
    ORDER BY o.created_at DESC
    LIMIT 10
');
$stmt->execute([':user_id' => $userId, ':user_id2' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'user' => $user,
    'addresses' => $addresses,
    'orders' => $orders
]);
