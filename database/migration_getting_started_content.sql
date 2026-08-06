-- Getting Started Section Header & Intro Text Table
CREATE TABLE IF NOT EXISTS home_getting_started_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL UNIQUE,
  heading_text VARCHAR(255) NOT NULL,
  description_text TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO home_getting_started_content (section_key, heading_text, description_text, is_active)
VALUES ('main', 'Here\'s How To Get Started', 'You know your brand and customers best. Let us help you build a custom private label line of offerings that are as unique as your brand.', 1)
ON DUPLICATE KEY UPDATE
  heading_text = VALUES(heading_text),
  description_text = VALUES(description_text);
