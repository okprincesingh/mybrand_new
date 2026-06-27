<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/catalog.php';

function coupon_clear_session(): void
{
    unset($_SESSION['checkout_coupon_code'], $_SESSION['checkout_discount_amount']);
}

function coupon_current_code(): string
{
    return strtoupper(trim((string) ($_SESSION['checkout_coupon_code'] ?? '')));
}

function coupon_cart_subtotal(): float
{
    $subtotal = 0.0;
    $cart = (array) ($_SESSION['cart'] ?? []);
    foreach ($cart as $slug => $qty) {
        $product = catalog_find_product((string) $slug);
        if (!$product) {
            continue;
        }
        $quantity = max(1, (int) $qty);
        $subtotal += ((float) ($product['price'] ?? 0.0)) * $quantity;
    }
    return max(0.0, $subtotal);
}

function coupon_find_valid(string $code, float $subtotal): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE code = :code LIMIT 1');
    $stmt->execute([':code' => $code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$coupon) {
        return null;
    }

    if ((int) ($coupon['is_active'] ?? 0) !== 1) {
        return null;
    }

    $now = time();
    $startsAt = !empty($coupon['starts_at']) ? strtotime((string) $coupon['starts_at']) : null;
    $expiresAt = !empty($coupon['expires_at']) ? strtotime((string) $coupon['expires_at']) : null;

    if ($startsAt !== null && $startsAt > $now) {
        return null;
    }
    if ($expiresAt !== null && $expiresAt < $now) {
        return null;
    }

    $usageLimit = isset($coupon['usage_limit']) ? (int) $coupon['usage_limit'] : null;
    $usageCount = (int) ($coupon['usage_count'] ?? 0);
    if ($usageLimit !== null && $usageLimit > 0 && $usageCount >= $usageLimit) {
        return null;
    }

    $minOrder = isset($coupon['minimum_order_amount']) ? (float) $coupon['minimum_order_amount'] : null;
    if ($minOrder !== null && $minOrder > 0 && $subtotal < $minOrder) {
        return null;
    }

    return $coupon;
}

function coupon_calculate_discount(array $coupon, float $subtotal): float
{
    $discountType = (string) ($coupon['discount_type'] ?? 'percent');
    $discountValue = (float) ($coupon['discount_value'] ?? 0.0);

    if ($discountValue <= 0 || $subtotal <= 0) {
        return 0.0;
    }

    $discount = 0.0;
    if ($discountType === 'fixed') {
        $discount = $discountValue;
    } else {
        $discount = ($subtotal * $discountValue) / 100;
    }

    $maxDiscount = isset($coupon['maximum_discount_amount']) ? (float) $coupon['maximum_discount_amount'] : null;
    if ($maxDiscount !== null && $maxDiscount > 0) {
        $discount = min($discount, $maxDiscount);
    }

    return round(max(0.0, min($discount, $subtotal)), 2);
}

function coupon_apply_to_session(string $code, float $subtotal): array
{
    $coupon = coupon_find_valid($code, $subtotal);
    if (!$coupon) {
        coupon_clear_session();
        return [
            'success' => false,
            'message' => 'Invalid or expired coupon code.',
            'data' => coupon_totals_summary($subtotal),
        ];
    }

    $discount = coupon_calculate_discount($coupon, $subtotal);
    $_SESSION['checkout_coupon_code'] = strtoupper((string) $coupon['code']);
    $_SESSION['checkout_discount_amount'] = $discount;

    return [
        'success' => true,
        'message' => 'Coupon applied successfully.',
        'data' => coupon_totals_summary($subtotal),
    ];
}

function coupon_refresh_session(float $subtotal): array
{
    $code = coupon_current_code();
    if ($code === '') {
        $_SESSION['checkout_discount_amount'] = 0.0;
        return coupon_totals_summary($subtotal);
    }

    $coupon = coupon_find_valid($code, $subtotal);
    if (!$coupon) {
        coupon_clear_session();
        return coupon_totals_summary($subtotal);
    }

    $discount = coupon_calculate_discount($coupon, $subtotal);
    $_SESSION['checkout_discount_amount'] = $discount;

    return coupon_totals_summary($subtotal);
}

function coupon_totals_summary(float $subtotal): array
{
    $discount = max(0.0, (float) ($_SESSION['checkout_discount_amount'] ?? 0.0));
    $couponCode = coupon_current_code();
    return [
        'coupon_code' => $couponCode,
        'subtotal' => round(max(0.0, $subtotal), 2),
        'discount_amount' => round($discount, 2),
        'total' => round(max(0.0, $subtotal - $discount), 2),
    ];
}
