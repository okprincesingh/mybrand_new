<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_permission('administration.permissions.manage');
$title = 'Access Management';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = (string)($_POST['action'] ?? 'save_permissions');
    if ($adminUser['role'] !== 'super_admin') {
        http_response_code(403); exit('403 Forbidden');
    }
    if ($action === 'create_admin') {
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            admin_flash_set('error', 'Enter a name, a valid email, and a password of at least 8 characters.');
            header('Location: access-management.php?create=1'); exit;
        }
        try {
            // Only this first-account bootstrap may create a Super Admin; all
            // accounts created here are restricted admins by design.
            $stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, is_active) VALUES (:name, :email, :password, :role, :active)');
            $stmt->execute([':name' => $name, ':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT), ':role' => 'admin', ':active' => $isActive]);
            $newId = (int)$pdo->lastInsertId();
            admin_flash_set('success', 'Administrator created. Assign access below.');
            header('Location: access-management.php?admin_id=' . $newId); exit;
        } catch (Throwable $e) {
            admin_flash_set('error', 'Could not create the administrator. That email may already be in use.');
            header('Location: access-management.php?create=1'); exit;
        }
    }
    if ($action === 'update_admin') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $targetStmt = $pdo->prepare('SELECT id, role FROM admins WHERE id = :id LIMIT 1');
        $targetStmt->execute([':id' => $adminId]); $target = $targetStmt->fetch();
        if (!$target || $target['role'] === 'super_admin') {
            admin_flash_set('error', 'The primary Super Admin account cannot be modified here.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || ($password !== '' && strlen($password) < 8)) {
            admin_flash_set('error', 'Enter a valid email and use at least 8 characters when changing the password.');
        } else {
            try {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE admins SET email = :email, password_hash = :password, is_active = :active WHERE id = :id');
                    $stmt->execute([':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT), ':active' => $isActive, ':id' => $adminId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE admins SET email = :email, is_active = :active WHERE id = :id');
                    $stmt->execute([':email' => $email, ':active' => $isActive, ':id' => $adminId]);
                }
                admin_flash_set('success', 'Administrator account updated.');
            } catch (Throwable $e) { admin_flash_set('error', 'Could not update the administrator. That email may already be in use.'); }
        }
        header('Location: access-management.php?admin_id=' . $adminId); exit;
    }
    if ($action === 'delete_admin') {
        $adminId = (int)($_POST['admin_id'] ?? 0);
        $targetStmt = $pdo->prepare('SELECT id, role FROM admins WHERE id = :id LIMIT 1');
        $targetStmt->execute([':id' => $adminId]); $target = $targetStmt->fetch();
        if (!$target || $target['role'] === 'super_admin') {
            admin_flash_set('error', 'The primary Super Admin account cannot be deleted.');
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM admins WHERE id = :id');
                $stmt->execute([':id' => $adminId]);
                admin_flash_set('success', 'Administrator account deleted.');
            } catch (Throwable $e) { admin_flash_set('error', 'Could not delete the administrator account.'); }
        }
        header('Location: access-management.php'); exit;
    }
    $adminId = (int)($_POST['admin_id'] ?? 0);
    $permissions = array_values(array_unique(array_filter((array)($_POST['permissions'] ?? []), 'is_string')));
    $target = $pdo->prepare('SELECT id, role FROM admins WHERE id = :id LIMIT 1');
    $target->execute([':id' => $adminId]);
    $target = $target->fetch();
    if (!$target) {
        admin_flash_set('error', 'Administrator not found.');
    } elseif ($target['role'] === 'super_admin') {
        admin_flash_set('error', 'The primary Super Admin always has complete access and cannot be changed here.');
    } else {
        $known = [];
        foreach (cms_all_permissions() as $module) foreach ($module['permissions'] as $key => $_) $known[$key] = true;
        $permissions = array_values(array_filter($permissions, fn($key) => isset($known[$key])));
        // A dashboard subsection is never useful without access to the page.
        foreach ($permissions as $key) {
            if (str_starts_with($key, 'dashboard.') && $key !== 'dashboard.view') {
                $permissions[] = 'dashboard.view';
                break;
            }
        }
        $permissions = array_values(array_unique($permissions));
        $pdo->beginTransaction();
        try {
            $delete = $pdo->prepare('DELETE FROM admin_permissions WHERE admin_id = :id');
            $delete->execute([':id' => $adminId]);
            $insert = $pdo->prepare('INSERT INTO admin_permissions (admin_id, permission_key) VALUES (:id, :key)');
            foreach ($permissions as $key) $insert->execute([':id' => $adminId, ':key' => $key]);
            $pdo->commit(); admin_flash_set('success', 'Access permissions updated.');
        } catch (Throwable $e) { $pdo->rollBack(); admin_flash_set('error', 'Could not update permissions. Run the RBAC migration first.'); }
    }
    header('Location: access-management.php?admin_id=' . $adminId); exit;
}

