USE mybrandplease;

CREATE TABLE IF NOT EXISTS shipping_methods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  method_name VARCHAR(120) NOT NULL,
  shipping_type ENUM('flat_rate','free_shipping','weight_based','price_based') NOT NULL,
  cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  min_order_amount DECIMAL(10,2) NULL,
  weight_min DECIMAL(10,3) NULL,
  weight_max DECIMAL(10,3) NULL,
  price_min DECIMAL(10,2) NULL,
  price_max DECIMAL(10,2) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  priority INT NOT NULL DEFAULT 0,
  estimated_delivery_days INT UNSIGNED NULL,
  zone_states TEXT NULL,
  zone_id BIGINT UNSIGNED NULL,
  rate_source ENUM('manual','api') NOT NULL DEFAULT 'manual',
  provider_code VARCHAR(50) NULL,
  provider_service_code VARCHAR(100) NULL,
  cache_ttl_seconds INT UNSIGNED NOT NULL DEFAULT 300,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_shipping_status_priority (status, priority, id),
  INDEX idx_shipping_type (shipping_type),
  INDEX idx_shipping_zone (zone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE shipping_methods
  ADD COLUMN IF NOT EXISTS zone_id BIGINT UNSIGNED NULL AFTER zone_states,
  ADD COLUMN IF NOT EXISTS rate_source ENUM('manual','api') NOT NULL DEFAULT 'manual' AFTER zone_id,
  ADD COLUMN IF NOT EXISTS provider_code VARCHAR(50) NULL AFTER rate_source,
  ADD COLUMN IF NOT EXISTS provider_service_code VARCHAR(100) NULL AFTER provider_code,
  ADD COLUMN IF NOT EXISTS cache_ttl_seconds INT UNSIGNED NOT NULL DEFAULT 300 AFTER provider_service_code;

CREATE TABLE IF NOT EXISTS shipping_zones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_name VARCHAR(120) NOT NULL,
  country_code VARCHAR(5) NOT NULL DEFAULT 'IN',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shipping_zone_name_country (zone_name, country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_zone_states (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_id BIGINT UNSIGNED NOT NULL,
  state_name VARCHAR(100) NOT NULL,
  state_code VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_zone_state (zone_id, state_name),
  INDEX idx_zone_state_name (state_name),
  CONSTRAINT fk_shipping_zone_states_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipping_provider_configs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_code VARCHAR(50) NOT NULL UNIQUE,
  provider_name VARCHAR(120) NOT NULL,
  api_base_url VARCHAR(255) NULL,
  api_key VARCHAR(255) NULL,
  api_secret VARCHAR(255) NULL,
  token VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shipping_provider_configs (provider_code, provider_name, api_base_url, is_active)
VALUES
  ('delhivery', 'Delhivery', 'https://track.delhivery.com', 0),
  ('shiprocket', 'Shiprocket', 'https://apiv2.shiprocket.in', 0)
ON DUPLICATE KEY UPDATE provider_name = VALUES(provider_name), api_base_url = VALUES(api_base_url);

INSERT INTO shipping_zones (zone_name, country_code, is_active)
VALUES ('India - All States', 'IN', 1)
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active);

SET @india_zone_id := (SELECT id FROM shipping_zones WHERE zone_name = 'India - All States' AND country_code = 'IN' LIMIT 1);
INSERT IGNORE INTO shipping_zone_states (zone_id, state_name) VALUES
(@india_zone_id, 'Andhra Pradesh'), (@india_zone_id, 'Arunachal Pradesh'), (@india_zone_id, 'Assam'), (@india_zone_id, 'Bihar'), (@india_zone_id, 'Chhattisgarh'),
(@india_zone_id, 'Goa'), (@india_zone_id, 'Gujarat'), (@india_zone_id, 'Haryana'), (@india_zone_id, 'Himachal Pradesh'), (@india_zone_id, 'Jharkhand'),
(@india_zone_id, 'Karnataka'), (@india_zone_id, 'Kerala'), (@india_zone_id, 'Madhya Pradesh'), (@india_zone_id, 'Maharashtra'), (@india_zone_id, 'Manipur'),
(@india_zone_id, 'Meghalaya'), (@india_zone_id, 'Mizoram'), (@india_zone_id, 'Nagaland'), (@india_zone_id, 'Odisha'), (@india_zone_id, 'Punjab'),
(@india_zone_id, 'Rajasthan'), (@india_zone_id, 'Sikkim'), (@india_zone_id, 'Tamil Nadu'), (@india_zone_id, 'Telangana'), (@india_zone_id, 'Tripura'),
(@india_zone_id, 'Uttar Pradesh'), (@india_zone_id, 'Uttarakhand'), (@india_zone_id, 'West Bengal'), (@india_zone_id, 'Andaman and Nicobar Islands'), (@india_zone_id, 'Chandigarh'),
(@india_zone_id, 'Dadra and Nagar Haveli and Daman and Diu'), (@india_zone_id, 'Delhi'), (@india_zone_id, 'Jammu and Kashmir'), (@india_zone_id, 'Ladakh'), (@india_zone_id, 'Lakshadweep'),
(@india_zone_id, 'Puducherry');

SET @fk_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'shipping_methods'
    AND CONSTRAINT_NAME = 'fk_shipping_methods_zone'
);
SET @sql := IF(@fk_exists = 0,
  'ALTER TABLE shipping_methods ADD CONSTRAINT fk_shipping_methods_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE SET NULL',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS shipping_method VARCHAR(150) NULL AFTER shipping_cost,
  ADD COLUMN IF NOT EXISTS shipping_method_id BIGINT UNSIGNED NULL AFTER shipping_method;

CREATE INDEX idx_orders_shipping_method_id ON orders (shipping_method_id);
