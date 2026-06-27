<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/url.php';

$orderNumber = isset($_GET['order']) ? trim((string) $_GET['order']) : '';
$order = null;

if ($orderNumber !== '') {
    $pdo = db();
    if ($pdo) {
        $stmt = $pdo->prepare('
            SELECT o.*, c.email as customer_email
            FROM orders o
            LEFT JOIN customers c ON c.id = o.customer_id
            WHERE o.order_number = ?
            LIMIT 1
        ');
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch();

        if ($order) {
            // Get order items
            $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $stmt->execute([$order['id']]);
            $order['items'] = $stmt->fetchAll();
        }
    }
}

$meta = [
    'title' => 'Mybrandplease | Order Confirmation',
    'description' => 'Your order has been placed successfully',
    'canonical' => 'order-success.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
            <div class="breadcumb-wrapper__title">Order Confirmation</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">Order Confirmation</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="order-success-page section-spacing-120">
    <div class="container container-1352">
        <?php if ($order): ?>
        <div class="order-success-card">
            <div class="order-success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 6L9 17l-5-5" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h1 class="order-success-title">Thank You, <?php echo htmlspecialchars($order['billing_first_name']); ?>!</h1>
            <p class="order-success-subtitle">Your order has been placed successfully</p>

            <div class="order-details-card">
                <div class="order-details-header">
                    <h3>Order Details</h3>
                    <span class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>

                <div class="order-details-grid">
                    <div class="order-detail-item">
                        <span class="order-detail-label">Order Date</span>
                        <span class="order-detail-value"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Order Status</span>
                        <span class="order-detail-value order-status order-status--<?php echo htmlspecialchars($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Payment Method</span>
                        <span class="order-detail-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></span>
                    </div>
                    <div class="order-detail-item">
                        <span class="order-detail-label">Total Amount</span>
                        <span class="order-detail-value order-total">$<?php echo number_format((float) $order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="order-items-card">
                <h3>Order Items</h3>
                <div class="order-items-list">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <div class="order-item-image">
                            <img src="<?php echo htmlspecialchars(url($item['product_image'] ?? 'assets/imgs/product/skin-care.webp'), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="order-item-details">
                            <h4 class="order-item-name"><?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="order-item-meta">Quantity: <?php echo (int) $item['quantity']; ?> x $<?php echo number_format((float) $item['unit_price'], 2); ?></p>
                        </div>
                        <div class="order-item-price">$<?php echo number_format((float) $item['total_price'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="order-info-grid">
                <div class="order-info-card">
                    <h4>Billing Address</h4>
                    <address>
                        <?php echo htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']); ?><br>
                        <?php if (!empty($order['billing_company'])): ?><?php echo htmlspecialchars($order['billing_company']); ?><br><?php endif; ?>
                        <?php echo htmlspecialchars($order['billing_address1']); ?><br>
                        <?php if (!empty($order['billing_address2'])): ?><?php echo htmlspecialchars($order['billing_address2']); ?><br><?php endif; ?>
                        <?php echo htmlspecialchars($order['billing_city'] . ', ' . $order['billing_state'] . ' ' . $order['billing_zip']); ?><br>
                        <?php echo htmlspecialchars($order['billing_country']); ?><br>
                        <br>
                        <?php echo htmlspecialchars($order['billing_email']); ?><br>
                        <?php echo htmlspecialchars($order['billing_phone']); ?>
                    </address>
                </div>

                <?php if (!empty($order['shipping_first_name']) && $order['shipping_first_name'] !== $order['billing_first_name']): ?>
                <div class="order-info-card">
                    <h4>Shipping Address</h4>
                    <address>
                        <?php echo htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']); ?><br>
                        <?php if (!empty($order['shipping_company'])): ?><?php echo htmlspecialchars($order['shipping_company']); ?><br><?php endif; ?>
                        <?php echo htmlspecialchars($order['shipping_address1']); ?><br>
                        <?php if (!empty($order['shipping_address2'])): ?><?php echo htmlspecialchars($order['shipping_address2']); ?><br><?php endif; ?>
                        <?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_zip']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_country']); ?><br>
                        <br>
                        <?php echo htmlspecialchars($order['shipping_email'] ?? $order['billing_email']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_phone'] ?? $order['billing_phone']); ?>
                    </address>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
            <div class="payment-instructions-card">
                <h4>Bank Transfer Instructions</h4>
                <p>Please transfer the total amount to the following bank account within 3 business days:</p>
                <div class="bank-details">
                    <p><strong>Bank Name:</strong> [Your Bank Name]</p>
                    <p><strong>Account Name:</strong> [Your Account Name]</p>
                    <p><strong>Account Number:</strong> [Your Account Number]</p>
                    <p><strong>SWIFT Code:</strong> [Your SWIFT Code]</p>
                </div>
                <p class="payment-note">Please use your Order Number <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong> as the payment reference.</p>
            </div>
            <?php endif; ?>

            <?php if ($order['payment_method'] === 'cod'): ?>
            <div class="payment-instructions-card">
                <h4>Cash on Delivery</h4>
                <p>Please keep the exact amount ready when your order is delivered. Our delivery partner will contact you before delivery.</p>
            </div>
            <?php endif; ?>

            <div class="order-actions">
                <a href="<?php echo url('shop.php'); ?>" class="btn btn-primary">Continue Shopping</a>
                <a href="<?php echo url('contact.php?order=' . urlencode($order['order_number'])); ?>" class="btn btn-secondary">Need Help?</a>
            </div>
        </div>
        <?php else: ?>
        <div class="order-not-found">
            <div class="order-not-found-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/>
                    <path d="M12 8v4m0 4h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>Order Not Found</h1>
            <p>We couldn't find an order with that number. Please check your order confirmation email.</p>
            <a href="<?php echo url('shop.php'); ?>" class="btn btn-primary">Continue Shopping</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.order-success-page {
    background: #f8f9fa;
    min-height: 60vh;
}
.order-success-card {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 48px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.order-success-icon {
    margin-bottom: 24px;
}
.order-success-title {
    font-size: 32px;
    font-weight: 700;
    color: #0C0C0C;
    margin-bottom: 8px;
}
.order-success-subtitle {
    font-size: 16px;
    color: #666;
    margin-bottom: 32px;
}
.order-details-card,
.order-items-card,
.order-info-card,
.payment-instructions-card {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 24px;
    margin-top: 24px;
    text-align: left;
}
.order-details-card h3,
.order-items-card h3,
.order-info-card h4,
.payment-instructions-card h4 {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 600;
    color: #0C0C0C;
}
.order-details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}
.order-number {
    font-weight: 600;
    color: #EE2D7A;
}
.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.order-detail-item {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
}
.order-detail-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}
.order-detail-value {
    display: block;
    font-size: 16px;
    font-weight: 600;
    color: #0C0C0C;
}
.order-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-block;
}
.order-status--pending { background: #fef3c7; color: #92400e; }
.order-status--processing { background: #dbeafe; color: #1e40af; }
.order-status--shipped { background: #e0e7ff; color: #3730a3; }
.order-status--delivered { background: #d1fae5; color: #065f46; }
.order-status--cancelled,
.order-status--refunded { background: #fee2e2; color: #991b1b; }
.order-total {
    color: #EE2D7A;
    font-size: 20px;
}
.order-items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.order-item {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff;
    padding: 16px;
    border-radius: 8px;
}
.order-item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}
.order-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.order-item-details {
    flex: 1;
}
.order-item-name {
    font-size: 16px;
    font-weight: 600;
    color: #0C0C0C;
    margin: 0 0 4px;
}
.order-item-meta {
    font-size: 14px;
    color: #666;
    margin: 0;
}
.order-item-price {
    font-size: 16px;
    font-weight: 600;
    color: #EE2D7A;
}
.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    margin-top: 16px;
}
.order-info-card address {
    font-style: normal;
    line-height: 1.6;
    color: #444;
}
.payment-instructions-card {
    border-left: 4px solid #EE2D7A;
}
.bank-details {
    background: #fff;
    padding: 16px;
    border-radius: 8px;
    margin: 16px 0;
}
.bank-details p {
    margin: 8px 0;
}
.payment-note {
    font-size: 14px;
    color: #666;
}
.order-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    margin-top: 32px;
}
.btn {
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}
.btn-primary {
    background: #EE2D7A;
    color: #fff;
}
.btn-primary:hover {
    background: #d4256a;
}
.btn-secondary {
    background: #f0f0f0;
    color: #333;
}
.btn-secondary:hover {
    background: #e0e0e0;
}
.order-not-found {
    max-width: 500px;
    margin: 0 auto;
    background: #fff;
    border-radius: 16px;
    padding: 48px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.order-not-found-icon {
    margin-bottom: 24px;
}
.order-not-found h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0C0C0C;
    margin-bottom: 16px;
}
.order-not-found p {
    font-size: 16px;
    color: #666;
    margin-bottom: 24px;
}
@media (max-width: 768px) {
    .order-success-card,
    .order-not-found {
        padding: 24px;
    }
    .order-success-title {
        font-size: 24px;
    }
    .order-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
