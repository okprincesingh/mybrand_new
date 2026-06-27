<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Add Coupon';
$pdo = db();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percent';
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $minimum_order_amount = !empty($_POST['minimum_order_amount']) ? (float)$_POST['minimum_order_amount'] : null;
    $maximum_discount_amount = !empty($_POST['maximum_discount_amount']) ? (float)$_POST['maximum_discount_amount'] : null;
    $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
    $starts_at = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($code)) {
        admin_flash_set('error', 'Coupon code is required.');
        header('Location: coupon-edit.php');
        exit;
    }
    
    if (!in_array($discount_type, ['percent', 'fixed'])) {
        admin_flash_set('error', 'Invalid discount type.');
        header('Location: coupon-edit.php');
        exit;
    }
    
    if ($discount_value <= 0) {
        admin_flash_set('error', 'Discount value must be greater than 0.');
        header('Location: coupon-edit.php');
        exit;
    }
    
    if ($discount_type === 'percent' && $discount_value > 100) {
        admin_flash_set('error', 'Percentage discount cannot exceed 100%.');
        header('Location: coupon-edit.php');
        exit;
    }
    
    try {
        // Check if code already exists
        $stmt = $pdo->prepare('SELECT id FROM coupons WHERE code = :code');
        $stmt->execute([':code' => $code]);
        if ($stmt->fetch()) {
            admin_flash_set('error', 'Coupon code already exists.');
            header('Location: coupon-edit.php');
            exit;
        }
        
        // Insert coupon
        $stmt = $pdo->prepare('
            INSERT INTO coupons (
                code, description, discount_type, discount_value, 
                minimum_order_amount, maximum_discount_amount, 
                usage_limit, starts_at, expires_at, is_active
            ) VALUES (
                :code, :description, :discount_type, :discount_value,
                :minimum_order_amount, :maximum_discount_amount,
                :usage_limit, :starts_at, :expires_at, :is_active
            )
        ');
        
        $stmt->execute([
            ':code' => $code,
            ':description' => $description,
            ':discount_type' => $discount_type,
            ':discount_value' => $discount_value,
            ':minimum_order_amount' => $minimum_order_amount,
            ':maximum_discount_amount' => $maximum_discount_amount,
            ':usage_limit' => $usage_limit,
            ':starts_at' => $starts_at,
            ':expires_at' => $expires_at,
            ':is_active' => $is_active
        ]);
        
        admin_flash_set('success', 'Coupon created successfully.');
        header('Location: coupons.php');
        exit;
    } catch (Exception $e) {
        admin_flash_set('error', 'Failed to create coupon: ' . $e->getMessage());
        header('Location: coupon-edit.php');
        exit;
    }
}

include __DIR__ . '/_layout_top.php';
?>
<div class="widget-card">
    <div class="widget-header">
        <h5 class="widget-title">Add New Coupon</h5>
        <div class="widget-actions">
            <a href="coupons.php" class="btn btn-outline-secondary btn-sm">Back to Coupons</a>
        </div>
    </div>
    <div class="widget-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required maxlength="50" placeholder="e.g., SAVE10, WELCOME20">
                        <div class="form-text">Unique code customers will enter at checkout</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" maxlength="255" placeholder="e.g., 10% off for first-time customers">
                        <div class="form-text">Internal description for admin reference</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                        <select name="discount_type" class="form-select" required>
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>
                        <div class="form-text">Choose percentage or fixed amount discount</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                        <input type="number" name="discount_value" class="form-control" required min="0.01" step="0.01" placeholder="10 or 20.50">
                        <div class="form-text">For percentage: 10 = 10%, For fixed: 20.50 = $20.50</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Minimum Order Amount</label>
                        <input type="number" name="minimum_order_amount" class="form-control" min="0" step="0.01" placeholder="e.g., 50.00">
                        <div class="form-text">Leave empty for no minimum order requirement</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Maximum Discount Amount</label>
                        <input type="number" name="maximum_discount_amount" class="form-control" min="0" step="0.01" placeholder="e.g., 100.00">
                        <div class="form-text">Only for percentage discounts. Leave empty for no limit</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" min="1" placeholder="e.g., 100">
                        <div class="form-text">Total number of times this coupon can be used. Leave empty for unlimited</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Active Status</label>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                            <label class="form-check-label">Coupon is active and can be used</label>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" name="starts_at" class="form-control">
                        <div class="form-text">When the coupon becomes active. Leave empty for immediate activation</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="datetime-local" name="expires_at" class="form-control">
                        <div class="form-text">When the coupon expires. Leave empty for no expiry</div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Coupon</button>
                <a href="coupons.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
