USE mybrandplease;
SELECT 'admins' t, COUNT(*) c FROM admins
UNION ALL SELECT 'pages', COUNT(*) FROM pages
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'products', COUNT(*) FROM products
UNION ALL SELECT 'product_images', COUNT(*) FROM product_images
UNION ALL SELECT 'product_reviews', COUNT(*) FROM product_reviews
UNION ALL SELECT 'menus', COUNT(*) FROM menus
UNION ALL SELECT 'menu_items', COUNT(*) FROM menu_items
UNION ALL SELECT 'site_settings', COUNT(*) FROM site_settings
UNION ALL SELECT 'footer_sections', COUNT(*) FROM footer_sections
UNION ALL SELECT 'footer_links', COUNT(*) FROM footer_links;
