<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Orders';
$pdo = db();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $action = $_POST['action'] ?? 'update_status';
    $newStatus = $_POST['status'] ?? null;
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($action === 'update_status' && $newStatus) {
            $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
            if (!in_array($newStatus, $validStatuses)) {
                admin_flash_set('error', 'Invalid order status.');
                header('Location: orders.php');
                exit;
            }

            $pdo->beginTransaction();

            // Get current status
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
            $stmt->execute([':id' => $orderId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus === false) {
                throw new Exception('Order not found.');
            }

            // Update order status
            $stmt = $pdo->prepare('UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $orderId]);

            // Log status change
            $stmt = $pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, notes, created_by) VALUES (:order_id, :old_status, :new_status, :notes, :created_by)');
            $stmt->execute([
                ':order_id' => $orderId,
                ':old_status' => $currentStatus,
                ':new_status' => $newStatus,
                ':notes' => $notes,
                ':created_by' => $adminUser['id'] ?? null
            ]);

            $pdo->commit();
            admin_flash_set('success', 'Order status updated successfully.');

        } elseif ($action === 'update_payment') {
            $paymentStatus = $_POST['payment_status'] ?? null;
            $validPaymentStatuses = ['pending', 'paid', 'failed', 'refunded'];

            if ($paymentStatus && in_array($paymentStatus, $validPaymentStatuses)) {
                $stmt = $pdo->prepare('UPDATE orders SET payment_status = :status, updated_at = NOW() WHERE id = :id');
                $stmt->execute([':status' => $paymentStatus, ':id' => $orderId]);
                admin_flash_set('success', 'Payment status updated successfully.');
            }
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        admin_flash_set('error', 'Failed to update: ' . $e->getMessage());
    }

    header('Location: orders.php');
    exit;
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$openOrderId = 0;
$openOrderNumber = trim((string) ($_GET['order'] ?? ''));
if ($openOrderNumber !== '') {
    $orderLookup = $pdo->prepare('SELECT id FROM orders WHERE order_number = :order_number LIMIT 1');
    $orderLookup->execute([':order_number' => $openOrderNumber]);
    $openOrderId = (int) ($orderLookup->fetchColumn() ?: 0);
}

// Build query
$where = [];
$params = [];

if ($statusFilter) {
    $where[] = 'o.status = :status';
    $params[':status'] = $statusFilter;
}

if ($dateFilter) {
    $where[] = 'DATE(o.created_at) = :date';
    $params[':date'] = $dateFilter;
}

if ($searchFilter) {
    $where[] = '(o.order_number LIKE :search OR c.email LIKE :search2 OR c.first_name LIKE :search3 OR c.last_name LIKE :search4)';
    $params[':search'] = '%' . $searchFilter . '%';
    $params[':search2'] = '%' . $searchFilter . '%';
    $params[':search3'] = '%' . $searchFilter . '%';
    $params[':search4'] = '%' . $searchFilter . '%';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get all orders with customer info
$orders = [];
$stmt = $pdo->prepare("
    SELECT o.*,
           c.first_name as customer_first_name,
           c.last_name as customer_last_name,
           c.email as customer_email,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    $whereClause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats = $pdo->query('
    SELECT
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = \'processing\' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status IN (\'shipped\', \'delivered\') THEN 1 ELSE 0 END) as completed_orders,
        COALESCE(SUM(CASE WHEN payment_status = \'paid\' THEN total_amount ELSE 0 END), 0) as total_revenue
    FROM orders
')->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/_layout_top.php';
?>
<div class="row mb-4">
    <div class="col-md-2">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Total Orders</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0"><?= (int)$stats['total_orders'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Pending</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-warning"><?= (int)$stats['pending_orders'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Processing</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-info"><?= (int)$stats['processing_orders'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Completed</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-success"><?= (int)$stats['completed_orders'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Total Revenue</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-primary">$<?= number_format((float)$stats['total_revenue'], 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="widget-card">
    <div class="widget-header">
        <h5 class="widget-title">All Orders</h5>
        <div class="widget-actions d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    <option value="refunded" <?= $statusFilter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                </select>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($dateFilter) ?>" onchange="this.form.submit()">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= e($searchFilter) ?>">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="orders.php" class="btn btn-sm btn-secondary">Clear</a>
            </form>
            <span class="text-muted">Total: <?= count($orders) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <div>
                            <strong><?= e($order['order_number']) ?></strong>
                            <br><small class="text-muted"><?= e($order['payment_method']) ?></small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <?= e($order['customer_first_name'] . ' ' . $order['customer_last_name']) ?>
                            <br><small class="text-muted"><?= e($order['customer_email']) ?></small>
                        </div>
                    </td>
                    <td>
                        <?= date('M j, Y', strtotime($order['created_at'])) ?>
                        <br><small class="text-muted"><?= date('g:i A', strtotime($order['created_at'])) ?></small>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= (int)$order['item_count'] ?> items</span>
                    </td>
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
                    <td>
                        <?php
                        $paymentClass = '';
                        switch ($order['payment_status']) {
                            case 'pending': $paymentClass = 'status-pending'; break;
                            case 'paid': $paymentClass = 'status-active'; break;
                            case 'failed': $paymentClass = 'status-draft'; break;
                            case 'refunded': $paymentClass = 'status-refunded'; break;
                        }
                        ?>
                        <span class="status-badge <?= e($paymentClass) ?>"><?= e(ucfirst($order['payment_status'])) ?></span>
                    </td>
                    <td>
                        <strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <br><small class="text-success">- $<?= number_format($order['discount_amount'], 2) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewOrder(<?= e($order['id']) ?>)">
                            <i class="bi bi-eye"></i> View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No orders found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewOrderContent">
                <div class="text-center"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modalOrderId">
                    <input type="hidden" name="action" value="update_status">

                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select name="status" class="form-select" id="modalOrderStatus" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add notes about this status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    document.getElementById('viewOrderContent').innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('api/order-details.php?id=' + orderId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const o = data.order;
                const items = data.items || [];
                const history = data.history || [];

                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-receipt"></i> Order Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Order Number:</td><td><strong>${o.order_number}</strong></td></tr>
                                <tr><td class="text-muted">Order Date:</td><td>${new Date(o.created_at).toLocaleString()}</td></tr>
                                <tr><td class="text-muted">Status:</td><td><span class="badge bg-${getStatusClass(o.status)}">${o.status}</span></td></tr>
                                <tr><td class="text-muted">Payment Status:</td><td><span class="badge bg-${getPaymentClass(o.payment_status)}">${o.payment_status}</span></td></tr>
                                <tr><td class="text-muted">Payment Method:</td><td>${o.payment_method}</td></tr>
                                <tr><td class="text-muted">Transaction ID:</td><td>${o.transaction_id || '-'}</td></tr>
                                <tr><td class="text-muted">Currency:</td><td>${o.currency || 'USD'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-person"></i> Customer Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Name:</td><td>${o.customer_first_name} ${o.customer_last_name}</td></tr>
                                <tr><td class="text-muted">Email:</td><td><a href="mailto:${o.customer_email}">${o.customer_email}</a></td></tr>
                                ${o.billing_phone ? `<tr><td class="text-muted">Phone:</td><td>${o.billing_phone}</td></tr>` : ''}
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-credit-card"></i> Billing Address</h6>
                            <div class="card card-body">
                                ${o.billing_first_name} ${o.billing_last_name}<br>
                                ${o.billing_company ? o.billing_company + '<br>' : ''}
                                ${o.billing_address1}<br>
                                ${o.billing_address2 ? o.billing_address2 + '<br>' : ''}
                                ${o.billing_city}, ${o.billing_state} ${o.billing_zip}<br>
                                ${o.billing_country}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-truck"></i> Shipping Address</h6>
                            <div class="card card-body">
                                ${o.shipping_first_name ? o.shipping_first_name + ' ' + o.shipping_last_name + '<br>' : ''}
                                ${o.shipping_company ? o.shipping_company + '<br>' : ''}
                                ${o.shipping_address1 || 'Same as billing'}<br>
                                ${o.shipping_address2 ? o.shipping_address2 + '<br>' : ''}
                                ${o.shipping_city ? o.shipping_city + ', ' + o.shipping_state + ' ' + o.shipping_zip + '<br>' : ''}
                                ${o.shipping_country || 'Same as billing'}
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6><i class="bi bi-box"></i> Order Items (${items.length})</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Image</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => `
                                    <tr>
                                        <td>
                                            <strong>${item.product_name}</strong>
                                            <br><small class="text-muted">${item.product_slug}</small>
                                        </td>
                                        <td>
                                            ${item.product_image ? `<img src="../${item.product_image}" alt="${item.product_name}" style="width: 50px; height: 50px; object-fit: cover;">` : '<span class="text-muted">No image</span>'}
                                        </td>
                                        <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                                        <td>${item.quantity}</td>
                                        <td><strong>$${parseFloat(item.total_price).toFixed(2)}</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-clock-history"></i> Status History</h6>
                            ${history.length > 0 ? `
                                <ul class="list-group">
                                    ${history.map(h => `
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <strong>${h.old_status || 'New'} &rarr; <span class="text-${getStatusClass(h.new_status)}">${h.new_status}</span></strong>
                                                <small class="text-muted">${new Date(h.created_at).toLocaleString()}</small>
                                            </div>
                                            ${h.notes ? `<small class="text-muted d-block mt-1">${h.notes}</small>` : ''}
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : '<p class="text-muted">No status history</p>'}
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-calculator"></i> Order Summary</h6>
                            <table class="table table-sm">
                                <tr><td class="text-muted">Subtotal:</td><td class="text-end">$${parseFloat(o.subtotal).toFixed(2)}</td></tr>
                                ${o.shipping_cost > 0 ? `<tr><td class="text-muted">Shipping:</td><td class="text-end">$${parseFloat(o.shipping_cost).toFixed(2)}</td></tr>` : ''}
                                ${o.discount_amount > 0 ? `<tr><td class="text-muted">Discount:</td><td class="text-end text-success">-$${parseFloat(o.discount_amount).toFixed(2)}</td></tr>` : ''}
                                ${o.tax_amount > 0 ? `<tr><td class="text-muted">Tax:</td><td class="text-end">$${parseFloat(o.tax_amount).toFixed(2)}</td></tr>` : ''}
                                <tr class="table-primary">
                                    <td><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$${parseFloat(o.total_amount).toFixed(2)}</strong></td>
                                </tr>
                            </table>
                            ${o.notes ? `<div class="alert alert-info"><strong>Customer Notes:</strong><br>${o.notes}</div>` : ''}
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-outline-primary" onclick="openUpdateStatusModal(${o.id}, '${o.status}')">
                            <i class="bi bi-pencil"></i> Update Status
                        </button>
                        <div>
                            ${(String(o.payment_method || '').toLowerCase() === 'stripe' && o.payment_status === 'paid' && o.transaction_id) ? `
                                <button class="btn btn-outline-danger me-2" onclick="refundOrder(${o.id})">
                                    <i class="bi bi-arrow-counterclockwise"></i> Refund
                                </button>
                            ` : ''}
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="order_id" value="${o.id}">
                                <input type="hidden" name="action" value="update_payment">
                                <select name="payment_status" class="form-select d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                    <option value="pending" ${o.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="paid" ${o.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
                                    <option value="failed" ${o.payment_status === 'failed' ? 'selected' : ''}>Failed</option>
                                    <option value="refunded" ${o.payment_status === 'refunded' ? 'selected' : ''}>Refunded</option>
                                </select>
                            </form>
                        </div>
                    </div>
                `;
                document.getElementById('viewOrderContent').innerHTML = html;
            } else {
                document.getElementById('viewOrderContent').innerHTML = '<div class="alert alert-danger">Error loading order data: ' + (data.message || 'Unknown error') + '</div>';
            }
        })
        .catch(err => {
            document.getElementById('viewOrderContent').innerHTML = '<div class="alert alert-danger">Error loading order data</div>';
            console.error(err);
        });
}

function openUpdateStatusModal(orderId, currentStatus) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalOrderStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function getStatusClass(status) {
    const classes = {
        'pending': 'warning',
        'processing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'cancelled': 'danger',
        'refunded': 'secondary'
    };
    return classes[status] || 'secondary';
}

function getPaymentClass(status) {
    const classes = {
        'pending': 'warning',
        'paid': 'success',
        'failed': 'danger',
        'refunded': 'secondary'
    };
    return classes[status] || 'secondary';
}

<?php if ((int) $openOrderId > 0): ?>
document.addEventListener('DOMContentLoaded', function () {
    viewOrder(<?= (int) $openOrderId ?>);
});
<?php endif; ?>

async function refundOrder(orderId) {
    const fullRefund = confirm('Click OK for full refund. Click Cancel to set custom amount.');
    let amount = null;
    if (!fullRefund) {
        const entered = prompt('Enter refund amount (leave blank to cancel):');
        if (entered === null || String(entered).trim() === '') {
            return;
        }
        amount = Number(entered);
        if (!Number.isFinite(amount) || amount <= 0) {
            alert('Invalid refund amount.');
            return;
        }
    }

    try {
        const response = await fetch('api/refund-payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= e(csrf_token()) ?>'
            },
            body: JSON.stringify({ order_id: Number(orderId), amount: amount })
        });
        const data = await response.json();
        if (data.success) {
            alert('Refund created successfully.');
            window.location.reload();
            return;
        }
        alert(data.message || 'Refund failed.');
    } catch (error) {
        alert('Refund request failed.');
    }
}</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>







