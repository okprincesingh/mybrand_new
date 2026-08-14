-- Migration: Dynamic Navbar & Logo Management
-- Tables: nav_logo and nav_menu_items

-- 1. nav_logo (single-row config table)
CREATE TABLE IF NOT EXISTS `nav_logo` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `logo_image` VARCHAR(255) NOT NULL DEFAULT 'uploads/logo/mybrandplease-1.gif',
    `eyebrow_text` VARCHAR(255) NOT NULL DEFAULT 'For dedicated Private Label Support',
    `brand_text` VARCHAR(255) NOT NULL DEFAULT 'mybrandplease.com',
    `tagline_text` VARCHAR(255) NOT NULL DEFAULT 'Your Vision | Our Expertise | Your Brand',
    `logo_link_url` VARCHAR(500) NOT NULL DEFAULT '',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default nav_logo row if empty
INSERT INTO `nav_logo` (`id`, `logo_image`, `eyebrow_text`, `brand_text`, `tagline_text`, `logo_link_url`)
SELECT 1, 'uploads/logo/mybrandplease-1.gif', 'For dedicated Private Label Support', 'mybrandplease.com', 'Your Vision | Our Expertise | Your Brand', ''
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `nav_logo` WHERE `id` = 1);

-- 2. nav_menu_items (self-referencing table for top-level and dropdown items)
CREATE TABLE IF NOT EXISTS `nav_menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
    `label` VARCHAR(180) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `open_in_new_tab` TINYINT(1) NOT NULL DEFAULT 0,
    `has_dropdown` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_parent_sort` (`parent_id`, `sort_order`),
    CONSTRAINT `fk_nav_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `nav_menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default nav_menu_items if empty
-- Top-level items:
-- 1. Sample (has_dropdown = 1, sort_order = 1)
-- 2. How it Works (has_dropdown = 1, sort_order = 2)
-- 3. Why Choose Us (has_dropdown = 1, sort_order = 3)
-- 4. About Us (has_dropdown = 1, sort_order = 4)
-- 5. Services (has_dropdown = 1, sort_order = 5)
-- 6. Resources (has_dropdown = 1, sort_order = 6)
