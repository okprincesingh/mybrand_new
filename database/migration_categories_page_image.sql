-- Add separate image support for category/sub-category detail pages
ALTER TABLE categories
  ADD COLUMN page_image_path VARCHAR(255) NULL AFTER image_path;
