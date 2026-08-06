-- Comprehensive Database Performance Indexing Migration
-- Target database optimization for fast website load times

-- 1. Products Table Optimizations
-- Speeds up shop category product filtering and listing: WHERE is_active=1 AND status='published' AND category_id=? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_products_cat_active_status_created ON products (category_id, is_active, status, created_at);

-- Speeds up shop price filtering: WHERE is_active=1 AND status='published' AND category_id=? AND price BETWEEN ? AND ?
CREATE INDEX IF NOT EXISTS idx_products_cat_active_status_price ON products (category_id, is_active, status, price);

-- Speeds up general active & published product listing: WHERE is_active=1 AND status='published' ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_products_active_status_created ON products (is_active, status, created_at, id);

-- Speeds up search queries filtering by product name
CREATE INDEX IF NOT EXISTS idx_products_name ON products (name);


-- 2. Categories Table Optimizations
-- Speeds up parent category navigation menus & subcategory fetching: WHERE parent_id=? AND is_active=1 ORDER BY sort_order ASC
CREATE INDEX IF NOT EXISTS idx_categories_parent_active_sort ON categories (parent_id, is_active, sort_order);

-- Speeds up category lookup by name
CREATE INDEX IF NOT EXISTS idx_categories_name ON categories (name);


-- 3. Blog Posts Table Optimizations
-- Speeds up published blog post listing with pagination: WHERE status='published' ORDER BY published_at DESC, id DESC
CREATE INDEX IF NOT EXISTS idx_blog_posts_status_published_id ON blog_posts (status, published_at, id);

-- Speeds up category blog post filtering: WHERE status='published' AND category=? ORDER BY published_at DESC
CREATE INDEX IF NOT EXISTS idx_blog_posts_cat_status_published ON blog_posts (category, status, published_at);

-- Speeds up searching blog posts by title
CREATE INDEX IF NOT EXISTS idx_blog_posts_title ON blog_posts (title);


-- 4. Product Reviews Table Optimizations
-- Speeds up product details review section: WHERE product_id=? AND status='approved' ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_product_reviews_prod_status_created ON product_reviews (product_id, status, created_at);


-- 5. Product Attributes Table Optimizations
-- Speeds up attribute lookup per product: WHERE product_id=? AND attribute_key=?
CREATE INDEX IF NOT EXISTS idx_product_attributes_prod_key ON product_attributes (product_id, attribute_key);


-- 6. Orders & Order Items Table Optimizations
-- Speeds up user dashboard order history: WHERE user_id=? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders (user_id, created_at);

-- Speeds up customer order lookup: WHERE customer_id=? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_orders_customer_created ON orders (customer_id, created_at);

-- Speeds up admin order filtering by status: WHERE status=? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_orders_status_created ON orders (status, created_at);

-- Speeds up payment status filtering
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders (payment_status);

-- Speeds up order items lookup: WHERE order_id=? AND product_id=?
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items (order_id, product_id);


-- 7. User Wishlist Table Optimizations
-- Speeds up user wishlist rendering: WHERE user_id=? ORDER BY created_at DESC
CREATE INDEX IF NOT EXISTS idx_user_wishlist_user_created ON user_wishlist (user_id, created_at);


-- 8. Coupons Table Optimizations
-- Speeds up checkout coupon code validation: WHERE code=? AND is_active=1
CREATE INDEX IF NOT EXISTS idx_coupons_code_active_dates ON coupons (code, is_active, starts_at, expires_at);


-- 9. Certificates Table Optimizations
-- Speeds up certificates page: WHERE category=? AND is_active=1 ORDER BY sort_order ASC
CREATE INDEX IF NOT EXISTS idx_certificates_cat_active_sort ON certificates (category, is_active, sort_order);


-- 10. FAQ & Accordions Table Optimizations
-- Speeds up accordion lookups: WHERE page_id=? AND is_active=1 ORDER BY sort_order ASC
CREATE INDEX IF NOT EXISTS idx_why_page_accordions_page_active ON why_page_accordions (page_id, is_active, sort_order);


-- 11. Sessions Table Optimizations
-- Speeds up admin session validation per HTTP request
CREATE INDEX IF NOT EXISTS idx_admin_sessions_token_revoked_expires ON admin_sessions (token_hash, revoked_at, expires_at);

-- Speeds up user session validation per HTTP request
CREATE INDEX IF NOT EXISTS idx_user_sessions_token_expires ON user_sessions (session_token, expires_at);


-- 12. Homepage CTA Cards Table Optimizations
-- Speeds up homepage CTA cards rendering: WHERE is_active=1 ORDER BY sort_order ASC
CREATE INDEX IF NOT EXISTS idx_home_cta_cards_active_order ON home_cta_cards (is_active, sort_order, id);

-- Speeds up admin lookup by card_key
CREATE INDEX IF NOT EXISTS idx_home_cta_cards_key ON home_cta_cards (card_key);

