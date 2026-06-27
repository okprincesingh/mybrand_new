USE mybrandplease;

INSERT INTO admins (name, email, password_hash, role, is_active)
VALUES ('Super Admin', 'admin@mybrandplease.com', '$2y$10$UEWStNzTYImvwJ4nFEezX.VJhbTRL98dyByIhDYoeQIOlNmpGs6gi', 'super_admin', 1)
ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role), is_active=1;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'MyBrandPlease'),
('site_email', 'info@mybrandplease.com'),
('site_phone', '+91 9717004615')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

INSERT INTO menus (name, location_key)
VALUES ('Header Main', 'header_main')
ON DUPLICATE KEY UPDATE name=VALUES(name);

SET @menu_id = (SELECT id FROM menus WHERE location_key='header_main' LIMIT 1);

INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order, is_active)
SELECT @menu_id, NULL, 'Home', 'index.php', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE menu_id=@menu_id AND title='Home' AND parent_id IS NULL);
INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order, is_active)
SELECT @menu_id, NULL, 'About', 'about.php', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE menu_id=@menu_id AND title='About' AND parent_id IS NULL);
INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order, is_active)
SELECT @menu_id, NULL, 'Products', 'shop.php', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE menu_id=@menu_id AND title='Products' AND parent_id IS NULL);
INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order, is_active)
SELECT @menu_id, NULL, 'Contact', 'contact.php', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE menu_id=@menu_id AND title='Contact' AND parent_id IS NULL);

INSERT INTO footer_sections (title, sort_order)
SELECT 'Quick Links', 1 WHERE NOT EXISTS (SELECT 1 FROM footer_sections WHERE title='Quick Links');
INSERT INTO footer_sections (title, sort_order)
SELECT 'Legal', 2 WHERE NOT EXISTS (SELECT 1 FROM footer_sections WHERE title='Legal');

SET @fs_quick = (SELECT id FROM footer_sections WHERE title='Quick Links' LIMIT 1);
SET @fs_legal = (SELECT id FROM footer_sections WHERE title='Legal' LIMIT 1);

INSERT INTO footer_links (section_id, label, url, sort_order)
SELECT @fs_quick, 'Shop', 'shop.php', 1
WHERE NOT EXISTS (SELECT 1 FROM footer_links WHERE section_id=@fs_quick AND label='Shop');
INSERT INTO footer_links (section_id, label, url, sort_order)
SELECT @fs_quick, 'About', 'about.php', 2
WHERE NOT EXISTS (SELECT 1 FROM footer_links WHERE section_id=@fs_quick AND label='About');
INSERT INTO footer_links (section_id, label, url, sort_order)
SELECT @fs_legal, 'Privacy Policy', 'contact.php', 1
WHERE NOT EXISTS (SELECT 1 FROM footer_links WHERE section_id=@fs_legal AND label='Privacy Policy');
INSERT INTO footer_links (section_id, label, url, sort_order)
SELECT @fs_legal, 'Terms of Service', 'contact.php', 2
WHERE NOT EXISTS (SELECT 1 FROM footer_links WHERE section_id=@fs_legal AND label='Terms of Service');

INSERT INTO pages (title, slug, content, status)
VALUES
('Home', 'home', 'Welcome to MyBrandPlease home page', 'published'),
('About Us', 'about-us', 'We are private label experts', 'published'),
('Contact', 'contact', 'Reach out to us', 'published')
ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content), status=VALUES(status);

INSERT INTO page_meta (page_id, meta_title, meta_description, meta_keywords, canonical_url)
SELECT p.id, CONCAT(p.title, ' | MyBrandPlease'), CONCAT('About ', p.title), 'cosmetics, private label', CONCAT(p.slug, '.php')
FROM pages p
WHERE p.slug IN ('home','about-us','contact')
ON DUPLICATE KEY UPDATE
meta_title=VALUES(meta_title),
meta_description=VALUES(meta_description),
meta_keywords=VALUES(meta_keywords),
canonical_url=VALUES(canonical_url);

