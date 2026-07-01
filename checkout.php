<?php
session_start();
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';
require_once __DIR__ . '/includes/shipping.php';
require_once __DIR__ . '/includes/stripe-config.php';
require_once __DIR__ . '/includes/coupons.php';

$user = user_current();
if (!$user) {
    $_SESSION['checkout_login_notice'] = 'Please login first to place your order.';
    header('Location: ' . url('login.php?redirect=checkout.php'));
    exit;
}

// Load cart items
$cartItems = [];
$subtotal = 0.0;
$totalItems = 0;

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    // Redirect to cart if empty
    header('Location: ' . url('cart.php'));
    exit;
}

foreach ($_SESSION['cart'] as $slug => $quantity) {
    $product = catalog_find_product($slug);
    if (!$product) {
        continue;
    }
    $qty = max(1, (int) $quantity);
    $lineTotal = $product['price'] * $qty;
    $subtotal += $lineTotal;
    $totalItems += $qty;
    $cartItems[] = [
        'slug' => $slug,
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => $product['image'] ?? 'assets/imgs/product/skin-care.webp',
        'quantity' => $qty,
        'line_total' => $lineTotal
    ];
}

if (empty($cartItems)) {
    header('Location: ' . url('cart.php'));
    exit;
}

$savedAddress = user_get_default_address((int) $user['id']);
$billingPrefill = [
    'first_name' => (string) ($savedAddress['first_name'] ?? $user['first_name'] ?? ''),
    'last_name' => (string) ($savedAddress['last_name'] ?? $user['last_name'] ?? ''),
    'company' => (string) ($savedAddress['company'] ?? ''),
    'country' => (string) ($savedAddress['country'] ?? 'US'),
    'address1' => (string) ($savedAddress['address1'] ?? ''),
    'address2' => (string) ($savedAddress['address2'] ?? ''),
    'city' => (string) ($savedAddress['city'] ?? ''),
    'state' => (string) ($savedAddress['state'] ?? ''),
    'zip' => (string) ($savedAddress['zip'] ?? ''),
    'phone' => (string) ($user['phone'] ?? ''),
    'email' => (string) ($user['email'] ?? ''),
];

$cartWeight = shipping_calculate_cart_weight((array) ($_SESSION['cart'] ?? []));
$shippingMethods = getAvailableShippingMethods($subtotal, $cartWeight, (string) ($billingPrefill['state'] ?? ''), (string) ($billingPrefill['zip'] ?? ''), (string) ($billingPrefill['country'] ?? ''));
$selectedShippingSession = shipping_get_session_selection();
$selectedShippingMethod = null;
if (is_array($selectedShippingSession) && !empty($selectedShippingSession['id'])) {
    foreach ($shippingMethods as $m) {
        if ((int) ($m['id'] ?? 0) === (int) $selectedShippingSession['id']) {
            $selectedShippingMethod = $m;
            break;
        }
    }
}
if (!$selectedShippingMethod && !empty($shippingMethods)) {
    $selectedShippingMethod = $shippingMethods[0];
    shipping_save_selection_to_session($selectedShippingMethod);
}
$shippingCost = (float) ($selectedShippingMethod['cost'] ?? 0.0);
$hasShippingMethod = is_array($selectedShippingMethod) && !empty($selectedShippingMethod['id']);
$couponSummary = coupon_refresh_session($subtotal);
$couponCode = (string) ($couponSummary['coupon_code'] ?? '');
$discountAmount = (float) ($couponSummary['discount_amount'] ?? 0.0);
$taxAmount = 0.0;
$total = $subtotal + $shippingCost + $taxAmount - $discountAmount;
$activePaymentMethods = payment_get_active_methods();
if (!is_array($activePaymentMethods) || empty($activePaymentMethods)) {
    $activePaymentMethods = [
        ['method_type' => 'cod', 'method_name' => 'Cash on Delivery', 'status' => 'active'],
    ];
}
$stripeConfig = stripe_get_config();
$stripePublishableKey = (string) ($stripeConfig['publishable_key'] ?? '');
$availableCurrencies = payment_get_supported_currencies();
$defaultCurrency = 'usd';

