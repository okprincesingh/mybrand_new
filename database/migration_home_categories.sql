-- Home Categories Migration
-- Manages the "Nature Powered Ingredients" section heading and category tabs.
-- Categories are now referenced from the catalog `categories` table.

-- Section heading (single-record, update-only module)
CREATE TABLE IF NOT EXISTS home_category_section (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  title_text VARCHAR(255) NOT NULL DEFAULT 'Nature Powered Ingredients',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Category tabs (multi-record) — now stores references to catalog categories
CREATE TABLE IF NOT EXISTS home_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_home_categories_category_id (category_id),
  INDEX idx_home_categories_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subcategory selection table
CREATE TABLE IF NOT EXISTS home_category_subcategories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  home_category_id BIGINT UNSIGNED NOT NULL,
  subcategory_id BIGINT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_home_cat_subcat (home_category_id, subcategory_id),
  INDEX idx_home_cat_subcat_order (home_category_id, sort_order, id),
  CONSTRAINT fk_home_cat_subcat_home FOREIGN KEY (home_category_id) REFERENCES home_categories(id) ON DELETE CASCADE,
  CONSTRAINT fk_home_cat_subcat_catalog FOREIGN KEY (subcategory_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default section heading
INSERT INTO home_category_section (section_key, title_text, is_active) VALUES
('main', 'Nature Powered Ingredients', 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- ============================================================
-- Migration steps to run directly in MySQL (for existing installs)
-- ============================================================

-- 1. Add category_id column to home_categories (references catalog categories.id)
-- ALTER TABLE home_categories
--   ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id,
--   ADD UNIQUE KEY uq_home_categories_category_id (category_id);

-- 2. Drop the old slug unique key (we no longer store slugs in this table)
-- ALTER TABLE home_categories
--   DROP INDEX uq_home_categories_slug;

-- 3. Create the subcategory selection table
-- CREATE TABLE IF NOT EXISTS home_category_subcategories (
--   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   home_category_id BIGINT UNSIGNED NOT NULL,
--   subcategory_id BIGINT UNSIGNED NOT NULL,
--   sort_order INT NOT NULL DEFAULT 0,
--   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   UNIQUE KEY uq_home_cat_subcat (home_category_id, subcategory_id),
--   INDEX idx_home_cat_subcat_order (home_category_id, sort_order, id),
--   CONSTRAINT fk_home_cat_subcat_home FOREIGN KEY (home_category_id) REFERENCES home_categories(id) ON DELETE CASCADE,
--   CONSTRAINT fk_home_cat_subcat_catalog FOREIGN KEY (subcategory_id) REFERENCES categories(id) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Clear old seed data from home_categories (will be re-selected via admin UI)
-- DELETE FROM home_categories;