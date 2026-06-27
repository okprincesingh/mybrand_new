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
           COUNT(cu.id) as usage_count,
           SUM(cu.discount_amount) as total_discount
    FROM coupons c 
    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
');
$stmt->execute();
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_layout_top.php';
?>
<div class="widget-card">
    <div class="widget-header">
        <h5 class="widget-title">All Coupons</h5>
        <div class="widget-actions">
            <a href="coupon-edit.php" class="btn btn-primary btn-sm">Add New Coupon</a>
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
                        <div>
                            <strong><?= e($coupon['code']) ?></strong>
                            <?php if ($coupon['minimum_order_amount']): ?>
                                <br><small class="text-muted">Min: $<?= number_format((float)$coupon['minimum_order_amount'], 2) ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= e($coupon['description'] ?? 'No description') ?></td>
                    <td>
                        <?php if ($coupon['discount_type'] === 'percent'): ?>
                            <span class="badge bg-primary">Percentage</span>
                        <?php else: ?>
                            <span class="badge bg-info">Fixed Amount</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['discount_type'] === 'percent'): ?>
                            <?= e($coupon['discount_value']) ?>%
                            <?php if ($coupon['maximum_discount_amount']): ?>
                                <br><small class="text-muted">Max: $<?= number_format((float)$coupon['maximum_discount_amount'], 2) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            $<?= number_format((float)$coupon['discount_value'], 2) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($coupon['usage_count']) ?> / <?= $coupon['usage_limit'] ? e($coupon['usage_limit']) : 'Unlimited' ?>
                        <?php if ($coupon['total_discount']): ?>
                            <br><small class="text-muted">Total: $<?= number_format((float)$coupon['total_discount'], 2) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['starts_at']): ?>
                            <small class="text-muted">From: <?= date('M j, Y', strtotime($coupon['starts_at'])) ?></small><br>
                        <?php endif; ?>
                        <?php if ($coupon['expires_at']): ?>
                            <small class="text-muted">To: <?= date('M j, Y', strtotime($coupon['expires_at'])) ?></small>
                        <?php else: ?>
                            <small class="text-muted">No expiry</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($coupon['is_active']): ?>
                            <span class="status-badge status-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-draft">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="coupon_id" value="<?= e($coupon['id']) ?>">
                                <input type="hidden" name="action" value="toggle_active">
                                <button type="submit" class="btn <?= $coupon['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm">
                                    <?= $coupon['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                <input type="hidden" name="coupon_id" value="<?= e($coupon['id']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No coupons found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>