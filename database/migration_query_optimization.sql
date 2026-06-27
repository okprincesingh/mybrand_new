USE mybrand_cms;

CREATE INDEX IF NOT EXISTS idx_pages_status_updated ON pages (status, updated_at, id);
CREATE INDEX IF NOT EXISTS idx_page_sections_page_sort ON page_sections (page_id, sort_order, id);
CREATE INDEX IF NOT EXISTS idx_page_section_items_section_sort ON page_section_items (section_id, sort_order, id);
CREATE INDEX IF NOT EXISTS idx_menu_items_menu_active_sort ON menu_items (menu_id, is_active, sort_order, id);
CREATE INDEX IF NOT EXISTS idx_footer_links_section_sort ON footer_links (section_id, sort_order, id);
CREATE INDEX IF NOT EXISTS idx_categories_active_parent_name ON categories (is_active, parent_id, name);
CREATE INDEX IF NOT EXISTS idx_products_active_status_created ON products (is_active, status, created_at, id);
CREATE INDEX IF NOT EXISTS idx_products_active_category_price ON products (is_active, category_id, price, id);
CREATE INDEX IF NOT EXISTS idx_product_images_product_sort ON product_images (product_id, sort_order, id);
CREATE INDEX IF NOT EXISTS idx_product_reviews_product_status ON product_reviews (product_id, status, id);