INSERT INTO categories (parent_id, name, slug, description, image_path, is_active)
VALUES
(NULL, 'Skin Care', 'skin-care', 'Skin care range', 'assets/imgs/product/skin-care.webp', 1),
(NULL, 'Hair Care', 'hair-care', 'Hair care range', 'assets/imgs/product/hair-care.webp', 1),
(NULL, 'Body Care', 'body-care', 'Body care range', 'assets/imgs/product/body-care.webp', 1),
((SELECT id FROM categories WHERE slug='skin-care' LIMIT 1), 'Vitamin C', 'vitamin-c', 'Vitamin C products', '', 1),
((SELECT id FROM categories WHERE slug='hair-care' LIMIT 1), 'Shampoo', 'shampoo', 'Shampoo products', '', 1)
ON DUPLICATE KEY UPDATE
name=VALUES(name),
description=VALUES(description),
image_path=VALUES(image_path),
is_active=VALUES(is_active);

INSERT INTO offers (title, offer_type, offer_value, starts_at, ends_at, is_active)
SELECT 'Launch Offer', 'percent', 10.00, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1
WHERE NOT EXISTS (SELECT 1 FROM offers WHERE title='Launch Offer');

SET @skin = (SELECT id FROM categories WHERE slug='skin-care' LIMIT 1);
SET @hair = (SELECT id FROM categories WHERE slug='hair-care' LIMIT 1);
SET @offer = (SELECT id FROM offers WHERE title='Launch Offer' LIMIT 1);

INSERT INTO products (category_id, offer_id, name, slug, short_description, description, price, stock, status, featured_image, is_active)
VALUES
(@skin, @offer, 'Vitamin C Face Serum', 'vitamin-c-face-serum', 'Brightening daily serum', 'High-performance vitamin C serum.', 19.99, 120, 'published', 'assets/imgs/products/10_-Glycolic-Acid-1-1.jpg', 1),
(@hair, NULL, 'Avocado Conditioner', 'avocado-conditioner', 'Nourishing conditioner', 'Smooth and soft hair conditioner.', 14.50, 90, 'published', 'assets/imgs/products/Avocado-Volumising-Hair-Conditioner-1.jpg', 1),
(@skin, NULL, 'Hydra Gel Cleanser', 'hydra-gel-cleanser', 'Gentle cleanser', 'Hydrating gel cleanser for all skin types.', 11.00, 150, 'draft', 'assets/imgs/product/skin-care.webp', 1)
ON DUPLICATE KEY UPDATE
category_id=VALUES(category_id),
offer_id=VALUES(offer_id),
short_description=VALUES(short_description),
description=VALUES(description),
price=VALUES(price),
stock=VALUES(stock),
status=VALUES(status),
featured_image=VALUES(featured_image),
is_active=VALUES(is_active);

INSERT INTO product_images (product_id, image_path, sort_order)
SELECT p.id, p.featured_image, 1
FROM products p
WHERE p.featured_image IS NOT NULL AND p.featured_image <> ''
AND NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id=p.id AND pi.image_path=p.featured_image);

INSERT INTO product_attributes (product_id, attribute_key, attribute_value)
SELECT p.id, 'Size', '100ml' FROM products p WHERE p.slug='vitamin-c-face-serum'
AND NOT EXISTS (SELECT 1 FROM product_attributes pa WHERE pa.product_id=p.id AND pa.attribute_key='Size');
INSERT INTO product_attributes (product_id, attribute_key, attribute_value)
SELECT p.id, 'Type', 'Daily Use' FROM products p WHERE p.slug='vitamin-c-face-serum'
AND NOT EXISTS (SELECT 1 FROM product_attributes pa WHERE pa.product_id=p.id AND pa.attribute_key='Type');

INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, review_text, status)
SELECT p.id, 'Riya', 'riya@example.com', 5, 'Excellent product quality', 'approved'
FROM products p WHERE p.slug='vitamin-c-face-serum'
AND NOT EXISTS (SELECT 1 FROM product_reviews pr WHERE pr.product_id=p.id AND pr.reviewer_email='riya@example.com');
INSERT INTO product_reviews (product_id, reviewer_name, reviewer_email, rating, review_text, status)
SELECT p.id, 'Arjun', 'arjun@example.com', 4, 'Good result after 2 weeks', 'pending'
FROM products p WHERE p.slug='avocado-conditioner'
AND NOT EXISTS (SELECT 1 FROM product_reviews pr WHERE pr.product_id=p.id AND pr.reviewer_email='arjun@example.com');

SELECT 'seed_completed' AS status;
