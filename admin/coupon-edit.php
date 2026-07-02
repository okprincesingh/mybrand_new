<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Coupon';
$pdo = db();
$couponId = (int) ($_GET['id'] ?? $_POST['coupon_id'] ?? 0);
$isEdit = $couponId > 0;
$coupon = [
    'id' => 0,
    'code' => '',
    'description' => '',
    'discount_type' => 'percent',
    'discount_value' => '',
    'minimum_order_amount' => '',
    'maximum_discount_amount' => '',
    'usage_limit' => '',
    'starts_at' => '',
    'expires_at' => '',
    'is_active' => 1,
];

if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $couponId]);
    $loadedCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$loadedCoupon) {
        admin_flash_set('error', 'Coupon not found.');
        header('Location: coupons.php');
        exit;
    }
    $coupon = array_merge($coupon, $loadedCoupon);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
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
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }
    
    if (!in_array($discount_type, ['percent', 'fixed'])) {
        admin_flash_set('error', 'Invalid discount type.');
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }
    
    if ($discount_value <= 0) {
        admin_flash_set('error', 'Discount value must be greater than 0.');
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }
    
    if ($discount_type === 'percent' && $discount_value > 100) {
        admin_flash_set('error', 'Percentage discount cannot exceed 100%.');
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }

    if ($starts_at && $expires_at && strtotime($expires_at) <= strtotime($starts_at)) {
        admin_flash_set('error', 'Expiry date must be after start date.');
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }
    
    try {
        // Check if code already exists
        $stmt = $pdo->prepare('SELECT id FROM coupons WHERE code = :code AND id <> :id');
        $stmt->execute([':code' => $code, ':id' => $couponId]);
        if ($stmt->fetch()) {
            admin_flash_set('error', 'Coupon code already exists.');
            header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
            exit;
        }
        
        if ($isEdit) {
            $stmt = $pdo->prepare('
                UPDATE coupons SET
                    code = :code,
                    description = :description,
                    discount_type = :discount_type,
                    discount_value = :discount_value,
                    minimum_order_amount = :minimum_order_amount,
                    maximum_discount_amount = :maximum_discount_amount,
                    usage_limit = :usage_limit,
                    starts_at = :starts_at,
                    expires_at = :expires_at,
                    is_active = :is_active
                WHERE id = :id
            ');
        } else {
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
        }
        
        $params = [
            ':code' => $code,
            ':description' => $description !== '' ? $description : null,
            ':discount_type' => $discount_type,
            ':discount_value' => $discount_value,
            ':minimum_order_amount' => $minimum_order_amount,
            ':maximum_discount_amount' => $maximum_discount_amount,
            ':usage_limit' => $usage_limit,
            ':starts_at' => $starts_at,
            ':expires_at' => $expires_at,
            ':is_active' => $is_active
        ];
        if ($isEdit) {
            $params[':id'] = $couponId;
        }
        $stmt->execute($params);
        
        admin_flash_set('success', $isEdit ? 'Coupon updated successfully.' : 'Coupon created successfully.');
        header('Location: coupons.php');
        exit;
    } catch (Exception $e) {
        admin_flash_set('error', 'Failed to save coupon: ' . $e->getMessage());
        header('Location: coupon-edit.php' . ($isEdit ? '?id=' . $couponId : ''));
        exit;
    }
}

function coupon_datetime_local($value): string
{
    if (empty($value)) {
        return '';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
}

include __DIR__ . '/_layout_top.php';
?>
<div class="form-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><?= $isEdit ? 'Edit Coupon' : 'Add New Coupon' ?></h5>
        <a href="coupons.php" class="btn btn-secondary-modern btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Coupons</a>
    </div>

    <form method="POST" style="display:flex;flex-direction:column;gap:0.5rem;">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="coupon_id" value="<?= (int) $couponId ?>">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            <div class="form-group">
                <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control" required maxlength="50" placeholder="e.g., SAVE10, WELCOME20" value="<?= e((string) $coupon['code']) ?>">
                <small class="form-text">Unique code customers will enter at checkout</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" maxlength="255" placeholder="e.g., 10% off for first-time customers" value="<?= e((string) ($coupon['description'] ?? '')) ?>">
                <small class="form-text">Internal description for admin reference</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                <select name="discount_type" class="form-select" required>
                    <option value="percent" <?= (string) $coupon['discount_type'] === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
                    <option value="fixed" <?= (string) $coupon['discount_type'] === 'fixed' ? 'selected' : '' ?>>Fixed Amount ($)</option>
                </select>
                <small class="form-text">Choose percentage or fixed amount discount</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Discount Value <span class="text-danger">*</span></label>
                <input type="number" name="discount_value" class="form-control" required min="0.01" step="0.01" placeholder="10 or 20.50" value="<?= e((string) $coupon['discount_value']) ?>">
                <small class="form-text">For percentage: 10 = 10%, For fixed: 20.50 = $20.50</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Minimum Order Amount</label>
                <input type="number" name="minimum_order_amount" class="form-control" min="0" step="0.01" placeholder="e.g., 50.00" value="<?= e((string) ($coupon['minimum_order_amount'] ?? '')) ?>">
                <small class="form-text">Leave empty for no minimum order requirement</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Maximum Discount Amount</label>
                <input type="number" name="maximum_discount_amount" class="form-control" min="0" step="0.01" placeholder="e.g., 100.00" value="<?= e((string) ($coupon['maximum_discount_amount'] ?? '')) ?>">
                <small class="form-text">Only for percentage discounts. Leave empty for no limit</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Usage Limit</label>
                <input type="number" name="usage_limit" class="form-control" min="1" placeholder="e.g., 100" value="<?= e((string) ($coupon['usage_limit'] ?? '')) ?>">
                <small class="form-text">Total number of times this coupon can be used. Leave empty for unlimited</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Active Status</label>
                <div class="form-check" style="margin-top:0.35rem;">
                    <input type="checkbox" name="is_active" class="form-check-input" value="1" id="isActive" <?= (int) ($coupon['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Coupon is active and can be used</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="datetime-local" name="starts_at" class="form-control" value="<?= e(coupon_datetime_local($coupon['starts_at'] ?? '')) ?>">
                <small class="form-text">When the coupon becomes active. Leave empty for immediate activation</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Expiry Date</label>
                <input type="datetime-local" name="expires_at" class="form-control" value="<?= e(coupon_datetime_local($coupon['expires_at'] ?? '')) ?>">
                <small class="form-text">When the coupon expires. Leave empty for no expiry</small>
            </div>
        </div>
        
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary-modern"><?= $isEdit ? 'Update Coupon' : 'Create Coupon' ?></button>
            <a href="coupons.php" class="btn btn-secondary-modern">Cancel</a>
        </div>
    </form>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
