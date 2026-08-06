<?php
$adminUser = $adminUser ?? admin_current();
$flash = admin_flash_get();
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$currentTab = $_GET['tab'] ?? '';
$homeNavPages = ['home-hero-video.php','home-slider.php','home-testimonials.php','home-offices.php','home-instagram.php','homepage-sections.php'];
$isHomeNavActive = in_array($currentPage, $homeNavPages, true);
$isHomepageSections = $currentPage === 'homepage-sections.php';
?><!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Admin Panel') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?php echo url('admin/assets/css/style.css'); ?>" rel="stylesheet">
  <link href="<?php echo url('admin/assets/css/section-preview.css'); ?>" rel="stylesheet">
</head>
<body class="admin-body">
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>
<div class="container-fluid admin-shell">
  <div class="row g-0">
    <aside class="col-12 col-lg-2 admin-sidebar" id="adminSidebar">
      <div class="admin-brand">
        <span class="admin-brand-badge">CM</span>
        <span>CMS Panel</span>
      </div>

      <div class="nav-group-label">Overview</div>
      <a class="admin-nav-link <?= $currentPage==='dashboard.php'?'active':'' ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>

      <a class="admin-nav-link admin-nav-toggle <?= $isHomeNavActive ? 'active open' : '' ?>" data-bs-toggle="collapse" href="#homeNavCollapse" role="button" aria-expanded="<?= $isHomeNavActive ? 'true' : 'false' ?>" aria-controls="homeNavCollapse">
        <i class="bi bi-house-door"></i><span>Home</span>
        <span class="admin-nav-caret bi bi-chevron-down"></span>
      </a>
      <div class="collapse admin-nav-collapse <?= $isHomeNavActive ? 'show' : '' ?>" id="homeNavCollapse">
        <a class="admin-nav-link admin-sub-link <?= $currentPage==='home-hero-video.php'?'active':'' ?>" href="home-hero-video.php"><i class="bi bi-play-btn"></i><span>Hero Video</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && ($currentTab === '' || $currentTab === 'working_process') ?'active':'' ?>" href="homepage-sections.php?tab=working_process"><i class="bi bi-list-task"></i><span>Working Process</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && $currentTab === 'marquee' ?'active':'' ?>" href="homepage-sections.php?tab=marquee"><i class="bi bi-arrow-repeat"></i><span>Marquee Strip</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && $currentTab === 'brand_builder' ?'active':'' ?>" href="homepage-sections.php?tab=brand_builder"><i class="bi bi-bricks"></i><span>Brand Builder</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && $currentTab === 'getting_started' ?'active':'' ?>" href="homepage-sections.php?tab=getting_started"><i class="bi bi-rocket-takeoff"></i><span>Getting Started</span></a>
        <a class="admin-nav-link admin-sub-link <?= $currentPage==='home-testimonials.php'?'active':'' ?>" href="home-testimonials.php"><i class="bi bi-chat-quote"></i><span>Testimonials</span></a>
        <a class="admin-nav-link admin-sub-link <?= $currentPage==='home-instagram.php'?'active':'' ?>" href="home-instagram.php"><i class="bi bi-instagram"></i><span>Instagram Reels</span></a>
        <a class="admin-nav-link admin-sub-link <?= $currentPage==='home-offices.php'?'active':'' ?>" href="home-offices.php"><i class="bi bi-geo-alt"></i><span>Our Offices</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && $currentTab === 'partner_logos' ?'active':'' ?>" href="homepage-sections.php?tab=partner_logos"><i class="bi bi-building"></i><span>Partner Logos</span></a>
        <a class="admin-nav-link admin-sub-link <?= $isHomepageSections && $currentTab === 'certification_logos' ?'active':'' ?>" href="homepage-sections.php?tab=certification_logos"><i class="bi bi-award"></i><span>Certification Logos</span></a>
        <a class="admin-nav-link admin-sub-link <?= $currentPage==='home-slider.php'?'active':'' ?>" href="home-slider.php"><i class="bi bi-sliders"></i><span>Slider</span></a>
      </div>

      <div class="nav-group-label">Content</div>
      <a class="admin-nav-link <?= $currentPage==='why-pages.php' || $currentPage==='why-page-edit.php'?'active':'' ?>" href="why-pages.php"><i class="bi bi-award"></i><span>Why Choose Us</span></a>
      <a class="admin-nav-link <?= $currentPage==='certificates.php'?'active':'' ?>" href="certificates.php"><i class="bi bi-patch-check"></i><span>Certificates</span></a>
      <a class="admin-nav-link <?= $currentPage==='faq-pages.php' || $currentPage==='faq-page-edit.php'?'active':'' ?>" href="faq-pages.php"><i class="bi bi-question-circle"></i><span>FAQs</span></a>
      <a class="admin-nav-link <?= $currentPage==='blogs.php' || $currentPage==='blog-edit.php'?'active':'' ?>" href="blogs.php"><i class="bi bi-journal-richtext"></i><span>Blog</span></a>
      <a class="admin-nav-link <?= $currentPage==='pages.php' || $currentPage==='page-edit.php'?'active':'' ?>" href="pages.php"><i class="bi bi-file-earmark-text"></i><span>SEO Pages</span></a>

      <div class="nav-group-label">Catalog</div>
      <a class="admin-nav-link <?= $currentPage==='products.php' || $currentPage==='product-edit.php'?'active':'' ?>" href="products.php"><i class="bi bi-box-seam"></i><span>Products</span></a>
      <a class="admin-nav-link <?= $currentPage==='categories.php'?'active':'' ?>" href="categories.php"><i class="bi bi-diagram-3"></i><span>Categories</span></a>
      <a class="admin-nav-link <?= $currentPage==='shop-content.php'?'active':'' ?>" href="shop-content.php"><i class="bi bi-shop-window"></i><span>Shop Content</span></a>
      <a class="admin-nav-link <?= $currentPage==='reviews.php'?'active':'' ?>" href="reviews.php"><i class="bi bi-chat-left-text"></i><span>Reviews</span></a>

      <div class="nav-group-label">Users</div>
      <a class="admin-nav-link <?= $currentPage==='users.php'?'active':'' ?>" href="users.php"><i class="bi bi-people"></i><span>Users</span></a>
      <a class="admin-nav-link <?= $currentPage==='enquiries.php'?'active':'' ?>" href="enquiries.php"><i class="bi bi-envelope-paper"></i><span>Enquiries</span></a>

      <div class="nav-group-label">Orders</div>
      <a class="admin-nav-link <?= $currentPage==='orders.php'?'active':'' ?>" href="orders.php"><i class="bi bi-receipt"></i><span>Orders</span></a>
      <a class="admin-nav-link <?= $currentPage==='shipping-methods.php'?'active':'' ?>" href="shipping-methods.php"><i class="bi bi-truck"></i><span>Shipping</span></a>
      <a class="admin-nav-link <?= $currentPage==='payment-settings.php'?'active':'' ?>" href="payment-settings.php"><i class="bi bi-credit-card"></i><span>Payments</span></a>

      <div class="nav-group-label">Marketing</div>
      <a class="admin-nav-link <?= $currentPage==='coupons.php' || $currentPage==='coupon-edit.php'?'active':'' ?>" href="coupons.php"><i class="bi bi-tag"></i><span>Coupons</span></a>
      <a class="admin-nav-link <?= $currentPage==='reports.php'?'active':'' ?>" href="reports.php"><i class="bi bi-bar-chart"></i><span>Reports</span></a>

      <div class="nav-group-label">Session</div>
      <a class="admin-nav-link <?= $currentPage==='settings.php'?'active':'' ?>" href="settings.php"><i class="bi bi-gear"></i><span>Settings</span></a>
      <a class="admin-nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
    </aside>

    <main class="col-12 col-lg-10 admin-main">
      <div class="admin-topbar d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-start gap-3">
          <button class="admin-menu-toggle d-lg-none" id="sidebarToggle" type="button" aria-label="Open navigation menu" aria-controls="adminSidebar" aria-expanded="false">
            <i class="bi bi-list"></i>
          </button>
          <div>
          <h1 class="admin-title"><?= e($title ?? 'Admin') ?></h1>
          <p class="admin-subtitle">Manage website content with clean modules and guided forms.</p>
          </div>
        </div>
      <div class="admin-user">
          <span class="admin-user-dot"></span>
          <span><?= e($adminUser['name'] ?? 'Admin') ?></span>
        </div>

        <!-- Preview Mode Toggle -->
        <?php $previewModeActive = preview_mode_enabled(); ?>
        <div class="admin-preview-toggle" style="display:flex;align-items:center;gap:8px;margin-right:8px;">
          <form method="post" action="dashboard.php" style="display:flex;align-items:center;gap:8px;margin:0;">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="preview_toggle">
            <input type="hidden" name="preview_enabled" value="<?= $previewModeActive ? '0' : '1' ?>">
            <input type="hidden" name="redirect" value="<?= e(basename($_SERVER['PHP_SELF'] ?? 'dashboard.php')) ?>">
            <button type="submit" class="btn btn-sm <?= $previewModeActive ? 'btn-warning' : 'btn-outline-secondary' ?>" title="<?= $previewModeActive ? 'Disable Preview Mode' : 'Enable Preview Mode' ?>" style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
              <i class="bi <?= $previewModeActive ? 'bi-eye-fill' : 'bi-eye' ?>"></i>
              <span><?= $previewModeActive ? 'Preview ON' : 'Preview' ?></span>
            </button>
          </form>
          <?php if ($previewModeActive): ?>
            <button type="button" id="livePreviewToggle" class="btn btn-sm btn-primary" title="Open live preview inside admin panel" style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
              <i class="bi bi-window-sidebar"></i>
              <span>Live Preview</span>
            </button>
          <?php endif; ?>
        </div>
        
        <!-- Notification Bell -->
        <div class="admin-top-actions">
          <div class="dropdown admin-notifications">
            <button class="btn admin-notification-btn" id="notificationBell" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              <i class="bi bi-bell"></i>
              <span class="admin-notification-count" id="notificationBadge" style="display:none;">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end admin-notification-menu" aria-labelledby="notificationBell">
              <div class="admin-notification-header d-flex justify-content-between align-items-center">
                <strong>Notifications</strong>
                <button class="btn btn-sm btn-outline-primary" id="markAllReadBtn" type="button">Mark all read</button>
              </div>
              <div class="admin-notification-body" id="notificationList"></div>
              <div class="admin-notification-empty text-center text-muted" id="noNotifications">No notifications yet</div>
              <div class="admin-notification-footer text-center">
                <a href="notifications.php" class="text-decoration-none">View all notifications</a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="page-shell">
        <?php if ($flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($previewModeActive): ?>
        <!-- Embedded Live Preview Panel -->
        <div id="livePreviewPanel" class="admin-live-preview" aria-hidden="true">
          <div class="admin-live-preview__header">
            <span><i class="bi bi-eye-fill"></i> Live Preview — Draft content shown here. Visitors see the live site.</span>
            <button type="button" id="livePreviewClose" class="btn btn-sm" aria-label="Close live preview">&times;</button>
          </div>
          <iframe id="livePreviewFrame" data-src="<?= e(url('index.php')) ?>" title="Live site preview" loading="eager"></iframe>
        </div>
        <style>
          .admin-live-preview {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: min(900px, 85vw);
            background: #fff;
            z-index: 10500;
            box-shadow: -8px 0 30px rgba(0,0,0,0.25);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #e2e8f0;
          }
          .admin-live-preview.is-open {
            transform: translateX(0);
          }
          .admin-live-preview__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 16px;
            background: #7c3aed;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            flex-shrink: 0;
          }
          .admin-live-preview__header .btn {
            color: #fff;
            border-color: rgba(255,255,255,0.4);
            font-size: 18px;
            line-height: 1;
            padding: 2px 10px;
          }
          .admin-live-preview iframe {
            flex: 1;
            width: 100%;
            border: 0;
            background: #fff;
          }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
          const toggleBtn = document.getElementById('livePreviewToggle');
          const panel = document.getElementById('livePreviewPanel');
          const closeBtn = document.getElementById('livePreviewClose');
          const frame = document.getElementById('livePreviewFrame');

          function openPreview() {
            if (!panel) return;
            panel.classList.add('is-open');
            panel.setAttribute('aria-hidden', 'false');
            if (frame && !frame.getAttribute('src') && frame.getAttribute('data-src')) {
              frame.setAttribute('src', frame.getAttribute('data-src'));
            }
          }

          function closePreview() {
            if (!panel) return;
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
          }

          // Auto-reload preview when a draft is saved/published/discarded.
          // Admin pages dispatch this event after a draft action, or redirect
          // back with ?draft_reload=1 so the event fires on page load.
          function reloadPreview() {
            if (frame && panel && panel.classList.contains('is-open')) {
              const current = frame.getAttribute('src');
              if (current) {
                frame.setAttribute('src', current + (current.indexOf('?') === -1 ? '?_r=' : '&_r=') + Date.now());
              }
            }
          }

          window.addEventListener('mybrandplease:preview-reload', reloadPreview);

          // Detect redirect back after a draft action on this page.
          if (window.location.search.indexOf('draft_reload=1') !== -1) {
            openPreview();
            reloadPreview();
            // Clean the query param without a full reload.
            if (window.history && typeof window.history.replaceState === 'function') {
              const cleanUrl = window.location.pathname + window.location.search.replace(/[?&]draft_reload=1/g, '').replace(/^&/, '?');
              window.history.replaceState({}, '', cleanUrl);
            }
          }

          if (toggleBtn) toggleBtn.addEventListener('click', openPreview);
          if (closeBtn) closeBtn.addEventListener('click', closePreview);
        });
        </script>
        <?php endif; ?>

                <!-- Notification JavaScript -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBadge = document.getElementById('notificationBadge');
            const notificationList = document.getElementById('notificationList');
            const noNotifications = document.getElementById('noNotifications');
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            const csrfToken = '<?= e(csrf_token()) ?>';

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            }

            function getNotificationIcon(type) {
                switch(type) {
                    case 'success': return 'bi-check-circle-fill';
                    case 'warning': return 'bi-exclamation-triangle-fill';
                    case 'error': return 'bi-x-circle-fill';
                    default: return 'bi-info-circle-fill';
                }
            }

            function normalizeActionUrl(rawUrl) {
                const url = String(rawUrl || '').trim();
                if (url.startsWith('admin/')) {
                    return url.substring(6);
                }
                return url;
            }

            function loadNotifications() {
                fetch('api/notifications.php?limit=12', { credentials: 'same-origin' })
                    .then(response => response.json())
                    .then(data => updateNotificationUI(data))
                    .catch(error => console.error('Error loading notifications:', error));
            }

            function updateNotificationUI(data) {
                const notifications = Array.isArray(data.notifications) ? data.notifications : [];
                const unreadCount = Number(data.unread_count || 0);

                notificationList.innerHTML = '';
                noNotifications.style.display = notifications.length === 0 ? 'block' : 'none';
                notificationBadge.style.display = unreadCount > 0 ? 'inline-flex' : 'none';
                notificationBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);

                notifications.forEach(notification => {
                    const row = document.createElement('div');
                    row.className = 'admin-notification-item' + (Number(notification.is_read) === 0 ? ' unread' : '');
                    const iconClass = getNotificationIcon(notification.type);
                    const iconTypeClass = 'type-' + (notification.type || 'info');
                    const actionUrl = normalizeActionUrl(notification.action_url);
                    const actionHtml = actionUrl
                        ? `<a href="${escapeHtml(actionUrl)}" class="btn btn-sm btn-primary">${escapeHtml(notification.action_text || 'View')}</a>`
                        : '';

                    row.innerHTML = `
                      <div class="admin-notification-icon ${iconTypeClass}"><i class="bi ${iconClass}"></i></div>
                      <div class="admin-notification-content">
                        <div class="admin-notification-title">${escapeHtml(notification.title)}</div>
                        <div class="admin-notification-message">${escapeHtml(notification.message)}</div>
                        <div class="admin-notification-meta">${formatTime(notification.created_at)}</div>
                      </div>
                      <div class="admin-notification-actions">
                        ${actionHtml}
                        <button class="btn btn-sm btn-outline-secondary mark-read-btn" type="button" data-id="${Number(notification.id)}">Mark read</button>
                      </div>
                    `;
                    notificationList.appendChild(row);
                });

                notificationList.querySelectorAll('.mark-read-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const id = Number(this.getAttribute('data-id') || 0);
                        if (!id) return;
                        markAsRead(id);
                    });
                });
            }

            function markAsRead(id) {
                fetch('api/notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ action: 'mark_read', id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) loadNotifications();
                });
            }

            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch('api/notifications.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ action: 'mark_all_read' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) loadNotifications();
                });
            });

            loadNotifications();
            setInterval(loadNotifications, 30000);
        });
        </script>