$admins = $pdo ? $pdo->query('SELECT id, name, email, role, is_active FROM admins ORDER BY role = "super_admin" DESC, name ASC')->fetchAll() : [];
$selectedId = (int)($_GET['admin_id'] ?? ($admins[0]['id'] ?? 0));
$selected = null; foreach ($admins as $item) if ((int)$item['id'] === $selectedId) $selected = $item;
$assigned = $selected ? array_flip(admin_permissions((int)$selected['id'])) : [];
$registry = cms_all_permissions();
require __DIR__ . '/_layout_top.php';
?>
<style>
  .access-management-page { max-width: 1280px; }
  .access-management-page .admin-list-card { position: sticky; top: 1rem; }
  @media (max-width: 991.98px) { .access-management-page .admin-list-card { position: static; } }
  @media (max-width: 575.98px) {
    .access-management-page .access-header { align-items: flex-start !important; }
    .access-management-page .access-header .btn { width: 100%; }
    .access-management-page .access-actions { width: 100%; flex-wrap: wrap; }
    .access-management-page .access-actions .form-control { flex-basis: 100%; }
  }
</style>
<div class="access-management-page">
  <div class="access-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h2 class="widget-title mb-1">Access Management</h2><p class="text-muted mb-0">Assign individual capabilities. New admin pages are discovered automatically; modules can register granular actions centrally.</p></div><a class="btn btn-primary" href="access-management.php?create=1"><i class="bi bi-person-plus"></i> Add Admin</a></div>
  <?php if (!empty($_GET['create'])): ?><div class="card mb-4"><div class="card-header">Add Administrator</div><div class="card-body"><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_admin"><div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" required maxlength="120"></div><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required maxlength="190"></div><div class="col-md-6"><label class="form-label">Password</label><input class="form-control" name="password" type="password" required minlength="8" autocomplete="new-password"></div><div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="newAdminActive" checked><label class="form-check-label" for="newAdminActive">Active account</label></div></div><div class="col-12"><button class="btn btn-primary" type="submit">Create Admin</button> <a class="btn btn-link" href="access-management.php">Cancel</a></div></form></div></div><?php endif; ?>
  <div class="row g-4"><div class="col-lg-4"><div class="card admin-list-card"><div class="card-header">Administrators</div><div class="list-group list-group-flush">
  <?php foreach ($admins as $item): ?><a class="list-group-item list-group-item-action <?= (int)$item['id'] === $selectedId ? 'active' : '' ?>" href="access-management.php?admin_id=<?= (int)$item['id'] ?>"><strong><?= e($item['name']) ?></strong><br><small><?= e($item['email']) ?> · <?= e($item['role']) ?></small></a><?php endforeach; ?>
  </div></div></div><div class="col-lg-8"><div class="card"><div class="card-body">
  <?php if (!$selected): ?><p class="text-muted mb-0">No administrator account is available.</p><?php elseif ($selected['role'] === 'super_admin'): ?><div class="alert alert-info mb-0"><strong>Super Admin:</strong> complete access is automatic, including future permissions.</div><?php else: ?>
  <div class="border rounded p-3 mb-4"><h2 class="h6 mb-3">Admin Account</h2><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update_admin"><input type="hidden" name="admin_id" value="<?= (int)$selected['id'] ?>"><div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="email" type="email" value="<?= e($selected['email']) ?>" required></div><div class="col-md-6"><label class="form-label">New password <small class="text-muted">(leave blank to keep)</small></label><input class="form-control" name="password" type="password" minlength="8" autocomplete="new-password"></div><div class="col-12 d-flex flex-wrap align-items-center gap-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="adminActive" <?= !empty($selected['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="adminActive">Active account</label></div><button class="btn btn-outline-primary" type="submit">Save account</button></div></form><form method="post" class="mt-3" onsubmit="return confirm('Delete this administrator permanently? This cannot be undone.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_admin"><input type="hidden" name="admin_id" value="<?= (int)$selected['id'] ?>"><button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Delete Admin</button></form></div>
  <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_permissions"><input type="hidden" name="admin_id" value="<?= (int)$selected['id'] ?>"><div class="access-actions d-flex gap-2 align-items-center mb-3"><input class="form-control" id="permissionSearch" placeholder="Search permissions or modules"><button class="btn btn-outline-secondary" type="button" id="selectAll">Select all</button><button class="btn btn-outline-secondary" type="button" id="clearAll">Clear all</button></div>
  <p class="text-muted small"><span id="assignedCount"><?= count($assigned) ?></span> assigned permission(s)</p>
  <?php foreach ($registry as $moduleKey => $module): ?><section class="border rounded p-3 mb-3 permission-module"><div class="d-flex justify-content-between"><h2 class="h6 mb-2"><?= e($module['label']) ?></h2><button type="button" class="btn btn-sm btn-link module-all">Select module</button></div><?php foreach ($module['permissions'] as $key => $permission): ?><label class="form-check permission-item d-block"><input class="form-check-input permission-check" type="checkbox" name="permissions[]" value="<?= e($key) ?>" <?= isset($assigned[$key]) ? 'checked' : '' ?>><span class="form-check-label"><strong><?= e($permission['label']) ?></strong> <small class="text-muted"><?= e($key) ?></small></span></label><?php endforeach; ?></section><?php endforeach; ?>
  <button class="btn btn-primary" type="submit">Save access</button></form>
  <?php endif; ?></div></div></div></div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const checks=[...document.querySelectorAll('.permission-check')], count=document.getElementById('assignedCount'), dashboardAccess=checks.find(x=>x.value==='dashboard.view'), dashboardSections=checks.filter(x=>x.value.startsWith('dashboard.')&&x.value!=='dashboard.view');const update=()=>{if(count)count.textContent=checks.filter(x=>x.checked).length}; document.getElementById('selectAll')?.addEventListener('click',()=>{checks.forEach(x=>x.checked=true);update()});document.getElementById('clearAll')?.addEventListener('click',()=>{checks.forEach(x=>x.checked=false);update()});document.querySelectorAll('.module-all').forEach(b=>b.addEventListener('click',()=>{b.closest('.permission-module').querySelectorAll('.permission-check').forEach(x=>x.checked=true);update()}));dashboardSections.forEach(x=>x.addEventListener('change',()=>{if(x.checked&&dashboardAccess)dashboardAccess.checked=true;update()}));checks.forEach(x=>x.addEventListener('change',update));document.getElementById('permissionSearch')?.addEventListener('input',e=>{let q=e.target.value.toLowerCase();document.querySelectorAll('.permission-item').forEach(x=>x.style.display=x.textContent.toLowerCase().includes(q)?'block':'none')})});</script>
<?php require __DIR__ . '/_layout_bottom.php'; ?>
