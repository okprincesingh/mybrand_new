SET @packaging_parent_id := (SELECT id FROM categories WHERE parent_id IS NULL AND LOWER(name)='packaging' LIMIT 1);
SET @standard_packaging_id := (SELECT id FROM categories WHERE parent_id=@packaging_parent_id AND LOWER(name)='standard packaging' LIMIT 1);

INSERT INTO categories (parent_id, name, slug, is_active)
SELECT @packaging_parent_id, 'Standard Packaging', 'standard-packaging', 1
FROM DUAL
WHERE @packaging_parent_id IS NOT NULL
  AND @standard_packaging_id IS NULL;

SET @standard_packaging_id := (SELECT id FROM categories WHERE parent_id=@packaging_parent_id AND LOWER(name)='standard packaging' ORDER BY id LIMIT 1);

UPDATE products
SET category_id = @standard_packaging_id
WHERE name LIKE 'Standard%'
  AND @standard_packaging_id IS NOT NULL;

UPDATE products
SET featured_image = CONCAT('wp-content/', featured_image)
WHERE name LIKE 'Standard%'
  AND featured_image IS NOT NULL
  AND featured_image <> ''
  AND featured_image LIKE 'uploads/%';

UPDATE product_images pi
INNER JOIN products p ON p.id = pi.product_id
SET pi.image_path = CONCAT('wp-content/', pi.image_path)
WHERE p.name LIKE 'Standard%'
  AND pi.image_path IS NOT NULL
  AND pi.image_path <> ''
  AND pi.image_path LIKE 'uploads/%';
