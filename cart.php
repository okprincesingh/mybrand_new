<?php
session_start();
require_once __DIR__ . '/includes/catalog.php';
require_once __DIR__ . '/includes/url.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/coupons.php';

function cartProductIdBySlug(PDO $pdo, string $slug): ?int
{
    $stmt = $pdo->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $productId = $stmt->fetchColumn();
    if ($productId === false) {
        return null;
    }
    return (int) $productId;
}

function cartUpsertRow(PDO $pdo, string $sessionId, int $productId, int $quantity): void
{
    $update = $pdo->prepare('UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?');
    $update->execute([$quantity, $sessionId, $productId]);
    if ($update->rowCount() === 0) {
        $insert = $pdo->prepare('INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)');
        $insert->execute([$sessionId, $productId, $quantity]);
    }
}

function cartIsDuplicateAdd(string $slug, int $quantity): bool
{
    $now = microtime(true);
    $last = $_SESSION['_cart_last_add'] ?? null;
    $_SESSION['_cart_last_add'] = [
        'slug' => $slug,
        'quantity' => $quantity,
        'time' => $now,
    ];
    if (!is_array($last)) {
        return false;
    }
    $lastSlug = (string) ($last['slug'] ?? '');
    $lastQty = (int) ($last['quantity'] ?? 0);
    $lastTime = (float) ($last['time'] ?? 0);
    return $lastSlug === $slug && $lastQty === $quantity && ($now - $lastTime) < 0.8;
}

// Load cart from database if session is empty
if ((!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) && session_id()) {
    $pdo = db();
    if ($pdo) {
        $sessionId = session_id();
        $rows = $pdo->prepare('SELECT p.slug, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ?');
        $rows->execute([$sessionId]);
        $cartItems = $rows->fetchAll();
        if ($cartItems) {
            $_SESSION['cart'] = [];
            foreach ($cartItems as $item) {
                $_SESSION['cart'][$item['slug']] = (int) $item['quantity'];
            }
        }
    }
}

$meta = [
  'title' => 'Mybrandplease | Cart',
  'description' => 'Mybrandplease - Shopping Cart',
  'canonical' => 'cart.php'
];

$productIndex = [];
foreach (catalog_products() as $product) {
  $slug = (string) ($product['slug'] ?? '');
  if ($slug === '') {
    continue;
  }
  $productIndex[$slug] = [
    'slug' => $slug,
    'title' => (string) ($product['name'] ?? 'Product'),
    'price' => (float) ($product['price'] ?? 0),
    'image' => (string) url((string) ($product['image'] ?? '')),
    'link' => (string) catalog_product_link($slug),
  ];
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? trim((string) $_POST['action']) : '';
  $slug = isset($_POST['slug']) ? trim((string) $_POST['slug']) : '';
  $pdo = db();
  $sessionId = session_id();

  if ($action === 'clear') {
    $_SESSION['cart'] = [];
    coupon_clear_session();
    if ($pdo && $sessionId !== '') {
      $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ?');
      $stmt->execute([$sessionId]);
    }
  } elseif ($action === 'remove' && $slug !== '') {
    unset($_SESSION['cart'][$slug]);
    if ($pdo && $sessionId !== '') {
      $stmt = $pdo->prepare('DELETE c FROM cart c JOIN products p ON c.product_id = p.id WHERE c.session_id = ? AND p.slug = ?');
      $stmt->execute([$sessionId, $slug]);
    }
  } elseif ($action === 'update' && $slug !== '') {
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    if (isset($_SESSION['cart'][$slug])) {
      $_SESSION['cart'][$slug] = $qty;
      if ($pdo && $sessionId !== '') {
        $productId = cartProductIdBySlug($pdo, $slug);
        if ($productId !== null) {
          cartUpsertRow($pdo, $sessionId, $productId, $qty);
        }
      }
    }
  }

  header('Location: ' . url('cart.php'));
  exit;
}

$addSlug = isset($_GET['add']) ? trim((string) $_GET['add']) : '';
if ($addSlug !== '' && isset($productIndex[$addSlug])) {
  if (cartIsDuplicateAdd($addSlug, 1)) {
    header('Location: ' . url('cart.php'));
    exit;
  }
  $currentQty = (int) ($_SESSION['cart'][$addSlug] ?? 0);
  $newQty = max(1, $currentQty + 1);
  $_SESSION['cart'][$addSlug] = $newQty;
  $pdo = db();
  $sessionId = session_id();
  if ($pdo && $sessionId !== '') {
    $productId = cartProductIdBySlug($pdo, $addSlug);
    if ($productId !== null) {
      cartUpsertRow($pdo, $sessionId, $productId, $newQty);
    }
  }
  header('Location: ' . url('cart.php'));
  exit;
}

