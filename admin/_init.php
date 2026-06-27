<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/url.php';
if (!function_exists('admin_flash_set')) {
    function admin_flash_set(string $type, string $message): void
    {
        admin_flash($type, $message);
    }
}

enforce_csrf_on_post();

