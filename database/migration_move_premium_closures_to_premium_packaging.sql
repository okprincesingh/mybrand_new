SET @packaging_id := (SELECT id FROM categories WHERE parent_id IS NULL AND LOWER(name)='packaging' LIMIT 1);
SET @premium_packaging_id := (SELECT id FROM categories WHERE parent_id=@packaging_id AND LOWER(name)='premium packaging' LIMIT 1);
SET @premium_closures_id := (SELECT id FROM categories WHERE parent_id IS NULL AND LOWER(name)='premium closures' LIMIT 1);

UPDATE products
SET category_id=@premium_packaging_id
WHERE category_id=@premium_closures_id
  AND @premium_packaging_id IS NOT NULL;

DELETE FROM categories
WHERE id=@premium_closures_id
  AND NOT EXISTS (SELECT 1 FROM products WHERE category_id=@premium_closures_id);
