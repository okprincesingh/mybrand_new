-- Migration for Home Offices Header Content & Performance Indexing

CREATE TABLE IF NOT EXISTS home_offices_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  eyebrow_text VARCHAR(255) NOT NULL DEFAULT 'GLOBAL PRESENCE',
  heading_text VARCHAR(255) NOT NULL DEFAULT 'Our Global Network',
  subheading_text VARCHAR(255) NOT NULL DEFAULT 'Our Group of Companies & Global Registered Offices',
  intro_text TEXT NOT NULL DEFAULT 'Our registered offices across key markets bring local expertise, seamless coordination, and responsive support to every partnership.',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_offices_content_key_active (section_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_offices_content (section_key, eyebrow_text, heading_text, subheading_text, intro_text, is_active)
VALUES (
  'main',
  'GLOBAL PRESENCE',
  'Our Global Network',
  'Our Group of Companies & Global Registered Offices',
  'Our registered offices across key markets bring local expertise, seamless coordination, and responsive support to every partnership.',
  1
)
ON DUPLICATE KEY UPDATE
  eyebrow_text = VALUES(eyebrow_text),
  heading_text = VALUES(heading_text),
  subheading_text = VALUES(subheading_text),
  intro_text = VALUES(intro_text);

-- Add performance index on home_offices table if not exists
CREATE INDEX IF NOT EXISTS idx_home_offices_active_order ON home_offices (is_active, sort_order, id);
