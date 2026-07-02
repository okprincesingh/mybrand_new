<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Coupons';
$pdo = db();

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $couponId = (int)($_POST['coupon_id'] ?? 0);
    
    if ($action === 'toggle_active' && $couponId > 0) {
        $stmt = $pdo->prepare('SELECT is_active FROM coupons WHERE id = :id');
        $stmt->execute([':id' => $couponId]);
        $current = $stmt->fetchColumn();
        
        if ($current !== false) {
            $newStatus = $current ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE coupons SET is_active = :status WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $couponId]);
            admin_flash_set('success', 'Coupon status updated successfully.');
        }
    } elseif ($action === 'delete' && $couponId > 0) {
        try {
            $pdo->beginTransaction();
            
            // Delete usage records first
            $stmt = $pdo->prepare('DELETE FROM coupon_usage WHERE coupon_id = :id');
            $stmt->execute([':id' => $couponId]);
            
            // Delete coupon
            $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = :id');
            $stmt->execute([':id' => $couponId]);
            
            $pdo->commit();
            admin_flash_set('success', 'Coupon deleted successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            admin_flash_set('error', 'Failed to delete coupon: ' . $e->getMessage());
        }
    }
    
    header('Location: coupons.php');
    exit;
}

// Get all coupons with usage stats
$coupons = [];
$stmt = $pdo->prepare('
    SELECT c.*, 
           COUNT(cu.id) as used_count,
           SUM(cu.discount_amount) as total_discount
    FROM coupons c 
    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
');
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate stats
$totalCoupons = count($coupons);
$activeCoupons = count(array_filter($coupons, fn($c) => $c['is_active']));
$totalUsage = array_sum(array_column($coupons, 'used_count'));
$totalDiscountGiven = array_sum(array_column($coupons, 'total_discount'));

include __DIR__ . '/_layout_top.php';
?>

<!-- Coupon Stats Row -->
<div class="dashboard-grid mb-4">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Coupons</span>
            <span class="stat-icon"><i class="bi bi-tag"></i></span>
        </div>
        <div class="stat-value"><?= (int) $totalCoupons ?></div>
        <div class="stat-trend">
            <span>All time</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Active</span>
            <span class="stat-icon" style="background: var(--success-soft); color: var(--success);"><i class="bi bi-check-circle"></i></span>
        </div>
        <div class="stat-value"><?= (int) $activeCoupons ?></div>
        <div class="stat-trend trend-up">
            <span>Currently active</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Used</span>
            <span class="stat-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="bi bi-cart-check"></i></span>
        </div>
        <div class="stat-value"><?= (int) $totalUsage ?></div>
        <div class="stat-trend">
            <span>Times redeemed</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Discount Given</span>
            <span class="stat-icon" style="background: #fef3c7; color: #d97706;"><i class="bi bi-cash-stack"></i></span>
        </div>
        <div class="stat-value">$<?= number_format((float) $totalDiscountGiven, 2) ?></div>
        <div class="stat-trend">
            <span>Total savings</span>
        </div>
    </div>
</div>

<div class="widget-card">
    <div class="widget-header">
        <h5 class="widget-title">All Coupons <span class="text-muted fs-6 fw-normal">(<?= count($coupons) ?>)</span></h5>
        <div class="widget-actions">
            <a href="coupon-edit.php" class="btn btn-primary-modern btn-sm"><i class="bi bi-plus-circle me-1"></i>Add New Coupon</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Usage</th>
                    <th>Validity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td>
                        <strong style="font-family: monospace; letter-spacing: 0.05em;"><?= e($coupon['code']) ?></strong>
                        <?php if ($coupon['minimum_order_amount']): ?>
                            <br><small class="text-muted">Min: $<?= number_format((float)$coupon['minimum_order_amount'], 2) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['description']): ?>
                            <?= e($coupon['description']) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['discount_type'] === 'percent'): ?>
                            <span class="status-badge" style="background: var(--primary-soft); color: var(--primary);">% Percentage</span>
                        <?php else: ?>
                            <span class="status-badge" style="background: var(--success-soft); color: var(--success);">$ Fixed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong>
                        <?php if ($coupon['discount_type'] === 'percent'): ?>
                            <?= (float) $coupon['discount_value'] ?>%
                            <?php if ($coupon['maximum_discount_amount']): ?>
                                <br><small class="text-muted">Max $<?= number_format((float)$coupon['maximum_discount_amount'], 2) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            $<?= number_format((float)$coupon['discount_value'], 2) ?>
                        <?php endif; ?>
                        </strong>
                    </td>
                    <td>
                        <?= (int) $coupon['used_count'] ?> / <?= $coupon['usage_limit'] ? (int) $coupon['usage_limit'] : '∞' ?>
                        <?php if ($coupon['total_discount']): ?>
                            <br><small class="text-muted">$<?= number_format((float)$coupon['total_discount'], 2) ?> saved</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['starts_at'] || $coupon['expires_at']): ?>
                            <?php if ($coupon['starts_at']): ?>
                                <small class="text-muted d-block">From: <?= date('M j, Y', strtotime($coupon['starts_at'])) ?></small>
                            <?php endif; ?>
                            <?php if ($coupon['expires_at']): ?>
                                <small class="text-muted d-block">To: <?= date('M j, Y', strtotime($coupon['expires_at'])) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <small class="text-muted">No time limit</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['is_active']): ?>
                            <span class="status-badge status-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="coupon-edit.php?id=<?= (int) $coupon['id'] ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="coupon_id" value="<?= (int) $coupon['id'] ?>">
                                <input type="hidden" name="action" value="toggle_active">
                                <button type="submit" class="btn <?= $coupon['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm" title="<?= $coupon['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi <?= $coupon['is_active'] ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="coupon_id" value="<?= (int) $coupon['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-tag" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.3;"></i>
                        No coupons found. <a href="coupon-edit.php" class="text-decoration-none">Create your first coupon</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
