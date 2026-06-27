<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();

$addresses = user_get_addresses((int) $user['id']);
$defaultAddress = user_get_default_address((int) $user['id']);
$editAddressId = (int) ($_GET['edit'] ?? 0);
$editAddress = null;
if ($editAddressId > 0) {
    foreach ($addresses as $addressRow) {
        if ((int) ($addressRow['id'] ?? 0) === $editAddressId) {
            $editAddress = $addressRow;
            break;
        }
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_address') {
        $addressData = [
            'type' => $_POST['type'] ?? 'both',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'address1' => trim($_POST['address1'] ?? ''),
            'address2' => trim($_POST['address2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'zip' => trim($_POST['zip'] ?? ''),
            'country' => trim($_POST['country'] ?? 'US'),
        ];

        if (empty($addressData['first_name']) || empty($addressData['last_name']) || empty($addressData['address1']) || empty($addressData['city']) || empty($addressData['state']) || empty($addressData['zip'])) {
            $error = 'Please fill in all required fields';
        } else {
            if (user_add_address((int) $user['id'], $addressData)) {
                $success = 'Address added successfully';
                // Refresh addresses
                $addresses = user_get_addresses((int) $user['id']);
                $defaultAddress = user_get_default_address((int) $user['id']);
            } else {
                $error = 'Failed to add address';
            }
        }
    } elseif ($action === 'update_address') {
        $addressId = (int) ($_POST['address_id'] ?? 0);
        $addressData = [
            'type' => $_POST['type'] ?? 'both',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'address1' => trim($_POST['address1'] ?? ''),
            'address2' => trim($_POST['address2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'zip' => trim($_POST['zip'] ?? ''),
            'country' => trim($_POST['country'] ?? 'US'),
        ];

        if ($addressId <= 0 || empty($addressData['first_name']) || empty($addressData['last_name']) || empty($addressData['address1']) || empty($addressData['city']) || empty($addressData['state']) || empty($addressData['zip'])) {
            $error = 'Please fill in all required fields';
        } else {
            if (user_update_address($addressId, (int) $user['id'], $addressData)) {
                $success = 'Address updated successfully';
                $addresses = user_get_addresses((int) $user['id']);
                $defaultAddress = user_get_default_address((int) $user['id']);
                $editAddress = null;
                $editAddressId = 0;
            } else {
                $error = 'Failed to update address';
            }
        }
    } elseif ($action === 'set_default') {
        $addressId = (int) ($_POST['address_id'] ?? 0);
        if ($addressId > 0 && user_set_default_address($addressId, (int) $user['id'])) {
            $success = 'Default address updated';
            $defaultAddress = user_get_default_address((int) $user['id']);
        } else {
            $error = 'Failed to update default address';
        }
    } elseif ($action === 'delete_address') {
        $addressId = (int) ($_POST['address_id'] ?? 0);
        if ($addressId > 0 && user_delete_address($addressId, (int) $user['id'])) {
            $success = 'Address deleted successfully';
            // Refresh addresses
            $addresses = user_get_addresses((int) $user['id']);
            $defaultAddress = user_get_default_address((int) $user['id']);
        } else {
            $error = 'Failed to delete address';
        }
    }
}

$meta = [
    'title' => 'Mybrandplease | Addresses',
    'description' => 'Manage your shipping and billing addresses',
    'canonical' => 'user-addresses.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-addresses.css'); ?>">

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">My Addresses</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">My Addresses</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-addresses section-spacing-120">
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
                            <li class="nav-item active">
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
                    <h1>My Addresses</h1>
                    <p>Manage your shipping and billing addresses</p>
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

                <!-- Add New Address Form -->
                <div class="address-form-card">
                    <div class="card-header">
                        <h2><?php echo $editAddress ? 'Edit Address' : 'Add New Address'; ?></h2>
                    </div>
                    
                    <form method="post" class="address-form">
                        <input type="hidden" name="action" value="<?php echo $editAddress ? 'update_address' : 'add_address'; ?>">
                        <?php if ($editAddress): ?>
                        <input type="hidden" name="address_id" value="<?php echo (int) $editAddress['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="required">*</span></label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars((string) ($editAddress['first_name'] ?? '')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="required">*</span></label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars((string) ($editAddress['last_name'] ?? '')); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company">Company Name (Optional)</label>
                            <input type="text" id="company" name="company" value="<?php echo htmlspecialchars((string) ($editAddress['company'] ?? '')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address1">Street Address <span class="required">*</span></label>
                            <input type="text" id="address1" name="address1" required placeholder="House number and street name" value="<?php echo htmlspecialchars((string) ($editAddress['address1'] ?? '')); ?>">
                        </div>

                        <div class="form-group">
                            <label for="address2">Apartment, suite, etc. (Optional)</label>
                            <input type="text" id="address2" name="address2" placeholder="Apartment, suite, unit, etc." value="<?php echo htmlspecialchars((string) ($editAddress['address2'] ?? '')); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City <span class="required">*</span></label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars((string) ($editAddress['city'] ?? '')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State <span class="required">*</span></label>
                                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars((string) ($editAddress['state'] ?? '')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="zip">Zip Code <span class="required">*</span></label>
                                <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars((string) ($editAddress['zip'] ?? '')); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="country">Country <span class="required">*</span></label>
                            <select id="country" name="country" required>
                                <option value="US" <?php echo (($editAddress['country'] ?? 'US') === 'US') ? 'selected' : ''; ?>>United States</option>
                                <option value="CA" <?php echo (($editAddress['country'] ?? '') === 'CA') ? 'selected' : ''; ?>>Canada</option>
                                <option value="UK" <?php echo (($editAddress['country'] ?? '') === 'UK') ? 'selected' : ''; ?>>United Kingdom</option>
                                <option value="AU" <?php echo (($editAddress['country'] ?? '') === 'AU') ? 'selected' : ''; ?>>Australia</option>
                                <option value="IN" <?php echo (($editAddress['country'] ?? '') === 'IN') ? 'selected' : ''; ?>>India</option>
                                <option value="DE" <?php echo (($editAddress['country'] ?? '') === 'DE') ? 'selected' : ''; ?>>Germany</option>
                                <option value="FR" <?php echo (($editAddress['country'] ?? '') === 'FR') ? 'selected' : ''; ?>>France</option>
                                <option value="ES" <?php echo (($editAddress['country'] ?? '') === 'ES') ? 'selected' : ''; ?>>Spain</option>
                                <option value="NL" <?php echo (($editAddress['country'] ?? '') === 'NL') ? 'selected' : ''; ?>>Netherlands</option>
                                <option value="BD" <?php echo (($editAddress['country'] ?? '') === 'BD') ? 'selected' : ''; ?>>Bangladesh</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="type">Address Type</label>
                            <select id="type" name="type">
                                <option value="both" <?php echo (($editAddress['type'] ?? 'both') === 'both') ? 'selected' : ''; ?>>Billing and Shipping</option>
                                <option value="billing" <?php echo (($editAddress['type'] ?? '') === 'billing') ? 'selected' : ''; ?>>Billing Address Only</option>
                                <option value="shipping" <?php echo (($editAddress['type'] ?? '') === 'shipping') ? 'selected' : ''; ?>>Shipping Address Only</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary"><?php echo $editAddress ? 'Update Address' : 'Add Address'; ?></button>
                        <?php if ($editAddress): ?>
                        <a href="<?php echo url('user-addresses.php'); ?>" class="btn btn-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Saved Addresses -->
                <div class="addresses-list-card">
                    <div class="card-header">
                        <h2>Saved Addresses</h2>
                        <span class="addresses-count"><?php echo count($addresses); ?> address(es)</span>
                    </div>
                    
                    <?php if (!empty($addresses)): ?>
                    <div class="addresses-grid">
                        <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo $address['is_default'] ? 'address-card--default' : ''; ?>">
                            <div class="address-header">
                                <div class="address-type">
                                    <span class="address-type-badge"><?php echo ucfirst(htmlspecialchars($address['type'])); ?></span>
                                    <?php if ($address['is_default']): ?>
                                    <span class="address-default-badge">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-actions">
                                    <a href="<?php echo url('user-addresses.php?edit=' . (int) $address['id']); ?>" class="address-action-btn" title="Edit Address">
                                        <i class="fa-regular fa-pen-to-square"></i> Edit
                                    </a>
                                    <?php if (!$address['is_default']): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="address_id" value="<?php echo (int) $address['id']; ?>">
                                        <button type="submit" class="address-action-btn">Set Default</button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="address_id" value="<?php echo (int) $address['id']; ?>">
                                        <button type="submit" class="address-action-btn address-action-btn--danger" onclick="return confirm('Are you sure you want to delete this address?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="address-content">
                                <div class="address-name">
                                    <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                    <?php if (!empty($address['company'])): ?>
                                    <br><span class="address-company"><?php echo htmlspecialchars($address['company']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="address-details">
                                    <?php echo htmlspecialchars($address['address1']); ?><br>
                                    <?php if (!empty($address['address2'])): ?><?php echo htmlspecialchars($address['address2']); ?><br><?php endif; ?>
                                    <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['zip']); ?><br>
                                    <?php echo htmlspecialchars($address['country']); ?>
                                </div>
                                
                                <div class="address-contact">
                                    <?php if (!empty($user['phone'])): ?>
                                    <i class="fa-regular fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-map-marker-alt"></i>
                        <h3>No addresses saved</h3>
                        <p>Add a new address to get started</p>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</section>


<?php include 'includes/footer.php'; ?>
