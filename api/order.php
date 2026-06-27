<?php
/**
 * Order API Endpoint
 * Handles order creation from checkout
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/shipping.php';
require_once __DIR__ . '/../includes/stripe-config.php';
require_once __DIR__ . '/../includes/coupons.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = isset($input['action']) ? trim((string) $input['action']) : '';

switch ($action) {
    case 'create':
        echo json_encode(order_create($input));
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function order_create($data) {
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }

    $user = user_current();
    if (!$user) {
        return ['success' => false, 'message' => 'Please login first to place your order'];
    }

    $required = ['billing' => ['first_name', 'last_name', 'email', 'address1', 'city', 'state', 'zip', 'country', 'phone']];
    foreach ($required as $section => $fields) {
        if (empty($data[$section])) {
            return ['success' => false, 'message' => "Missing {$section} information"];
        }
        foreach ($fields as $field) {
            if (empty(trim($data[$section][$field] ?? ''))) {
                return ['success' => false, 'message' => "Missing {$field} in {$section}"];
            }
        }
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return ['success' => false, 'message' => 'Your cart is empty'];
    }

    $cartItems = [];
    $subtotal = 0.0;
    $cartWeight = 0.0;
    foreach ($_SESSION['cart'] as $slug => $quantity) {
        $product = catalog_find_product($slug);
        if (!$product) {
            continue;
        }
        $qty = max(1, (int) $quantity);
        $lineTotal = ((float) $product['price']) * $qty;
        $subtotal += $lineTotal;
        $cartWeight += shipping_extract_product_weight($product) * $qty;
        $cartItems[] = [
            'slug' => $slug,
            'product_id' => $product['id'] ?? null,
            'name' => $product['name'],
            'image' => $product['image'] ?? '',
            'quantity' => $qty,
            'unit_price' => $product['price'],
            'line_total' => $lineTotal
        ];
    }

    if (empty($cartItems)) {
        return ['success' => false, 'message' => 'No valid items in cart'];
    }

    $selectedShippingMethod = null;
    $requestedMethodId = (int) ($data['shipping_method_id'] ?? 0);
$destinationState = (string) ((!empty($data['use_shipping_address']) && $data['use_shipping_address'] === true) ? ($data['shipping']['state'] ?? '') : ($data['billing']['state'] ?? ''));
$postalCode = (string) ((!empty($data['use_shipping_address']) && $data['use_shipping_address'] === true) ? ($data['shipping']['zip'] ?? '') : ($data['billing']['zip'] ?? ''));

    if ($requestedMethodId > 0) {
        $selectedShippingMethod = shipping_get_method_by_id($requestedMethodId, $subtotal, $cartWeight, $destinationState, $postalCode);
        if (!$selectedShippingMethod) {
            return ['success' => false, 'message' => 'Selected shipping method is no longer available.'];
        }
        shipping_save_selection_to_session($selectedShippingMethod);
    } else {
        $sessionSelection = shipping_get_session_selection();
        if (is_array($sessionSelection) && !empty($sessionSelection['id'])) {
            $selectedShippingMethod = shipping_get_method_by_id((int) $sessionSelection['id'], $subtotal, $cartWeight, $destinationState, $postalCode);
        }
        if (!$selectedShippingMethod) {
            $available = getAvailableShippingMethods($subtotal, $cartWeight, $destinationState, $postalCode);
            if (!empty($available)) {
                $selectedShippingMethod = $available[0];
                shipping_save_selection_to_session($selectedShippingMethod);
            }
        }
    }

    $shippingCost = (float) ($selectedShippingMethod['cost'] ?? 0.0);
    $shippingMethodName = (string) ($selectedShippingMethod['method_name'] ?? 'Standard Shipping');
    $shippingMethodId = (int) ($selectedShippingMethod['id'] ?? 0);

    $discountAmount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
    $taxAmount = 0.0;
    $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;

    $paymentMethod = strtolower(trim((string) ($data['payment_method'] ?? 'cod')));
    $paymentStatus = 'pending';
    $transactionId = trim((string) ($data['transaction_id'] ?? $data['payment_intent_id'] ?? ''));
    $orderCurrency = payment_currency_normalize((string) ($data['currency'] ?? 'usd'));
    $stripeCustomerId = '';

    if ($paymentMethod === 'stripe') {
        if ($transactionId === '') {
            return ['success' => false, 'message' => 'Stripe transaction is required.'];
        }

        try {
            $intent = confirmPayment($transactionId);
            $expectedAmount = payment_amount_to_minor_units($totalAmount, $orderCurrency);
            $intentAmount = (int) ($intent['amount'] ?? 0);
            $intentCurrency = strtolower((string) ($intent['currency'] ?? 'usd'));

            if ((string) ($intent['status'] ?? '') !== 'succeeded') {
                payment_log_event('stripe', 'order_payment_not_succeeded', $transactionId, ['order_total' => $expectedAmount], $intent, 'failed');
                return ['success' => false, 'message' => 'Stripe payment is not successful.'];
            }

            if ($intentAmount !== $expectedAmount || $intentCurrency !== $orderCurrency) {
                payment_log_event('stripe', 'order_payment_amount_mismatch', $transactionId, ['order_total' => $expectedAmount, 'currency' => $orderCurrency], $intent, 'failed');
                return ['success' => false, 'message' => 'Paid amount does not match order total.'];
            }

            $paymentStatus = 'paid';
            $stripeCustomerId = (string) ($intent['customer'] ?? '');
        } catch (Throwable $paymentError) {
            payment_log_event('stripe', 'order_payment_verification_failed', $transactionId, ['input' => $data], ['error' => $paymentError->getMessage()], 'failed');
            return ['success' => false, 'message' => 'Unable to verify Stripe payment.'];
        }
    }

    $billing = $data['billing'];
    $shipping = $data['shipping'] ?? $billing;
    $useShippingAddress = !empty($data['use_shipping_address']) && $data['use_shipping_address'] === true;
    if (!$useShippingAddress) {
        $shipping = $billing;
    }

    $orderNumber = 'ORD-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);

    try {
        $pdo->beginTransaction();

        $customerId = null;
        $customerEmail = (string) ($user['email'] ?? $billing['email'] ?? '');
        if ($customerEmail !== '') {
            $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = ?');
            $stmt->execute([$customerEmail]);
            $customer = $stmt->fetch();
            if ($customer) {
                $customerId = $customer['id'];
            } else {
                $stmt = $pdo->prepare('INSERT INTO customers (email, first_name, last_name, phone) VALUES (?, ?, ?, ?)');
                $stmt->execute([$customerEmail, $billing['first_name'], $billing['last_name'], $billing['phone'] ?? '']);
                $customerId = $pdo->lastInsertId();
            }
        }

        if ($customerId) {
            $stmt = $pdo->prepare('UPDATE customers SET first_name = ?, last_name = ?, phone = ? WHERE id = ?');
            $stmt->execute([$billing['first_name'], $billing['last_name'], $billing['phone'] ?? '', $customerId]);
        }

        user_update_profile((int) $user['id'], [
            'first_name' => $billing['first_name'],
            'last_name' => $billing['last_name'],
            'phone' => $billing['phone'] ?? '',
        ]);

        order_sync_user_address((int) $user['id'], $billing, 'billing');
        if ($useShippingAddress) {
            order_sync_user_address((int) $user['id'], $shipping, 'shipping');
        } else {
            order_sync_user_address((int) $user['id'], $billing, 'both');
        }

        $hasOrderUserId = order_has_user_id_column($pdo);
        $hasTransactionId = order_has_transaction_id_column($pdo);
        $hasStripeCustomerId = order_has_stripe_customer_id_column($pdo);
        $shippingColumns = shipping_order_has_columns($pdo);

        $orderColumns = '
                order_number, customer_id, session_id, status, payment_method, payment_status,
                subtotal, shipping_cost, discount_amount, tax_amount, total_amount, currency,
                billing_first_name, billing_last_name, billing_email, billing_phone, billing_company,
                billing_address1, billing_address2, billing_city, billing_state, billing_zip, billing_country,
                shipping_first_name, shipping_last_name, shipping_email, shipping_phone, shipping_company,
                shipping_address1, shipping_address2, shipping_city, shipping_state, shipping_zip, shipping_country,
                notes';
        $orderValues = '
                :order_number, :customer_id, :session_id, :status, :payment_method, :payment_status,
                :subtotal, :shipping_cost, :discount_amount, :tax_amount, :total_amount, :currency,
                :billing_first_name, :billing_last_name, :billing_email, :billing_phone, :billing_company,
                :billing_address1, :billing_address2, :billing_city, :billing_state, :billing_zip, :billing_country,
                :shipping_first_name, :shipping_last_name, :shipping_email, :shipping_phone, :shipping_company,
                :shipping_address1, :shipping_address2, :shipping_city, :shipping_state, :shipping_zip, :shipping_country,
                :notes';

        if (!empty($shippingColumns['shipping_method'])) {
            $orderColumns .= ', shipping_method';
            $orderValues .= ', :shipping_method';
        }
        if (!empty($shippingColumns['shipping_method_id'])) {
            $orderColumns .= ', shipping_method_id';
            $orderValues .= ', :shipping_method_id';
        }

        if ($hasOrderUserId) {
            $orderColumns .= ', user_id';
            $orderValues .= ', :user_id';
        }
        if ($hasTransactionId) {
            $orderColumns .= ', transaction_id';
            $orderValues .= ', :transaction_id';
        }
        if ($hasStripeCustomerId) {
            $orderColumns .= ', stripe_customer_id';
            $orderValues .= ', :stripe_customer_id';
        }

        $stmt = $pdo->prepare("INSERT INTO orders ({$orderColumns}) VALUES ({$orderValues})");

        $sessionId = session_id();
        $notes = $data['notes'] ?? '';

        $orderParams = [
            ':order_number' => $orderNumber,
            ':customer_id' => $customerId,
            ':session_id' => $sessionId,
            ':status' => 'pending',
            ':payment_method' => $paymentMethod,
            ':payment_status' => $paymentStatus,
            ':subtotal' => $subtotal,
            ':shipping_cost' => $shippingCost,
            ':discount_amount' => $discountAmount,
            ':tax_amount' => $taxAmount,
            ':total_amount' => $totalAmount,
            ':currency' => strtoupper($orderCurrency),
            ':billing_first_name' => $billing['first_name'],
            ':billing_last_name' => $billing['last_name'],
            ':billing_email' => $customerEmail,
            ':billing_phone' => $billing['phone'] ?? '',
            ':billing_company' => $billing['company'] ?? '',
            ':billing_address1' => $billing['address1'],
            ':billing_address2' => $billing['address2'] ?? '',
            ':billing_city' => $billing['city'],
            ':billing_state' => $billing['state'],
            ':billing_zip' => $billing['zip'],
            ':billing_country' => $billing['country'],
            ':shipping_first_name' => $shipping['first_name'] ?? '',
            ':shipping_last_name' => $shipping['last_name'] ?? '',
            ':shipping_email' => $shipping['email'] ?? $customerEmail,
            ':shipping_phone' => $shipping['phone'] ?? ($billing['phone'] ?? ''),
            ':shipping_company' => $shipping['company'] ?? '',
            ':shipping_address1' => $shipping['address1'] ?? '',
            ':shipping_address2' => $shipping['address2'] ?? '',
            ':shipping_city' => $shipping['city'] ?? '',
            ':shipping_state' => $shipping['state'] ?? '',
            ':shipping_zip' => $shipping['zip'] ?? '',
            ':shipping_country' => $shipping['country'] ?? '',
            ':notes' => $notes,
        ];

        if (!empty($shippingColumns['shipping_method'])) {
            $orderParams[':shipping_method'] = $shippingMethodName;
        }
        if (!empty($shippingColumns['shipping_method_id'])) {
            $orderParams[':shipping_method_id'] = $shippingMethodId ?: null;
        }

        if ($hasOrderUserId) {
            $orderParams[':user_id'] = (int) $user['id'];
        }
        if ($hasTransactionId) {
            $orderParams[':transaction_id'] = $transactionId !== '' ? $transactionId : null;
        }
        if ($hasStripeCustomerId) {
            $orderParams[':stripe_customer_id'] = $stripeCustomerId !== '' ? $stripeCustomerId : null;
        }

        $stmt->execute($orderParams);

        $orderId = $pdo->lastInsertId();
        if ($paymentMethod === 'stripe' && $transactionId !== '') {
            payment_log_event('stripe', 'order_record_created', $transactionId, ['order_id' => $orderId, 'order_number' => $orderNumber], ['payment_status' => $paymentStatus], 'success', (int) $orderId);
        }

        $itemStmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, product_name, product_slug, product_image, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        foreach ($cartItems as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['slug'],
                $item['image'],
                $item['quantity'],
                $item['unit_price'],
                $item['line_total']
            ]);
        }

        $historyStmt = $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status) VALUES (?, NULL, ?)');
        $historyStmt->execute([$orderId, 'pending']);

        $appliedCouponCode = coupon_current_code();
        $appliedDiscount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
        if ($appliedCouponCode !== '' && $appliedDiscount > 0) {
            $couponStmt = $pdo->prepare('SELECT id FROM coupons WHERE code = :code LIMIT 1');
            $couponStmt->execute([':code' => $appliedCouponCode]);
            $couponId = (int) ($couponStmt->fetchColumn() ?: 0);
            if ($couponId > 0) {
                $pdo->prepare('UPDATE coupons SET usage_count = usage_count + 1 WHERE id = :id')->execute([':id' => $couponId]);

                $couponUsageSql = 'INSERT INTO coupon_usage (coupon_id, order_id, user_id, discount_amount) VALUES (:coupon_id, :order_id, :user_id, :discount_amount)';
                $pdo->prepare($couponUsageSql)->execute([
                    ':coupon_id' => $couponId,
                    ':order_id' => $orderId,
                    ':user_id' => (int) ($user['id'] ?? 0) ?: null,
                    ':discount_amount' => $appliedDiscount,
                ]);
            }
        }

        $pdo->commit();

        $_SESSION['cart'] = [];
        shipping_clear_session_selection();
        unset($_SESSION['checkout_discount_amount']);
        if ($pdo) {
            $pdo->prepare('DELETE FROM cart WHERE session_id = ?')->execute([$sessionId]);
        }

        try {
            create_notification(
                'New Order Placed',
                sprintf(
                    'Order %s placed by %s %s (%s) - Total $%0.2f',
                    $orderNumber,
                    (string) ($billing['first_name'] ?? ''),
                    (string) ($billing['last_name'] ?? ''),
                    (string) ($customerEmail ?: ($billing['email'] ?? '')),
                    (float) $totalAmount
                ),
                'success',
                'orders.php?order=' . urlencode($orderNumber),
                'View Order'
            );
        } catch (\Throwable $notificationError) {
        }

        return [
            'success' => true,
            'message' => 'Order placed successfully',
            'data' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'shipping_method' => $shippingMethodName,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'redirect_url' => 'order-success.php?order=' . urlencode($orderNumber)
            ]
        ];

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Failed to create order: ' . $e->getMessage()];
    }
}

function order_normalize(string $value): string
{
    return strtolower(trim($value));
}

function order_same_address(array $existing, array $incoming): bool
{
    $fields = ['first_name', 'last_name', 'company', 'address1', 'address2', 'city', 'state', 'zip', 'country'];
    foreach ($fields as $field) {
        $left = order_normalize((string) ($existing[$field] ?? ''));
        $right = order_normalize((string) ($incoming[$field] ?? ''));
        if ($left !== $right) {
            return false;
        }
    }
    return true;
}

function order_sync_user_address(int $userId, array $source, string $type): void
{
    if ($userId <= 0) {
        return;
    }

    $addressData = [
        'type' => $type,
        'first_name' => trim((string) ($source['first_name'] ?? '')),
        'last_name' => trim((string) ($source['last_name'] ?? '')),
        'company' => trim((string) ($source['company'] ?? '')),
        'address1' => trim((string) ($source['address1'] ?? '')),
        'address2' => trim((string) ($source['address2'] ?? '')),
        'city' => trim((string) ($source['city'] ?? '')),
        'state' => trim((string) ($source['state'] ?? '')),
        'zip' => trim((string) ($source['zip'] ?? '')),
        'country' => trim((string) ($source['country'] ?? 'US')),
    ];

    if ($addressData['first_name'] === '' || $addressData['last_name'] === '' || $addressData['address1'] === '' || $addressData['city'] === '' || $addressData['state'] === '' || $addressData['zip'] === '') {
        return;
    }

    $addresses = user_get_addresses($userId);
    foreach ($addresses as $address) {
        if (order_same_address($address, $addressData)) {
            return;
        }
    }

    if ($type === 'shipping') {
        user_add_address($userId, $addressData);
        return;
    }

    $defaultAddress = user_get_default_address($userId);
    if ($defaultAddress) {
        user_update_address((int) $defaultAddress['id'], $userId, $addressData);
    } else {
        user_add_address($userId, $addressData);
    }
}

function order_has_user_id_column(PDO $pdo): bool
{
    static $hasUserId = null;
    if ($hasUserId !== null) {
        return $hasUserId;
    }

    $sql = 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
    $count = (int) (db_fetch_value($pdo, $sql, [':table_name' => 'orders', ':column_name' => 'user_id']) ?? 0);
    $hasUserId = $count > 0;
    return $hasUserId;
}

function order_has_stripe_customer_id_column(PDO $pdo): bool
{
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }

    $sql = 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
    $count = (int) (db_fetch_value($pdo, $sql, [':table_name' => 'orders', ':column_name' => 'stripe_customer_id']) ?? 0);
    $hasColumn = $count > 0;
    return $hasColumn;
}

function order_has_transaction_id_column(PDO $pdo): bool
{
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }

    $sql = 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
    $count = (int) (db_fetch_value($pdo, $sql, [':table_name' => 'orders', ':column_name' => 'transaction_id']) ?? 0);
    $hasColumn = $count > 0;
    return $hasColumn;
}
?>













