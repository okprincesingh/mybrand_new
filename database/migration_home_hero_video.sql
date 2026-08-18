-- Homepage Hero Video migration
-- Creates the home_hero_videos table for admin-managed hero videos.
-- Live site falls back to hardcoded URLs when no active record exists.

CREATE TABLE IF NOT EXISTS home_hero_videos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(180) NULL,
  desktop_video_url VARCHAR(500) NULL,
  desktop_light_video_url VARCHAR(500) NULL,
  mobile_video_url VARCHAR(500) NULL,
  desktop_video_file VARCHAR(255) NULL,
  desktop_light_video_file VARCHAR(255) NULL,
  mobile_video_file VARCHAR(255) NULL,
  poster_image VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_hero_videos_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