if (isset($_GET['clear']) && (string) $_GET['clear'] === '1') {
  $_SESSION['cart'] = [];
  coupon_clear_session();
  $pdo = db();
  $sessionId = session_id();
  if ($pdo && $sessionId !== '') {
    $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ?');
    $stmt->execute([$sessionId]);
  }
  header('Location: ' . url('cart.php'));
  exit;
}

$cartRows = [];
$subtotal = 0.0;
foreach ($_SESSION['cart'] as $slug => $quantity) {
  if (!isset($productIndex[$slug])) {
    continue;
  }
  $item = $productIndex[$slug];
  $qty = max(1, (int) $quantity);
  $lineTotal = $item['price'] * $qty;
  $subtotal += $lineTotal;
  $cartRows[] = [
    'slug' => $slug,
    'title' => $item['title'],
    'image' => $item['image'],
    'link' => $item['link'],
    'price' => $item['price'],
    'quantity' => $qty,
    'line_total' => $lineTotal,
  ];
}

$couponSummary = coupon_refresh_session($subtotal);
$couponCode = (string) ($couponSummary['coupon_code'] ?? '');
$discountAmount = (float) ($couponSummary['discount_amount'] ?? 0.0);
$totalAfterDiscount = (float) ($couponSummary['total'] ?? $subtotal);

include 'includes/head.php';
include 'includes/header.php';
?>
<link rel="stylesheet" href="<?php echo url('assets/css/cart.css'); ?>">

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
      <div class="breadcumb-wrapper__title">Shopping Cart</div>
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="shop.php" class="breadcumb-wrapper__items-list-title">Shop</a></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="cart.php" class="breadcumb-wrapper__items-list-title2">Cart</a></li>
      </ul>
    </div>
  </div>
</div>

