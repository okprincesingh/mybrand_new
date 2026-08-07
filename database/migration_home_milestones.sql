-- Home Milestones Migration
-- Create tables for managing "Our Milestones" section content and dynamic cards

CREATE TABLE IF NOT EXISTS home_milestones_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  eyebrow_text VARCHAR(255) NOT NULL DEFAULT 'Growth Snapshot',
  heading_text VARCHAR(255) NOT NULL DEFAULT 'Our Milestones',
  description_text TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_milestones_content_key_active (section_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_milestones_content (section_key, eyebrow_text, heading_text, description_text, is_active)
VALUES ('main', 'Growth Snapshot', 'Our Milestones', 'A quick look at the scale, consistency, and trust we keep building with every private label partnership.', 1)
ON DUPLICATE KEY UPDATE
  eyebrow_text = VALUES(eyebrow_text),
  heading_text = VALUES(heading_text),
  description_text = VALUES(description_text);

CREATE TABLE IF NOT EXISTS home_milestones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(255) NOT NULL,
  image_alt VARCHAR(255) NOT NULL DEFAULT '',
  kicker VARCHAR(120) NOT NULL DEFAULT '',
  number_value VARCHAR(120) NOT NULL DEFAULT '',
  title TEXT NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_milestones_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_milestones (image_path, image_alt, kicker, number_value, title, sort_order, is_active)
SELECT * FROM (
  SELECT 'assets/imgs/home/milestone/4381dcfc16-300x254.webp' AS image_path, 'Monthly worldwide inquiries' AS image_alt, 'Monthly Avg.' AS kicker, '1075+' AS number_value, 'Monthly Worldwide Inquiries' AS title, 1 AS sort_order, 1 AS is_active UNION ALL
  SELECT 'assets/imgs/home/milestone/f99c232e29-2-300x202.webp', 'Customers served monthly', 'Monthly Avg.', '950+', 'Customer\'s Served Monthly', 2, 1 UNION ALL
  SELECT 'assets/imgs/home/milestone/ec2ce0607f-150x150.webp', 'Contract manufacturing for brands', 'Brand Scale', '650+', 'Contract Manufacturing for Brands', 3, 1 UNION ALL
  SELECT 'assets/imgs/home/milestone/b3099fe017-150x150.webp', 'Ayurvedic personal care formulations', 'Formula Library', '525+', 'Ayurvedic Personal Care Formulations', 4, 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM home_milestones LIMIT 1);
