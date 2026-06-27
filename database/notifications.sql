CREATE TABLE IF NOT EXISTS admin_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
  action_url VARCHAR(255) NULL,
  action_text VARCHAR(100) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  INDEX idx_notifications_read (is_read),
  INDEX idx_notifications_type (type),
  INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

-- Insert sample notifications for testing
INSERT INTO admin_notifications (title, message, type, action_url, action_text) VALUES
('New User Registration', 'A new user has registered: john.doe@example.com', 'info', 'users.php', 'View Users'),
('New Order Created', 'Order #ORD-2024-001 has been placed for $150.00', 'success', 'orders.php', 'View Orders'),
('Low Stock Alert', 'Product "Face Cream" is running low on stock (5 units remaining)', 'warning', 'products.php', 'View Products'),
('System Update', 'System maintenance scheduled for tonight at 2:00 AM', 'info', '#', 'Dismiss');