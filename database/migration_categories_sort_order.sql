-- Add sort order support to categories for manual ordering
ALTER TABLE categories
  ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER image_path;
