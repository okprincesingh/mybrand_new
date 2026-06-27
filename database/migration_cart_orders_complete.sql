USE mybrandplease;
-- Cart table for persistent cart storage
CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(128) NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_product` (`session_id`, `product_id`),
  KEY `session_id` (`session_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(128) NOT NULL,
  `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` VARCHAR(3) DEFAULT 'USD',
  `billing_first_name` VARCHAR(100) NOT NULL,
  `billing_last_name` VARCHAR(100) NOT NULL,
  `billing_email` VARCHAR(255) NOT NULL,
  `billing_phone` VARCHAR(20) DEFAULT NULL,
  `billing_company` VARCHAR(255) DEFAULT NULL,
  `billing_address1` VARCHAR(255) NOT NULL,
  `billing_address2` VARCHAR(255) DEFAULT NULL,
  `billing_city` VARCHAR(100) NOT NULL,
  `billing_state` VARCHAR(100) NOT NULL,
  `billing_zip` VARCHAR(20) NOT NULL,
  `billing_country` VARCHAR(100) NOT NULL DEFAULT 'US',
  `shipping_first_name` VARCHAR(100) DEFAULT NULL,
  `shipping_last_name` VARCHAR(100) DEFAULT NULL,
  `shipping_email` VARCHAR(255) DEFAULT NULL,
  `shipping_phone` VARCHAR(20) DEFAULT NULL,
  `shipping_company` VARCHAR(255) DEFAULT NULL,
  `shipping_address1` VARCHAR(255) DEFAULT NULL,
  `shipping_address2` VARCHAR(255) DEFAULT NULL,
  `shipping_city` VARCHAR(100) DEFAULT NULL,
  `shipping_state` VARCHAR(100) DEFAULT NULL,
  `shipping_zip` VARCHAR(20) DEFAULT NULL,
  `shipping_country` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  KEY `session_id` (`session_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `orders_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `product_slug` VARCHAR(255) NOT NULL,
  `product_image` VARCHAR(500) DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Status History table
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `old_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_history_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