<section class="cart-page section-spacing-120">
  <div class="container container-1352">
    <div class="row g-4">
      <!-- Cart Items -->
      <div class="col-lg-8">
        <div class="cart-box">
          <div class="cart-header">
            <h2 class="cart-title">Your Items (<?php echo count($cartRows); ?>)</h2>
            
          </div>

          <?php if (!$cartRows): ?>
          <div class="empty-cart">
            <div class="empty-cart-icon">
              <svg fill="#EE2D7A" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                width="80px" height="80px" viewBox="0 0 902.86 902.86"
                xml:space="preserve">
              <g>
                <g>
                  <path d="M671.504,577.829l110.485-432.609H902.86v-68H729.174L703.128,179.2L0,178.697l74.753,399.129h596.751V577.829z
                    M685.766,247.188l-67.077,262.64H131.199L81.928,246.756L685.766,247.188z"/>
                  <path d="M578.418,825.641c59.961,0,108.743-48.783,108.743-108.744s-48.782-108.742-108.743-108.742H168.717
                    c-59.961,0-108.744,48.781-108.744,108.742s48.782,108.744,108.744,108.744c59.962,0,108.743-48.783,108.743-108.744
                    c0-14.4-2.821-28.152-7.927-40.742h208.069c-5.107,12.59-7.928,26.342-7.928,40.742
                    C469.675,776.858,518.457,825.641,578.418,825.641z M209.46,716.897c0,22.467-18.277,40.744-40.743,40.744
                    c-22.466,0-40.744-18.277-40.744-40.744c0-22.465,18.277-40.742,40.744-40.742C191.183,676.155,209.46,694.432,209.46,716.897z
                    M619.162,716.897c0,22.467-18.277,40.744-40.743,40.744s-40.743-18.277-40.743-40.744c0-22.465,18.277-40.742,40.743-40.742
                    S619.162,694.432,619.162,716.897z"/>
                </g>
              </g>
              </svg>
            </div>
            <h3 class="empty-cart-title">Your cart is empty</h3>
            <p class="empty-cart-text">Looks like you haven't added any products to your cart yet.</p>
            <a href="shop.php" class="btn-shop-now">
              <i class="fa-regular fa-shopping-bag"></i> Continue Shopping
            </a>
          </div>
          <?php else: ?>
          <div class="cart-items">
            <?php foreach ($cartRows as $row): ?>
            <div class="cart-item" data-slug="<?php echo htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8'); ?>">
              <div class="cart-item-image">
                <a href="<?php echo htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8'); ?>">
                  <img src="<?php echo htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>">
                </a>
              </div>
              <div class="cart-item-details">
                <div class="cart-item-info">
                  <h4 class="cart-item-title">
                    <a href="<?php echo htmlspecialchars($row['link'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                  </h4>
                  <p class="cart-item-sku">SKU: <?php echo htmlspecialchars(strtoupper(substr($row['slug'], 0, 10)), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="cart-item-actions">
                  <div class="quantity-wrapper">
                    <span class="quantity-label">Quantity:</span>
                    <div class="quantity-control">
                      <button type="button" class="qty-btn qty-minus" aria-label="Decrease quantity">
                        <i class="fa-regular fa-minus"></i>
                      </button>
                      <input type="number" class="qty-input" value="<?php echo (int) $row['quantity']; ?>" min="1" max="99" data-slug="<?php echo htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8'); ?>">
                      <button type="button" class="qty-btn qty-plus" aria-label="Increase quantity">
                        <i class="fa-regular fa-plus"></i>
                      </button>
                    </div>
                  </div>
                  <button type="button" class="remove-item" data-slug="<?php echo htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8'); ?>" title="Remove item">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </div>
              </div>
              <div class="cart-item-price">
                <div class="item-price">
                  <span class="price-label">Price:</span>
                  <span class="price-value">$<?php echo number_format((float) $row['price'], 2); ?></span>
                </div>
                <div class="item-total">
                  <span class="total-label">Total:</span>
                  <span class="total-value" data-original="<?php echo (float) $row['line_total']; ?>">$<?php echo number_format((float) $row['line_total'], 2); ?></span>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="cart-footer">
            <a href="shop.php" class="btn-continue">
              <i class="fa-regular fa-arrow-left"></i> Continue Shopping
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="col-lg-4">
        <div class="order-summary">
          <h3 class="summary-title">Order Summary</h3>

          <div class="summary-row">
            <span class="summary-label">Subtotal</span>
            <span class="summary-value" id="cart-subtotal">$<?php echo number_format((float) $subtotal, 2); ?></span>
          </div>

          <div class="summary-row">
            <span class="summary-label">Shipping</span>
            <span class="summary-value summary-shipping">Calculated at checkout</span>
          </div>

          <div class="summary-row summary-discount">
            <span class="summary-label">Discount</span>
            <span class="summary-value" id="cart-discount">-$<?php echo number_format($discountAmount, 2); ?></span>
          </div>

          <div class="summary-divider"></div>

          <div class="summary-row summary-total">
            <span class="summary-label">Total</span>
            <span class="summary-total-value" id="cart-total">$<?php echo number_format($totalAfterDiscount, 2); ?></span>
          </div>
          <div id="cart-applied-coupon-badge" style="<?php echo $couponCode !== '' ? '' : 'display:none;'; ?>margin-top:10px;">
            <span style="display:inline-block;background:#eef7ff;color:#0b5ed7;border:1px solid #b6dcff;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:600;">
              Applied coupon: <span id="cart-applied-coupon-code"><?php echo htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'); ?></span>
            </span>
          </div>

          <a href="checkout.php" class="btn-checkout">
            Proceed to Checkout <i class="fa-regular fa-arrow-right"></i>
          </a>

          <div class="trust-badges">
            <div class="trust-item">
              <i class="fa-regular fa-shield-check"></i>
              <span>Secure Checkout</span>
            </div>
            <div class="trust-item">
              <i class="fa-regular fa-truck"></i>
              <span>Free Shipping</span>
            </div>
            <div class="trust-item">
              <i class="fa-regular fa-rotate-left"></i>
              <span>Easy Returns</span>
            </div>
          </div>
        </div>

        <!-- Promo Code -->
        <div class="promo-box">
          <h4 class="promo-title">Have a promo code?</h4>
          <form class="promo-form" id="cart-coupon-form">
            <input type="text" class="promo-input" placeholder="Enter code" name="promo_code" id="cart-coupon-input" value="<?php echo htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="promo-btn">Apply</button>
            <button type="button" class="promo-btn" id="cart-coupon-remove" style="<?php echo $couponCode !== '' ? '' : 'display:none;'; ?>">Remove</button>
          </form>
          <p id="cart-coupon-message" style="margin-top:8px;font-size:13px;color:#666;"></p>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
// Cart functionality with AJAX
const CartManager = {
  items: [],
  subtotal: 0,
  discountAmount: <?php echo json_encode((float) $discountAmount); ?>,

  init() {
    this.loadItems();
    this.bindEvents();
  },

  loadItems() {
    document.querySelectorAll('.cart-item').forEach(item => {
      const slug = item.dataset.slug;
      const qtyInput = item.querySelector('.qty-input');
      const totalEl = item.querySelector('.total-value');
      const price = parseFloat(totalEl.dataset.original) / parseInt(qtyInput.value);

      this.items.push({
        slug,
        price,
        quantity: parseInt(qtyInput.value),
        element: item,
        input: qtyInput,
        totalEl
      });
    });

    this.updateTotals();
  },

  bindEvents() {
    // Quantity minus
    document.querySelectorAll('.qty-minus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const wrapper = btn.closest('.quantity-control');
        const input = wrapper.querySelector('.qty-input');
        const val = parseInt(input.value);
        if (val > 1) {
          input.value = val - 1;
          this.updateQuantity(input);
        }
      });
    });

    // Quantity plus
    document.querySelectorAll('.qty-plus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const wrapper = btn.closest('.quantity-control');
        const input = wrapper.querySelector('.qty-input');
        const val = parseInt(input.value);
        if (val < 99) {
          input.value = val + 1;
          this.updateQuantity(input);
        }
      });
    });

    // Input change
    document.querySelectorAll('.qty-input').forEach(input => {
      input.addEventListener('change', () => this.updateQuantity(input));
    });

    // Remove item
    document.querySelectorAll('.remove-item').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const slug = btn.dataset.slug;
        this.removeItem(slug, btn);
      });
    });

    // Clear cart
    const clearBtn = document.getElementById('clearCartBtn');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => this.clearCart());
    }
  },

  updateQuantity(input) {
    const slug = input.dataset.slug;
    const newQty = parseInt(input.value) || 1;
    const item = this.items.find(i => i.slug === slug);

    if (!item) return;

    const newTotal = item.price * newQty;
    item.quantity = newQty;
    item.totalEl.textContent = '$' + newTotal.toFixed(2);
    item.totalEl.dataset.original = newTotal;

    // Animate update
    item.element.classList.add('item-updated');
    setTimeout(() => item.element.classList.remove('item-updated'), 500);

    this.updateTotals();
    this.sendUpdate(slug, newQty);
    this.showToast('Cart updated successfully', 'success');
  },

  updateTotals() {
    this.subtotal = this.items.reduce((sum, item) => {
      return sum + (item.price * item.quantity);
    }, 0);

    const subtotalEl = document.getElementById('cart-subtotal');
    const totalEl = document.getElementById('cart-total');

    if (subtotalEl) {
      subtotalEl.textContent = '$' + this.subtotal.toFixed(2);
    }
    const discountEl = document.getElementById('cart-discount');
    if (discountEl) {
      discountEl.textContent = '-$' + Number(this.discountAmount || 0).toFixed(2);
    }
    if (totalEl) {
      const totalAfterDiscount = Math.max(0, this.subtotal - Number(this.discountAmount || 0));
      totalEl.textContent = '$' + totalAfterDiscount.toFixed(2);
    }

    // Update item count in header
    const countEl = document.querySelector('.cart-title');
    if (countEl) {
      const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
      countEl.textContent = `Your Items (${totalItems})`;
    }
  },

  sendUpdate(slug, quantity) {
    const progressBar = document.querySelector('.cart-progress__bar');
    progressBar?.classList.add('cart-progress__bar--loading');

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('slug', slug);
    formData.append('quantity', quantity);

    fetch('cart.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(() => {
      progressBar?.classList.remove('cart-progress__bar--loading');
      this.refreshCouponSummary();
    })
    .catch(err => {
      console.error('Update failed:', err);
      progressBar?.classList.remove('cart-progress__bar--loading');
      this.showToast('Failed to update cart', 'error');
    });
  },

  removeItem(slug, btn) {
    if (!confirm('Remove this item from your cart?')) return;

    const item = document.querySelector(`.cart-item[data-slug="${slug}"]`);
    if (!item) return;

    item.style.transition = 'all 0.4s ease';
    item.style.transform = 'translateX(100%)';
    item.style.opacity = '0';

    setTimeout(() => {
      const formData = new FormData();
      formData.append('action', 'remove');
      formData.append('slug', slug);

      fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(() => {
        item.remove();
        const idx = this.items.findIndex(i => i.slug === slug);
        if (idx > -1) this.items.splice(idx, 1);

        this.updateTotals();
        this.refreshCouponSummary();
        this.showToast('Item removed from cart', 'success');

        if (this.items.length === 0) {
          setTimeout(() => location.reload(), 500);
        }
      })
      .catch(err => {
        console.error('Remove failed:', err);
        this.showToast('Failed to remove item', 'error');
      });
    }, 400);
  },

  refreshCouponSummary() {
    fetch('api/coupon.php?action=summary')
      .then(r => r.json())
      .then(data => {
        if (data && data.success && data.data) {
          this.discountAmount = Number(data.data.discount_amount || 0);
          this.updateTotals();
          this.updateCouponUI(data.data, '');
        }
      })
      .catch(() => {});
  },

  updateCouponUI(summary, message) {
    const input = document.getElementById('cart-coupon-input');
    const removeBtn = document.getElementById('cart-coupon-remove');
    const msgEl = document.getElementById('cart-coupon-message');
    const badge = document.getElementById('cart-applied-coupon-badge');
    const badgeCode = document.getElementById('cart-applied-coupon-code');
    if (input && summary) {
      input.value = summary.coupon_code || '';
    }
    if (removeBtn && summary) {
      removeBtn.style.display = summary.coupon_code ? '' : 'none';
    }
    if (badge && badgeCode && summary) {
      badge.style.display = summary.coupon_code ? '' : 'none';
      badgeCode.textContent = summary.coupon_code || '';
    }
    if (msgEl && typeof message === 'string') {
      msgEl.textContent = message;
    }
  },

  clearCart() {
    if (!confirm('Clear all items from your cart?')) return;

    const items = document.querySelectorAll('.cart-item');
    items.forEach((item, i) => {
      setTimeout(() => {
        item.style.transition = 'all 0.3s ease';
        item.style.transform = 'translateY(20px)';
        item.style.opacity = '0';
      }, i * 50);
    });

    setTimeout(() => {
      const formData = new FormData();
      formData.append('action', 'clear');

      fetch('cart.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(() => {
        window.location.href = 'cart.php?cleared=1';
      });
    }, items.length * 50 + 300);
  },

  showToast(message, type = 'success') {
    const existingToast = document.querySelector('.cart-toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = `cart-toast cart-toast--${type}`;
    toast.innerHTML = `
      <div class="cart-toast__icon">
        ${type === 'success'
          ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
          : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'}
      </div>
      <span class="cart-toast__message">${message}</span>
    `;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.add('cart-toast--show');
    });

    setTimeout(() => {
      toast.classList.remove('cart-toast--show');
      setTimeout(() => toast.remove(), 400);
    }, 3000);
  }
};

