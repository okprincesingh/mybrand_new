<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();

$orderNumber = isset($_GET['order']) ? trim((string) $_GET['order']) : '';
$order = null;
$orderItems = [];
$orderHistory = [];

if ($orderNumber !== '') {
    $order = user_get_order_by_number($orderNumber, (int) $user['id']);
    if ($order) {
        $orderItems = user_get_order_items((int) $order['id'], (int) $user['id']);
        $orderHistory = user_get_order_status_history((int) $order['id'], (int) $user['id']);
    }
}

// Get user's orders for the list
$orders = user_get_orders((int) $user['id'], 50, 0);

$meta = [
    'title' => 'Mybrandplease | Orders',
    'description' => 'Your order history and details',
    'canonical' => 'user-orders.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-orders.css'); ?>">

<div class="breadcumb">
    <div class="container">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">My Orders</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">My Orders</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-orders section-spacing-120">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-lg-3 mb-4 mb-lg-0">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="user-info d-flex align-items-center mb-3">
                            <div class="user-avatar me-3">
                                <i class="fa-regular fa-user-circle"></i>
                            </div>
                            <div class="user-details">
                                <h4 class="mb-1"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                <p class="mb-0 text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                        </div>
                        
                        <nav class="sidebar-nav">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a href="<?php echo url('user-dashboard.php'); ?>" class="nav-link">
                                        <i class="fa-regular fa-tachometer-alt-fast me-2"></i>
                                        Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo url('user-orders.php'); ?>" class="nav-link active">
                                        <i class="fa-regular fa-shopping-bag me-2"></i>
                                        Orders
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo url('user-wishlist.php'); ?>" class="nav-link">
                                        <i class="fa-regular fa-heart me-2"></i>
                                        Wishlist
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo url('user-addresses.php'); ?>" class="nav-link">
                                        <i class="fa-regular fa-map-marker-alt me-2"></i>
                                        Addresses
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo url('user-profile.php'); ?>" class="nav-link">
                                        <i class="fa-regular fa-user me-2"></i>
                                        Profile
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="<?php echo url('user-settings.php'); ?>" class="nav-link">
                                        <i class="fa-regular fa-cog me-2"></i>
                                        Settings
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <div class="sidebar-actions mt-3 pt-3 border-top">
                            <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline-secondary w-100">
                                <i class="fa-regular fa-sign-out me-2"></i>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="col-lg-9">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="dashboard-header">
                            <h1 class="mb-2">My Orders</h1>
                            <p class="text-muted mb-0">Track your orders and order history</p>
                        </div>
                    </div>
                </div>

                <?php if ($orderNumber && $order): ?>
                <!-- Single Order View -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h2 class="h5 mb-0 me-3">Order #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                            <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                        <div class="btn-group" role="group">
                            <a href="<?php echo url('user-orders.php'); ?>" class="btn btn-outline-secondary">
                                <i class="fa-regular fa-arrow-left me-2"></i>
                                Back to Orders
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-danger" onclick="cancelOrder('<?php echo htmlspecialchars($order['order_number']); ?>')">
                                <i class="fa-regular fa-times-circle me-2"></i>
                                Cancel Order
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Order Summary -->
                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Order Information</h5>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <small class="text-muted">Order Date</small>
                                                <div><?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Order Number</small>
                                                <div><?php echo htmlspecialchars($order['order_number']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Payment Method</small>
                                                <div><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Payment Status</small>
                                                <div><?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Shipping Information</h5>
                                        <div class="mb-2">
                                            <small class="text-muted">Shipping Address</small>
                                            <div class="mt-1">
                                                <?php echo htmlspecialchars($order['shipping_first_name'] . ' ' . $order['shipping_last_name']); ?><br>
                                                <?php if (!empty($order['shipping_company'])): ?><?php echo htmlspecialchars($order['shipping_company']); ?><br><?php endif; ?>
                                                <?php echo htmlspecialchars($order['shipping_address1']); ?><br>
                                                <?php if (!empty($order['shipping_address2'])): ?><?php echo htmlspecialchars($order['shipping_address2']); ?><br><?php endif; ?>
                                                <?php echo htmlspecialchars($order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_zip']); ?><br>
                                                <?php echo htmlspecialchars($order['shipping_country']); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <small class="text-muted">Shipping Method</small>
                                            <div>Free Shipping</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Billing Information</h5>
                                        <div class="mb-2">
                                            <small class="text-muted">Billing Address</small>
                                            <div class="mt-1">
                                                <?php echo htmlspecialchars($order['billing_first_name'] . ' ' . $order['billing_last_name']); ?><br>
                                                <?php if (!empty($order['billing_company'])): ?><?php echo htmlspecialchars($order['billing_company']); ?><br><?php endif; ?>
                                                <?php echo htmlspecialchars($order['billing_address1']); ?><br>
                                                <?php if (!empty($order['billing_address2'])): ?><?php echo htmlspecialchars($order['billing_address2']); ?><br><?php endif; ?>
                                                <?php echo htmlspecialchars($order['billing_city'] . ', ' . $order['billing_state'] . ' ' . $order['billing_zip']); ?><br>
                                                <?php echo htmlspecialchars($order['billing_country']); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <small class="text-muted">Contact</small>
                                            <div><?php echo htmlspecialchars($order['billing_email']); ?><br><?php echo htmlspecialchars($order['billing_phone']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Order Items</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo htmlspecialchars(url($item['product_image'] ?? 'assets/imgs/product/skin-care.webp'), ENT_QUOTES, 'UTF-8'); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                             class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo (int) $item['quantity']; ?></td>
                                                <td>$<?php echo number_format((float) $item['unit_price'], 2); ?></td>
                                                <td><strong>$<?php echo number_format((float) $item['total_price'], 2); ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Order Totals -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Order Totals</h5>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Subtotal</span>
                                                    <span>$<?php echo number_format((float) $order['subtotal'], 2); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Shipping</span>
                                                    <span>$<?php echo number_format((float) $order['shipping_cost'], 2); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Discount</span>
                                                    <span class="text-danger">-$<?php echo number_format((float) $order['discount_amount'], 2); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Tax</span>
                                                    <span>$<?php echo number_format((float) $order['tax_amount'], 2); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-2">
                                                <hr>
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-bold fs-5">Total</span>
                                                    <span class="fw-bold fs-5">$<?php echo number_format((float) $order['total_amount'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Status History -->
                        <?php if (!empty($orderHistory)): ?>
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Order Status History</h5>
                                <div class="timeline">
                                    <?php foreach ($orderHistory as $history): ?>
                                    <div class="timeline-item d-flex align-items-start mb-3">
                                        <div class="timeline-marker me-3">
                                            <span class="badge bg-<?php echo $history['new_status'] === 'delivered' ? 'success' : ($history['new_status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($history['new_status'])); ?>
                                            </span>
                                        </div>
                                        <div class="timeline-content flex-grow-1">
                                            <div class="text-muted small"><?php echo date('F j, Y \a\t g:i A', strtotime($history['created_at'])); ?></div>
                                            <?php if (!empty($history['notes'])): ?>
                                            <div class="mt-1"><?php echo htmlspecialchars($history['notes']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Order Notes -->
                        <?php if (!empty($order['notes'])): ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Order Notes</h5>
                                <p><?php echo htmlspecialchars($order['notes']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <!-- Orders List -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Order History</h2>
                        <span class="badge bg-primary"><?php echo count($orders); ?> orders</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                            <br><small class="text-muted"><?php echo (int) $order['item_count']; ?> item(s)</small>
                                        </td>
                                        <td><?php echo date('F j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo (int) $order['item_count']; ?> item(s)</td>
                                        <td><strong>$<?php echo number_format((float) $order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <div class="btn-group-vertical" role="group">
                                                <a href="<?php echo url('user-orders.php?order=' . urlencode($order['order_number'])); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-regular fa-eye me-2"></i>View Details
                                                </a>
                                                <?php if ($order['status'] === 'delivered'): ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="return false;">
                                                    <i class="fa-regular fa-map me-2"></i>Track Order
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fa-regular fa-shopping-bag" style="font-size: 48px; color: #ddd;"></i>
                            <h3 class="mt-3">No orders yet</h3>
                            <p class="text-muted">Start shopping to see your orders here</p>
                            <a href="<?php echo url('shop.php'); ?>" class="btn btn-primary">Start Shopping</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>



<script>
function cancelOrder(orderNumber) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // In a real implementation, this would make an API call to cancel the order
        alert('Order cancellation would be processed here.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>