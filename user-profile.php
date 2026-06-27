<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required';
    } else {
        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'date_of_birth' => !empty($dateOfBirth) ? $dateOfBirth : null,
            'gender' => in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say']) ? $gender : null,
        ];

        if (user_update_profile((int) $user['id'], $data)) {
            $success = 'Profile updated successfully';
            // Refresh user data
            $user = user_get_by_id((int) $user['id']);
        } else {
            $error = 'Failed to update profile';
        }
    }
}

$lastLoginAt = user_get_last_login_at((int) $user['id']);
$isEmailVerified = !empty($user['email_verified_at']);
$accountStatus = ((int) ($user['is_active'] ?? 0) === 1) ? 'Active' : 'Inactive';

$meta = [
    'title' => 'Mybrandplease | Profile',
    'description' => 'Manage your profile information',
    'canonical' => 'user-profile.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-profile.css'); ?>">

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">My Profile</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">My Profile</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-profile section-spacing-120">
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
                            <li class="nav-item active">
                                <a href="<?php echo url('user-profile.php'); ?>">
                                    <i class="fa-regular fa-user"></i>
                                    Profile
                                </a>
                            </li>
                            <li class="nav-item">
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
                    <h1>My Profile</h1>
                    <p>Manage your personal information</p>
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

                <div class="profile-card">
                    <div class="card-header">
                        <h2>Personal Information</h2>
                    </div>
                    
                    <form method="post" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="form-hint">To change your email address, please contact support</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter your phone number">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select gender</option>
                                    <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    <option value="prefer_not_to_say" <?php echo ($user['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="member_since">Member Since</label>
                            <input type="text" id="member_since" name="member_since" value="<?php echo !empty($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="last_updated">Last Updated</label>
                            <input type="text" id="last_updated" name="last_updated" value="<?php echo !empty($user['updated_at']) ? date('F j, Y \a\t g:i A', strtotime($user['updated_at'])) : 'N/A'; ?>" disabled>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <a href="<?php echo url('user-settings.php'); ?>" class="btn btn-secondary">Change Password</a>
                        </div>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="account-info-card">
                    <div class="card-header">
                        <h2>Account Information</h2>
                    </div>
                    
                    <div class="account-info-grid">
                        <div class="info-item">
                            <span class="info-label">Account Status</span>
                            <span class="info-value"><?php echo $accountStatus; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Verified</span>
                            <span class="info-value <?php echo $isEmailVerified ? 'info-value--verified' : 'info-value--unverified'; ?>">
                                <?php echo $isEmailVerified ? 'Yes' : 'No'; ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Created</span>
                            <span class="info-value"><?php echo !empty($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Login</span>
                            <span class="info-value"><?php echo $lastLoginAt ? date('F j, Y \a\t g:i A', strtotime($lastLoginAt)) : 'No login yet'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="quick-links-card">
                    <div class="card-header">
                        <h2>Quick Links</h2>
                    </div>
                    
                    <div class="quick-links-grid">
                        <a href="<?php echo url('user-addresses.php'); ?>" class="quick-link">
                            <i class="fa-regular fa-map-marker-alt"></i>
                            <div class="quick-link-content">
                                <h4>Manage Addresses</h4>
                                <p>Update your shipping and billing addresses</p>
                            </div>
                        </a>
                        <a href="<?php echo url('user-orders.php'); ?>" class="quick-link">
                            <i class="fa-regular fa-shopping-bag"></i>
                            <div class="quick-link-content">
                                <h4>Order History</h4>
                                <p>View your past orders and track shipments</p>
                            </div>
                        </a>
                        <a href="<?php echo url('user-wishlist.php'); ?>" class="quick-link">
                            <i class="fa-regular fa-heart"></i>
                            <div class="quick-link-content">
                                <h4>Wishlist</h4>
                                <p>View and manage your saved items</p>
                            </div>
                        </a>
                        <a href="<?php echo url('user-settings.php'); ?>" class="quick-link">
                            <i class="fa-regular fa-cog"></i>
                            <div class="quick-link-content">
                                <h4>Account Settings</h4>
                                <p>Change password and privacy settings</p>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</section>


<?php include 'includes/footer.php'; ?>
