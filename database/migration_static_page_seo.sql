INSERT INTO pages (title, slug, content, status, page_group, template_key)
VALUES
('404', '404', NULL, 'published', 'general', 'default'),
('About', 'about', NULL, 'published', 'general', 'default'),
('Blog', 'blog', NULL, 'published', 'general', 'default'),
('Blog Details', 'blog-details', NULL, 'published', 'general', 'default'),
('Blog Standard', 'blog-standard', NULL, 'published', 'general', 'default'),
('Cart', 'cart', NULL, 'published', 'general', 'default'),
('Checkout', 'checkout', NULL, 'published', 'general', 'default'),
('Collections', 'collections', NULL, 'published', 'general', 'default'),
('Contact', 'contact', NULL, 'published', 'general', 'default'),
('Data Sheets', 'data-sheets', NULL, 'published', 'general', 'default'),
('FAQ', 'faq', NULL, 'published', 'general', 'default'),
('Form Center', 'form-center', NULL, 'published', 'general', 'default'),
('Google Calendar Connect', 'google-calendar-connect', NULL, 'published', 'general', 'default'),
('Home', 'home', NULL, 'published', 'general', 'default'),
('How It Works', 'how-it-works', NULL, 'published', 'general', 'default'),
('Login', 'login', NULL, 'published', 'general', 'default'),
('Logout', 'logout', NULL, 'published', 'general', 'default'),
('Meeting Details', 'meeting-details', NULL, 'published', 'general', 'default'),
('Meeting Schedule', 'meeting-schedule', NULL, 'published', 'general', 'default'),
('Order Success', 'order-success', NULL, 'published', 'general', 'default'),
('Our Services', 'our-services', NULL, 'published', 'general', 'default'),
('Privacy Policy', 'privacy', NULL, 'published', 'general', 'default'),
('Private Label Skin Care Manufacturer', 'private-label-skin-care-manufacturer', NULL, 'published', 'general', 'default'),
('Product', 'product', NULL, 'published', 'general', 'default'),
('Product Catalog', 'product-catalog', NULL, 'published', 'general', 'default'),
('Product Details', 'product-details', NULL, 'published', 'general', 'default'),
('Register', 'register', NULL, 'published', 'general', 'default'),
('Services', 'services', NULL, 'published', 'general', 'default'),
('Shipping Policy', 'shipping-policy', NULL, 'published', 'general', 'default'),
('Shop', 'shop', NULL, 'published', 'general', 'default'),
('Terms and Conditions', 'terms-conditions', NULL, 'published', 'general', 'default'),
('User Addresses', 'user-addresses', NULL, 'published', 'general', 'default'),
('User Dashboard', 'user-dashboard', NULL, 'published', 'general', 'default'),
('User Orders', 'user-orders', NULL, 'published', 'general', 'default'),
('User Profile', 'user-profile', NULL, 'published', 'general', 'default'),
('User Settings', 'user-settings', NULL, 'published', 'general', 'default'),
('User Wishlist', 'user-wishlist', NULL, 'published', 'general', 'default'),
('Why Page', 'why-page', NULL, 'published', 'general', 'default'),
('Wishlist', 'wishlist', NULL, 'published', 'general', 'default'),
('Bathing Soap Manufacturer', 'bathing-soap-manufacturer', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Contract Manufacturer for Cosmetics Products', 'contract-manufacturer-for-cosmetics-products', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Luxury Private Label Cosmetics', 'luxury-private-label-cosmetics', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Cosmetics Brand', 'private-label-cosmetics-brand', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Essential Oil Supplier', 'private-label-essential-oil-supplier', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Hair Care Manufacturer', 'private-label-hair-care-manufacturer', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Men''s Grooming Products', 'private-label-mens-grooming-products', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Salon Products', 'private-label-salon-products', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Private Label Spa Product', 'private-label-spa-product', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('Third Party Cosmetic', 'third-party-cosmetic', NULL, 'published', 'why_choose_us', 'why_choose_us'),
('White Label Makeup', 'white-label-makeup', NULL, 'published', 'why_choose_us', 'why_choose_us')
ON DUPLICATE KEY UPDATE
title = VALUES(title),
status = VALUES(status),
page_group = VALUES(page_group),
template_key = VALUES(template_key);

INSERT INTO page_meta (page_id, meta_title, meta_description, meta_keywords, canonical_url)
SELECT p.id, v.meta_title, v.meta_description, v.meta_keywords, v.canonical_url
FROM pages p
JOIN (
SELECT '404' AS slug, 'Page Not Found | MyBrandPlease' AS meta_title, 'The page you are looking for could not be found on MyBrandPlease.' AS meta_description, 'page not found, MyBrandPlease' AS meta_keywords, 'https://mybrandplease.com/404.php' AS canonical_url
UNION ALL SELECT 'about', 'About MyBrandPlease | Private Label Cosmetics Manufacturer', 'Learn about MyBrandPlease, a trusted private label cosmetics manufacturer helping brands create skincare, hair care, personal care and beauty products.', 'about MyBrandPlease, private label cosmetics manufacturer, beauty product manufacturer', 'https://mybrandplease.com/about.php'
UNION ALL SELECT 'blog', 'Beauty Manufacturing Blog | MyBrandPlease', 'Read private label cosmetics, skincare, hair care and beauty manufacturing insights from MyBrandPlease.', 'beauty blog, cosmetics manufacturing insights, private label tips', 'https://mybrandplease.com/blog.php'
UNION ALL SELECT 'blog-details', 'Blog Details | MyBrandPlease', 'Read detailed articles and updates from MyBrandPlease on private label cosmetics and beauty product manufacturing.', 'beauty blog details, cosmetics article, private label article', 'https://mybrandplease.com/blog-details.php'
UNION ALL SELECT 'blog-standard', 'Blog Standard | MyBrandPlease', 'Browse MyBrandPlease blog articles about cosmetic manufacturing, private label products and beauty brand growth.', 'beauty blog, cosmetics articles, skincare manufacturing blog', 'https://mybrandplease.com/blog-standard.php'
UNION ALL SELECT 'cart', 'Cart | MyBrandPlease', 'Review selected products and continue your MyBrandPlease order or enquiry process.', 'cart, product cart, MyBrandPlease cart', 'https://mybrandplease.com/cart.php'
UNION ALL SELECT 'checkout', 'Checkout | MyBrandPlease', 'Complete your MyBrandPlease checkout and submit your product order details securely.', 'checkout, cosmetic product order, MyBrandPlease checkout', 'https://mybrandplease.com/checkout.php'
UNION ALL SELECT 'collections', 'Product Collections | MyBrandPlease', 'Explore MyBrandPlease product collections across skincare, hair care, body care and private label beauty categories.', 'cosmetic collections, skincare collections, hair care collections', 'https://mybrandplease.com/collections.php'
UNION ALL SELECT 'contact', 'Contact MyBrandPlease | Private Label Cosmetics Manufacturer', 'Contact MyBrandPlease to discuss private label cosmetics, skincare, hair care, body care and custom beauty product manufacturing.', 'contact MyBrandPlease, cosmetics manufacturer contact, private label enquiry', 'https://mybrandplease.com/contact.php'
UNION ALL SELECT 'data-sheets', 'Product Data Sheets | MyBrandPlease', 'Access MyBrandPlease product data sheets and support resources for private label beauty products.', 'product data sheets, cosmetics data sheets, formulation documents', 'https://mybrandplease.com/data-sheets.php'
UNION ALL SELECT 'faq', 'FAQs | MyBrandPlease', 'Find answers to common questions about MyBrandPlease private label cosmetics manufacturing, product development and ordering.', 'private label cosmetics FAQ, beauty manufacturing questions, MyBrandPlease FAQ', 'https://mybrandplease.com/faq.php'
UNION ALL SELECT 'form-center', 'Form Center | MyBrandPlease', 'Submit forms and enquiries to MyBrandPlease for private label cosmetics and beauty product manufacturing support.', 'form center, enquiry form, MyBrandPlease forms', 'https://mybrandplease.com/form-center.php'
UNION ALL SELECT 'google-calendar-connect', 'Google Calendar Connect | MyBrandPlease', 'Connect or manage Google Calendar scheduling for MyBrandPlease meetings and consultations.', 'calendar connect, meeting calendar, MyBrandPlease scheduling', 'https://mybrandplease.com/google-calendar-connect.php'
UNION ALL SELECT 'home', 'Luxury Private Label Cosmetics Manufacturer | mybrandplease', 'mybrandplease is your trusted luxury private label cosmetics manufacturer, offering custom formulations, premium packaging, and full support to launch your unique beauty brand', 'luxury private label cosmetics manufacturer, private label cosmetics, custom cosmetics, MyBrandPlease', 'https://mybrandplease.com/'
UNION ALL SELECT 'how-it-works', 'How It Works | MyBrandPlease', 'See how MyBrandPlease helps create private label cosmetics from formulation and packaging to production and delivery.', 'how private label works, cosmetics manufacturing process, MyBrandPlease process', 'https://mybrandplease.com/how-it-works.php'
UNION ALL SELECT 'login', 'Login | MyBrandPlease', 'Log in to your MyBrandPlease account to manage orders, wishlist, profile and account details.', 'login, MyBrandPlease account, customer login', 'https://mybrandplease.com/login.php'
UNION ALL SELECT 'logout', 'Logout | MyBrandPlease', 'Log out securely from your MyBrandPlease account.', 'logout, MyBrandPlease account', 'https://mybrandplease.com/logout.php'
UNION ALL SELECT 'meeting-details', 'Meeting Details | MyBrandPlease', 'View your MyBrandPlease meeting details for private label cosmetics consultation and project discussion.', 'meeting details, consultation details, MyBrandPlease meeting', 'https://mybrandplease.com/meeting-details.php'
UNION ALL SELECT 'meeting-schedule', 'Schedule a Meeting | MyBrandPlease', 'Schedule a consultation with MyBrandPlease to discuss private label cosmetics and beauty manufacturing requirements.', 'schedule meeting, cosmetics consultation, private label consultation', 'https://mybrandplease.com/meeting-schedule.php'
UNION ALL SELECT 'order-success', 'Order Success | MyBrandPlease', 'Your MyBrandPlease order or enquiry has been submitted successfully.', 'order success, order confirmation, MyBrandPlease order', 'https://mybrandplease.com/order-success.php'
UNION ALL SELECT 'our-services', 'Our Services | MyBrandPlease', 'Explore MyBrandPlease services for private label cosmetics, skincare, hair care, body care, packaging and product development.', 'private label services, cosmetics manufacturing services, beauty product services', 'https://mybrandplease.com/our-services.php'
UNION ALL SELECT 'privacy', 'Privacy Policy | MyBrandPlease', 'Read the MyBrandPlease privacy policy to understand how customer and enquiry information is handled.', 'privacy policy, MyBrandPlease privacy, data privacy', 'https://mybrandplease.com/privacy.php'
UNION ALL SELECT 'private-label-skin-care-manufacturer', 'Private Label Skin Care Manufacturer for Beauty Solutions', 'MyBrandPlease provides private label skin care manufacturing solutions with custom formulations, packaging and product support for beauty brands.', 'private label skin care manufacturer, skincare manufacturer, beauty solutions', 'https://mybrandplease.com/private-label-skin-care-manufacturer.php'
UNION ALL SELECT 'product', 'Products | MyBrandPlease', 'Explore MyBrandPlease private label cosmetic products and beauty manufacturing categories.', 'private label products, cosmetic products, skincare products', 'https://mybrandplease.com/product/'
UNION ALL SELECT 'product-catalog', 'Product Catalog | MyBrandPlease', 'Browse the MyBrandPlease product catalog for private label skincare, hair care, body care and beauty products.', 'product catalog, cosmetics catalog, private label catalog', 'https://mybrandplease.com/product-catalog.php'
UNION ALL SELECT 'product-details', 'Product Details | MyBrandPlease', 'View product details, images and specifications for MyBrandPlease private label beauty products.', 'product details, cosmetic product details, skincare product details', 'https://mybrandplease.com/product-details.php'
UNION ALL SELECT 'register', 'Register | MyBrandPlease', 'Create a MyBrandPlease account to manage enquiries, orders, wishlist and profile information.', 'register, create account, MyBrandPlease account', 'https://mybrandplease.com/register.php'
UNION ALL SELECT 'services', 'Services | MyBrandPlease', 'Discover MyBrandPlease private label cosmetics manufacturing services for skincare, hair care, body care and personal care brands.', 'cosmetic manufacturing services, private label services, skincare services', 'https://mybrandplease.com/services.php'
UNION ALL SELECT 'shipping-policy', 'Shipping Policy | MyBrandPlease', 'Review the MyBrandPlease shipping policy for product orders, delivery timelines and related information.', 'shipping policy, delivery policy, MyBrandPlease shipping', 'https://mybrandplease.com/shipping-policy.php'
UNION ALL SELECT 'shop', 'Shop Private Label Products | MyBrandPlease', 'Shop and explore MyBrandPlease private label cosmetics, skincare, hair care, body care and packaging products.', 'shop cosmetics, private label products, skincare products, hair care products', 'https://mybrandplease.com/shop.php'
UNION ALL SELECT 'terms-conditions', 'Terms and Conditions | MyBrandPlease', 'Read the MyBrandPlease terms and conditions for website use, orders, services and customer responsibilities.', 'terms and conditions, MyBrandPlease terms, website terms', 'https://mybrandplease.com/terms-conditions.php'
UNION ALL SELECT 'user-addresses', 'My Addresses | MyBrandPlease', 'Manage saved addresses in your MyBrandPlease account.', 'user addresses, account addresses, MyBrandPlease account', 'https://mybrandplease.com/user-addresses.php'
UNION ALL SELECT 'user-dashboard', 'User Dashboard | MyBrandPlease', 'View your MyBrandPlease dashboard for orders, wishlist, profile and account activity.', 'user dashboard, MyBrandPlease dashboard, customer account', 'https://mybrandplease.com/user-dashboard.php'
UNION ALL SELECT 'user-orders', 'My Orders | MyBrandPlease', 'View and manage your MyBrandPlease order history and order details.', 'user orders, order history, MyBrandPlease orders', 'https://mybrandplease.com/user-orders.php'
UNION ALL SELECT 'user-profile', 'My Profile | MyBrandPlease', 'Manage your profile information and customer details in your MyBrandPlease account.', 'user profile, MyBrandPlease profile, account profile', 'https://mybrandplease.com/user-profile.php'
UNION ALL SELECT 'user-settings', 'Account Settings | MyBrandPlease', 'Update account settings and preferences for your MyBrandPlease account.', 'account settings, user settings, MyBrandPlease settings', 'https://mybrandplease.com/user-settings.php'
UNION ALL SELECT 'user-wishlist', 'My Wishlist | MyBrandPlease', 'View and manage saved products in your MyBrandPlease account wishlist.', 'user wishlist, MyBrandPlease wishlist, saved products', 'https://mybrandplease.com/user-wishlist.php'
UNION ALL SELECT 'why-page', 'Why Choose MyBrandPlease | Private Label Cosmetics Manufacturer', 'Learn why brands choose MyBrandPlease for private label cosmetics manufacturing, product development and beauty brand support.', 'why choose MyBrandPlease, private label manufacturer, cosmetics partner', 'https://mybrandplease.com/why-page.php'
UNION ALL SELECT 'wishlist', 'Wishlist | MyBrandPlease', 'View saved MyBrandPlease products in your wishlist.', 'wishlist, saved products, MyBrandPlease wishlist', 'https://mybrandplease.com/wishlist.php'
UNION ALL SELECT 'bathing-soap-manufacturer', 'Mybrandplease | Bathing Soap Manufacturer', 'Private label bathing soap manufacturing support for brands looking to build quality personal care collections.', 'bathing soap manufacturer, private label soap, personal care manufacturer', 'https://mybrandplease.com/bathing-soap-manufacturer/'
UNION ALL SELECT 'contract-manufacturer-for-cosmetics-products', 'Mybrandplease | Contract Manufacturer for Cosmetics Products', 'Partner with Mybrandplease as a contract manufacturer for cosmetics products and launch with stronger execution.', 'contract manufacturer for cosmetics products, cosmetics contract manufacturing, private label cosmetics', 'https://mybrandplease.com/contract-manufacturer-for-cosmetics-products/'
UNION ALL SELECT 'luxury-private-label-cosmetics', 'Mybrandplease | Luxury Private Label Cosmetics', 'Develop premium private label cosmetic products with elevated packaging and brand-ready positioning from Mybrandplease.', 'luxury private label cosmetics, premium cosmetics manufacturer, private label beauty', 'https://mybrandplease.com/luxury-private-label-cosmetics/'
UNION ALL SELECT 'private-label-cosmetics-brand', 'Mybrandplease | Private Label Cosmetics Brand Support', 'Build a stronger private label cosmetics brand with help from Mybrandplease across product, packaging, and launch execution.', 'private label cosmetics brand, beauty brand support, cosmetics branding', 'https://mybrandplease.com/private-label-cosmetics-brand/'
UNION ALL SELECT 'private-label-essential-oil-supplier', 'Mybrandplease | Private Label Essential Oil Supplier', 'Launch a trusted essential oil line with private label sourcing, packaging, and production support from Mybrandplease.', 'private label essential oil supplier, essential oil manufacturer, private label oils', 'https://mybrandplease.com/private-label-essential-oil-supplier/'
UNION ALL SELECT 'private-label-hair-care-manufacturer', 'Private Label Hair Care Manufacturer | Mybrandplease', 'Create shampoos, conditioners, treatments and styling products with private label hair care manufacturing support from Mybrandplease.', 'private label hair care manufacturer, hair care manufacturer, shampoo manufacturer', 'https://mybrandplease.com/private-label-hair-care-manufacturer/'
UNION ALL SELECT 'private-label-mens-grooming-products', 'Private Label Men''s Grooming Products | Mybrandplease', 'Develop men''s grooming products with private label formulation, packaging and manufacturing support from Mybrandplease.', 'private label mens grooming products, mens grooming manufacturer, grooming products', 'https://mybrandplease.com/private-label-mens-grooming-products/'
UNION ALL SELECT 'private-label-salon-products', 'Mybrandplease | Private Label Salon Products', 'Launch salon-quality private label products with branding, packaging, and market-ready support from Mybrandplease.', 'private label salon products, salon product manufacturer, professional hair care', 'https://mybrandplease.com/private-label-salon-products/'
UNION ALL SELECT 'private-label-spa-product', 'Mybrandplease | Private Label Spa Product', 'Create spa-focused private label products with a clean, premium brand experience supported by Mybrandplease.', 'private label spa product, spa product manufacturer, private label spa', 'https://mybrandplease.com/private-label-spa-product/'
UNION ALL SELECT 'third-party-cosmetic', 'Mybrandplease | Third Party Cosmetic Manufacturing', 'Explore third party cosmetic manufacturing support for beauty brands looking to scale with reliable production and branding help.', 'third party cosmetic manufacturing, cosmetics manufacturer, private label beauty', 'https://mybrandplease.com/third-party-cosmetic/'
UNION ALL SELECT 'white-label-makeup', 'Mybrandplease | White Label Makeup', 'Build a white label makeup line with flexible branding, packaging, and launch support from Mybrandplease.', 'white label makeup, makeup manufacturer, private label makeup', 'https://mybrandplease.com/white-label-makeup/'
) v ON v.slug = p.slug
ON DUPLICATE KEY UPDATE
meta_title = VALUES(meta_title),
meta_description = VALUES(meta_description),
meta_keywords = VALUES(meta_keywords),
canonical_url = VALUES(canonical_url);

UPDATE pages
SET page_group = 'why_choose_us', template_key = 'why_choose_us', status = 'published'
WHERE slug IN (
  'bathing-soap-manufacturer',
  'contract-manufacturer-for-cosmetics-products',
  'luxury-private-label-cosmetics',
  'private-label-cosmetics-brand',
  'private-label-essential-oil-supplier',
  'private-label-hair-care-manufacturer',
  'private-label-mens-grooming-products',
  'private-label-salon-products',
  'private-label-skin-care-manufacturer',
  'private-label-spa-product',
  'third-party-cosmetic',
  'white-label-makeup'
);

UPDATE menu_items mi
JOIN menus m ON m.id = mi.menu_id AND m.location_key = 'header_main'
JOIN pages p ON p.slug = TRIM(BOTH '/' FROM mi.url)
SET mi.url = CONCAT(p.slug, '/')
WHERE p.page_group = 'why_choose_us';
