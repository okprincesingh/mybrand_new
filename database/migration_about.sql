-- Migration: about.php Dynamic Content Tables & Default Seeding
-- -------------------------------------------------------------

-- 1. Table: about_blocks (Who We Are / What We Offer / How We Formulate)
CREATE TABLE IF NOT EXISTS about_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_heading VARCHAR(255) NULL,
    section_intro TEXT NULL,
    block_title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_alt VARCHAR(255) NULL,
    layout ENUM('left', 'right') NOT NULL DEFAULT 'left',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_about_blocks_active_order (is_active, sort_order, id),
    INDEX idx_about_blocks_order (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table: about_certifications
CREATE TABLE IF NOT EXISTS about_certifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    icon_path VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_about_certs_active_order (is_active, sort_order, id),
    INDEX idx_about_certs_order (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table: about_key_benefits (Private Label Key Benefits)
CREATE TABLE IF NOT EXISTS about_key_benefits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_about_benefits_active_order (is_active, sort_order, id),
    INDEX idx_about_benefits_order (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table: about_accreditations
CREATE TABLE IF NOT EXISTS about_accreditations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_about_accred_active_order (is_active, sort_order, id),
    INDEX idx_about_accred_order (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Default Settings Seed
INSERT INTO site_settings (setting_key, setting_value) VALUES
('about_intro_heading', 'Thank you for your interest in <span class="theme-color-font">mybrandplease.com!</span>'),
('about_certifications_heading', 'Our Trusted Certifications'),
('about_private_label_heading', 'Why Private Label?'),
('about_private_label_intro', 'Unleash the power of your brand with our exclusive range of private label skin, hair, and body care products, strategically designed to elevate your reputation and drive exceptional profitability. Experience our top-notch quality private label cosmetics with low minimum order quantities (MOQs) and competitive pricing, ensuring customer loyalty, impressive profit margins, and sustainable returns.'),
('about_private_label_block_title', 'Key Benefits'),
('about_private_label_image', 'assets/imgs/about/Key-Benefits-min-768x466.jpg'),
('about_accreditations_heading', 'Accreditations & Associations'),
('about_accreditations_intro', 'Trusted compliance and industry partnerships that reinforce global quality standards.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- 6. Default Seed for about_blocks
INSERT INTO about_blocks (section_heading, section_intro, block_title, body, image_path, image_alt, layout, sort_order, is_active) VALUES
(
    NULL,
    NULL,
    'Who We Are?',
    '<p class="mb-2 text-muted lh-base fs-17 word-spacing-6">With an extensive industry experience spanning over two decades, mybrandplease.com has gained the trust of numerous global brands as their preferred manufacturing partner, facilitating the realization of their vision.</p><p class="mb-2 text-muted lh-base fs-17 word-spacing-6">Our team consists of dedicated personal care experts and enthusiasts who provide comprehensive assistance throughout your Private Label journey</p><p class="mb-2 text-muted lh-base fs-17 word-spacing-6">We offer an expansive range of product and packaging options, enabling you to craft a distinctive product line that is not only cost-effective and of superior quality but also proudly made in INDIA with unwavering love and passion.</p>',
    'assets/imgs/about/Who-we-are-min-2048x1238.jpg',
    'Our Products',
    'left',
    1,
    1
),
(
    'We Located in the vibrant city of <span class="theme-color-font h3">Delhi, India</span>',
    '<span class="theme-color-font">mybrandplease.com</span> proudly operates as a trusted hub for numerous renowned brands, distinguished Spas, Hotels, Salons, & Retailers across the globe.',
    'What We Offer?',
    '<p class="mb-2 text-muted lh-base fs-17 word-spacing-6">With an extensive industry experience spanning over two decades, mybrandplease.com has gained the trust of numerous global brands as their preferred manufacturing partner, facilitating the realization of their vision.</p><p class="mb-2 text-muted lh-base fs-17 word-spacing-6">Our team consists of dedicated personal care experts and enthusiasts who provide comprehensive assistance throughout your Private Label journey</p><p class="mb-2 text-muted lh-base fs-17 word-spacing-6">We offer an expansive range of product and packaging options, enabling you to craft a distinctive product line that is not only cost-effective and of superior quality but also proudly made in INDIA with unwavering love and passion.</p>',
    'assets/imgs/about/what-do-we-offer-min-2048x1241.jpg',
    'Manufacturing',
    'right',
    2,
    1
),
(
    'Our relentless pursuit: safety, efficacy, and the essence of natural formulation.',
    NULL,
    'How We Formulate?',
    '<p class="mb-3 text-muted lh-base fs-17 word-spacing-6">Embracing scientific rigor, our formulations epitomize excellence, with premium ingredients securing robust shelf life and customer safety.</p><p class="mb-3 text-muted lh-base fs-17 word-spacing-6">The alchemy of science and nature converge in formulations that astound with tangible, transformative results. Our goal: ignite customer devotion, fueling repeat purchases and skyrocketing sales.</p><p class="mb-0 text-muted lh-base fs-17 word-spacing-6">We grasp the pivotal role of results-driven formulations, fusing the epitome of scientific innovation with the bounties of nature. Unveiling nature’s finest, we harness the potency of natural and organic ingredients, unveiling a realm of unparalleled beauty and wellness.</p>',
    'assets/imgs/about/How-do-we-Formulate-min-2048x1241.jpg',
    'How We Formulate',
    'left',
    3,
    1
);

-- 7. Default Seed for about_certifications
INSERT INTO about_certifications (icon_path, title, description, sort_order, is_active) VALUES
('assets/imgs/about/Picture-1.png-11-500x497.jpg', 'Vegan Formulas', 'The majority of our formulations offered are Vegan.', 1, 1),
('assets/imgs/about/Curelty.jpg', 'Cruelty Free', 'Our formulations are never tested on animals.', 2, 1),
('assets/imgs/about/GMP-500x500.jpg', 'GMP Certified', 'The products are manufactured in a GMP Certified Facility.', 3, 1),
('assets/imgs/about/Organic-500x500.jpg', '100% ORGANIC', 'Our ingredients are 100% organic and safe from any side effects.', 4, 1),
('assets/imgs/about/FDA-scaled-500x502.jpg', 'FDA COMPLIANT', 'Our products are made in a FDA Compliant Facility.', 5, 1),
('assets/imgs/about/MOQ-500x500.jpg', 'Low MOQ', 'We strive to make starting your own line accessible to all.', 6, 1),
('assets/imgs/about/9001.jpg', 'ISO Certified', 'Our facilities is ISO 9001:2015 Certified.', 7, 1),
('assets/imgs/about/premium-500x500.jpg', 'Premium Quality', '100% Premium Quality Products are offered.', 8, 1);

-- 8. Default Seed for about_key_benefits
INSERT INTO about_key_benefits (label, description, sort_order, is_active) VALUES
('Higher Profits', 'Unlock the freedom to determine your own pricing with our premium natural and organic-based skin and hair care products, delivering uncompromising quality at costs rivaling or surpassing top brands, Eliminating The Constraints of MSRP.', 1, 1),
('Brand Equity', 'Elevate your brand’s reputation and market presence by selling your exclusive private label skin and hair care products, fostering customer loyalty and driving business value growth.', 2, 1),
('Increased Sales', 'Empower your staff by involving them in the development of your private label products, igniting their commitment and driving remarkable growth in product sales', 3, 1),
('Client Retention', 'Unleash the power of your brand as your clients become ambassadors, carrying your essence and influence straight to their homes.', 4, 1);

-- 9. Default Seed for about_accreditations
INSERT INTO about_accreditations (image_path, alt_text, sort_order, is_active) VALUES
('assets/imgs/about/FDA-scaled-500x502.jpg', 'FDA Compliant Facility', 1, 1),
('assets/imgs/about/TUV-500x500.jpg', 'TUV Rheinland Certified', 2, 1),
('assets/imgs/about/9001.jpg', 'ISO 9001 Certified', 3, 1),
('assets/imgs/about/GMP1-500x500.jpg', 'GMP Certified', 4, 1),
('assets/imgs/about/PBA-500x189.jpg', 'Professional Beauty Association', 5, 1),
('assets/imgs/about/FIEO-500x214.jpg', 'FIEO', 6, 1),
('assets/imgs/about/EU.jpg', 'European Standards', 7, 1),
('assets/imgs/about/HACCP1-1-500x268.jpg', 'HACCP Certified', 8, 1);
