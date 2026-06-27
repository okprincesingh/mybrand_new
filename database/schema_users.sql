-- User Management Database Schema
-- Add this to your existing database schema

-- Users table for customer accounts
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  date_of_birth DATE DEFAULT NULL,
  gender ENUM('male','female','other','prefer_not_to_say') DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  email_verified_at DATETIME NULL,
  email_verification_token VARCHAR(255) NULL,
  password_reset_token VARCHAR(255) NULL,
  password_reset_expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_email (email),
  INDEX idx_users_active (is_active),
  INDEX idx_users_verification_token (email_verification_token),
  INDEX idx_users_reset_token (password_reset_token)
) ENGINE=InnoDB;

-- User addresses table
CREATE TABLE IF NOT EXISTS user_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  type ENUM('billing','shipping','both') NOT NULL DEFAULT 'both',
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  company VARCHAR(255) DEFAULT NULL,
  address1 VARCHAR(255) NOT NULL,
  address2 VARCHAR(255) DEFAULT NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  zip VARCHAR(20) NOT NULL,
  country VARCHAR(100) NOT NULL DEFAULT 'US',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_addresses_user (user_id),
  INDEX idx_user_addresses_default (user_id, is_default),
  CONSTRAINT fk_user_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User wishlist table
CREATE TABLE IF NOT EXISTS user_wishlist (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_wishlist (user_id, product_id),
  INDEX idx_user_wishlist_user (user_id),
  INDEX idx_user_wishlist_product (product_id),
  CONSTRAINT fk_user_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User sessions table for persistent login
CREATE TABLE IF NOT EXISTS user_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  session_token VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_sessions_user (user_id),
  INDEX idx_user_sessions_token (session_token),
  INDEX idx_user_sessions_expires (expires_at),
  CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User order history view (existing orders table will be linked)
-- Note: Orders table already exists, we'll link users to existing orders
ALTER TABLE orders ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER customer_id;
ALTER TABLE orders ADD INDEX idx_orders_user (user_id);
ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Update existing customers to link with users if needed
-- This would be handled during migration