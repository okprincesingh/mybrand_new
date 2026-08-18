-- Migration: Dynamic Footer & Global Social Media Module
-- Created for mybrandplease

-- 1. footer_brand (single-row configuration)
CREATE TABLE IF NOT EXISTS `footer_brand` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `logo` VARCHAR(255) NOT NULL DEFAULT 'uploads/logo/mybrandfooter.gif',
    `tagline` TEXT NOT NULL,
    `phone` VARCHAR(100) NOT NULL DEFAULT '+91 (971) 700 4615',
    `email` VARCHAR(150) NOT NULL DEFAULT 'info@mybrandplease.com',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default footer_brand if empty
INSERT INTO `footer_brand` (`id`, `logo`, `tagline`, `phone`, `email`)
SELECT 1, 'uploads/logo/mybrandfooter.gif', 'Get in touch with us however is most convenient for you.', '+91 (971) 700 4615', 'info@mybrandplease.com'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `footer_brand` WHERE `id` = 1);

-- 2. footer_links (Quick Links, Compliances, Legal Disclaimers)
CREATE TABLE IF NOT EXISTS `footer_links_new` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `group_key` ENUM('quick_links', 'compliances', 'legal_disclaimers') NOT NULL,
    `label` VARCHAR(180) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `open_in_new_tab` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_group_sort` (`group_key`, `status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `footer_links`;
RENAME TABLE `footer_links_new` TO `footer_links`;

-- Seed default footer_links
INSERT INTO `footer_links` (`group_key`, `label`, `url`, `open_in_new_tab`, `sort_order`, `status`) VALUES
-- Quick Links
('quick_links', 'Skin Care', 'shop.php?category=skin-care', 0, 1, 'active'),
('quick_links', 'Body Care', 'shop.php?category=body-care', 0, 2, 'active'),
('quick_links', 'Hair Care', 'shop.php?category=hair-care', 0, 3, 'active'),
('quick_links', 'Bathing Soaps', 'shop.php?category=bathing-soaps', 0, 4, 'active'),
('quick_links', 'For Men', 'shop.php?category=especially-for-men', 0, 5, 'active'),
('quick_links', 'Fragrance', 'shop.php?category=fragrances', 0, 6, 'active'),
('quick_links', 'Our Product Catalogue', 'product-catalog.php', 0, 7, 'active'),
('quick_links', 'mybrandplease@alibaba.com', 'https://mybrandplease.trustpass.alibaba.com/', 1, 8, 'active'),

-- Compliances
('compliances', 'FDA Registered', 'https://www.fda.gov/', 1, 1, 'active'),
('compliances', 'ISO 22716 Certified', 'https://www.iso.org/standard/36437.html', 1, 2, 'active'),
('compliances', 'Compliant to EU CosIng', 'https://ec.europa.eu/growth/tools-databases/cosing/reference/annexes', 1, 3, 'active'),
('compliances', 'MoCRA Compliant', 'https://www.fda.gov/cosmetics/cosmetics-laws-regulations/modernization-cosmetics-regulation-act-2022-mocra', 1, 4, 'active'),
('compliances', 'EWG Verified®', 'https://www.ewg.org/ewgverified/', 1, 5, 'active'),
('compliances', 'Credo Clean Standard', 'https://credobeauty.com/pages/the-credo-clean-standard-1', 1, 6, 'active'),
('compliances', 'MADE SAFE®', 'https://madesafe.org/collections/cosmetics', 1, 7, 'active'),
('compliances', 'Clean Label Project', 'https://cleanlabelproject.org/clean-label-project-certification/', 1, 8, 'active'),
('compliances', 'Cruelty-Free Compliant', 'https://www.crueltyfreeinternational.org/for-brands/our-approval-programme/', 1, 9, 'active'),
('compliances', 'Vegan Certified', 'https://biorius.com/cosmetics-certifications/vegan-certification/', 1, 10, 'active'),

-- Legal Disclaimers
('legal_disclaimers', 'Terms & Conditions', 'terms-conditions.php', 0, 1, 'active'),
('legal_disclaimers', 'Privacy Policy', 'privacy.php', 0, 2, 'active'),
('legal_disclaimers', 'Refund Policy', 'contact.php', 0, 3, 'active'),
('legal_disclaimers', 'Shipping Policy', 'shipping-policy.php', 0, 4, 'active'),
('legal_disclaimers', 'Form Center', 'form-center.php', 0, 5, 'active');

-- 3. footer_bottom (single-row configuration)
CREATE TABLE IF NOT EXISTS `footer_bottom` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `copyright_text` TEXT NOT NULL,
    `developer_credit_text` VARCHAR(255) NOT NULL DEFAULT 'Developed and Maintained by',
    `developer_credit_label` VARCHAR(100) NOT NULL DEFAULT 'JTPL',
    `developer_credit_url` VARCHAR(500) NOT NULL DEFAULT 'https://jaikviktechnology.com/',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default footer_bottom if empty
INSERT INTO `footer_bottom` (`id`, `copyright_text`, `developer_credit_text`, `developer_credit_label`, `developer_credit_url`)
SELECT 1, '&copy; 2005-2026 NIMISHA IMPEX WORLDWIDE (P) LIMITED | All rights reserved', 'Developed and Maintained by', 'JTPL', 'https://jaikviktechnology.com/'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `footer_bottom` WHERE `id` = 1);

-- 4. social_media_links (Global shared module)
CREATE TABLE IF NOT EXISTS `social_media_links` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `platform` VARCHAR(60) NOT NULL,
    `icon_class` VARCHAR(100) NOT NULL,
    `icon_image` VARCHAR(255) NULL,
    `url` VARCHAR(500) NOT NULL,
    `open_in_new_tab` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_sort_status` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default social_media_links if empty
INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'YouTube', 'fa-brands fa-youtube', 'https://www.youtube.com/@mybrandplease', 1, 1, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'YouTube');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'Facebook', 'fa-brands fa-facebook-f', 'https://www.facebook.com/mybrandplease', 1, 2, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'Facebook');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'Instagram', 'fa-brands fa-instagram', 'https://www.instagram.com/mybrandplease_/', 1, 3, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'Instagram');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'TikTok', 'fa-brands fa-tiktok', 'https://www.tiktok.com/@mybrandplease.com', 1, 4, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'TikTok');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'X', 'fa-brands fa-x-twitter', 'https://x.com/mybrandplease', 1, 5, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'X');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'LinkedIn', 'fa-brands fa-linkedin-in', 'https://www.linkedin.com/in/mybrandplease', 1, 6, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'LinkedIn');

INSERT INTO `social_media_links` (`platform`, `icon_class`, `url`, `open_in_new_tab`, `sort_order`, `status`)
SELECT 'Pinterest', 'fa-brands fa-pinterest-p', 'https://in.pinterest.com/mybrandplease/', 1, 7, 'active' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `social_media_links` WHERE `platform` = 'Pinterest');
