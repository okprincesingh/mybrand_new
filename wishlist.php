<?php
$meta = [
  'title' => 'Mybrandplease | wishlist',
  'description' => 'Mybrandplease - wishlist page',
  'canonical' => 'wishlist.php'
];
include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
      <div class="breadcumb-wrapper__title">Wishlist</div>
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="shop.php" class="breadcumb-wrapper__items-list-title">Category</a></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="wishlist.php" class="breadcumb-wrapper__items-list-title2">Wishlist</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="wishlist-page section-spacing-120">
  <div class="container container-1352">
    <div class="row">
      <div class="col-12">
        <div class="wishlist-page__items">
          <div class="wishlist-page__table">
            <div class="wishlist-page__table-header">
              <div class="row align-items-center">
                <div class="col-md-4"><div class="wishlist-page__table-header-text">Product item</div></div>
                <div class="col-md-2 text-center"><div class="wishlist-page__table-header-text">Price</div></div>
                <div class="col-md-2 text-center"><div class="wishlist-page__table-header-text">Move</div></div>
                <div class="col-md-3 text-center"><div class="wishlist-page__table-header-text">Add To Cart</div></div>
                <div class="col-md-1 text-center"><div class="wishlist-page__table-header-text">Remove</div></div>
              </div>
            </div>
            <div class="wishlist-page__table-body" id="wishlist-page__table-body"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('wishlist-page__table-body');

    function getStore() {
      return window.MybrandStore || null;
    }

    function money(value) {
      return '$' + Number(value || 0).toFixed(2);
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function renderWishlist() {
      const store = getStore();
      const wishlist = store ? store.getWishlist() : [];
      if (!body) return;

      if (!store) {
        body.innerHTML = '<div class="alert alert-light border m-3">Loading wishlist...</div>';
        setTimeout(renderWishlist, 120);
        return;
      }

      if (!wishlist.length) {
        body.innerHTML = '<div class="alert alert-light border m-3">Your wishlist is empty. <a href="shop.php">Browse products</a>.</div>';
        return;
      }

      body.innerHTML = wishlist.map(function (item) {
        return `
          <div class="wishlist-page__item" data-wishlist-slug="${escapeHtml(item.slug)}">
            <div class="row align-items-center">
              <div class="col-md-4">
                <div class="wishlist-page__item-product">
                  <div class="wishlist-page__item-product-image">
                    <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.title)}">
                  </div>
                  <div class="wishlist-page__item-product-info">
                    <div class="wishlist-page__item-product-title">
                      <a href="${escapeHtml(item.link || 'product-details.php')}">${escapeHtml(item.title)}</a>
                    </div>
                    <p class="wishlist-page__item-product-rating"><span>Saved for later</span></p>
                  </div>
                </div>
              </div>
              <div class="col-md-2 text-center"><div class="wishlist-page__item-price">${money(item.price)}</div></div>
              <div class="col-md-2 text-center"><div class="wishlist-page__item-total">${escapeHtml(item.slug || 'NA')}</div></div>
              <div class="col-md-3 text-center">
                <button type="button" class="wishlist-page__add-to-cart" data-wishlist-cart>Add To Cart</button>
              </div>
              <div class="col-md-1 text-center">
                <button type="button" class="wishlist-page__remove" aria-label="Remove item" data-wishlist-remove>
                  <i class="fa-solid fa-x"></i>
                </button>
              </div>
            </div>
          </div>`;
      }).join('');
    }

    document.addEventListener('click', function (event) {
      const row = event.target.closest('[data-wishlist-slug]');
      const store = getStore();
      if (!row || !store) return;
      const slug = row.getAttribute('data-wishlist-slug');
      const wishlist = store.getWishlist();
      const item = wishlist.find(function (entry) { return entry.slug === slug; });
      if (!item) return;

      if (event.target.closest('[data-wishlist-cart]')) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('slug', item.slug);
        formData.append('quantity', '1');
        fetch('<?php echo url("api/cart.php"); ?>', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (data && data.success) {
            if (window.MybrandStore && typeof window.MybrandStore.syncBadges === 'function') {
              window.MybrandStore.syncBadges();
            }
          }
        })
        .catch(function () {});
      }

      if (event.target.closest('[data-wishlist-remove]')) {
        store.removeFromWishlist(slug);
      }
    });

    function syncFromServerThenRender() {
      fetch('<?php echo url("api/wishlist.php"); ?>', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(response => response.json())
      .then(data => {
        if (data && data.success && data.data && Array.isArray(data.data.items)) {
          localStorage.setItem('wishlist', JSON.stringify(data.data.items));
        }
      })
      .catch(() => {})
      .finally(renderWishlist);
    }

    window.addEventListener('mybrand:store-updated', renderWishlist);
    syncFromServerThenRender();
  });
</script>

<?php include 'includes/footer.php'; ?>
