-- ============================================================
-- Migration: footer_trust_badges
-- Adds the footer_trust_badges table and seeds the 6 default
-- badges (Google Reviews, Trustpilot, USFDA, DUNS, CPNP, Stripe).
-- ============================================================

CREATE TABLE IF NOT EXISTS `footer_trust_badges` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `label`           VARCHAR(255)    NOT NULL DEFAULT '',
  `image`           VARCHAR(500)    NOT NULL DEFAULT '',
  `link_url`        VARCHAR(500)    DEFAULT NULL,
  `open_in_new_tab` TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`      INT             NOT NULL DEFAULT 0,
  `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sort_status` (`status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default trust badges (matching the current hardcoded items)
INSERT INTO `footer_trust_badges` (`label`, `image`, `link_url`, `open_in_new_tab`, `sort_order`, `status`) VALUES
('Google Reviews',  'assets/imgs/home/footer/google-Reviews_mybrand.webp',         'https://g.co/kgs/YgaRfY',  1, 1, 'active'),
('Trustpilot',      'assets/imgs/home/footer/Trust-Pilot-Reviews_mybrand.webp',     'https://www.trustpilot.com/review/mybrandplease.com?utm_medium=trustbox&utm_source=TrustBoxReviewCollector', 1, 2, 'active'),
('USFDA',           'assets/imgs/home/footer/fei.webp',                             NULL, 0, 3, 'active'),
('DUNS',            'assets/imgs/home/footer/duns.webp',                            NULL, 0, 4, 'active'),
('CPNP Registered', 'assets/imgs/home/footer/CPNP-Registered.webp',                NULL, 0, 5, 'active'),
('Stripe Payment',  'assets/imgs/home/footer/stripe.png',                           NULL, 0, 6, 'active');
