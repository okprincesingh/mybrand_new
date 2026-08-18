# RBAC setup

Run `migration_admin_rbac.sql` once against the CMS database. It adds the
`admin_permissions` table and widens the existing role column without changing
any existing URL or account.

The primary `super_admin` bypasses all checks automatically. Other admins must
be granted the relevant permission in `admin/access-management.php`.

New files placed directly in `admin/` and `admin/api/` are discovered in the
Access Management screen automatically. For a feature/action more specific
than page or endpoint access, register it from the module bootstrap:

```php
cms_register_permissions('orders', 'Orders', [
  ['key' => 'orders.refund', 'label' => 'Refund an order'],
]);
// Then gate the operation: admin_require_permission('orders.refund');
```
