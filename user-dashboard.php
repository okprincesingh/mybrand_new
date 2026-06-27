<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();

$orders = user_get_orders((int) $user['id'], 5, 0);
$allOrdersCount = count(user_get_orders((int) $user['id'], 500, 0));
$wishlistCount = count(user_get_wishlist((int) $user['id']));
$addresses = user_get_addresses((int) $user['id']);
$defaultAddress = user_get_default_address((int) $user['id']);
$isEmailVerified = !empty($user['email_verified_at']);

$meta = [
    'title' => 'Mybrandplease | Dashboard',
    'description' => 'Your Mybrandplease account dashboard',
    'canonical' => 'user-dashboard.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-dashboard.css'); ?>">

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">My Account</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">My Account</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-dashboard section-spacing-120">
    <div class="container container-1352">
        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-card">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fa-regular fa-user-circle"></i>
                        </div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    
                    <nav class="sidebar-nav">
                        <ul>
                            <li class="nav-item active">
                                <a href="<?php echo url('user-dashboard.php'); ?>">
                                    <i class="fa-regular fa-tachometer-alt-fast"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo url('user-orders.php'); ?>">
                                    <i class="fa-regular fa-shopping-bag"></i>
                                    Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo url('user-wishlist.php'); ?>">
                                    <i class="fa-regular fa-heart"></i>
                                    Wishlist
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo url('user-addresses.php'); ?>">
                                    <i class="fa-regular fa-map-marker-alt"></i>
                                    Addresses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo url('user-profile.php'); ?>">
                                    <i class="fa-regular fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo url('user-settings.php'); ?>">
                                    <i class="fa-regular fa-cog"></i>
                                    Settings
                                </a>
                            </li>
                        </ul>
                    </nav>

                    <div class="sidebar-actions">
                        <a href="<?php echo url('logout.php'); ?>" class="btn btn-secondary">
                            <i class="fa-regular fa-sign-out"></i>
                            Sign Out
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="dashboard-content">
                <div class="dashboard-header">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                    <p>Here's an overview of your account</p>
                </div>

                <!-- Quick Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-shopping-bag"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $allOrdersCount; ?></h3>
                            <p>Total Orders</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-heart"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $wishlistCount; ?></h3>
                            <p>Wishlist Items</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($addresses); ?></h3>
                            <p>Addresses</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo date('M Y', strtotime($user['created_at'])); ?></h3>
                            <p>Member Since</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="<?php echo url('user-orders.php'); ?>" class="view-all">View All Orders</a>
                    </div>
                    
                    <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <div class="order-number">
                                    Order #<?php echo htmlspecialchars($order['order_number']); ?>
                                </div>
                                <div class="order-date">
                                    <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="order-status order-status--<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </div>
                            </div>
                            <div class="order-details">
                                <div class="order-items">
                                    <?php echo (int) $order['item_count']; ?> item(s)
                                </div>
                                <div class="order-total">
                                    $<?php echo number_format((float) $order['total_amount'], 2); ?>
                                </div>
                                <a href="<?php echo url('user-orders.php?order=' . urlencode($order['order_number'])); ?>" class="order-link">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-shopping-bag"></i>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here</p>
                        <a href="<?php echo url('shop.php'); ?>" class="btn btn-primary">Start Shopping</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="<?php echo url('shop.php'); ?>" class="action-btn">
                            <i class="fa-regular fa-shopping-bag"></i>
                            <span>Continue Shopping</span>
                        </a>
                        <a href="<?php echo url('user-wishlist.php'); ?>" class="action-btn">
                            <i class="fa-regular fa-heart"></i>
                            <span>View Wishlist</span>
                        </a>
                        <a href="<?php echo url('user-addresses.php'); ?>" class="action-btn">
                            <i class="fa-regular fa-map-marker-alt"></i>
                            <span>Manage Addresses</span>
                        </a>
                        <a href="<?php echo url('user-profile.php'); ?>" class="action-btn">
                            <i class="fa-regular fa-user"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Account Security</h2>
                    </div>
                    
                    <div class="security-info">
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <div class="security-content">
                                <h4>Email Address</h4>
                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                <span class="security-status <?php echo $isEmailVerified ? 'verified' : 'pending'; ?>">
                                    <?php echo $isEmailVerified ? 'Verified' : 'Not verified'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fa-regular fa-shield-check"></i>
                            </div>
                            <div class="security-content">
                                <h4>Password</h4>
                                <p>Last changed: <?php echo date('F j, Y', strtotime($user['updated_at'])); ?></p>
                                <a href="<?php echo url('user-settings.php'); ?>" class="security-link">Change Password</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
