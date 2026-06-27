<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Dashboard';
$pdo = db();
$counts = ['pages'=>0,'products'=>0,'categories'=>0,'reviews'=>0,'users'=>0,'orders'=>0,'coupons'=>0];
if ($pdo) {
    $counts['pages'] = (int) $pdo->query('SELECT COUNT(*) FROM pages')->fetchColumn();
    $counts['products'] = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $counts['categories'] = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    $counts['reviews'] = (int) $pdo->query('SELECT COUNT(*) FROM product_reviews')->fetchColumn();
    $counts['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
    $counts['orders'] = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $counts['coupons'] = (int) $pdo->query('SELECT COUNT(*) FROM coupons')->fetchColumn();
}

// Get recent users
$recentUsers = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, email, first_name, last_name, created_at FROM users WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5');
    $stmt->execute();
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent products
$recentProducts = [];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, name, price, featured_image as image, is_active, created_at FROM products ORDER BY created_at DESC LIMIT 5');
    $stmt->execute();
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent orders
$recentOrders = [];
if ($pdo) {
    $stmt = $pdo->prepare('
        SELECT o.*, 
               c.first_name as customer_first_name, 
               c.last_name as customer_last_name,
               c.email as customer_email
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        ORDER BY o.created_at DESC LIMIT 5
    ');
    $stmt->execute();
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent coupons
$recentCoupons = [];
if ($pdo) {
    $stmt = $pdo->prepare('
        SELECT c.*, 
               COUNT(cu.id) as usage_count,
               SUM(cu.discount_amount) as total_discount
        FROM coupons c 
        LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id 
        GROUP BY c.id 
        ORDER BY c.created_at DESC LIMIT 5
    ');
    $stmt->execute();
    $recentCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get revenue stats for dashboard
$revenueStats = [];
if ($pdo) {
    $stmt = $pdo->prepare('
        SELECT 
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_orders,
            AVG(total_amount) as avg_order_value
        FROM orders 
        WHERE status IN ("delivered", "shipped", "processing")
    ');
    $stmt->execute();
    $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
}

include __DIR__ . '/_layout_top.php';
?>
<div class="dashboard-grid">
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
      <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +25%</div>
    </div>
    <div class="stat-title">Total Revenue</div>
    <div class="stat-value">$<?= number_format((float)($revenueStats['total_revenue'] ?? 0), 2) ?></div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon"><i class="bi bi-receipt"></i></div>
      <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +12%</div>
    </div>
    <div class="stat-title">Total Orders</div>
    <div class="stat-value"><?= (int)$counts['orders'] ?></div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +15%</div>
    </div>
    <div class="stat-title">Total Users</div>
    <div class="stat-value"><?= (int)$counts['users'] ?></div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
      <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +8%</div>
    </div>
    <div class="stat-title">Total Products</div>
    <div class="stat-value"><?= (int)$counts['products'] ?></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Users</h5>
        <div class="widget-actions">
          <a href="users.php" class="btn btn-outline-primary btn-sm">View All Users</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Joined</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentUsers as $user): ?>
            <tr>
              <td><?= e($user['first_name'] . ' ' . $user['last_name']) ?></td>
              <td><?= e($user['email']) ?></td>
              <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
              <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentUsers)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted">No users found</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Products</h5>
        <div class="widget-actions">
          <a href="products.php" class="btn btn-outline-primary btn-sm">View All Products</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Status</th>
              <th>Added</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentProducts as $product): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                  <span><?= e($product['name']) ?></span>
                </div>
              </td>
              <td>$<?= number_format((float)$product['price'], 2) ?></td>
              <td>
                <?php if ($product['is_active']): ?>
                  <span class="status-badge status-active">Active</span>
                <?php else: ?>
                  <span class="status-badge status-draft">Draft</span>
                <?php endif; ?>
              </td>
              <td><?= date('M j, Y', strtotime($product['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentProducts)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted">No products found</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Orders</h5>
        <div class="widget-actions">
          <a href="orders.php" class="btn btn-outline-primary btn-sm">View All Orders</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><?= e($order['order_number']) ?></td>
              <td><?= e($order['customer_first_name'] . ' ' . $order['customer_last_name']) ?></td>
              <td>$<?= number_format((float)$order['total_amount'], 2) ?></td>
              <td>
                <?php
                $statusClass = '';
                switch ($order['status']) {
                    case 'pending': $statusClass = 'status-pending'; break;
                    case 'processing': $statusClass = 'status-processing'; break;
                    case 'shipped': $statusClass = 'status-shipped'; break;
                    case 'delivered': $statusClass = 'status-active'; break;
                    case 'cancelled': $statusClass = 'status-draft'; break;
                    case 'refunded': $statusClass = 'status-refunded'; break;
                }
                ?>
                <span class="status-badge <?= e($statusClass) ?>"><?= e(ucfirst($order['status'])) ?></span>
              </td>
              <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No orders found</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Activity</h5>
        <div class="widget-actions">
          <button class="btn btn-outline-secondary btn-sm">View All</button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Activity</th>
              <th>User</th>
              <th>Time</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>New product added</td>
              <td><?= e($adminUser['name'] ?? 'Admin') ?></td>
              <td>2 minutes ago</td>
              <td><span class="status-badge status-active">Success</span></td>
            </tr>
            <tr>
              <td>Page updated</td>
              <td><?= e($adminUser['name'] ?? 'Admin') ?></td>
              <td>15 minutes ago</td>
              <td><span class="status-badge status-active">Success</span></td>
            </tr>
            <tr>
              <td>Review moderated</td>
              <td><?= e($adminUser['name'] ?? 'Admin') ?></td>
              <td>1 hour ago</td>
              <td><span class="status-badge status-active">Approved</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-lg-4">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Status Overview</h5>
      </div>
      <div class="d-flex flex-column gap-2">
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Published Products</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Draft Products</span>
          <span class="status-badge status-draft"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 0')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Active Users</span>
          <span class="status-badge status-active"><?= (int)$counts['users'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Total Categories</span>
          <span class="status-badge status-active"><?= (int)$counts['categories'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Pending Orders</span>
          <span class="status-badge status-pending"><?= (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Completed Orders</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status IN ("delivered", "shipped")')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Active Coupons</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM coupons WHERE is_active = 1')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Expired Coupons</span>
          <span class="status-badge status-draft"><?= (int)$pdo->query('SELECT COUNT(*) FROM coupons WHERE expires_at < NOW() AND is_active = 1')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
          <span class="text-muted">Avg Order Value</span>
          <span class="status-badge status-active">$<?= number_format((float)($revenueStats['avg_order_value'] ?? 0), 2) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
