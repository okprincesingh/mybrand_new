-- Content Drafts migration
-- Stores draft JSON overrides for any content type (home modules, products, pages, blogs, etc).
-- Real table rows are never modified until Publish is clicked.

CREATE TABLE IF NOT EXISTS content_drafts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_type VARCHAR(80) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  draft_data LONGTEXT NOT NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_content_draft (content_type, entity_id),
  INDEX idx_content_drafts_type (content_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;