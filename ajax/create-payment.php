<?php

session_start();

require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/shipping.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/stripe-config.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!hash_equals(csrf_token(), csrf_request_token())) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user = user_current();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$currency = payment_currency_normalize((string) ($input['currency'] ?? 'usd'));
$requestedAmount = isset($input['amount']) ? (float) $input['amount'] : null;
$requestedOrderId = trim((string) ($input['order_id'] ?? ''));
$requestedName = trim((string) ($input['customer_name'] ?? ''));
$requestedEmail = trim((string) ($input['email'] ?? ''));

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$subtotal = 0.0;
$cartWeight = 0.0;
foreach ($_SESSION['cart'] as $slug => $quantity) {
    $product = catalog_find_product($slug);
    if (!$product) {
        continue;
    }
    $qty = max(1, (int) $quantity);
    $subtotal += ((float) ($product['price'] ?? 0)) * $qty;
    $cartWeight += shipping_extract_product_weight($product) * $qty;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'message' => 'Unable to calculate order amount.']);
    exit;
}

$billing = $input['billing'] ?? [];
$shipping = $input['shipping'] ?? [];
$useShippingAddress = !empty($input['use_shipping_address']) && $input['use_shipping_address'] === true;
$destinationState = (string) (($useShippingAddress ? ($shipping['state'] ?? '') : ($billing['state'] ?? '')) ?: '');
$postalCode = (string) (($useShippingAddress ? ($shipping['zip'] ?? '') : ($billing['zip'] ?? '')) ?: '');
$destinationCountry = (string) (($useShippingAddress ? ($shipping['country'] ?? '') : ($billing['country'] ?? '')) ?: '');

$shippingMethod = null;
$requestedMethodId = (int) ($input['shipping_method_id'] ?? 0);
if ($requestedMethodId > 0) {
    $shippingMethod = shipping_get_method_by_id($requestedMethodId, $subtotal, $cartWeight, $destinationState, $postalCode, $destinationCountry);
}
if (!$shippingMethod) {
    $sessionSelection = shipping_get_session_selection();
    if (is_array($sessionSelection) && !empty($sessionSelection['id'])) {
        $shippingMethod = shipping_get_method_by_id((int) $sessionSelection['id'], $subtotal, $cartWeight, $destinationState, $postalCode, $destinationCountry);
    }
}
if (!$shippingMethod) {
    $available = getAvailableShippingMethods($subtotal, $cartWeight, $destinationState, $postalCode, $destinationCountry);
    if (!empty($available)) {
        $shippingMethod = $available[0];
    }
}

$shippingCost = $shippingMethod ? (float) ($shippingMethod['cost'] ?? 0.0) : 0.0;
$discountAmount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
$taxAmount = 0.0;
$totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;
$allowZeroAmountCheckout = in_array(strtolower((string) getenv('ALLOW_ZERO_AMOUNT_CHECKOUT')), ['1', 'true', 'yes'], true);
if ($totalAmount <= 0 && $allowZeroAmountCheckout) {
    echo json_encode([
        'success' => true,
        'message' => 'Zero amount test order. Payment skipped.',
        'data' => [
            'skip_payment' => true,
            'payment_intent_id' => 'TEST_ZERO_AMOUNT',
            'amount' => 0,
            'currency' => $currency,
            'total_amount' => 0,
        ],
    ]);
    exit;
}
if ($totalAmount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Order amount must be greater than zero.']);
    exit;
}

if ($requestedAmount !== null && payment_amount_to_minor_units($requestedAmount, $currency) !== payment_amount_to_minor_units($totalAmount, $currency)) {
    payment_log_event('stripe', 'payment_intent_amount_rejected', null, ['requested_amount' => $requestedAmount], ['server_total' => $totalAmount], 'failed', null, null, $currency, $totalAmount);
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Order total changed. Please review your order and try again.']);
    exit;
}

$userId = (int) ($user['id'] ?? 0);
$email = (string) ($user['email'] ?? ($billing['email'] ?? $requestedEmail));
if ($requestedEmail !== '' && filter_var($requestedEmail, FILTER_VALIDATE_EMAIL)) {
    $email = $requestedEmail;
}
$name = trim((string) (($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? '')));
if ($requestedName !== '') {
    $name = substr($requestedName, 0, 120);
}
$customerId = stripe_get_or_create_customer($userId, $email, $name, ['user_id' => (string) $userId]);

try {
    $intent = createPaymentIntent($totalAmount, $currency, [
        'user_id' => (string) $userId,
        'email' => $email,
        'customer_name' => $name,
        'order_id' => $requestedOrderId !== '' ? substr($requestedOrderId, 0, 80) : 'checkout',
    ], $customerId);

    echo json_encode([
        'success' => true,
        'message' => 'Payment intent created.',
        'data' => [
            'client_secret' => $intent['client_secret'],
            'payment_intent_id' => $intent['id'],
            'amount' => $intent['amount'],
            'currency' => $intent['currency'],
            'total_amount' => round($totalAmount, 2),
            'customer_id' => $intent['customer'] ?: $customerId,
        ],
    ]);
} catch (Throwable $e) {
    payment_log_event('stripe', 'payment_intent_create_failed', null, $input, ['error' => $e->getMessage()], 'failed', null, null, $currency, $totalAmount);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
