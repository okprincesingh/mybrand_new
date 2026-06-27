USE mybrandplease;

CREATE TABLE IF NOT EXISTS admins (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','editor') NOT NULL DEFAULT 'editor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admins_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  revoked_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admin_sessions_token_hash (token_hash),
  INDEX idx_admin_sessions_admin (admin_id),
  INDEX idx_admin_sessions_expires (expires_at),
  INDEX idx_admin_sessions_fingerprint (ip_hash, user_agent_hash),
  CONSTRAINT fk_admin_sessions_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  content LONGTEXT NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  page_group VARCHAR(100) NOT NULL DEFAULT 'general',
  template_key VARCHAR(100) NOT NULL DEFAULT 'default',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pages_slug (slug),
  INDEX idx_pages_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_meta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  meta_title VARCHAR(255) NULL,
  meta_description TEXT NULL,
  meta_keywords VARCHAR(255) NULL,
  canonical_url VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_meta_page (page_id),
  CONSTRAINT fk_page_meta_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  section_key VARCHAR(120) NOT NULL,
  title VARCHAR(255) NULL,
  body LONGTEXT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_page_sections_page (page_id),
  INDEX idx_page_sections_key (section_key),
  CONSTRAINT fk_page_sections_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_section_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  item_key VARCHAR(120) NULL,
  title VARCHAR(255) NULL,
  body TEXT NULL,
  image_path VARCHAR(255) NULL,
  link_url VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_page_section_items_section (section_id),
  CONSTRAINT fk_page_section_items_section FOREIGN KEY (section_id) REFERENCES page_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value LONGTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_site_settings_key (setting_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menus (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  location_key VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_menus_location_key (location_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS menu_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id BIGINT UNSIGNED NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  title VARCHAR(120) NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_menu_items_menu (menu_id),
  INDEX idx_menu_items_parent (parent_id),
  CONSTRAINT fk_menu_items_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
  CONSTRAINT fk_menu_items_parent FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS footer_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS footer_links (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_footer_links_section (section_id),
  CONSTRAINT fk_footer_links_section FOREIGN KEY (section_id) REFERENCES footer_sections(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  description TEXT NULL,
  image_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_slug (slug),
  INDEX idx_categories_parent (parent_id),
  INDEX idx_categories_active (is_active),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS offers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  offer_type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  offer_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_offers_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NULL,
  offer_id BIGINT UNSIGNED NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL,
  short_description TEXT NULL,
  description LONGTEXT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock INT NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  page_group VARCHAR(100) NOT NULL DEFAULT 'general',
  template_key VARCHAR(100) NOT NULL DEFAULT 'default',
  featured_image VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_products_slug (slug),
  INDEX idx_products_category (category_id),
  INDEX idx_products_status (status),
  INDEX idx_products_price (price),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_products_offer FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_images_product (product_id),
  CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_attributes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  attribute_key VARCHAR(120) NOT NULL,
  attribute_value VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_attributes_product (product_id),
  INDEX idx_product_attributes_key (attribute_key),
  CONSTRAINT fk_product_attributes_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  reviewer_name VARCHAR(120) NOT NULL,
  reviewer_email VARCHAR(190) NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review_text TEXT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_product_reviews_product (product_id),
  INDEX idx_product_reviews_status (status),
  CONSTRAINT fk_product_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
`r`n

-- Performance indexes
CREATE INDEX idx_pages_status_updated ON pages (status, updated_at, id);
CREATE INDEX idx_pages_group_template ON pages (page_group, template_key, status, updated_at, id);
CREATE INDEX idx_page_sections_page_sort ON page_sections (page_id, sort_order, id);
CREATE INDEX idx_page_section_items_section_sort ON page_section_items (section_id, sort_order, id);
CREATE INDEX idx_menu_items_menu_active_sort ON menu_items (menu_id, is_active, sort_order, id);
CREATE INDEX idx_footer_links_section_sort ON footer_links (section_id, sort_order, id);
CREATE INDEX idx_categories_active_parent_name ON categories (is_active, parent_id, name);
CREATE INDEX idx_products_active_status_created ON products (is_active, status, created_at, id);
CREATE INDEX idx_products_active_category_price ON products (is_active, category_id, price, id);
CREATE INDEX idx_product_images_product_sort ON product_images (product_id, sort_order, id);
CREATE INDEX idx_product_reviews_product_status ON product_reviews (product_id, status, id);


CREATE TABLE IF NOT EXISTS home_slides (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  badge_text VARCHAR(255) NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  button_text VARCHAR(120) NULL,
  button_url VARCHAR(255) NULL,
  image_path VARCHAR(255) NOT NULL,
  image_alt VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_slides_active_order (is_active, sort_order, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS home_testimonials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  location VARCHAR(255) NULL,
  content TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  image_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_testimonials_active_order (is_active, sort_order, id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS home_offices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country VARCHAR(150) NOT NULL,
  address TEXT NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NULL,
  image_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_offices_active_order (is_active, sort_order, id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS why_page_accordions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  body_html LONGTEXT NULL,
  is_open TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_why_page_accordions_page (page_id, is_active, sort_order, id),
  CONSTRAINT fk_why_page_accordions_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS home_instagram_reels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reel_url VARCHAR(500) NULL,
  video_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_instagram_reels_active_order (is_active, sort_order, id)
) ENGINE=InnoDB;


