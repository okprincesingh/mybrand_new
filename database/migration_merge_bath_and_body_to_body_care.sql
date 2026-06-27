SET @body_care_id := (
  SELECT id
  FROM categories
  WHERE parent_id IS NULL AND LOWER(name) = 'body care'
  LIMIT 1
);

SET @bath_body_id := (
  SELECT id
  FROM categories
  WHERE parent_id IS NULL AND LOWER(name) = 'bath and body'
  LIMIT 1
);

-- Ensure one "Body oil" under Body care
INSERT INTO categories (parent_id, name, slug, is_active)
SELECT @body_care_id, 'Body oil', 'body-care-body-oil', 1
FROM DUAL
WHERE @body_care_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM categories c
    WHERE c.parent_id = @body_care_id
      AND LOWER(c.name) = 'body oil'
  );

SET @body_oil_target_id := (
  SELECT id
  FROM categories
  WHERE parent_id = @body_care_id AND LOWER(name) = 'body oil'
  ORDER BY id ASC
  LIMIT 1
);

-- Move parent-level Bath And Body products to Body care
UPDATE products
SET category_id = @body_care_id
WHERE category_id = @bath_body_id
  AND @body_care_id IS NOT NULL
  AND @bath_body_id IS NOT NULL;

-- Merge duplicate Bath And Body sub-categories into Body care sub-categories
UPDATE products p
JOIN categories src ON src.id = p.category_id
JOIN categories dst ON dst.parent_id = @body_care_id
  AND LOWER(dst.name) = LOWER(src.name)
SET p.category_id = dst.id
WHERE src.parent_id = @bath_body_id
  AND src.id <> dst.id;

-- Map alternate slug names to standard Body care sub-categories
UPDATE products p
SET p.category_id = (
  SELECT id FROM categories WHERE parent_id = @body_care_id AND LOWER(name) = 'body butters' LIMIT 1
)
WHERE p.category_id IN (
  SELECT id FROM categories WHERE parent_id = @bath_body_id AND LOWER(name) = 'body-butters'
);

UPDATE products p
SET p.category_id = (
  SELECT id FROM categories WHERE parent_id = @body_care_id AND LOWER(name) = 'body wash & shower gel' LIMIT 1
)
WHERE p.category_id IN (
  SELECT id FROM categories WHERE parent_id = @bath_body_id AND LOWER(name) = 'body-wash-shower-gel'
);

UPDATE products p
SET p.category_id = (
  SELECT id FROM categories WHERE parent_id = @body_care_id AND LOWER(name) = 'lotions' LIMIT 1
)
WHERE p.category_id IN (
  SELECT id FROM categories WHERE parent_id = @bath_body_id AND LOWER(name) = 'lotions'
);

UPDATE products p
SET p.category_id = @body_oil_target_id
WHERE p.category_id IN (
  SELECT id
  FROM categories
  WHERE parent_id = @bath_body_id
    AND LOWER(name) IN ('body oil', 'body-oil')
)
AND @body_oil_target_id IS NOT NULL;

-- Remove old Bath And Body sub-categories (only if empty now)
DELETE c
FROM categories c
LEFT JOIN products p ON p.category_id = c.id
WHERE c.parent_id = @bath_body_id
  AND p.id IS NULL;

-- Remove Bath And Body parent (only if empty now)
DELETE c
FROM categories c
LEFT JOIN products p ON p.category_id = c.id
WHERE c.id = @bath_body_id
  AND p.id IS NULL;
