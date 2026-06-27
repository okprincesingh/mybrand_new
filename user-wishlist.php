<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/url.php';

$user = user_require_auth();

$wishlist = user_get_wishlist((int) $user['id']);

$meta = [
    'title' => 'Mybrandplease | Wishlist',
    'description' => 'Your wishlist of favorite products',
    'canonical' => 'user-wishlist.php'
];

include 'includes/head.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo url('assets/css/user-wishlist.css'); ?>">

<div class="breadcumb">
    <div class="container rr-container-1895">
        <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <div class="breadcumb-wrapper__title">My Wishlist</div>
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
                    <span class="breadcumb-wrapper__items-list-title2">My Wishlist</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<section class="user-wishlist section-spacing-120">
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
                            <li class="nav-item active">
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
                    <h1>My Wishlist</h1>
                    <p>Save your favorite products for later</p>
                </div>

                <?php if (!empty($wishlist)): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist as $product): ?>
                    <?php
                        $productSlug = (string) ($product['slug'] ?? '');
                        $productName = (string) ($product['name'] ?? 'Product');
                        $productImage = (string) ($product['featured_image'] ?? ($product['image'] ?? 'assets/imgs/product/skin-care.webp'));
                        $productPrice = (float) ($product['price'] ?? 0);
                        $productRating = (float) ($product['rating'] ?? 0);
                        $productReviews = (int) ($product['reviews'] ?? 0);
                        $productDescription = trim((string) ($product['description'] ?? ''));
                    ?>
                    <div class="wishlist-item">
                        <div class="wishlist-item-image">
                            <img src="<?php echo htmlspecialchars(url($productImage), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="wishlist-item-overlay">
                                <button class="wishlist-action-btn js-wishlist-remove" data-product-slug="<?php echo htmlspecialchars($productSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-regular fa-heart"></i>
                                    Remove
                                </button>
                                <a href="<?php echo url('product-details.php?slug=' . urlencode($productSlug)); ?>" class="wishlist-action-btn">
                                    <i class="fa-regular fa-eye"></i>
                                    View
                                </a>
                                <button class="wishlist-action-btn js-wishlist-add-to-cart" data-product-slug="<?php echo htmlspecialchars($productSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-regular fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                        
                        <div class="wishlist-item-content">
                            <h3 class="wishlist-item-title">
                                <a href="<?php echo url('product-details.php?slug=' . urlencode($productSlug)); ?>">
                                    <?php echo htmlspecialchars($productName); ?>
                                </a>
                            </h3>
                            
                            <div class="wishlist-item-meta">
                                <span class="wishlist-item-price">$<?php echo number_format($productPrice, 2); ?></span>
                                <?php if ($productRating > 0): ?>
                                <div class="wishlist-item-rating">
                                    <i class="fa-regular fa-star"></i>
                                    <span><?php echo number_format($productRating, 1); ?></span>
                                    <span class="wishlist-item-reviews">(<?php echo $productReviews; ?> reviews)</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($productDescription !== ''): ?>
                            <?php $plainDescription = trim(strip_tags(html_entity_decode($productDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8'))); ?>
                            <p class="wishlist-item-description"><?php echo htmlspecialchars(substr($plainDescription, 0, 100) . (strlen($plainDescription) > 100 ? '...' : '')); ?></p>
                            <?php endif; ?>
                            
                            <div class="wishlist-item-actions">
                                <button class="btn btn-primary js-wishlist-add-to-cart" data-product-slug="<?php echo htmlspecialchars($productSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-regular fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                                <button class="btn btn-secondary js-wishlist-remove" data-product-slug="<?php echo htmlspecialchars($productSlug, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fa-regular fa-heart"></i>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="wishlist-actions">
                    <button class="btn btn-primary" onclick="addAllToCart()">
                        <i class="fa-regular fa-shopping-cart"></i>
                        Add All to Cart
                    </button>
                    <button class="btn btn-danger" onclick="clearWishlist()">
                        <i class="fa-regular fa-trash"></i>
                        Clear Wishlist
                    </button>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Add products to your wishlist to save them for later</p>
                    <a href="<?php echo url('shop.php'); ?>" class="btn btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</section>



<script>
async function addToCart(slug) {
    const normalizedSlug = String(slug || '').trim();
    if (!normalizedSlug) {
        showToast('Invalid product slug', 'error');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('slug', normalizedSlug);
        formData.append('quantity', '1');

        const response = await fetch('<?php echo url('api/cart.php'); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('Product added to cart!', 'success');
            updateCartCount(data.data.cart_count);
        } else {
            showToast(data.message || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
}

async function removeFromWishlist(slug) {
    const normalizedSlug = String(slug || '').trim();
    if (!normalizedSlug) {
        showToast('Invalid product slug', 'error');
        return;
    }

    try {
        const response = await fetch('<?php echo url('api/wishlist.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'remove',
                slug: normalizedSlug
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('Removed from wishlist', 'success');
            // Reload the page to update the wishlist
            location.reload();
        } else {
            showToast(data.message || 'Failed to remove from wishlist', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
}

async function addAllToCart() {
    const addButtons = document.querySelectorAll('.js-wishlist-add-to-cart');
    let addedCount = 0;
    
    for (const btn of addButtons) {
        const slug = String(btn.getAttribute('data-product-slug') || '').trim();
        if (!slug) continue;
        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('slug', slug);
            formData.append('quantity', '1');

            const response = await fetch('<?php echo url('api/cart.php'); ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            
            const data = await response.json();
            if (data.success) {
                addedCount++;
            }
        } catch (error) {
            // Continue with other items
        }
    }
    
    if (addedCount > 0) {
        showToast(`${addedCount} item(s) added to cart!`, 'success');
        updateCartCount(); // Refresh cart count
    } else {
        showToast('Failed to add items to cart', 'error');
    }
}

async function clearWishlist() {
    if (!confirm('Are you sure you want to clear your wishlist?')) {
        return;
    }
    
    try {
        const response = await fetch('<?php echo url('api/wishlist.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'clear'
            })
        });

        const data = await response.json();
        
        if (data.success) {
            showToast('Wishlist cleared', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Failed to clear wishlist', 'error');
        }
    } catch (error) {
        showToast('An error occurred', 'error');
    }
}

function showToast(message, type) {
    const existingToast = document.querySelector('.toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = 'toast toast--' + type;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(function() {
        toast.classList.add('toast--show');
    });

    setTimeout(function() {
        toast.classList.remove('toast--show');
        setTimeout(function() {
            toast.remove();
        }, 400);
    }, 3000);
}

function updateCartCount(newCount) {
    // Update cart count in header if exists
    const cartCount = document.querySelector('.cart-count');
    if (cartCount && newCount !== undefined) {
        cartCount.textContent = newCount;
        cartCount.style.display = Number(newCount) > 0 ? '' : 'none';
    }
}

document.addEventListener('click', function (event) {
    const addBtn = event.target.closest('.js-wishlist-add-to-cart');
    if (addBtn) {
        event.preventDefault();
        const slug = addBtn.getAttribute('data-product-slug') || '';
        addToCart(slug);
        return;
    }

    const removeBtn = event.target.closest('.js-wishlist-remove');
    if (removeBtn) {
        event.preventDefault();
        const slug = removeBtn.getAttribute('data-product-slug') || '';
        removeFromWishlist(slug);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
