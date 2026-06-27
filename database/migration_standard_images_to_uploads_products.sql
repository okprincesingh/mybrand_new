-- Revert Standard product image paths from wp-content/uploads/... to uploads/...
UPDATE products
SET featured_image = REPLACE(featured_image, 'wp-content/uploads/', 'uploads/')
WHERE name LIKE 'Standard%'
  AND featured_image LIKE 'wp-content/uploads/%';

UPDATE product_images pi
INNER JOIN products p ON p.id = pi.product_id
SET pi.image_path = REPLACE(pi.image_path, 'wp-content/uploads/', 'uploads/')
WHERE p.name LIKE 'Standard%'
  AND pi.image_path LIKE 'wp-content/uploads/%';
