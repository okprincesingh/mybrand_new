<?php
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/url.php';
require_once __DIR__ . '/../includes/preview.php';
if (!function_exists('admin_flash_set')) {
    function admin_flash_set(string $type, string $message): void
    {
        admin_flash($type, $message);
    }
}

if (!function_exists('admin_pagination_items')) {
    function admin_pagination_items(int $currentPage, int $totalPages, int $window = 2): array
    {
        if ($totalPages <= 1) {
            return [];
        }

        $currentPage = max(1, min($currentPage, $totalPages));
        $window = max(1, $window);
        $pages = [1, $totalPages];

        for ($page = $currentPage - $window; $page <= $currentPage + $window; $page++) {
            if ($page > 1 && $page < $totalPages) {
                $pages[] = $page;
            }
        }

        $pages = array_values(array_unique($pages));
        sort($pages);

        $items = [];
        $previous = 0;
        foreach ($pages as $page) {
            if ($previous && $page > $previous + 1) {
                $items[] = 'ellipsis';
            }
            $items[] = $page;
            $previous = $page;
        }

        return $items;
    }
}

enforce_csrf_on_post();

// Handle Preview Mode toggle from the admin topbar.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preview_toggle') {
    verify_csrf_or_fail();
    $previewEnabled = !empty($_POST['preview_enabled']);
    preview_mode_toggle($previewEnabled);
    admin_flash('success', $previewEnabled ? 'Preview Mode enabled. You can now view draft content on the site.' : 'Preview Mode disabled.');
    header('Location: ' . ($_POST['redirect'] ?? 'dashboard.php'));
    exit;
}

