-- Working Process Content Migration
-- Single-record, update-only module for the Working Process section hero/intro content.
-- Follows the home_brand_builder pattern (unique section_key + ON DUPLICATE KEY UPDATE).

CREATE TABLE IF NOT EXISTS home_working_process_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  eyebrow_text VARCHAR(255) NOT NULL DEFAULT 'Private Label',
  title_span_text VARCHAR(255) NOT NULL DEFAULT 'Why launch',
  title_text TEXT NOT NULL,
  animation_mode VARCHAR(32) NOT NULL DEFAULT 'default',
  description_text TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add title_span_text column for existing installs (idempotent).
-- MySQL 8+ ignores the error if the column already exists; for older MySQL
-- this statement is safe because the table is created above if missing.
ALTER TABLE home_working_process_content ADD COLUMN IF NOT EXISTS title_span_text VARCHAR(255) NOT NULL DEFAULT 'Why launch' AFTER eyebrow_text;
ALTER TABLE home_working_process_content ADD COLUMN IF NOT EXISTS animation_mode VARCHAR(32) NOT NULL DEFAULT 'default' AFTER title_text;

-- Seed default values matching the current hardcoded frontend content.
-- title_text supports <br> line breaks (rendered as HTML on the frontend).
INSERT INTO home_working_process_content (section_key, eyebrow_text, title_span_text, title_text, animation_mode, description_text, is_active) VALUES
('main', 'Private Label', 'Why launch', 'your own brand', 'default', 'Enhance your brand reputation and profitability with premium private label cosmetic products, low minimum order quantity, and competitive pricing.', 1)
ON DUPLICATE KEY UPDATE
  eyebrow_text = VALUES(eyebrow_text),
  title_span_text = VALUES(title_span_text),
  title_text = VALUES(title_text),
  animation_mode = VALUES(animation_mode),
  description_text = VALUES(description_text),
  updated_at = CURRENT_TIMESTAMP;
