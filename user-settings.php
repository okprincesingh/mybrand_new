<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();
$activeSessions = user_get_active_session_count((int) $user['id']);
$isEmailVerified = !empty($user['email_verified_at']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } else {
            $result = user_change_password((int) $user['id'], $currentPassword, $newPassword);
            if ($result && $result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'delete_account') {
        $confirmEmail = trim($_POST['confirm_email'] ?? '');
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $latestUser = user_get_by_id((int) $user['id']);
        $passwordHash = (string) ($latestUser['password_hash'] ?? $user['password_hash'] ?? '');

        if ($confirmEmail !== $user['email'] || empty($confirmPassword)) {
            $error = 'Please confirm your email and password to delete your account';
        } elseif ($passwordHash === '' || !password_verify($confirmPassword, $passwordHash)) {
            $error = 'Incorrect password';
        } else {
            // In a real implementation, you would mark the account as deleted rather than actually deleting it
            // For now, we'll just show a success message
            $success = 'Account deletion would be processed here';
        }
    }
}

$meta = [
    'title' => 'Mybrandplease | Settings',
    'description' => 'Manage your account settings',
    'canonical' => 'user-settings.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-settings.css'); ?>">

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">Account Settings</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">Account Settings</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-settings section-spacing-120">
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
                            <li class="nav-item">
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
                            <li class="nav-item active">
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
                    <h1>Account Settings</h1>
                    <p>Manage your account security and preferences</p>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-regular fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fa-regular fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <!-- Change Password -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2>Change Password</h2>
                    </div>
                    
                    <form method="post" class="settings-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="password-strength"></div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-match" id="password-match"></div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </div>
                    </form>
                </div>

                <!-- Security Information -->
                <div class="security-card">
                    <div class="card-header">
                        <h2>Security Information</h2>
                    </div>
                    
                    <div class="security-info-grid">
                        <div class="security-item">
                            <div class="security-icon">
                                <i class="fa-regular fa-shield-check"></i>
                            </div>
                            <div class="security-content">
                                <h4>Password</h4>
                                <p>Last changed: <?php echo date('F j, Y', strtotime($user['updated_at'])); ?></p>
                                <span class="security-status verified">Strong</span>
                            </div>
                        </div>
                        
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

                        

                        
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="account-actions-card">
                    <div class="card-header">
                        <h2>Account Actions</h2>
                    </div>
                    
                    <div class="actions-grid">
                        <div class="action-item">
                            <div class="action-icon">
                                <i class="fa-regular fa-download"></i>
                            </div>
                            <div class="action-content">
                                <h4>Download Data</h4>
                                <p>Download a copy of your personal data</p>
                                <a href="#" class="action-link">Request Data Export</a>
                            </div>
                        </div>

                        <div class="action-item">
                            <div class="action-icon">
                                <i class="fa-regular fa-trash"></i>
                            </div>
                            <div class="action-content">
                                <h4>Delete Account</h4>
                                <p>Permanently delete your account and all data</p>
                                <button class="action-link action-link--danger" onclick="showDeleteModal()">Delete Account</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>

<!-- Delete Account Modal -->
<div id="delete-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Account</h3>
            <button class="modal-close" onclick="hideDeleteModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <p class="modal-warning">Warning: This action cannot be undone. All your data will be permanently deleted.</p>
            
            <form method="post" id="delete-form">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="form-group">
                    <label for="confirm_email">Confirm your email address</label>
                    <input type="email" id="confirm_email" name="confirm_email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Enter your password</label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleBtn = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}

// Password strength indicator
const passwordInput = document.getElementById('new_password');
const passwordStrength = document.getElementById('password-strength');

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        const value = this.value;
        let strength = 'weak';
        let text = 'Weak';
        
        if (value.length >= 8 && /[A-Z]/.test(value) && /[0-9]/.test(value) && /[^A-Za-z0-9]/.test(value)) {
            strength = 'strong';
            text = 'Strong';
        } else if (value.length >= 6 && /[A-Z]/.test(value) && /[0-9]/.test(value)) {
            strength = 'medium';
            text = 'Medium';
        }
        
        passwordStrength.className = 'password-strength ' + strength;
        passwordStrength.textContent = 'Password strength: ' + text;
    });
}

// Password match indicator
const confirmPasswordInput = document.getElementById('confirm_password');
const passwordMatch = document.getElementById('password-match');

if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword === '') {
            passwordMatch.className = 'password-match';
            passwordMatch.textContent = '';
        } else if (password === confirmPassword) {
            passwordMatch.className = 'password-match match';
            passwordMatch.textContent = 'Passwords match';
        } else {
            passwordMatch.className = 'password-match no-match';
            passwordMatch.textContent = 'Passwords do not match';
        }
    });
}

function showDeleteModal() {
    document.getElementById('delete-modal').style.display = 'flex';
}

function hideDeleteModal() {
    document.getElementById('delete-modal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideDeleteModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
