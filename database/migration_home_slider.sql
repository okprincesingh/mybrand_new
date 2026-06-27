USE mybrand_cms;

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
