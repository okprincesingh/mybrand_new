USE mybrand_cms;

ALTER TABLE admin_sessions
  ADD COLUMN IF NOT EXISTS ip_hash CHAR(64) NOT NULL AFTER token_hash,
  ADD COLUMN IF NOT EXISTS user_agent_hash CHAR(64) NOT NULL AFTER ip_hash,
  ADD INDEX IF NOT EXISTS idx_admin_sessions_fingerprint (ip_hash, user_agent_hash);

CREATE TABLE IF NOT EXISTS admin_refresh_tokens (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  replaced_by_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_refresh_tokens_hash (token_hash),
  INDEX idx_admin_refresh_tokens_admin (admin_id),
  INDEX idx_admin_refresh_tokens_expires (expires_at),
  INDEX idx_admin_refresh_tokens_fingerprint (ip_hash, user_agent_hash),
  CONSTRAINT fk_admin_refresh_tokens_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
  CONSTRAINT fk_admin_refresh_tokens_replaced FOREIGN KEY (replaced_by_id) REFERENCES admin_refresh_tokens(id) ON DELETE SET NULL
) ENGINE=InnoDB;

