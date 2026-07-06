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

// Get revenue stats
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

// Orders by status
$ordersByStatus = [];
if ($pdo) {
    $stmt = $pdo->query('SELECT status, COUNT(*) as cnt FROM orders GROUP BY status ORDER BY cnt DESC');
    $ordersByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/_layout_top.php';
?>
<!-- Top Stats Row -->
<div class="dashboard-grid mb-4">
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-title">Total Revenue</span>
      <span class="stat-icon"><i class="bi bi-currency-dollar"></i></span>
    </div>
    <div class="stat-value">$<?= number_format((float)($revenueStats['total_revenue'] ?? 0), 2) ?></div>
    <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> All time earnings</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-title">Total Orders</span>
      <span class="stat-icon" style="background: var(--success-soft); color: var(--success);"><i class="bi bi-receipt"></i></span>
    </div>
    <div class="stat-value"><?= (int)$counts['orders'] ?></div>
    <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +<?= (int)($revenueStats['total_orders'] ?? 0) ?> fulfilled</div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-title">Avg Order</span>
      <span class="stat-icon" style="background: #fef3c7; color: #d97706;"><i class="bi bi-cart-check"></i></span>
    </div>
    <div class="stat-value">$<?= number_format((float)($revenueStats['avg_order_value'] ?? 0), 2) ?></div>
    <div class="stat-trend"><span>Per order average</span></div>
  </div>
  
  <div class="stat-card">
    <div class="stat-header">
      <span class="stat-title">Users</span>
      <span class="stat-icon" style="background: #ede9fe; color: #7c3aed;"><i class="bi bi-people"></i></span>
    </div>
    <div class="stat-value"><?= (int)$counts['users'] ?></div>
    <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Active accounts</div>
  </div>
</div>

<!-- Quick Stats Grid -->
<div class="dashboard-grid mb-4">
  <div class="stat-card" style="grid-column:span 3;">
    <div class="stat-header">
      <span class="stat-title">Products</span>
      <span class="stat-icon" style="background: #dbeafe; color: #2563eb;"><i class="bi bi-box-seam"></i></span>
    </div>
    <div style="display:flex;gap:1rem;margin-top:0.5rem;">
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$counts['products'] ?></span><br><small class="text-muted">Total</small></div>
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn() ?></span><br><small class="text-muted">Published</small></div>
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 0')->fetchColumn() ?></span><br><small class="text-muted">Drafts</small></div>
    </div>
  </div>
  
  <div class="stat-card" style="grid-column:span 3;">
    <div class="stat-header">
      <span class="stat-title">Content</span>
      <span class="stat-icon" style="background: #fce7f3; color: #db2777;"><i class="bi bi-file-earmark-text"></i></span>
    </div>
    <div style="display:flex;gap:1rem;margin-top:0.5rem;">
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$counts['pages'] ?></span><br><small class="text-muted">Pages</small></div>
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$counts['categories'] ?></span><br><small class="text-muted">Categories</small></div>
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$counts['reviews'] ?></span><br><small class="text-muted">Reviews</small></div>
      <div><span class="stat-value" style="font-size:1.4rem;"><?= (int)$counts['coupons'] ?></span><br><small class="text-muted">Coupons</small></div>
    </div>
  </div>
  
  <div class="stat-card" style="grid-column:span 3;">
    <div class="stat-header">
      <span class="stat-title">Orders by Status</span>
      <span class="stat-icon" style="background: #ecfdf5; color: #059669;"><i class="bi bi-pie-chart"></i></span>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-top:0.5rem;">
      <?php 
      $statusColors = [
        'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Pending'],
        'processing' => ['bg' => '#dbeafe', 'text' => '#1e40af', 'label' => 'Processing'],
        'shipped' => ['bg' => '#ede9fe', 'text' => '#5b21b6', 'label' => 'Shipped'],
        'delivered' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'Delivered'],
        'cancelled' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'Cancelled'],
        'refunded' => ['bg' => '#f3f4f6', 'text' => '#4b5563', 'label' => 'Refunded'],
      ];
      foreach ($ordersByStatus as $os): 
        $s = $os['status'];
        $colors = $statusColors[$s] ?? ['bg' => '#f3f4f6', 'text' => '#4b5563', 'label' => ucfirst($s)];
      ?>
        <span class="status-badge" style="background:<?= $colors['bg'] ?>;color:<?= $colors['text'] ?>;border-color:transparent;">
          <?= $colors['label'] ?> (<?= (int)$os['cnt'] ?>)
        </span>
      <?php endforeach; ?>
    </div>
  </div>
  
  <div class="stat-card" style="grid-column:span 3;">
    <div class="stat-header">
      <span class="stat-title">Quick Actions</span>
      <span class="stat-icon" style="background: #e0f2fe; color: #0284c7;"><i class="bi bi-lightning-fill"></i></span>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-top:0.5rem;">
      <a href="product-edit.php" class="btn btn-primary-modern btn-sm"><i class="bi bi-plus-circle me-1"></i>Add Product</a>
      <a href="coupon-edit.php" class="btn btn-primary-modern btn-sm" style="background:linear-gradient(135deg,#059669,#047857);"><i class="bi bi-tag me-1"></i>Add Coupon</a>
      <a href="blog-edit.php" class="btn btn-primary-modern btn-sm" style="background:linear-gradient(135deg,#7c3aed,#6d28d9);"><i class="bi bi-pencil-square me-1"></i>Write Blog</a>
      <a href="home-slider.php" class="btn btn-secondary-modern btn-sm"><i class="bi bi-sliders me-1"></i>Slider</a>
      <a href="orders.php" class="btn btn-secondary-modern btn-sm"><i class="bi bi-receipt me-1"></i>Orders</a>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Recent Orders -->
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Orders</h5>
        <div class="widget-actions">
          <a href="orders.php" class="btn btn-primary-modern btn-sm">View All</a>
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
              <td><span style="font-family:monospace;font-weight:600;">#<?= e($order['order_number'] ?? $order['id']) ?></span></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:50%;background:var(--primary-soft);color:var(--primary);font-size:0.8rem;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($order['customer_first_name'] ?? '?', 0, 1) . substr($order['customer_last_name'] ?? '', 0, 1)) ?>
                  </span>
                  <span><?= e($order['customer_first_name'] . ' ' . $order['customer_last_name']) ?></span>
                </div>
              </td>
              <td><strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong></td>
              <td>
                <?php
                $statusMap = [
                  'pending' => ['label' => 'Pending', 'class' => 'status-draft'],
                  'processing' => ['label' => 'Processing', 'class' => 'status-published'],
                  'shipped' => ['label' => 'Shipped', 'class' => 'status-published'],
                  'delivered' => ['label' => 'Delivered', 'class' => 'status-active'],
                  'cancelled' => ['label' => 'Cancelled', 'class' => 'status-inactive'],
                  'refunded' => ['label' => 'Refunded', 'class' => 'status-inactive'],
                ];
                $sm = $statusMap[$order['status']] ?? ['label' => ucfirst($order['status']), 'class' => 'status-draft'];
                ?>
                <span class="status-badge <?= $sm['class'] ?>"><?= $sm['label'] ?></span>
              </td>
              <td><small class="text-muted"><?= date('M j, Y', strtotime($order['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted py-4">
                <i class="bi bi-receipt" style="font-size:1.5rem;display:block;margin-bottom:0.3rem;opacity:0.3;"></i>
                No orders yet
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Recent Products -->
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Products</h5>
        <div class="widget-actions">
          <a href="products.php" class="btn btn-primary-modern btn-sm">View All</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Price</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentProducts as $product): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($product['image']): ?>
                    <img src="<?= e(url($product['image'])) ?>" alt="<?= e($product['name']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--border);flex-shrink:0;">
                  <?php else: ?>
                    <span class="d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;border-radius:8px;background:var(--surface-soft);color:var(--muted);font-size:1rem;flex-shrink:0;">
                      <i class="bi bi-box"></i>
                    </span>
                  <?php endif; ?>
                  <span style="font-weight:600;"><?= e($product['name']) ?></span>
                </div>
              </td>
              <td><strong>$<?= number_format((float)$product['price'], 2) ?></strong></td>
              <td>
                <?php if ($product['is_active']): ?>
                  <span class="status-badge status-active">Active</span>
                <?php else: ?>
                  <span class="status-badge status-draft">Draft</span>
                <?php endif; ?>
              </td>
              <td><small class="text-muted"><?= date('M j, Y', strtotime($product['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentProducts)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-box-seam" style="font-size:1.5rem;display:block;margin-bottom:0.3rem;opacity:0.3;"></i>
                No products yet
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Recent Users -->
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Recent Users</h5>
        <div class="widget-actions">
          <a href="users.php" class="btn btn-primary-modern btn-sm">View All</a>
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
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;border-radius:50%;background:var(--success-soft);color:var(--success);font-size:0.8rem;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'S', 0, 1)) ?>
                  </span>
                  <span style="font-weight:600;"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
                </div>
              </td>
              <td><small><?= e($user['email']) ?></small></td>
              <td><small class="text-muted"><?= date('M j, Y', strtotime($user['created_at'])) ?></small></td>
              <td><span class="status-badge status-active">Active</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentUsers)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">
                <i class="bi bi-people" style="font-size:1.5rem;display:block;margin-bottom:0.3rem;opacity:0.3;"></i>
                No users found
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Quick Overview -->
  <div class="col-lg-6">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Overview Summary</h5>
        <div class="widget-actions">
          <span class="text-muted small">Live stats</span>
        </div>
      </div>
      <div class="d-flex flex-column gap-2">
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-box-seam me-2"></i>Published Products</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-pencil me-2"></i>Draft Products</span>
          <span class="status-badge status-draft"><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 0')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-people me-2"></i>Active Users</span>
          <span class="status-badge status-active"><?= (int)$counts['users'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-diagram-3 me-2"></i>Categories</span>
          <span class="status-badge status-active"><?= (int)$counts['categories'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-tag me-2"></i>Active Coupons</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM coupons WHERE is_active = 1')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-receipt me-2"></i>Completed Orders</span>
          <span class="status-badge status-active"><?= (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status IN ("delivered", "shipped")')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-hourglass-split me-2"></i>Pending Orders</span>
          <span class="status-badge" style="background:#fef3c7;color:#92400e;"><?= (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn() ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-cash-coin me-2"></i>Avg Order Value</span>
          <span class="status-badge status-active">$<?= number_format((float)($revenueStats['avg_order_value'] ?? 0), 2) ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:var(--surface-soft);">
          <span class="text-muted"><i class="bi bi-star me-2"></i>Reviews</span>
          <span class="status-badge status-active"><?= (int)$counts['reviews'] ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mt-2">
  <!-- Navigation shortcuts -->
  <div class="col-12">
    <div class="widget-card" style="background:linear-gradient(135deg, var(--sidebar), var(--sidebar-soft));border:none;">
      <div class="widget-header" style="border-bottom-color:rgba(255,255,255,0.1);">
        <h5 class="widget-title" style="color:#fff;">Quick Navigation</h5>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="dashboard.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a href="products.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-box-seam me-1"></i>Products</a>
        <a href="categories.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-diagram-3 me-1"></i>Categories</a>
        <a href="orders.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-receipt me-1"></i>Orders</a>
        <a href="users.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-people me-1"></i>Users</a>
        <a href="blogs.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-journal-richtext me-1"></i>Blog</a>
        <a href="coupons.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-tag me-1"></i>Coupons</a>
        <a href="reviews.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-chat-left-text me-1"></i>Reviews</a>
        <a href="enquiries.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-envelope-paper me-1"></i>Enquiries</a>
        <a href="reports.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#e2e8f0;border:1px solid rgba(255,255,255,0.1);"><i class="bi bi-bar-chart me-1"></i>Reports</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>