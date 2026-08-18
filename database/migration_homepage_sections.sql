-- Homepage Dynamic Sections Migration
-- This migration creates tables for managing homepage content through admin panel

-- Working Process Cards
CREATE TABLE IF NOT EXISTS home_working_process (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title_small VARCHAR(120) NOT NULL,
  title_large VARCHAR(120) NOT NULL,
  text TEXT NOT NULL,
  href VARCHAR(255) NOT NULL DEFAULT 'contact.php',
  image_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_working_process_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Brand Builder Section
CREATE TABLE IF NOT EXISTS home_brand_builder (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  kicker_text TEXT NULL,
  title_text TEXT NOT NULL,
  subtitle_text TEXT NULL,
  primary_btn_text VARCHAR(120) NULL,
  primary_btn_url VARCHAR(255) NULL,
  secondary_btn_text VARCHAR(120) NULL,
  secondary_btn_url VARCHAR(255) NULL,
  stat_1_number VARCHAR(50) NULL,
  stat_1_label VARCHAR(120) NULL,
  stat_2_number VARCHAR(50) NULL,
  stat_2_label VARCHAR(120) NULL,
  stat_3_number VARCHAR(50) NULL,
  stat_3_label VARCHAR(120) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Brand Builder Rotating Words and Images
CREATE TABLE IF NOT EXISTS home_brand_builder_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  word_text VARCHAR(120) NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  image_alt VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_brand_builder_items_section (section_id, sort_order, id),
  CONSTRAINT fk_brand_builder_items_section FOREIGN KEY (section_id) REFERENCES home_brand_builder(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Getting Started Steps
CREATE TABLE IF NOT EXISTS home_getting_started (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  step_number VARCHAR(10) NOT NULL,
  icon_emoji VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  learn_more_url VARCHAR(255) NOT NULL,
  back_image_path VARCHAR(255) NOT NULL,
  back_image_alt VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_getting_started_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marquee Strips (for working process strip and partner logos)
CREATE TABLE IF NOT EXISTS home_marquee_strips (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  strip_key VARCHAR(120) NOT NULL UNIQUE,
  items TEXT NOT NULL,
  brand_text VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Partner/Manufactured Products Marquee
CREATE TABLE IF NOT EXISTS home_partner_logos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  logo_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_partner_logos_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Certification/Partner Marquee (auto-scroll section)
CREATE TABLE IF NOT EXISTS home_certification_logos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  logo_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_certification_logos_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data for brand builder section
INSERT INTO home_brand_builder (section_key, kicker_text, title_text, subtitle_text, primary_btn_text, primary_btn_url, secondary_btn_text, secondary_btn_url, stat_1_number, stat_1_label, stat_2_number, stat_2_label, stat_3_number, stat_3_label, is_active) VALUES
('main', 'Just add your brand.<br>mybrandplease.com handles the rest.', 'The modern<br>way to build a<br><span class="brand-builder__changing-word" data-brand-builder-word>skin care</span> <br>brand', 'Start Free Today! - Lowest MOQ | Premium Packaging | World-Class Manufacturing', 'Explore Private Label', 'shop.php', 'Explore Custom Formulation', 'services.php', '100K+', 'Brands built', '4.9 ★', 'Over 400 reviews', '1M+', 'Orders shipped', 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default brand builder items
INSERT INTO home_brand_builder_items (section_id, word_text, image_path, image_alt, sort_order, is_active) VALUES
(1, 'skin care', 'assets/imgs/modern/1.jpg', 'Skin care product category', 1, 1),
(1, 'hair care', 'assets/imgs/modern/2.jpg', 'Hair care product category', 2, 1),
(1, 'body care', 'assets/imgs/modern/3.jpg', 'Body care product category', 3, 1),
(1, 'bath products', 'assets/imgs/modern/4.jpg', 'Bath products category', 4, 1),
(1, 'styling products', 'assets/imgs/modern/5.jpg', 'Styling products category', 5, 1),
(1, 'wellness products', 'assets/imgs/modern/6.jpg', 'Wellness products category', 6, 1),
(1, 'nature inspired', 'assets/imgs/modern/7.jpg', 'Facial masks category', 7, 1),
(1, 'essential oils', 'assets/imgs/modern/8.jpg', 'Essential oils category', 8, 1),
(1, "men's grooming", 'assets/imgs/modern/9.jpg', "Men's grooming category", 9, 1),
(1, 'lip balm & scrub', 'assets/imgs/modern/10.jpg', 'Lip balm and scrub category', 10, 1),
(1, 'handmade soaps', 'assets/imgs/modern/11.jpg', 'Handmade soaps category', 11, 1),
(1, 'medicated soaps', 'assets/imgs/modern/12.jpg', 'Medicated soaps category', 12, 1),
(1, 'beauty soaps', 'assets/imgs/modern/13.jpg', 'Beauty soaps category', 13, 1),
(1, 'scented candles', 'assets/imgs/modern/14.jpg', 'Scented candles category', 14, 1),
(1, 'reed diffusers', 'assets/imgs/modern/15.jpg', 'Reed diffusers category', 15, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default working process steps
INSERT INTO home_working_process (title_small, title_large, text, href, image_path, alt_text, sort_order, is_active) VALUES
('Brand', 'Equity', 'Building sales of your own skin and hair care brand strengthens your prestige with customers and in the market.', 'contact.php', 'assets/imgs/home/4.png', 'Brand equity', 1, 1),
('Client', 'Retention', 'Retain customers with your own brand while offering premium product experiences at strong pricing, helping you create brand loyalty.', 'contact.php', 'assets/imgs/home/3.png', 'Customer loyalty', 2, 1),
('Increased', 'Sales', 'Market your own brand with margin and product sale price in your control, giving you stronger flexibility in marketing approach and decisions.', 'contact.php', 'assets/imgs/home/2.png', 'Increased sales', 3, 1),
('Higher', 'Profits', 'Our high-quality natural and organic-based skin and hair care products are offered at costs comparable to or lower than leading brands, while you set the sale price.', 'contact.php', 'assets/imgs/home/1.png', 'Profit growth', 4, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default getting started steps
INSERT INTO home_getting_started (step_number, icon_emoji, title, description, learn_more_url, back_image_path, back_image_alt, sort_order, is_active) VALUES
('01', '🎨', 'Order Sample & Determine Products', 'We offer over 200 formulations in body, skin, and hair care. Choose your favourites that you know your clients will love and order samples online.', 'how-it-works.php#define-offerings', 'assets/imgs/how-it-works/1.png', 'Order samples and determine products', 1, 1),
('02', '🧴', 'Consult with Us on Packaging', 'Focus on your message and the details of your opening order. Identify which packaging works best with your products and your brand.', 'how-it-works.php#product-components', 'assets/imgs/how-it-works/2.png', 'Choose product packaging components', 2, 1),
('03', '✨', 'Get Your Label Designed', 'With the help of our label designing experts, see your brand come to life. We can also assist your designer on label designing of your choice.', 'how-it-works.php#design-and-printing', 'assets/imgs/how-it-works/3.png', 'Label design and printing', 3, 1),
('04', '📦', 'Consider Finishing Touches', 'Details are everything. We can assist you with product boxes, shrink wrap, inserts, and much more to perfect your presentation.', 'how-it-works.php#finishing-touches', 'assets/imgs/how-it-works/4.png', 'Finishing touches for private label packaging', 4, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default marquee strip for working process
INSERT INTO home_marquee_strips (strip_key, items, brand_text, is_active) VALUES
('working_process_services', 'Skin Care,Hair Care,Body Care,Fragrances,Cosmetic Packaging', 'mybrandplease.com', 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default partner logos
INSERT INTO home_partner_logos (logo_path, alt_text, sort_order, is_active) VALUES
('assets/imgs/home/Amazon-logo-min-300x126.jpg', 'Amazon', 1, 1),
('assets/imgs/home/Costco_Wholesale_logo-min-300x108.jpg', 'Costco', 2, 1),
('assets/imgs/home/desertcart-logo-min-300x74.jpg', 'Desert Cart', 3, 1),
('assets/imgs/home/EBay_logo-min-300x120.jpg', 'eBay', 4, 1),
('assets/imgs/home/Etsy-min-300x171.jpg', 'Etsy', 5, 1),
('assets/imgs/home/final_logo_1_37ee31bf-a041-4af1-9b0e-d86fd4b2da83-300x85.jpg', 'MyBrand', 6, 1),
('assets/imgs/home/iherb-min-300x117.jpg', 'iHerb', 7, 1),
('assets/imgs/home/Macys_Logo-min-300x86.jpg', 'Macy''s', 8, 1),
('assets/imgs/home/Nordstrom-logo-min-300x169.jpg', 'Nordstrom', 9, 1),
('assets/imgs/home/Saks_Fifth_Avenue_Logo_-min-300x225.jpg', 'Saks Fifth Avenue', 10, 1),
('assets/imgs/home/target-min-300x83.jpg', 'Target', 11, 1),
('assets/imgs/home/the-detox-market-min-300x28.jpg', 'The Detox Market', 12, 1),
('assets/imgs/home/TJ_Maxx-min-300x96.jpg', 'TJ Maxx', 13, 1),
('assets/imgs/home/Walmart_logo.svg-min-300x72.jpg', 'Walmart', 14, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Insert default certification logos
INSERT INTO home_certification_logos (logo_path, alt_text, sort_order, is_active) VALUES
('assets/imgs/partner-logos/31.png', 'TÜV Rheinland', 1, 1),
('assets/imgs/partner-logos/CLEAN%20LABEL.png', 'Clean Label', 2, 1),
('assets/imgs/partner-logos/COSMOS.png', 'Cosmos', 3, 1),
('assets/imgs/partner-logos/CPNP.png', 'CPNP Registered', 4, 1),
('assets/imgs/partner-logos/CREDO%20New.png', 'Credo', 5, 1),
('assets/imgs/partner-logos/Cruelty%20Free.png', 'Cruelty Free', 6, 1),
('assets/imgs/partner-logos/EWG.png', 'EWG Verified', 7, 1),
('assets/imgs/partner-logos/GLP.png', 'GLP Certified', 8, 1),
('assets/imgs/partner-logos/GMP.png', 'GMP Certified', 9, 1),
('assets/imgs/partner-logos/LOW%20MOQ.png', 'Low MOQ', 10, 1),
('assets/imgs/partner-logos/MADE%20SAFE.png', 'Made Safe', 11, 1),
('assets/imgs/partner-logos/MOCRA.png', 'MOCRA Compliant', 12, 1),
('assets/imgs/partner-logos/SEPHORA.png', 'Sephora', 13, 1),
('assets/imgs/partner-logos/USDA%20ORGANIC.png', 'USDA Organic', 14, 1),
('assets/imgs/partner-logos/USFDA.png', 'FDA Registered', 15, 1),
('assets/imgs/partner-logos/VEGAN.png', 'Vegan', 16, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;