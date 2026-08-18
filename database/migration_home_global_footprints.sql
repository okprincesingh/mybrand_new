-- Dynamic pins for the homepage Global Footprint map.
CREATE TABLE IF NOT EXISTS home_global_footprint_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  location_name VARCHAR(255) NOT NULL,
  formatted_address VARCHAR(500) NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  map_top DECIMAL(6,3) NOT NULL,
  map_left DECIMAL(6,3) NOT NULL,
  pin_height SMALLINT UNSIGNED NOT NULL DEFAULT 55,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_home_global_footprint_active_order (is_active, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
