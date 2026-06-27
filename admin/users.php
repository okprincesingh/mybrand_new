<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Users';
$pdo = db();
$searchFilter = trim((string)($_GET['search'] ?? ''));
// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        try {
            switch ($action) {
                case 'toggle_active':
                    $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = :id');
                    $stmt->execute([':id' => $userId]);
                    $current = $stmt->fetchColumn();
                    if ($current !== false) {
                        $newStatus = $current ? 0 : 1;
                        $stmt = $pdo->prepare('UPDATE users SET is_active = :status WHERE id = :id');
                        $stmt->execute([':status' => $newStatus, ':id' => $userId]);
                        admin_flash_set('success', 'User status updated successfully.');
                    }
                    break;

                case 'update_profile':
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName = trim($_POST['last_name'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $dob = $_POST['date_of_birth'] ?? null;
                    $gender = $_POST['gender'] ?? null;

                    if (empty($firstName) || empty($lastName)) {
                        admin_flash_set('error', 'First name and last name are required.');
                    } else {
                        $stmt = $pdo->prepare('UPDATE users SET first_name = :fn, last_name = :ln, phone = :phone, date_of_birth = :dob, gender = :gender, updated_at = NOW() WHERE id = :id');
                        $stmt->execute([
                            ':fn' => $firstName,
                            ':ln' => $lastName,
                            ':phone' => $phone ?: null,
                            ':dob' => $dob ?: null,
                            ':gender' => $gender ?: null,
                            ':id' => $userId
                        ]);
                        admin_flash_set('success', 'User profile updated successfully.');
                    }
                    break;

                case 'reset_password':
                    $tempPassword = bin2hex(random_bytes(4));
                    $tempHash = password_hash($tempPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
                    $stmt->execute([':hash' => $tempHash, ':id' => $userId]);
                    admin_flash_set('success', 'Password reset. Temporary password: ' . $tempPassword);
                    break;
            }
        } catch (Exception $e) {
            admin_flash_set('error', 'Error: ' . $e->getMessage());
        }
    }

    header('Location: users.php');
    exit;
}

// Get all users with additional stats
$users = [];
$stmt = $pdo->query('
    SELECT u.*,
           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = (SELECT c.id FROM customers c WHERE c.email COLLATE utf8mb4_unicode_ci = u.email COLLATE utf8mb4_unicode_ci LIMIT 1) OR o.user_id = u.id) as order_count,
           (SELECT MAX(created_at) FROM user_sessions WHERE user_id = u.id) as last_login
    FROM users u
    ORDER BY u.created_at DESC
');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats = $pdo->query('
    SELECT
        COUNT(*) as total_users,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users
    FROM users
