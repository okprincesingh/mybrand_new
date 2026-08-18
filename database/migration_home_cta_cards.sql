-- Homepage CTA Cards (URLs Module) Migration
-- Manages the 3 homepage CTA cards ("Explore Now", "Try Our Products", "Contact Us") and custom added cards

CREATE TABLE IF NOT EXISTS home_cta_cards (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  card_key VARCHAR(50) NULL UNIQUE,
  title VARCHAR(120) NOT NULL,
  button_text VARCHAR(120) NOT NULL,
  button_url VARCHAR(255) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  image_alt VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_cta_cards_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert seed data if table is empty
INSERT INTO home_cta_cards (card_key, title, button_text, button_url, image_path, image_alt, sort_order, is_active)
SELECT * FROM (
    SELECT 'explore_now' AS card_key, 'Explore Now' AS title, 'Explore now' AS button_text, 'shop.php' AS button_url, 'assets/imgs/category/category_thumb2.jpeg' AS image_path, 'Explore now thumb' AS image_alt, 1 AS sort_order, 1 AS is_active
    UNION ALL
    SELECT 'try_products' AS card_key, 'Try Our Products' AS title, 'Try Our Products' AS button_text, 'shop.php' AS button_url, 'assets/imgs/category/category_thumb3.jpeg' AS image_path, 'Try Our Products thumb' AS image_alt, 2 AS sort_order, 1 AS is_active
    UNION ALL
    SELECT 'contact_us' AS card_key, 'Contact Us' AS title, 'Contact Us' AS button_text, 'shop.php' AS button_url, 'assets/imgs/category/category_thumb1.jpeg' AS image_path, 'Contact Us thumb' AS image_alt, 3 AS sort_order, 1 AS is_active
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM home_cta_cards LIMIT 1);
