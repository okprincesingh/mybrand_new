<?php

session_start();

require_once __DIR__ . '/../includes/coupons.php';

header('Content-Type: application/json');

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? $_GET;
$action = strtolower(trim((string) ($input['action'] ?? 'summary')));

$subtotal = coupon_cart_subtotal();

if ($method === 'POST') {
    if ($action === 'apply') {
        $code = trim((string) ($input['code'] ?? ''));
        echo json_encode(coupon_apply_to_session($code, $subtotal));
        exit;
    }

    if ($action === 'remove') {
        coupon_clear_session();
        echo json_encode([
            'success' => true,
            'message' => 'Coupon removed.',
            'data' => coupon_totals_summary($subtotal),
        ]);
        exit;
    }
}

$summary = coupon_refresh_session($subtotal);
echo json_encode([
    'success' => true,
    'message' => 'Coupon summary loaded.',
    'data' => $summary,
]);