')->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/_layout_top.php';
?>
<div class="row mb-4">
    <div class="col-md-4">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Total Users</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0"><?= (int)$stats['total_users'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Active Users</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-success"><?= (int)$stats['active_users'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-card">
            <div class="widget-header">
                <h6 class="widget-title">Inactive Users</h6>
            </div>
            <div class="widget-body text-center">
                <h2 class="mb-0 text-danger"><?= (int)$stats['inactive_users'] ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="widget-card">
    <div class="widget-header">
        <h5 class="widget-title">All Users</h5>
        <div class="widget-actions d-flex gap-2 align-items-center">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name/email/id" value="<?= e($searchFilter) ?>">
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                <a href="users.php" class="btn btn-sm btn-secondary">Clear</a>
            </form>
            <span class="text-muted">Total: <?= count($users) ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Orders</th>
                    <th>Last Login</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?= e($user['id']) ?></td>
                    <td>
                        <div>
                            <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                            <?php if (!empty($user['gender'])): ?>
                                <br><small class="text-muted"><i class="bi bi-<?= e($user['gender'] === 'male' ? 'person' : ($user['gender'] === 'female' ? 'person-fill-dress' : 'person-fill')) ?>"></i> <?= e(ucfirst($user['gender'])) ?></small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <a href="mailto:<?= e($user['email']) ?>"><?= e($user['email']) ?></a>
                        <?php if (!empty($user['email_verified_at'])): ?>
                            <br><small class="text-success"><i class="bi bi-check-circle"></i> Verified</small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($user['phone'] ?? '-') ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="status-badge status-active">Active</span>
                        <?php else: ?>
                            <span class="status-badge status-draft">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$user['order_count'] > 0): ?>
                            <span class="badge bg-primary"><?= (int)$user['order_count'] ?> orders</span>
                        <?php else: ?>
                            <span class="text-muted">No orders</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($user['last_login'])): ?>
                            <small><?= date('M j, Y', strtotime($user['last_login'])) ?></small>
                            <br><small class="text-muted"><?= date('g:i A', strtotime($user['last_login'])) ?></small>
                        <?php else: ?>
                            <span class="text-muted">Never</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="viewUser(<?= e($user['id']) ?>)" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="editUser(<?= e($user['id']) ?>)" title="Edit Profile">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-<?= $user['is_active'] ? 'warning' : 'success' ?>" onclick="toggleUserStatus(<?= e($user['id']) ?>, <?= $user['is_active'] ? 1 : 0 ?>)" title="<?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="bi bi-<?= $user['is_active'] ? 'lock' : 'unlock' ?>"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted">No users found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <div class="text-center"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="editLastName" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" id="editPhone">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" id="editDob">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender" id="editGender">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div id="editStatus"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    document.getElementById('viewUserContent').innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('api/user-details.php?id=' + userId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.user;
                const addresses = data.addresses || [];
                const orders = data.orders || [];

                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Personal Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">ID:</td><td>#${u.id}</td></tr>
                                <tr><td class="text-muted">Name:</td><td>${u.first_name} ${u.last_name}</td></tr>
                                <tr><td class="text-muted">Email:</td><td>${u.email} ${u.email_verified_at ? '<span class="text-success"><i class="bi bi-check-circle"></i> Verified</span>' : ''}</td></tr>
                                <tr><td class="text-muted">Phone:</td><td>${u.phone || '-'}</td></tr>
                                <tr><td class="text-muted">Gender:</td><td>${u.gender ? u.gender.charAt(0).toUpperCase() + u.gender.slice(1) : '-'}</td></tr>
                                <tr><td class="text-muted">Date of Birth:</td><td>${u.date_of_birth || '-'}</td></tr>
                                <tr><td class="text-muted">Age:</td><td>${u.date_of_birth ? new Date().getFullYear() - new Date(u.date_of_birth).getFullYear() : '-'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Account Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td class="text-muted">Status:</td><td><span class="badge ${u.is_active ? 'bg-success' : 'bg-danger'}">${u.is_active ? 'Active' : 'Inactive'}</span></td></tr>
                                <tr><td class="text-muted">Joined:</td><td>${new Date(u.created_at).toLocaleDateString()}</td></tr>
                                <tr><td class="text-muted">Last Login:</td><td>${u.last_login ? new Date(u.last_login).toLocaleString() : 'Never'}</td></tr>
                                <tr><td class="text-muted">Total Orders:</td><td><span class="badge bg-primary">${u.order_count || 0}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <h6>Addresses (${addresses.length})</h6>
                    ${addresses.length > 0 ? addresses.map(a => `
                        <div class="card mb-2">
                            <div class="card-body py-2">
                                <strong>${a.first_name} ${a.last_name}</strong>
                                ${a.is_default ? '<span class="badge bg-info ms-2">Default</span>' : ''}
                                <br>${a.address1}${a.address2 ? ', ' + a.address2 : ''}
                                <br>${a.city}, ${a.state} ${a.zip}
                                <br>${a.country}
                            </div>
                        </div>
                    `).join('') : '<p class="text-muted">No addresses saved</p>'}
                    <hr>
                    <h6>Recent Orders (${orders.length})</h6>
                    ${orders.length > 0 ? `
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Order #</th><th>Date</th><th>Status</th><th>Amount</th></tr>
                                </thead>
                                <tbody>
                                    ${orders.map(o => `
                                        <tr>
                                            <td>
                                                <a href="orders.php?order=${encodeURIComponent(o.order_number)}" class="fw-semibold text-decoration-none" title="View full order details">
                                                    ${o.order_number}
                                                </a>
                                            </td>
                                            <td>${new Date(o.created_at).toLocaleDateString()}</td>
                                            <td><span class="badge bg-${getStatusClass(o.status)}">${o.status}</span></td>
                                            <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    ` : '<p class="text-muted">No orders yet</p>'}
                `;
                document.getElementById('viewUserContent').innerHTML = html;
            } else {
                document.getElementById('viewUserContent').innerHTML = '<div class="alert alert-danger">Error loading user data</div>';
            }
        })
        .catch(() => {
            document.getElementById('viewUserContent').innerHTML = '<div class="alert alert-danger">Error loading user data</div>';
        });
}

function editUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    document.getElementById('editUserId').value = userId;
    modal.show();

    fetch('api/user-details.php?id=' + userId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.user;
                document.getElementById('editFirstName').value = u.first_name;
                document.getElementById('editLastName').value = u.last_name;
                document.getElementById('editEmail').value = u.email;
                document.getElementById('editPhone').value = u.phone || '';
                document.getElementById('editDob').value = u.date_of_birth || '';
                document.getElementById('editGender').value = u.gender || '';
                document.getElementById('editStatus').innerHTML = `<span class="badge ${u.is_active ? 'bg-success' : 'bg-danger'}">${u.is_active ? 'Active' : 'Inactive'}</span>`;
            }
        });
}

function toggleUserStatus(userId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this user?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">            <input type="hidden" name="action" value="toggle_active">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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
</script>

<?php include __DIR__ . '/_layout_bottom.php'; ?>


