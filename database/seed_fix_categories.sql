USE mybrandplease;

INSERT INTO categories (parent_id, name, slug, description, image_path, is_active)
VALUES
(NULL, 'Skin Care', 'skin-care', 'Skin care range', 'assets/imgs/product/skin-care.webp', 1),
(NULL, 'Hair Care', 'hair-care', 'Hair care range', 'assets/imgs/product/hair-care.webp', 1),
(NULL, 'Body Care', 'body-care', 'Body care range', 'assets/imgs/product/body-care.webp', 1)
ON DUPLICATE KEY UPDATE
name=VALUES(name),
description=VALUES(description),
image_path=VALUES(image_path),
is_active=VALUES(is_active);

SET @skin = (SELECT id FROM categories WHERE slug='skin-care' LIMIT 1);
SET @hair = (SELECT id FROM categories WHERE slug='hair-care' LIMIT 1);

INSERT INTO categories (parent_id, name, slug, description, image_path, is_active)
VALUES (@skin, 'Vitamin C', 'vitamin-c', 'Vitamin C products', '', 1)
ON DUPLICATE KEY UPDATE parent_id=VALUES(parent_id), name=VALUES(name), description=VALUES(description), is_active=VALUES(is_active);

INSERT INTO categories (parent_id, name, slug, description, image_path, is_active)
VALUES (@hair, 'Shampoo', 'shampoo', 'Shampoo products', '', 1)
ON DUPLICATE KEY UPDATE parent_id=VALUES(parent_id), name=VALUES(name), description=VALUES(description), is_active=VALUES(is_active);