document.addEventListener('DOMContentLoaded', () => CartManager.init());

document.addEventListener('DOMContentLoaded', () => {
  const couponForm = document.getElementById('cart-coupon-form');
  const couponInput = document.getElementById('cart-coupon-input');
  const removeBtn = document.getElementById('cart-coupon-remove');

  couponForm?.addEventListener('submit', function(e) {
    e.preventDefault();
    const code = String(couponInput?.value || '').trim();
    if (!code) {
      CartManager.showToast('Enter coupon code', 'error');
      return;
    }
    fetch('api/coupon.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'apply', code: code })
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.data) {
        CartManager.discountAmount = Number(data.data.discount_amount || 0);
        CartManager.updateTotals();
        CartManager.updateCouponUI(data.data, data.message || '');
      }
      CartManager.showToast((data && data.message) || 'Coupon update complete', (data && data.success) ? 'success' : 'error');
    })
    .catch(() => CartManager.showToast('Coupon request failed', 'error'));
  });

  removeBtn?.addEventListener('click', function() {
    fetch('api/coupon.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'remove' })
    })
    .then(r => r.json())
    .then(data => {
      if (data && data.data) {
        CartManager.discountAmount = Number(data.data.discount_amount || 0);
        CartManager.updateTotals();
        CartManager.updateCouponUI(data.data, data.message || '');
      }
      CartManager.showToast((data && data.message) || 'Coupon removed', 'success');
    })
    .catch(() => CartManager.showToast('Coupon remove failed', 'error'));
  });
});
</script>

<?php include 'includes/footer.php'; ?>
