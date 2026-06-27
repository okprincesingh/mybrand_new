<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Reports & Analytics';
$pdo = db();

// Get sales data for the last 12 months
$salesData = [];
$stmt = $pdo->prepare('
    SELECT 
        DATE_FORMAT(created_at, "%Y-%m") as month,
        DATE_FORMAT(created_at, "%M %Y") as month_name,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order_value
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, "%Y-%m")
    ORDER BY month DESC
    LIMIT 12
');
$stmt->execute();
$salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top products
$topProducts = [];
$stmt = $pdo->prepare('
    SELECT 
        p.name,
        p.slug,
        p.price,
        p.featured_image,
        COUNT(oi.id) as order_count,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue
    FROM products p
    INNER JOIN order_items oi ON p.id = oi.product_id
    INNER JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ("delivered", "shipped", "processing")
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
');
$stmt->execute();
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customer insights
$customerInsights = [];
$stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d,
        (SELECT COUNT(*) FROM orders WHERE status IN ("delivered", "shipped")) as total_orders,
        (SELECT AVG(total_amount) FROM orders WHERE status IN ("delivered", "shipped")) as avg_order_value,
        (SELECT COUNT(DISTINCT customer_id) FROM orders WHERE status IN ("delivered", "shipped")) as unique_customers
    FROM users 
    WHERE is_active = 1
');
$stmt->execute();
$customerInsights = $stmt->fetch(PDO::FETCH_ASSOC);

// Get coupon usage stats
$couponStats = [];
$stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_coupons,
        COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_coupons,
        COUNT(CASE WHEN expires_at < NOW() THEN 1 END) as expired_coupons,
        COUNT(cu.id) as total_usage,
        SUM(cu.discount_amount) as total_discount_given
    FROM coupons c
    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
');
$stmt->execute();
$couponStats = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
    <!-- Sales Overview -->
    <div class="col-lg-12">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">Sales Overview (Last 12 Months)</h5>
            </div>
            <div class="widget-body">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Avg Order Value</th>
                                <th>Growth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesData as $index => $sale): ?>
                            <tr>
                                <td><?= e($sale['month_name']) ?></td>
                                <td><?= e($sale['order_count']) ?></td>
                                <td><strong>$<?= number_format((float)$sale['revenue'], 2) ?></strong></td>
                                <td>$<?= number_format((float)$sale['avg_order_value'], 2) ?></td>
                                <td>
                                    <?php if ($index < count($salesData) - 1): ?>
                                        <?php 
                                        $prevRevenue = $salesData[$index + 1]['revenue'];
                                        $growth = $prevRevenue > 0 ? (($sale['revenue'] - $prevRevenue) / $prevRevenue) * 100 : 0;
                                        ?>
                                        <span class="badge <?= $growth >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $growth >= 0 ? '+' : '' ?><?= number_format($growth, 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-lg-8">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">Top Products by Sales</h5>
            </div>
            <div class="widget-body">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Orders</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= e($product['featured_image']) ?>" alt="<?= e($product['name']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                        <div>
                                            <strong><?= e($product['name']) ?></strong>
                                            <br><small class="text-muted"><?= e($product['slug']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($product['order_count']) ?></td>
                                <td><?= e($product['total_sold']) ?></td>
                                <td><strong>$<?= number_format((float)$product['total_revenue'], 2) ?></strong></td>
                                <td>$<?= number_format((float)$product['price'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topProducts)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No sales data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Insights -->
    <div class="col-lg-4">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">Customer Insights</h5>
            </div>
            <div class="widget-body">
                <div class="analytics-stats">
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-primary-subtle">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Total Customers</h6>
                            <div class="analytics-stat-value"><?= e($customerInsights['total_customers'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-success-subtle">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">New Customers (30d)</h6>
                            <div class="analytics-stat-value"><?= e($customerInsights['new_customers_30d'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-info-subtle">
                            <i class="bi bi-bag-check"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Total Orders</h6>
                            <div class="analytics-stat-value"><?= e($customerInsights['total_orders'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-warning-subtle">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Avg Order Value</h6>
                            <div class="analytics-stat-value">$<?= number_format((float)($customerInsights['avg_order_value'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coupon Analytics -->
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">Coupon Analytics</h5>
            </div>
            <div class="widget-body">
                <div class="analytics-stats">
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-primary-subtle">
                            <i class="bi bi-tag"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Total Coupons</h6>
                            <div class="analytics-stat-value"><?= e($couponStats['total_coupons'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-success-subtle">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Active Coupons</h6>
                            <div class="analytics-stat-value"><?= e($couponStats['active_coupons'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-danger-subtle">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Expired Coupons</h6>
                            <div class="analytics-stat-value"><?= e($couponStats['expired_coupons'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-warning-subtle">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Total Usage</h6>
                            <div class="analytics-stat-value"><?= e($couponStats['total_usage'] ?? 0) ?></div>
                        </div>
                    </div>
                    
                    <div class="analytics-stat">
                        <div class="analytics-stat-icon bg-info-subtle">
                            <i class="bi bi-cash"></i>
                        </div>
                        <div class="analytics-stat-content">
                            <h6 class="analytics-stat-title">Discounts Given</h6>
                            <div class="analytics-stat-value">$<?= number_format((float)($couponStats['total_discount_given'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Chart Placeholder -->
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title">Revenue Trend</h5>
            </div>
            <div class="widget-body">
                <div class="chart-placeholder">
                    <div class="chart-header">
                        <div class="chart-controls">
                            <button class="btn btn-outline-primary btn-sm active" onclick="updateChart('monthly')">Monthly</button>
                            <button class="btn btn-outline-primary btn-sm" onclick="updateChart('weekly')">Weekly</button>
                            <button class="btn btn-outline-primary btn-sm" onclick="updateChart('daily')">Daily</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                    <div class="chart-summary">
                        <div class="row text-center">
                            <div class="col-4">
                                <strong>Total Revenue</strong><br>
                                <span class="text-success">$<?= number_format(array_sum(array_column($salesData, 'revenue')), 2) ?></span>
                            </div>
                            <div class="col-4">
                                <strong>Total Orders</strong><br>
                                <span><?= array_sum(array_column($salesData, 'order_count')) ?></span>
                            </div>
                            <div class="col-4">
                                <strong>Avg Growth</strong><br>
                                <span class="text-info">+<?= number_format(array_sum(array_column($salesData, 'avg_order_value')) / count($salesData), 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for Revenue Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Prepare data for chart
    const labels = <?= json_encode(array_column($salesData, 'month_name')) ?>;
    const revenueData = <?= json_encode(array_column($salesData, 'revenue')) ?>;
    const orderData = <?= json_encode(array_column($salesData, 'order_count')) ?>;
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.reverse(),
            datasets: [{
                label: 'Revenue ($)',
                data: revenueData.reverse(),
                borderColor: '#EE2D7A',
                backgroundColor: 'rgba(238, 45, 122, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Orders',
                data: orderData.reverse(),
                borderColor: '#0C0C0C',
                backgroundColor: 'rgba(12, 12, 12, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    window.updateChart = function(type) {
        // This would implement different time periods
        // For now, just update the button states
        document.querySelectorAll('.chart-controls .btn').forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // In a real implementation, you would fetch new data based on the type
        // and update the chart with chart.update()
    };
});
</script>

<style>
.analytics-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.analytics-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 12px;
    border: 1px solid #e9ecef;
}

.analytics-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
}

.analytics-stat-content {
    flex: 1;
}

.analytics-stat-title {
    margin: 0 0 4px 0;
    font-size: 14px;
    color: #666;
    font-weight: 600;
}

.analytics-stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #0C0C0C;
}

.chart-placeholder {
    padding: 20px;
}

.chart-header {
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chart-controls .btn {
    margin-right: 8px;
}

.chart-container {
    height: 300px;
    margin-bottom: 20px;
}

.chart-summary {
    border-top: 1px solid #e9ecef;
    padding-top: 20px;
}

@media (max-width: 768px) {
    .analytics-stats {
        grid-template-columns: 1fr;
    }
    
    .chart-summary .row {
        text-align: left;
    }
    
    .chart-summary .col-4 {
        margin-bottom: 10px;
    }
}
</style>

<?php include __DIR__ . '/_layout_bottom.php'; ?>