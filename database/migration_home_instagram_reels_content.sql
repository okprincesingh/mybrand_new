-- Migration for Instagram Reels Header Content & Performance Indexing

CREATE TABLE IF NOT EXISTS home_instagram_reels_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  eyebrow_text VARCHAR(255) NOT NULL DEFAULT 'Video Showcase',
  heading_html TEXT NOT NULL,
  intro_text TEXT NOT NULL DEFAULT 'We don’t just manufacture products. We manufacture dominance.',
  tagline_text TEXT NOT NULL DEFAULT 'mybrandplease.com - turns your ambition into artistry, and your brand into a lasting legacy.',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_reels_content_key_active (section_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_instagram_reels_content (section_key, eyebrow_text, heading_html, intro_text, tagline_text, is_active)
VALUES (
  'main',
  'Video Showcase',
  '<span>Watch it!</span> <span class="social-reels__title-star" aria-hidden="true">*</span> <span class="social-reels__title-love">Love it!</span> <span class="social-reels__title-star" aria-hidden="true">*</span> <span>Build it!</span>',
  'We don’t just manufacture products. We manufacture dominance.',
  'mybrandplease.com - turns your ambition into artistry, and your brand into a lasting legacy.',
  1
)
ON DUPLICATE KEY UPDATE
  eyebrow_text = VALUES(eyebrow_text),
  heading_html = VALUES(heading_html),
  intro_text = VALUES(intro_text),
  tagline_text = VALUES(tagline_text);

-- Add performance index on home_instagram_reels if table exists
CREATE INDEX IF NOT EXISTS idx_home_reels_active_order ON home_instagram_reels (is_active, sort_order, id);
