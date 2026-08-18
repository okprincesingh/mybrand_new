<?php
/**
 * Preview Mode
 * -------------
 * Session-based preview toggle for admin users.
 * When enabled, the logged-in admin can preview draft/unpublished content
 * on the public site without publishing anything.
 *
 * Safety guarantees:
 *  - No database schema changes.
 *  - No existing data is modified.
 *  - Live cache is never polluted with draft content (cache is bypassed).
 *  - Only the logged-in admin's session sees preview content.
 *  - Preview pages are marked noindex,nofollow so search engines ignore them.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (!function_exists('preview_mode_force_request')) {
    /**
     * Force preview mode for the current request only (no session write).
     * Used by the section-preview iframe so it can merge drafts without
     * permanently flipping the admin "Preview Mode" toggle.
     */
    function preview_mode_force_request(bool $enabled = true): void
    {
        $GLOBALS['__preview_mode_force_request'] = $enabled;
    }
}

if (!function_exists('preview_mode_enabled')) {
    function preview_mode_enabled(): bool
    {
        if (!empty($GLOBALS['__preview_mode_force_request'])) {
            return true;
        }
        // Request-scoped preview via ?preview=1 is honored for logged-in admins
        // only (no session write). Used by the section-preview iframe redirects
        // so public pages merge drafts even when the topbar toggle is off.
        if (($_GET['preview'] ?? '') === '1' && function_exists('admin_current') && admin_current()) {
            return true;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return !empty($_SESSION['admin_preview_mode']);
    }
}

if (!function_exists('preview_mode_toggle')) {
    function preview_mode_toggle(bool $enabled): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if ($enabled) {
            $_SESSION['admin_preview_mode'] = true;
        } else {
            unset($_SESSION['admin_preview_mode']);
        }
    }
}

if (!function_exists('preview_mode_url')) {
    /**
     * Build a URL that carries the preview session flag.
     * Used by the "View Site" link in the admin topbar.
     */
    function preview_mode_url(string $path = ''): string
    {
        $base = function_exists('url') ? url($path) : $path;
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . 'preview=1';
    }
}

if (!function_exists('preview_mode_should_bypass_cache')) {
    /**
     * When preview mode is active, all CMS/catalog/blog reads must bypass
     * the file cache so draft changes appear instantly.
     */
    function preview_mode_should_bypass_cache(): bool
    {
        return preview_mode_enabled();
    }
}

if (!function_exists('preview_mode_include_drafts')) {
    /**
     * When preview mode is active, queries should include draft/inactive records.
     */
    function preview_mode_include_drafts(): bool
    {
        return preview_mode_enabled();
    }
}

if (!function_exists('preview_mode_robots_meta')) {
    /**
     * Preview pages must never be indexed by search engines.
     */
    function preview_mode_robots_meta(): string
    {
        return preview_mode_enabled() ? 'noindex,nofollow' : 'index,follow';
    }
}

if (!function_exists('preview_mode_banner')) {
    /**
     * Renders a visible banner on the public site when preview mode is active.
     */
function preview_mode_banner(): string
{
    if (!preview_mode_enabled()) {
        return '';
    }

    return '<div id="preview-mode-banner" style="position:fixed;top:0;left:0;right:0;z-index:99999;background:#7c3aed;color:#fff;text-align:center;padding:8px 16px;font-size:13px;font-weight:600;font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;gap:12px;box-shadow:0 2px 8px rgba(0,0,0,0.2);">'
        . '<span>🔍 PREVIEW MODE — You are viewing draft/unpublished content. Visitors see the live site.</span>'
        . '</div>';
}
}