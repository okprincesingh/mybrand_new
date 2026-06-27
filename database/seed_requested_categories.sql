USE mybrandplease;

INSERT INTO categories (parent_id,name,slug,is_active) VALUES
(NULL,'Skin Care','skin-care',1),
(NULL,'Body care','body-care',1),
(NULL,'Hair care','hair-care',1),
(NULL,'Bathing Soaps','bathing-soaps',1),
(NULL,'Especially For Men','especially-for-men',1),
(NULL,'Aerosols & Parfumes','aerosols-parfumes',1),
(NULL,'Beauty Products','beauty-products',1)
ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=1;

SET @skin = (SELECT id FROM categories WHERE slug='skin-care' LIMIT 1);
SET @body = (SELECT id FROM categories WHERE slug='body-care' LIMIT 1);
SET @hair = (SELECT id FROM categories WHERE slug='hair-care' LIMIT 1);
SET @soap = (SELECT id FROM categories WHERE slug='bathing-soaps' LIMIT 1);
SET @beauty = (SELECT id FROM categories WHERE slug='beauty-products' LIMIT 1);

INSERT INTO categories (parent_id,name,slug,is_active) VALUES
(@skin,'Environmental Defense','skin-care-environmental-defense',1),
(@skin,'Advanced','skin-care-advanced',1),
(@skin,'Age Defying','skin-care-age-defying',1),
(@skin,'Peptides','skin-care-peptides',1),
(@skin,'Vitamin C','skin-care-vitamin-c',1),
(@skin,'Brightening','skin-care-brightening',1),
(@skin,'Super Fruits','skin-care-super-fruits',1),
(@skin,'Marine Complex','skin-care-marine-complex',1),
(@skin,'Blemish Prone Skin','skin-care-blemish-prone-skin',1),
(@skin,'Botanical','skin-care-botanical',1),

(@body,'Specialty Products','body-care-specialty-products',1),
(@body,'Body Wash & Shower Gel','body-care-body-wash-shower-gel',1),
(@body,'Lotions','body-care-lotions',1),
(@body,'Body Butters','body-care-body-butters',1),
(@body,'Salts & Soaks','body-care-salts-soaks',1),
(@body,'Lip Balms & Lip Scrubs','body-care-lip-balms-lip-scrubs',1),
(@body,'Body Scrubs','body-care-body-scrubs',1),
(@body,'Manicure & Pedicure','body-care-manicure-pedicure',1),

(@hair,'Bars','hair-care-bars',1),
(@hair,'Shampoo','hair-care-shampoo',1),
(@hair,'Conditioner','hair-care-conditioner',1),
(@hair,'Styling Products','hair-care-styling-products',1),
(@hair,'Treatment Products','hair-care-treatment-products',1),

(@soap,'Beauty Soaps','bathing-soaps-beauty-soaps',1),
(@soap,'Men''s Soap','bathing-soaps-mens-soap',1),
(@soap,'Medicated Soaps','bathing-soaps-medicated-soaps',1),
(@soap,'Hotel Soap','bathing-soaps-hotel-soap',1),
(@soap,'Novelty Soaps','bathing-soaps-novelty-soaps',1),

(@beauty,'Environmental Defense','beauty-products-environmental-defense',1),
(@beauty,'Advanced','beauty-products-advanced',1),
(@beauty,'Age Defying','beauty-products-age-defying',1),
(@beauty,'Peptides','beauty-products-peptides',1),
(@beauty,'Vitamin C','beauty-products-vitamin-c',1),
(@beauty,'Brightening','beauty-products-brightening',1),
(@beauty,'Super Fruits','beauty-products-super-fruits',1),
(@beauty,'Marine Complex','beauty-products-marine-complex',1),
(@beauty,'Blemish Prone Skin','beauty-products-blemish-prone-skin',1),
(@beauty,'Botanical','beauty-products-botanical',1)
ON DUPLICATE KEY UPDATE parent_id=VALUES(parent_id), name=VALUES(name), is_active=1;
