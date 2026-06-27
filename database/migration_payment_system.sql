-- Payment system migration (Stripe + gateway management)

CREATE TABLE IF NOT EXISTS payment_methods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  method_name VARCHAR(120) NOT NULL,
  method_type VARCHAR(50) NOT NULL,
  stripe_publishable_key VARCHAR(255) NULL,
  stripe_secret_key VARCHAR(255) NULL,
  mode ENUM('test', 'live') NOT NULL DEFAULT 'test',
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_payment_methods_status (status),
  INDEX idx_payment_methods_type (method_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NULL,
  payment_method VARCHAR(50) NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  provider_event_id VARCHAR(255) NULL,
  transaction_id VARCHAR(255) NULL,
  currency VARCHAR(10) NULL,
  amount DECIMAL(12,2) NULL,
  request_payload LONGTEXT NULL,
  response_payload LONGTEXT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'info',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payment_logs_order (order_id),
  INDEX idx_payment_logs_transaction (transaction_id),
  INDEX idx_payment_logs_provider_event (provider_event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  provider_customer_id VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payment_customers_user_method (user_id, payment_method),
  UNIQUE KEY uq_payment_customers_provider_customer_id (provider_customer_id),
  INDEX idx_payment_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_webhook_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id VARCHAR(255) NOT NULL,
  event_type VARCHAR(120) NOT NULL,
  processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_stripe_webhook_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(255) NULL AFTER payment_status,
  ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(255) NULL AFTER transaction_id,
  ADD COLUMN IF NOT EXISTS currency VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER total_amount,
  ADD INDEX idx_orders_transaction_id (transaction_id),
  ADD INDEX idx_orders_stripe_customer_id (stripe_customer_id);
