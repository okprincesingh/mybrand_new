-- Additive RBAC migration. Existing admin URLs, accounts and authentication remain unchanged.
ALTER TABLE admins MODIFY role VARCHAR(50) NOT NULL DEFAULT 'editor';

CREATE TABLE IF NOT EXISTS admin_permissions (
  admin_id BIGINT UNSIGNED NOT NULL,
  permission_key VARCHAR(190) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (admin_id, permission_key),
  CONSTRAINT fk_admin_permissions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