$meta = [
    'title' => 'Mybrandplease | Checkout',
    'description' => 'Complete your order - Mybrandplease',
    'canonical' => 'checkout.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
            <div class="breadcumb-wrapper__title">Check Out</div>
            <ul class="breadcumb-wrapper__items">
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-house"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <a href="<?php echo url('index.php'); ?>" class="breadcumb-wrapper__items-list-title">Home</a>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <a href="<?php echo url('cart.php'); ?>" class="breadcumb-wrapper__items-list-title">Cart</a>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                    <span class="breadcumb-wrapper__items-list-title2">Checkout</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="checkout-page section-spacing-120">
    <div class="container container-1352">
        <form id="checkout-form" class="checkout-form" method="post">
            <input type="hidden" name="action" value="create-order">
            <div class="row">
                <div class="col-lg-7 col-md-12">
                    <div class="checkout-page__billing">
                        <h2 class="checkout-page__billing-title">Billing Details</h2>

                        <?php if (!empty($_SESSION['checkout_login_notice'])): ?>
                        <div class="checkout-page__banner">
                            <p class="checkout-page__banner-text"><?php echo htmlspecialchars((string) $_SESSION['checkout_login_notice']); ?></p>
                        </div>
                        <?php unset($_SESSION['checkout_login_notice']); ?>
                        <?php endif; ?>

                        <div class="checkout-page__banner">
                            <p class="checkout-page__banner-text">
                                Have a coupon? <a href="#coupon" class="checkout-page__banner-link" id="coupon-toggle">Click here to enter your code</a>
                                <i class="fa-regular fa-chevron-down checkout-page__banner-icon"></i>
                            </p>
                        </div>
                        <div id="coupon-box" style="display:none;margin-bottom:16px;">
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="checkout-coupon-input" class="checkout-page__billing-form-input" placeholder="Enter coupon code" value="<?php echo htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'); ?>" style="margin-bottom:0;">
                                <button type="button" id="checkout-coupon-apply" class="checkout-page__place-order-btn" style="width:auto;padding:10px 16px;">Apply</button>
                                <button type="button" id="checkout-coupon-remove" class="checkout-page__place-order-btn" style="width:auto;padding:10px 16px;<?php echo $couponCode !== '' ? '' : 'display:none;'; ?>">Remove</button>
                            </div>
                            <p id="checkout-coupon-message" style="margin-top:8px;font-size:13px;color:#666;"></p>
                        </div>

                        <div class="checkout-page__billing-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="checkout-page__billing-form-group">
                                        <label class="checkout-page__billing-form-label">
                                            First Name <span class="checkout-page__billing-form-required">*</span>
                                        </label>
                                        <input type="text" name="billing[first_name]" class="checkout-page__billing-form-input" placeholder="John" value="<?php echo htmlspecialchars($billingPrefill['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="checkout-page__billing-form-group">
                                        <label class="checkout-page__billing-form-label">
                                            Last Name <span class="checkout-page__billing-form-required">*</span>
                                        </label>
                                        <input type="text" name="billing[last_name]" class="checkout-page__billing-form-input" placeholder="Doe" value="<?php echo htmlspecialchars($billingPrefill['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">Company Name (Optional)</label>
                                <input type="text" name="billing[company]" class="checkout-page__billing-form-input" placeholder="Your Company" value="<?php echo htmlspecialchars($billingPrefill['company'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Country / Region <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <select name="billing[country]" class="checkout-page__billing-form-select" required>
                                    <option value="US" <?php echo $billingPrefill['country'] === 'US' ? 'selected' : ''; ?>>United States</option>
                                    <option value="CA" <?php echo $billingPrefill['country'] === 'CA' ? 'selected' : ''; ?>>Canada</option>
                                    <option value="UK" <?php echo $billingPrefill['country'] === 'UK' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="AU" <?php echo $billingPrefill['country'] === 'AU' ? 'selected' : ''; ?>>Australia</option>
                                    <option value="IN" <?php echo $billingPrefill['country'] === 'IN' ? 'selected' : ''; ?>>India</option>
                                    <option value="DE" <?php echo $billingPrefill['country'] === 'DE' ? 'selected' : ''; ?>>Germany</option>
                                    <option value="FR" <?php echo $billingPrefill['country'] === 'FR' ? 'selected' : ''; ?>>France</option>
                                    <option value="IT" <?php echo $billingPrefill['country'] === 'IT' ? 'selected' : ''; ?>>Italy</option>
                                    <option value="ES" <?php echo $billingPrefill['country'] === 'ES' ? 'selected' : ''; ?>>Spain</option>
                                    <option value="NL" <?php echo $billingPrefill['country'] === 'NL' ? 'selected' : ''; ?>>Netherlands</option>
                                    <option value="BD" <?php echo $billingPrefill['country'] === 'BD' ? 'selected' : ''; ?>>Bangladesh</option>
                                    <option value="AE" <?php echo $billingPrefill['country'] === 'AE' ? 'selected' : ''; ?>>United Arab Emirates</option>
                                    <option value="SA" <?php echo $billingPrefill['country'] === 'SA' ? 'selected' : ''; ?>>Saudi Arabia</option>
                                    <option value="QA" <?php echo $billingPrefill['country'] === 'QA' ? 'selected' : ''; ?>>Qatar</option>
                                    <option value="ZA" <?php echo $billingPrefill['country'] === 'ZA' ? 'selected' : ''; ?>>South Africa</option>
                                    <option value="NG" <?php echo $billingPrefill['country'] === 'NG' ? 'selected' : ''; ?>>Nigeria</option>
                                    <option value="BR" <?php echo $billingPrefill['country'] === 'BR' ? 'selected' : ''; ?>>Brazil</option>
                                    <option value="AR" <?php echo $billingPrefill['country'] === 'AR' ? 'selected' : ''; ?>>Argentina</option>
                                    <option value="CL" <?php echo $billingPrefill['country'] === 'CL' ? 'selected' : ''; ?>>Chile</option>
                                </select>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Street Address <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="text" name="billing[address1]" class="checkout-page__billing-form-input" placeholder="House number and street name" value="<?php echo htmlspecialchars($billingPrefill['address1'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                <input type="text" name="billing[address2]" class="checkout-page__billing-form-input checkout-page__billing-form-input--optional" placeholder="Apartment, suite, unit, etc. (optional)" value="<?php echo htmlspecialchars($billingPrefill['address2'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Town / City <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="text" name="billing[city]" class="checkout-page__billing-form-input" placeholder="Your city" value="<?php echo htmlspecialchars($billingPrefill['city'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    State <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="text" name="billing[state]" class="checkout-page__billing-form-input" placeholder="Your state" value="<?php echo htmlspecialchars($billingPrefill['state'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Zip Code <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="text" name="billing[zip]" class="checkout-page__billing-form-input" placeholder="Your zip code" value="<?php echo htmlspecialchars($billingPrefill['zip'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Phone <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="tel" name="billing[phone]" class="checkout-page__billing-form-input" placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($billingPrefill['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">
                                    Email Address <span class="checkout-page__billing-form-required">*</span>
                                </label>
                                <input type="email" name="billing[email]" class="checkout-page__billing-form-input" placeholder="your@email.com" value="<?php echo htmlspecialchars($billingPrefill['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-checkbox">
                                    <input type="checkbox" name="use_shipping_address" class="checkout-page__billing-form-checkbox-input" id="ship-to-different-check">
                                    <span class="checkout-page__billing-form-checkbox-label">Ship to a different address?</span>
                                </label>
                            </div>

                            <div class="checkout-page__billing-form-group" id="shipping-address-section" style="display:none;">
                                <h4 class="mb-3">Shipping Address</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="checkout-page__billing-form-group">
                                            <label class="checkout-page__billing-form-label">First Name</label>
                                            <input type="text" name="shipping[first_name]" class="checkout-page__billing-form-input" placeholder="First Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkout-page__billing-form-group">
                                            <label class="checkout-page__billing-form-label">Last Name</label>
                                            <input type="text" name="shipping[last_name]" class="checkout-page__billing-form-input" placeholder="Last Name">
                                        </div>
                                    </div>
                                </div>
                                <div class="checkout-page__billing-form-group">
                                    <label class="checkout-page__billing-form-label">Street Address</label>
                                    <input type="text" name="shipping[address1]" class="checkout-page__billing-form-input" placeholder="Street Address">
                                </div>
                                <div class="checkout-page__billing-form-group">
                                    <label class="checkout-page__billing-form-label">City</label>
                                    <input type="text" name="shipping[city]" class="checkout-page__billing-form-input" placeholder="City">
                                </div>
                                <div class="checkout-page__billing-form-group">
                                    <label class="checkout-page__billing-form-label">State</label>
                                    <input type="text" name="shipping[state]" class="checkout-page__billing-form-input" placeholder="State">
                                </div>
                                <div class="checkout-page__billing-form-group">
                                    <label class="checkout-page__billing-form-label">Zip Code</label>
                                    <input type="text" name="shipping[zip]" class="checkout-page__billing-form-input" placeholder="Zip Code">
                                </div>
                                <div class="checkout-page__billing-form-group">
                                    <label class="checkout-page__billing-form-label">Country</label>
                                    <select name="shipping[country]" class="checkout-page__billing-form-select">
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="UK">United Kingdom</option>
                                        <option value="AU">Australia</option>
                                        <option value="IN">India</option>
                                        <option value="DE">Germany</option>
                                        <option value="FR">France</option>
                                        <option value="IT">Italy</option>
                                        <option value="ES">Spain</option>
                                        <option value="NL">Netherlands</option>
                                        <option value="AE">United Arab Emirates</option>
                                        <option value="SA">Saudi Arabia</option>
                                        <option value="QA">Qatar</option>
                                        <option value="ZA">South Africa</option>
                                        <option value="NG">Nigeria</option>
                                        <option value="BR">Brazil</option>
                                        <option value="AR">Argentina</option>
                                        <option value="CL">Chile</option>
                                    </select>
                                </div>
                            </div>

                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">Order Notes (Optional)</label>
                                <textarea name="notes" class="checkout-page__billing-form-textarea" placeholder="Notes about your order, e.g. special notes for delivery."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 col-md-12">
                    <div class="checkout-page__order">
                        <h2 class="checkout-page__order-title">Your Order</h2>

                        <div class="checkout-page__order-summary">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-page__order-summary-item">
                                <div class="checkout-page__order-summary-item-image">
                                    <img src="<?php echo htmlspecialchars(url($item['image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="checkout-page__order-summary-item-content">
                                    <div class="checkout-page__order-summary-item-title"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <p class="checkout-page__order-summary-item-quantity">QTY: <?php echo (int) $item['quantity']; ?></p>
                                </div>
                                <div class="checkout-page__order-summary-item-price">$<?php echo number_format((float) $item['line_total'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>

                            <div class="checkout-page__order-summary-totals">
                                <div class="checkout-page__order-summary-totals-row">
                                    <span class="checkout-page__order-summary-totals-label">Subtotal</span>
                                    <span class="checkout-page__order-summary-totals-value" id="subtotal">$<?php echo number_format((float) $subtotal, 2); ?></span>
                                </div>
                                <div class="checkout-page__order-summary-totals-row" style="align-items:flex-start;">
                                    <span class="checkout-page__order-summary-totals-label">Shipping</span>
                                    <span class="checkout-page__order-summary-totals-value" id="shipping-cost">
                                        <?php echo $hasShippingMethod ? ($shippingCost > 0 ? ('$' . number_format((float)$shippingCost, 2)) : 'Free') : 'Shipping quote after order'; ?>
                                    </span>
                                    <input type="hidden" name="shipping_method_id" id="shipping-method-id" value="<?php echo (int) ($selectedShippingMethod['id'] ?? 0); ?>">
                                </div>
                                <div class="checkout-page__order-summary-totals-row checkout-page__order-summary-totals-row--discount">
                                    <span class="checkout-page__order-summary-totals-label">Discount</span>
                                    <span class="checkout-page__order-summary-totals-value" id="discount">-$0.00</span>
                                </div>
                                <div class="checkout-page__order-summary-totals-row checkout-page__order-summary-totals-row--total">
                                    <span class="checkout-page__order-summary-totals-label">Total</span>
                                    <span class="checkout-page__order-summary-totals-value checkout-page__order-summary-totals-value--highlight" id="total">$<?php echo number_format((float) $total, 2); ?></span>
                                </div>
                                <div id="checkout-applied-coupon-badge" style="<?php echo $couponCode !== '' ? '' : 'display:none;'; ?>margin-top:10px;text-align:right;">
                                    <span style="display:inline-block;background:#eef7ff;color:#0b5ed7;border:1px solid #b6dcff;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:600;">
                                        Applied coupon: <span id="checkout-applied-coupon-code"><?php echo htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-page__payment">
                            <h4 class="mb-3">Payment Method</h4>
                            <div class="checkout-page__billing-form-group">
                                <label class="checkout-page__billing-form-label">Currency</label>
                                <select name="currency" id="checkout-currency" class="checkout-page__billing-form-select">
                                    <?php foreach ($availableCurrencies as $cur): ?>
                                    <option value="<?php echo htmlspecialchars($cur, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $cur === $defaultCurrency ? 'selected' : ''; ?>>
                                        <?php echo strtoupper(htmlspecialchars($cur, ENT_QUOTES, 'UTF-8')); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php foreach ($activePaymentMethods as $index => $paymentMethod): ?>
                            <?php
                                $methodType = strtolower((string) ($paymentMethod['method_type'] ?? ''));
                                $methodName = (string) ($paymentMethod['method_name'] ?? ucfirst($methodType));
                                if ($methodType === '') {
                                    continue;
                                }
                                $isChecked = $index === 0;
                            ?>
                            <div class="checkout-page__payment-option">
                                <label class="checkout-page__payment-option-label">
                                    <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($methodType, ENT_QUOTES, 'UTF-8'); ?>" class="checkout-page__payment-option-input" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <span class="checkout-page__payment-option-text">
                                        <strong><?php echo htmlspecialchars($methodName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </span>
                                </label>
                            </div>
                            <?php endforeach; ?>

                            <div id="stripe-card-section" class="checkout-page__stripe-card" style="display:none;">
                                <label class="checkout-page__billing-form-label">Card Details</label>
                                <div class="checkout-page__stripe-fields">
                                    <div class="checkout-page__stripe-field checkout-page__stripe-field--full">
                                        <span class="checkout-page__stripe-field-label">Card Number</span>
                                        <div id="stripe-card-number" class="checkout-page__stripe-element"></div>
                                    </div>
                                    <div class="checkout-page__stripe-field">
                                        <span class="checkout-page__stripe-field-label">Expiry Date</span>
                                        <div id="stripe-card-expiry" class="checkout-page__stripe-element"></div>
                                    </div>
                                    <div class="checkout-page__stripe-field">
                                        <span class="checkout-page__stripe-field-label">CVC</span>
                                        <div id="stripe-card-cvc" class="checkout-page__stripe-element"></div>
                                    </div>
                                </div>
                                <div id="stripe-card-errors" class="checkout-page__stripe-error" role="alert"></div>
                            </div>
                        </div>

                        <button type="submit" class="checkout-page__place-order-btn" id="place-order-btn">
                            Place Order <i class="fa-regular fa-arrow-right"></i>
                        </button>

                        <!-- <div class="trust-badges mt-3">
                            <div class="trust-item">
                                <i class="fa-regular fa-shield-check"></i>
                                <span>Secure Checkout</span>
                            </div>
                            <div class="trust-item">
                                <i class="fa-regular fa-truck"></i>
                                <span>Free Shipping</span>
                            </div>
                            <div class="trust-item">
                                <i class="fa-regular fa-rotate-left"></i>
                                <span>Easy Returns</span>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<style>
.checkout-page__order-summary-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}
.checkout-page__order-summary-item:last-child {
    border-bottom: none;
}
.checkout-page__order-summary-item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.checkout-page__order-summary-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.checkout-page__order-summary-item-content {
    flex: 1;
}
.checkout-page__order-summary-item-title {
    font-size: 14px;
    font-weight: 600;
    color: #0C0C0C;
    margin-bottom: 4px;
}
.checkout-page__order-summary-item-quantity {
    font-size: 12px;
    color: #666;
    margin: 0;
}
.checkout-page__order-summary-item-price {
    font-size: 14px;
    font-weight: 600;
    color: #EE2D7A;
}
.checkout-page__shipping-options {
    display: flex;
    flex-direction: column;
    gap: 6px;
    text-align: right;
}
.checkout-page__shipping-option {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    font-size: 13px;
}
.checkout-page__shipping-option input[type="radio"] {
    accent-color: #EE2D7A;
}
.checkout-page__place-order-btn {
    width: 100%;
    padding: 16px 24px;
    background: #EE2D7A;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.checkout-page__place-order-btn:hover {
    background: #d4256a;
    transform: translateY(-2px);
}
.checkout-page__place-order-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}
.checkout-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 8px;
    color: #fff;
    font-weight: 600;
    z-index: 10000;
    transform: translateX(calc(100% + 40px));
    opacity: 0;
    transition: all 0.4s ease;
}
.checkout-toast--show {
    transform: translateX(0);
    opacity: 1;
}
.checkout-toast--success {
    background: #10b981;
}
.checkout-toast--error {
    background: #ef4444;
}
.checkout-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
    display: none;
}
.checkout-spinner {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 40px;
    border-radius: 16px;
    text-align: center;
    z-index: 9999;
    display: none;
}
.checkout-spinner__icon {
    width: 48px;
    height: 48px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #EE2D7A;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.checkout-page__stripe-card {
    margin-top: 12px;
    padding: 18px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
}
.checkout-page__stripe-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.checkout-page__stripe-field {
    min-width: 0;
}
.checkout-page__stripe-field--full {
    grid-column: 1 / -1;
}
.checkout-page__stripe-field-label {
    display: block;
    margin-bottom: 7px;
    color: #0C0C0C;
    font-size: 13px;
    font-weight: 700;
}
.checkout-page__stripe-element {
    min-height: 48px;
    padding: 13px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #fff;
    overflow: hidden;
}
.checkout-page__stripe-element iframe {
    display: block !important;
    min-height: 22px !important;
    background: #fff !important;
}
.checkout-page__stripe-element.StripeElement--focus {
    border-color: #EE2D7A;
    box-shadow: 0 0 0 3px rgba(238, 45, 122, 0.12);
}
.checkout-page__stripe-element.StripeElement--invalid {
    border-color: #dc2626;
}
.checkout-page__stripe-error {
    margin-top: 8px;
    color: #dc2626;
    font-size: 13px;
}
@media (max-width: 575px) {
    .checkout-page__stripe-card {
        padding: 14px;
    }
    .checkout-page__stripe-fields {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

</style>

<script src="https://js.stripe.com/v3/"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');
    const shipToDifferentCheck = document.getElementById('ship-to-different-check');
    const shippingAddressSection = document.getElementById('shipping-address-section');
    const couponBox = document.getElementById('coupon-box');
    const couponInput = document.getElementById('checkout-coupon-input');
    const couponApplyBtn = document.getElementById('checkout-coupon-apply');
    const couponRemoveBtn = document.getElementById('checkout-coupon-remove');
    const couponMessage = document.getElementById('checkout-coupon-message');
    const couponBadge = document.getElementById('checkout-applied-coupon-badge');
    const couponBadgeCode = document.getElementById('checkout-applied-coupon-code');
    const stripeCardSection = document.getElementById('stripe-card-section');
    const stripeCardErrors = document.getElementById('stripe-card-errors');
    const stripeCardNumberContainer = document.getElementById('stripe-card-number');
    const stripeCardExpiryContainer = document.getElementById('stripe-card-expiry');
    const stripeCardCvcContainer = document.getElementById('stripe-card-cvc');

    const stripePublishableKey = <?php echo json_encode($stripePublishableKey, JSON_UNESCAPED_SLASHES); ?>;
    let stripe = null;
    let elements = null;
    let cardNumberElement = null;
    let cardExpiryElement = null;
    let cardCvcElement = null;

    if (stripePublishableKey && stripeCardNumberContainer && stripeCardExpiryContainer && stripeCardCvcContainer) {
        stripe = Stripe(stripePublishableKey);
        elements = stripe.elements();
        const stripeElementStyle = {
            base: {
                color: '#111827',
                backgroundColor: '#ffffff',
                fontFamily: 'Arial, sans-serif',
                fontSize: '16px',
                lineHeight: '20px',
                iconColor: '#111827',
                '::placeholder': { color: '#9ca3af' }
            },
            invalid: {
                color: '#dc2626',
                iconColor: '#dc2626'
            },
            complete: {
                color: '#111827',
                iconColor: '#10b981'
            }
        };
        cardNumberElement = elements.create('cardNumber', {
            showIcon: true,
            placeholder: '1234 1234 1234 1234',
            style: stripeElementStyle
        });
        cardExpiryElement = elements.create('cardExpiry', {
            placeholder: 'MM / YY',
            style: stripeElementStyle
        });
        cardCvcElement = elements.create('cardCvc', {
            placeholder: 'CVC',
            style: stripeElementStyle
        });
        cardNumberElement.mount('#stripe-card-number');
        cardExpiryElement.mount('#stripe-card-expiry');
        cardCvcElement.mount('#stripe-card-cvc');
        [cardNumberElement, cardExpiryElement, cardCvcElement].forEach(function(element) {
            element.on('change', function(event) {
                if (!stripeCardErrors) return;
                stripeCardErrors.textContent = event.error ? event.error.message : '';
            });
        });
    }

    const overlay = document.createElement('div');
    overlay.className = 'checkout-overlay';
    document.body.appendChild(overlay);

    const spinner = document.createElement('div');
    spinner.className = 'checkout-spinner';
    spinner.innerHTML = '<div class="checkout-spinner__icon"></div><p>Processing your order...</p>';
    document.body.appendChild(spinner);

    shipToDifferentCheck.addEventListener('change', function() {
        shippingAddressSection.style.display = this.checked ? 'block' : 'none';
        refreshShippingOptions();
    });

    document.getElementById('coupon-toggle')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (couponBox) {
            couponBox.style.display = couponBox.style.display === 'none' ? 'block' : 'none';
        }
    });

    function getSelectedPaymentMethod() {
        const selected = form.querySelector('input[name="payment_method"]:checked');
        return selected ? String(selected.value || '').toLowerCase() : '';
    }

    function updatePaymentUI() {
        const selectedMethod = getSelectedPaymentMethod();
        const useStripe = selectedMethod === 'stripe';
        if (stripeCardSection) {
            stripeCardSection.style.display = useStripe ? 'block' : 'none';
        }
    }

    form.querySelectorAll('input[name="payment_method"]').forEach(function(input) {
        input.addEventListener('change', updatePaymentUI);
    });
    updatePaymentUI();

    function formatMoney(amount) {
        const value = Number(amount || 0);
        return '$' + value.toFixed(2);
    }

    function applySummary(summary) {
        if (!summary) return;
        const subtotalEl = document.getElementById('subtotal');
        const shippingCostEl = document.getElementById('shipping-cost');
        const discountEl = document.getElementById('discount');
        const totalEl = document.getElementById('total');
        if (subtotalEl) subtotalEl.textContent = formatMoney(summary.subtotal || 0);
        if (shippingCostEl && Object.prototype.hasOwnProperty.call(summary, 'shipping_cost')) {
            shippingCostEl.textContent = summary.has_shipping_method === false
                ? 'Unavailable'
                : (Number(summary.shipping_cost || 0) > 0 ? formatMoney(summary.shipping_cost) : 'Free');
        }
        if (discountEl) discountEl.textContent = '-' + formatMoney(summary.discount_amount || 0);
        if (totalEl) totalEl.textContent = formatMoney(summary.total || 0);
    }

    function updateCouponUI(summary, message) {
        if (!summary) return;
        if (couponInput) couponInput.value = summary.coupon_code || '';
        if (couponRemoveBtn) couponRemoveBtn.style.display = summary.coupon_code ? '' : 'none';
        if (couponMessage && typeof message === 'string') couponMessage.textContent = message;
        if (couponBadge && couponBadgeCode) {
            couponBadge.style.display = summary.coupon_code ? '' : 'none';
            couponBadgeCode.textContent = summary.coupon_code || '';
        }
        applySummary(summary);

        const shippingText = (document.getElementById('shipping-cost')?.textContent || '').trim().toLowerCase();
        const shippingNumber = (shippingText === 'free' || shippingText === 'unavailable') ? 0 : Number(String(shippingText).replace(/[^0-9.]/g, '')) || 0;
        const adjustedTotal = Math.max(0, Number(summary.subtotal || 0) + shippingNumber - Number(summary.discount_amount || 0));
        const totalEl = document.getElementById('total');
        if (totalEl) totalEl.textContent = formatMoney(adjustedTotal);
    }

    function applyCoupon(action) {
        const code = String(couponInput?.value || '').trim();
        if (action === 'apply' && !code) {
            showToast('Enter coupon code first', 'error');
            return;
        }

        fetch('<?php echo url("api/coupon.php"); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: action, code: code })
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.data) {
                updateCouponUI(data.data, data.message || '');
            }
            showToast((data && data.message) || 'Coupon updated', (data && data.success) ? 'success' : 'error');
        })
        .catch(() => showToast('Coupon request failed', 'error'));
    }

    couponApplyBtn?.addEventListener('click', function() { applyCoupon('apply'); });
    couponRemoveBtn?.addEventListener('click', function() { applyCoupon('remove'); });

    function renderShippingOptions(methods, selected) {
        const methodIdInput = document.getElementById('shipping-method-id');
        const shippingCostEl = document.getElementById('shipping-cost');
        if (!Array.isArray(methods) || methods.length === 0) {
            if (methodIdInput) methodIdInput.value = '0';
            if (shippingCostEl) shippingCostEl.textContent = 'Shipping quote after order';
            return;
        }
        const selectedId = Number(selected && selected.id ? selected.id : methods[0].id);
        const selectedMethod = methods.find(function(method) {
            return Number(method.id || 0) === selectedId;
        }) || methods[0];
        const cost = Number(selectedMethod.cost || 0);
        if (methodIdInput) methodIdInput.value = String(Number(selectedMethod.id || 0));
        if (shippingCostEl) shippingCostEl.textContent = cost > 0 ? formatMoney(cost) : 'Free';
    }

    function refreshShippingOptions() {
        const useShippingAddress = document.getElementById('ship-to-different-check')?.checked;
        const prefix = useShippingAddress ? 'shipping' : 'billing';
        fetch('<?php echo url("api/shipping.php"); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'list',
                state: (form.querySelector('[name="' + prefix + '[state]"]')?.value || '').trim(),
                postal_code: (form.querySelector('[name="' + prefix + '[zip]"]')?.value || '').trim(),
                country: (form.querySelector('[name="' + prefix + '[country]"]')?.value || '').trim()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                renderShippingOptions(data.methods || [], data.selected || null);
                applySummary(data.summary || null);
            }
        })
        .catch(function() {
            showToast('Shipping options could not be refreshed.', 'error');
        });
    }

    ['billing[country]', 'billing[state]', 'billing[zip]', 'shipping[country]', 'shipping[state]', 'shipping[zip]'].forEach(function(name) {
        form.querySelector('[name="' + name + '"]')?.addEventListener('change', refreshShippingOptions);
    });

    function buildOrderData(formData) {
        const useShippingAddress = document.getElementById('ship-to-different-check').checked;
        const shippingData = {};
        if (useShippingAddress) {
            shippingData.first_name = formData.get('shipping[first_name]') || '';
            shippingData.last_name = formData.get('shipping[last_name]') || '';
            shippingData.address1 = formData.get('shipping[address1]') || '';
            shippingData.city = formData.get('shipping[city]') || '';
            shippingData.state = formData.get('shipping[state]') || '';
            shippingData.zip = formData.get('shipping[zip]') || '';
            shippingData.country = formData.get('shipping[country]') || '';
        }

        return {
            action: 'create',
            billing: {
                first_name: formData.get('billing[first_name]'),
                last_name: formData.get('billing[last_name]'),
                company: formData.get('billing[company]') || '',
                address1: formData.get('billing[address1]'),
                address2: formData.get('billing[address2]') || '',
                city: formData.get('billing[city]'),
                state: formData.get('billing[state]'),
                zip: formData.get('billing[zip]'),
                country: formData.get('billing[country]'),
                phone: formData.get('billing[phone]'),
                email: formData.get('billing[email]')
            },
            shipping: shippingData,
            use_shipping_address: useShippingAddress,
            shipping_method_id: parseInt(formData.get('shipping_method_id') || '0', 10),
            payment_method: String(formData.get('payment_method') || 'cod').toLowerCase(),
            notes: formData.get('notes') || '',
            currency: String(formData.get('currency') || 'usd').toLowerCase(),
            coupon_code: String(couponInput?.value || '').trim()
        };
    }

    function setLoading(isLoading) {
        placeOrderBtn.disabled = isLoading;
        placeOrderBtn.innerHTML = isLoading
            ? '<i class="fa-solid fa-spinner fa-spin"></i> Processing...'
            : 'Place Order <i class="fa-regular fa-arrow-right"></i>';
        overlay.style.display = isLoading ? 'block' : 'none';
        spinner.style.display = isLoading ? 'block' : 'none';
    }

    async function readJsonResponse(response, fallbackMessage) {
        const text = await response.text();
        if (!text.trim()) {
            throw new Error(fallbackMessage || 'Server returned an empty response.');
        }

        try {
            return JSON.parse(text);
        } catch (error) {
            const cleanText = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            throw new Error(cleanText || fallbackMessage || 'Server returned an invalid response.');
        }
    }

    async function createStripePaymentIntent(orderData) {
        const response = await fetch('<?php echo url("ajax/create-payment.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                billing: orderData.billing,
                shipping: orderData.shipping,
                use_shipping_address: orderData.use_shipping_address,
                shipping_method_id: orderData.shipping_method_id,
                currency: orderData.currency
            })
        });
        return readJsonResponse(response, 'Payment request failed. Please check Stripe settings.');
    }

    async function submitOrder(orderData) {
        const response = await fetch('<?php echo url("api/order.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(orderData)
        });
        return readJsonResponse(response, 'Order request failed. Please try again.');
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        requiredFields.forEach(function(field) {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }
        });

        if (!isValid) {
            showToast('Please fill in all required fields', 'error');
            return;
        }

        const formData = new FormData(form);
        const orderData = buildOrderData(formData);

        setLoading(true);

        try {
            if (orderData.payment_method === 'stripe') {
                const intentRes = await createStripePaymentIntent(orderData);
                if (intentRes.success && intentRes.data && intentRes.data.skip_payment) {
                    orderData.payment_method = 'stripe';
                    orderData.transaction_id = intentRes.data.payment_intent_id || 'TEST_ZERO_AMOUNT';
                    orderData.payment_intent_id = intentRes.data.payment_intent_id || 'TEST_ZERO_AMOUNT';
                } else {
                    if (!stripe || !cardNumberElement) {
                        throw new Error('Stripe is not available. Please contact support.');
                    }

                if (!intentRes.success || !intentRes.data || !intentRes.data.client_secret) {
                    throw new Error(intentRes.message || 'Unable to initialize Stripe payment.');
                }

                const billingName = [orderData.billing.first_name, orderData.billing.last_name].filter(Boolean).join(' ').trim();
                const stripeResult = await stripe.confirmCardPayment(intentRes.data.client_secret, {
                    payment_method: {
                        card: cardNumberElement,
                        billing_details: {
                            name: billingName,
                            email: orderData.billing.email,
                            phone: orderData.billing.phone,
                            address: {
                                line1: orderData.billing.address1,
                                line2: orderData.billing.address2 || undefined,
                                city: orderData.billing.city,
                                state: orderData.billing.state,
                                postal_code: orderData.billing.zip,
                                country: orderData.billing.country || 'US'
                            }
                        }
                    }
                });

                if (stripeResult.error) {
                    throw new Error(stripeResult.error.message || 'Card payment failed.');
                }

                if (!stripeResult.paymentIntent || stripeResult.paymentIntent.status !== 'succeeded') {
                    throw new Error('Payment was not completed.');
                }

                orderData.transaction_id = stripeResult.paymentIntent.id;
                orderData.payment_intent_id = stripeResult.paymentIntent.id;
                }
            }

            const data = await submitOrder(orderData);
            if (data.success) {
                showToast('Order placed successfully!', 'success');
                setTimeout(function() {
                    window.location.href = data.data.redirect_url;
                }, 900);
                return;
            }

            throw new Error(data.message || 'Failed to place order');
        } catch (err) {
            showToast(err.message || 'An error occurred. Please try again.', 'error');
        } finally {
            setLoading(false);
        }
    });

    function showToast(message, type) {
        const existingToast = document.querySelector('.checkout-toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = 'checkout-toast checkout-toast--' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function() {
            toast.classList.add('checkout-toast--show');
        });

        setTimeout(function() {
            toast.classList.remove('checkout-toast--show');
            setTimeout(function() {
                toast.remove();
            }, 400);
        }, 3200);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
