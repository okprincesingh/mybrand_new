<?php
/**
 * Central, extensible CMS permission registry.  Modules may add entries with
 * cms_register_permissions() from their bootstrap code; no UI changes are needed.
 */

function cms_permission_registry(): array
{
    static $loaded = false;
    if ($loaded) return cms_permission_registry_storage();
    $loaded = true;
    cms_register_permissions('dashboard', 'Dashboard', [
        ['key' => 'dashboard.view', 'label' => 'Dashboard access'],
        ['key' => 'dashboard.metrics', 'label' => 'Revenue and key metrics'],
        ['key' => 'dashboard.catalog', 'label' => 'Product and content statistics'],
        ['key' => 'dashboard.order_status', 'label' => 'Order status summary'],
        ['key' => 'dashboard.quick_actions', 'label' => 'Quick actions'],
        ['key' => 'dashboard.recent_orders', 'label' => 'Recent orders'],
        ['key' => 'dashboard.recent_products', 'label' => 'Recent products'],
        ['key' => 'dashboard.recent_users', 'label' => 'Recent users'],
        ['key' => 'dashboard.overview', 'label' => 'Overview summary'],
        ['key' => 'dashboard.navigation', 'label' => 'Quick navigation'],
    ]);
    cms_register_permissions('administration', 'Administration', [
        ['key' => 'administration.admins.manage', 'label' => 'Manage administrator accounts'],
        ['key' => 'administration.permissions.manage', 'label' => 'Manage access permissions'],
    ]);

    // Every admin page is discoverable by default. New pages become assignable
    // automatically, even before their module registers more granular actions.
    foreach (glob(__DIR__ . '/../admin/*.php') ?: [] as $file) {
        $name = basename($file);
        if (in_array($name, ['_init.php', '_layout_top.php', '_layout_bottom.php', 'login.php', 'logout.php', 'signup.php', 'dashboard.php'], true)) continue;
        $key = 'page.' . str_replace(['.php', '-'], ['', '.'], $name) . '.view';
        cms_register_permissions('pages', 'CMS Pages', [[
            'key' => $key,
            'label' => ucwords(str_replace(['.php', '-'], ['', ' '], $name)),
            'page' => $name,
        ]]);
    }
    // Admin JSON endpoints are first-class capabilities too. This protects
    // direct calls even when an endpoint is not linked in the sidebar.
    foreach (glob(__DIR__ . '/../admin/api/*.php') ?: [] as $file) {
        $name = basename($file);
        cms_register_permissions('api', 'Administrative API', [[
            'key' => 'api.' . str_replace(['.php', '-'], ['', '.'], $name) . '.access',
            'label' => 'Use API: ' . ucwords(str_replace(['.php', '-'], ['', ' '], $name)),
            'api' => $name,
        ]]);
    }
    return cms_permission_registry_storage();
}

function cms_register_permissions(string $module, string $label, array $permissions): void
{
    $registry =& cms_permission_registry_storage();
    if (!isset($registry[$module])) $registry[$module] = ['label' => $label, 'permissions' => []];
    foreach ($permissions as $permission) {
        if (!empty($permission['key'])) $registry[$module]['permissions'][$permission['key']] = $permission;
    }
}

function &cms_permission_registry_storage(): array
{
    static $registry = [];
    return $registry;
}

function cms_all_permissions(): array
{
    cms_permission_registry();
    return cms_permission_registry_storage();
}

function admin_permissions(int $adminId): array
{
    $pdo = db();
    if (!$pdo || $adminId < 1) return [];
    try {
        $stmt = $pdo->prepare('SELECT permission_key FROM admin_permissions WHERE admin_id = :id');
        $stmt->execute([':id' => $adminId]);
        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) { return []; }
}

function admin_can(array $admin, string $permission): bool
{
    if (($admin['role'] ?? '') === 'super_admin') return true;
    return in_array($permission, admin_permissions((int)($admin['id'] ?? 0)), true);
}

function admin_require_permission(string $permission, ?array $admin = null): array
{
    $admin = $admin ?? admin_require_auth();
    if (admin_can($admin, $permission)) return $admin;
    if (str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') || str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        http_response_code(403); header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }
    http_response_code(403); echo '403 Forbidden'; exit;
}

/**
 * Return the first permitted destination from the same page registry rendered
 * by Access Management. The dashboard is deliberately checked first, but is
 * not assumed to be available to every administrator.
 */
function admin_first_accessible_admin_page(array $admin): ?string
{
    if (admin_can($admin, 'dashboard.view')) return 'dashboard.php';
    foreach (cms_all_permissions() as $module) {
        foreach ($module['permissions'] as $key => $permission) {
            if (!empty($permission['page']) && admin_can($admin, $key)) return $permission['page'];
        }
    }
    return null;
}

function admin_can_dashboard_section(array $admin, string $permission): bool
{
    if (($admin['role'] ?? '') === 'super_admin') return true;
    $permissions = admin_permissions((int)($admin['id'] ?? 0));
    $hasGranularSetting = false;
    foreach ($permissions as $key) {
        if (str_starts_with($key, 'dashboard.') && $key !== 'dashboard.view') { $hasGranularSetting = true; break; }
    }
    // Existing Dashboard-only grants retain their current complete dashboard.
    return $hasGranularSetting ? in_array($permission, $permissions, true) : admin_can($admin, 'dashboard.view');
}

function cms_current_page_permission(): ?string
{
    $script = str_replace('\\', '/', (string)($_SERVER['PHP_SELF'] ?? ''));
    if (!str_contains($script, '/admin/')) return null;
    $page = basename($script);
    if ($page === '' || in_array($page, ['login.php', 'logout.php', 'signup.php'], true)) return null;
    if (str_contains($script, '/admin/api/')) {
        return 'api.' . str_replace(['.php', '-'], ['', '.'], $page) . '.access';
    }
    if ($page === 'access-management.php') return 'administration.permissions.manage';
    if ($page === 'dashboard.php') return 'dashboard.view';
    return 'page.' . str_replace(['.php', '-'], ['', '.'], $page) . '.view';
}
