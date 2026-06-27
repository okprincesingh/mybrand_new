-- Migration: Create faq_page_accordions table and seed FAQ data

CREATE TABLE IF NOT EXISTS faq_page_accordions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    body_html TEXT,
    sort_order INT DEFAULT 0,
    is_open TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page_id (page_id),
    INDEX idx_sort_order (sort_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure FAQ page exists in pages table (for admin + frontend dynamic render)
INSERT INTO pages (title, slug, content, status, page_group, template_key)
SELECT 'FAQs', 'faq', '', 'published', 'faq', 'faq'
WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'faq');

-- Prefer slug 'faq', fallback to 'faqs'
SET @faq_page_id = (
  SELECT id
  FROM pages
  WHERE slug IN ('faq', 'faqs')
  ORDER BY CASE WHEN slug = 'faq' THEN 0 ELSE 1 END, id ASC
  LIMIT 1
);

-- Reset seeded accordions for the FAQ page (safe to rerun)
DELETE FROM faq_page_accordions WHERE page_id = @faq_page_id;

INSERT INTO faq_page_accordions (page_id, title, body_html, sort_order, is_open, is_active) VALUES
(@faq_page_id, 'What is private label manufacturing?', '<p>Private label manufacturing is a business model where a company creates products that are sold under another company\'s brand name. This allows businesses to offer unique products without the need for in-house manufacturing capabilities.</p>', 1, 1, 1),
(@faq_page_id, 'How long does the private label process take?', '<p>The timeline for private label manufacturing typically ranges from 4-8 weeks, depending on the complexity of the product, formulation requirements, and packaging specifications. We work closely with our clients to ensure timely delivery while maintaining the highest quality standards.</p>', 2, 0, 1),
(@faq_page_id, 'What are the minimum order quantities?', '<p>Our minimum order quantities start at just 100 units per product, making it accessible for businesses of all sizes. We understand that every business has different needs, so we offer flexible MOQs based on your specific requirements and product type.</p>', 3, 0, 1),
(@faq_page_id, 'Can you help with product formulation?', '<p>Yes, our team of experienced chemists and formulators can help you develop custom formulations tailored to your specific needs. We use only the highest quality ingredients and follow strict quality control procedures to ensure consistent results.</p>', 4, 0, 1),
(@faq_page_id, 'What packaging options do you offer?', '<p>We offer a wide range of packaging options including bottles, jars, tubes, sachets, and more. Our packaging team can help you select the perfect packaging solution that aligns with your brand identity and product requirements.</p>', 5, 0, 1),
(@faq_page_id, 'Are your products cruelty-free and vegan?', '<p>Yes, all our products are cruelty-free and we offer a wide range of vegan formulations. We are committed to ethical manufacturing practices and can provide certification documentation upon request.</p>', 6, 0, 1),
(@faq_page_id, 'Do you handle regulatory compliance?', '<p>Absolutely. We ensure all our products comply with relevant regulations including FDA, EU Cosmetics Regulation, and other international standards. Our team stays updated on regulatory changes to keep your products compliant.</p>', 7, 0, 1),
(@faq_page_id, 'Can I get samples before placing a large order?', '<p>Yes, we encourage sample requests before large orders. This allows you to test the product quality, packaging, and overall presentation. Sample fees may apply but are often credited toward your first order.</p>', 8, 0, 1);

