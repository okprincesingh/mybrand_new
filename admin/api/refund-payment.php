<?php

require_once __DIR__ . '/../_init.php';
$adminUser = admin_require_auth();
require_once __DIR__ . '/../../includes/stripe-config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf_or_fail();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$orderId = (int) ($input['order_id'] ?? 0);
$amount = isset($input['amount']) && is_numeric($input['amount']) ? (float) $input['amount'] : null;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$pdo = db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, transaction_id, total_amount, currency, payment_method, payment_status FROM orders WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if (strtolower((string) ($order['payment_method'] ?? '')) !== 'stripe') {
    echo json_encode(['success' => false, 'message' => 'Refund is only available for Stripe payments']);
    exit;
}

$transactionId = trim((string) ($order['transaction_id'] ?? ''));
if ($transactionId === '') {
    echo json_encode(['success' => false, 'message' => 'Transaction ID not found for this order']);
    exit;
}

try {
    $currency = payment_currency_normalize((string) ($order['currency'] ?? 'usd'));
    $amountInMinor = null;
    if ($amount !== null && $amount > 0) {
        $amountInMinor = payment_amount_to_minor_units($amount, $currency);
    }

    $refund = stripe_refund_payment($transactionId, $amountInMinor, [
        'order_id' => (string) $orderId,
        'admin_id' => (string) ((int) ($adminUser['id'] ?? 0)),
    ]);

    $pdo->prepare('UPDATE orders SET payment_status = :payment_status, status = CASE WHEN status IN ("pending","processing") THEN "refunded" ELSE status END WHERE id = :id')
        ->execute([':payment_status' => 'refunded', ':id' => $orderId]);

    payment_log_event('stripe', 'refund_order_updated', $transactionId, ['order_id' => $orderId], $refund, 'success', $orderId, null, (string) $refund['currency'], payment_minor_units_to_amount((int) $refund['amount'], (string) $refund['currency']));

    echo json_encode([
        'success' => true,
        'message' => 'Refund created successfully',
        'data' => [
            'refund_id' => $refund['id'],
            'status' => $refund['status'],
            'amount' => $refund['amount'],
            'currency' => $refund['currency'],
            'display_amount' => payment_minor_units_to_amount((int) $refund['amount'], (string) $refund['currency']),
        ]
    ]);
} catch (Throwable $e) {
    payment_log_event('stripe', 'refund_failed', $transactionId, ['order_id' => $orderId, 'amount' => $amount], ['error' => $e->getMessage()], 'failed', $orderId);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
