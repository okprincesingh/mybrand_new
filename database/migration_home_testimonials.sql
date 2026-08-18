-- Home Testimonials Section Header & Content Migration

CREATE TABLE IF NOT EXISTS home_testimonials_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  eyebrow_text VARCHAR(255) NOT NULL DEFAULT 'Verified Reviews',
  heading_text VARCHAR(255) NOT NULL DEFAULT 'Here\'s what our customers say',
  rating_prefix VARCHAR(255) NOT NULL DEFAULT 'mybrandplease.com is rated',
  rating_highlight VARCHAR(255) NOT NULL DEFAULT 'Excellent',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_testimonials_content_key_active (section_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_testimonials_content (section_key, eyebrow_text, heading_text, rating_prefix, rating_highlight, is_active)
VALUES ('main', 'Verified Reviews', 'Here\'s what our customers say', 'mybrandplease.com is rated', 'Excellent', 1)
ON DUPLICATE KEY UPDATE
  eyebrow_text = VALUES(eyebrow_text),
  heading_text = VALUES(heading_text),
  rating_prefix = VALUES(rating_prefix),
  rating_highlight = VALUES(rating_highlight);

-- Ensure platform and review_date columns exist in home_testimonials
CREATE TABLE IF NOT EXISTS home_testimonials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(20) NOT NULL DEFAULT 'tp',
  name VARCHAR(150) NOT NULL,
  location VARCHAR(255) NULL,
  content TEXT NOT NULL,
  rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
  review_date VARCHAR(50) NULL,
  image_path VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_testimonials_active_order (is_active, sort_order, id),
  INDEX idx_home_testimonials_plat_active_order (platform, is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add platform column if table already existed without it
SET @dbname = DATABASE();
SET @tablename = "home_testimonials";
SET @columnname = "platform";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE home_testimonials ADD COLUMN platform VARCHAR(20) NOT NULL DEFAULT 'tp' AFTER id;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add review_date column if table already existed without it
SET @columnname = "review_date";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE home_testimonials ADD COLUMN review_date VARCHAR(50) NULL AFTER rating;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Seed initial 15 reviews if home_testimonials is empty
INSERT INTO home_testimonials (platform, name, content, rating, review_date, sort_order, is_active)
SELECT * FROM (
  SELECT 'tp' AS platform, 'Steve Marc' AS name, 'Communication was clear and professional from the beginning, the team stayed responsive, and the products arrived on time with quality that met expectations.' AS content, 5 AS rating, '8 Mar 2026' AS review_date, 1 AS sort_order, 1 AS is_active UNION ALL
  SELECT 'tp', 'Zain Sheikh', 'A professional long-term partner with strong expertise across formulation, packaging, design, compliance, and customer service.', 5, '21 Feb 2026', 2, 1 UNION ALL
  SELECT 'tp', 'Meghana Ghosh', 'mybrandplease supported the brand from concept to launch with guidance on ingredients, positioning, compliance, packaging, and market readiness.', 5, '15 Feb 2026', 3, 1 UNION ALL
  SELECT 'tp', 'Yawovi Yevoudakor', 'Good products, helpful customer service, and a pleasant purchase experience made it easy to return for another order.', 5, '16 Oct 2025', 4, 1 UNION ALL
  SELECT 'tp', 'Elina', 'The hair care range delivered top-shelf quality and made launching a new brand feel simple and successful.', 5, '11 May 2025', 5, 1 UNION ALL
  SELECT 'goog', 'Priya Mehta', 'Incredible service from start to finish. They handled formulation and labeling while keeping the MOQ practical for a startup brand.', 5, '9 May 2026', 6, 1 UNION ALL
  SELECT 'goog', 'James Carter', 'Exceptional quality control and a responsive team. The custom formulation matched the brief and gave us confidence to expand the line.', 5, '1 May 2026', 7, 1 UNION ALL
  SELECT 'goog', 'Ananya Joshi', 'The team guided us through each step of the private label process and helped the final products look premium.', 5, '24 Apr 2026', 8, 1 UNION ALL
  SELECT 'goog', 'Rahul Sharma', 'Top quality private label formulations with noticeable customer response after switching to mybrandplease.', 5, '18 Apr 2026', 9, 1 UNION ALL
  SELECT 'goog', 'Nisha Kapoor', 'Supportive communication, polished packaging, and dependable timelines made the launch process much smoother.', 5, '12 Apr 2026', 10, 1 UNION ALL
  SELECT 'ali', 'Li Wei', 'A strong B2B supplier for private label cosmetics with fast communication and reliable bulk order delivery.', 5, '6 May 2026', 11, 1 UNION ALL
  SELECT 'ali', 'Maria Santos', 'Custom branding was handled well, the products passed quality checks, and the pricing stayed competitive for reorder planning.', 5, '28 Apr 2026', 12, 1 UNION ALL
  SELECT 'ali', 'Omar Khan', 'Samples, packaging options, and production details were explained clearly, which helped us move forward with confidence.', 5, '19 Apr 2026', 13, 1 UNION ALL
  SELECT 'ali', 'Sofia Martins', 'The team responded quickly during sourcing and kept the order organized from product selection through dispatch.', 5, '10 Apr 2026', 14, 1 UNION ALL
  SELECT 'ali', 'Daniel Roberts', 'Reliable supplier experience with clear communication, good packaging quality, and consistent private label support.', 5, '2 Apr 2026', 15, 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM home_testimonials LIMIT 1);
