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
SELECT '404' AS slug, 'Page Not Found | mybrandplease' AS meta_title, 'The page you are looking for could not be found on mybrandplease.' AS meta_description, 'page not found, mybrandplease' AS meta_keywords, 'https://mybrandplease.com/404.php' AS canonical_url
UNION ALL SELECT 'about', 'About mybrandplease | Private Label Cosmetics Manufacturer', 'Learn about mybrandplease, a trusted private label cosmetics manufacturer helping brands create skincare, hair care, personal care and beauty products.', 'about mybrandplease, private label cosmetics manufacturer, beauty product manufacturer', 'https://mybrandplease.com/about.php'
UNION ALL SELECT 'blog', 'Beauty Manufacturing Blog | mybrandplease', 'Read private label cosmetics, skincare, hair care and beauty manufacturing insights from mybrandplease.', 'beauty blog, cosmetics manufacturing insights, private label tips', 'https://mybrandplease.com/blog.php'
UNION ALL SELECT 'blog-details', 'Blog Details | mybrandplease', 'Read detailed articles and updates from mybrandplease on private label cosmetics and beauty product manufacturing.', 'beauty blog details, cosmetics article, private label article', 'https://mybrandplease.com/blog-details.php'
UNION ALL SELECT 'blog-standard', 'Blog Standard | mybrandplease', 'Browse mybrandplease blog articles about cosmetic manufacturing, private label products and beauty brand growth.', 'beauty blog, cosmetics articles, skincare manufacturing blog', 'https://mybrandplease.com/blog-standard.php'
UNION ALL SELECT 'cart', 'Cart | mybrandplease', 'Review selected products and continue your mybrandplease order or enquiry process.', 'cart, product cart, mybrandplease cart', 'https://mybrandplease.com/cart.php'
UNION ALL SELECT 'checkout', 'Checkout | mybrandplease', 'Complete your mybrandplease checkout and submit your product order details securely.', 'checkout, cosmetic product order, mybrandplease checkout', 'https://mybrandplease.com/checkout.php'
UNION ALL SELECT 'collections', 'Product Collections | mybrandplease', 'Explore mybrandplease product collections across skincare, hair care, body care and private label beauty categories.', 'cosmetic collections, skincare collections, hair care collections', 'https://mybrandplease.com/collections.php'
UNION ALL SELECT 'contact', 'Contact mybrandplease | Private Label Cosmetics Manufacturer', 'Contact mybrandplease to discuss private label cosmetics, skincare, hair care, body care and custom beauty product manufacturing.', 'contact mybrandplease, cosmetics manufacturer contact, private label enquiry', 'https://mybrandplease.com/contact.php'
UNION ALL SELECT 'data-sheets', 'Product Data Sheets | mybrandplease', 'Access mybrandplease product data sheets and support resources for private label beauty products.', 'product data sheets, cosmetics data sheets, formulation documents', 'https://mybrandplease.com/data-sheets.php'
UNION ALL SELECT 'faq', 'FAQs | mybrandplease', 'Find answers to common questions about mybrandplease private label cosmetics manufacturing, product development and ordering.', 'private label cosmetics FAQ, beauty manufacturing questions, mybrandplease FAQ', 'https://mybrandplease.com/faq.php'
UNION ALL SELECT 'form-center', 'Form Center | mybrandplease', 'Submit forms and enquiries to mybrandplease for private label cosmetics and beauty product manufacturing support.', 'form center, enquiry form, mybrandplease forms', 'https://mybrandplease.com/form-center.php'
UNION ALL SELECT 'google-calendar-connect', 'Google Calendar Connect | mybrandplease', 'Connect or manage Google Calendar scheduling for mybrandplease meetings and consultations.', 'calendar connect, meeting calendar, mybrandplease scheduling', 'https://mybrandplease.com/google-calendar-connect.php'
UNION ALL SELECT 'home', 'Luxury Private Label Cosmetics Manufacturer | mybrandplease', 'mybrandplease is your trusted luxury private label cosmetics manufacturer, offering custom formulations, premium packaging, and full support to launch your unique beauty brand', 'luxury private label cosmetics manufacturer, private label cosmetics, custom cosmetics, mybrandplease', 'https://mybrandplease.com/'
UNION ALL SELECT 'how-it-works', 'How It Works | mybrandplease', 'See how mybrandplease helps create private label cosmetics from formulation and packaging to production and delivery.', 'how private label works, cosmetics manufacturing process, mybrandplease process', 'https://mybrandplease.com/how-it-works.php'
UNION ALL SELECT 'login', 'Login | mybrandplease', 'Log in to your mybrandplease account to manage orders, wishlist, profile and account details.', 'login, mybrandplease account, customer login', 'https://mybrandplease.com/login.php'
UNION ALL SELECT 'logout', 'Logout | mybrandplease', 'Log out securely from your mybrandplease account.', 'logout, mybrandplease account', 'https://mybrandplease.com/logout.php'
UNION ALL SELECT 'meeting-details', 'Meeting Details | mybrandplease', 'View your mybrandplease meeting details for private label cosmetics consultation and project discussion.', 'meeting details, consultation details, mybrandplease meeting', 'https://mybrandplease.com/meeting-details.php'
UNION ALL SELECT 'meeting-schedule', 'Schedule a Meeting | mybrandplease', 'Schedule a consultation with mybrandplease to discuss private label cosmetics and beauty manufacturing requirements.', 'schedule meeting, cosmetics consultation, private label consultation', 'https://mybrandplease.com/meeting-schedule.php'
UNION ALL SELECT 'order-success', 'Order Success | mybrandplease', 'Your mybrandplease order or enquiry has been submitted successfully.', 'order success, order confirmation, mybrandplease order', 'https://mybrandplease.com/order-success.php'
UNION ALL SELECT 'our-services', 'Our Services | mybrandplease', 'Explore mybrandplease services for private label cosmetics, skincare, hair care, body care, packaging and product development.', 'private label services, cosmetics manufacturing services, beauty product services', 'https://mybrandplease.com/our-services.php'
UNION ALL SELECT 'privacy', 'Privacy Policy | mybrandplease', 'Read the mybrandplease privacy policy to understand how customer and enquiry information is handled.', 'privacy policy, mybrandplease privacy, data privacy', 'https://mybrandplease.com/privacy.php'
UNION ALL SELECT 'private-label-skin-care-manufacturer', 'Private Label Skin Care Manufacturer for Beauty Solutions', 'mybrandplease provides private label skin care manufacturing solutions with custom formulations, packaging and product support for beauty brands.', 'private label skin care manufacturer, skincare manufacturer, beauty solutions', 'https://mybrandplease.com/private-label-skin-care-manufacturer.php'
UNION ALL SELECT 'product', 'Products | mybrandplease', 'Explore mybrandplease private label cosmetic products and beauty manufacturing categories.', 'private label products, cosmetic products, skincare products', 'https://mybrandplease.com/product/'
UNION ALL SELECT 'product-catalog', 'Product Catalog | mybrandplease', 'Browse the mybrandplease product catalog for private label skincare, hair care, body care and beauty products.', 'product catalog, cosmetics catalog, private label catalog', 'https://mybrandplease.com/product-catalog.php'
UNION ALL SELECT 'product-details', 'Product Details | mybrandplease', 'View product details, images and specifications for mybrandplease private label beauty products.', 'product details, cosmetic product details, skincare product details', 'https://mybrandplease.com/product-details.php'
UNION ALL SELECT 'register', 'Register | mybrandplease', 'Create a mybrandplease account to manage enquiries, orders, wishlist and profile information.', 'register, create account, mybrandplease account', 'https://mybrandplease.com/register.php'
UNION ALL SELECT 'services', 'Services | mybrandplease', 'Discover mybrandplease private label cosmetics manufacturing services for skincare, hair care, body care and personal care brands.', 'cosmetic manufacturing services, private label services, skincare services', 'https://mybrandplease.com/services.php'
UNION ALL SELECT 'shipping-policy', 'Shipping Policy | mybrandplease', 'Review the mybrandplease shipping policy for product orders, delivery timelines and related information.', 'shipping policy, delivery policy, mybrandplease shipping', 'https://mybrandplease.com/shipping-policy.php'
UNION ALL SELECT 'shop', 'Shop Private Label Products | mybrandplease', 'Shop and explore mybrandplease private label cosmetics, skincare, hair care, body care and packaging products.', 'shop cosmetics, private label products, skincare products, hair care products', 'https://mybrandplease.com/shop.php'
UNION ALL SELECT 'terms-conditions', 'Terms and Conditions | mybrandplease', 'Read the mybrandplease terms and conditions for website use, orders, services and customer responsibilities.', 'terms and conditions, mybrandplease terms, website terms', 'https://mybrandplease.com/terms-conditions.php'
UNION ALL SELECT 'user-addresses', 'My Addresses | mybrandplease', 'Manage saved addresses in your mybrandplease account.', 'user addresses, account addresses, mybrandplease account', 'https://mybrandplease.com/user-addresses.php'
UNION ALL SELECT 'user-dashboard', 'User Dashboard | mybrandplease', 'View your mybrandplease dashboard for orders, wishlist, profile and account activity.', 'user dashboard, mybrandplease dashboard, customer account', 'https://mybrandplease.com/user-dashboard.php'
UNION ALL SELECT 'user-orders', 'My Orders | mybrandplease', 'View and manage your mybrandplease order history and order details.', 'user orders, order history, mybrandplease orders', 'https://mybrandplease.com/user-orders.php'
UNION ALL SELECT 'user-profile', 'My Profile | mybrandplease', 'Manage your profile information and customer details in your mybrandplease account.', 'user profile, mybrandplease profile, account profile', 'https://mybrandplease.com/user-profile.php'
UNION ALL SELECT 'user-settings', 'Account Settings | mybrandplease', 'Update account settings and preferences for your mybrandplease account.', 'account settings, user settings, mybrandplease settings', 'https://mybrandplease.com/user-settings.php'
UNION ALL SELECT 'user-wishlist', 'My Wishlist | mybrandplease', 'View and manage saved products in your mybrandplease account wishlist.', 'user wishlist, mybrandplease wishlist, saved products', 'https://mybrandplease.com/user-wishlist.php'
UNION ALL SELECT 'why-page', 'Why Choose mybrandplease | Private Label Cosmetics Manufacturer', 'Learn why brands choose mybrandplease for private label cosmetics manufacturing, product development and beauty brand support.', 'why choose mybrandplease, private label manufacturer, cosmetics partner', 'https://mybrandplease.com/why-page.php'
UNION ALL SELECT 'wishlist', 'Wishlist | mybrandplease', 'View saved mybrandplease products in your wishlist.', 'wishlist, saved products, mybrandplease wishlist', 'https://mybrandplease.com/wishlist.php'
UNION ALL SELECT 'bathing-soap-manufacturer', 'mybrandplease | Bathing Soap Manufacturer', 'Private label bathing soap manufacturing support for brands looking to build quality personal care collections.', 'bathing soap manufacturer, private label soap, personal care manufacturer', 'https://mybrandplease.com/bathing-soap-manufacturer/'
UNION ALL SELECT 'contract-manufacturer-for-cosmetics-products', 'mybrandplease | Contract Manufacturer for Cosmetics Products', 'Partner with mybrandplease as a contract manufacturer for cosmetics products and launch with stronger execution.', 'contract manufacturer for cosmetics products, cosmetics contract manufacturing, private label cosmetics', 'https://mybrandplease.com/contract-manufacturer-for-cosmetics-products/'
UNION ALL SELECT 'luxury-private-label-cosmetics', 'mybrandplease | Luxury Private Label Cosmetics', 'Develop premium private label cosmetic products with elevated packaging and brand-ready positioning from mybrandplease.', 'luxury private label cosmetics, premium cosmetics manufacturer, private label beauty', 'https://mybrandplease.com/luxury-private-label-cosmetics/'
UNION ALL SELECT 'private-label-cosmetics-brand', 'mybrandplease | Private Label Cosmetics Brand Support', 'Build a stronger private label cosmetics brand with help from mybrandplease across product, packaging, and launch execution.', 'private label cosmetics brand, beauty brand support, cosmetics branding', 'https://mybrandplease.com/private-label-cosmetics-brand/'
UNION ALL SELECT 'private-label-essential-oil-supplier', 'mybrandplease | Private Label Essential Oil Supplier', 'Launch a trusted essential oil line with private label sourcing, packaging, and production support from mybrandplease.', 'private label essential oil supplier, essential oil manufacturer, private label oils', 'https://mybrandplease.com/private-label-essential-oil-supplier/'
UNION ALL SELECT 'private-label-hair-care-manufacturer', 'Private Label Hair Care Manufacturer | mybrandplease', 'Create shampoos, conditioners, treatments and styling products with private label hair care manufacturing support from mybrandplease.', 'private label hair care manufacturer, hair care manufacturer, shampoo manufacturer', 'https://mybrandplease.com/private-label-hair-care-manufacturer/'
UNION ALL SELECT 'private-label-mens-grooming-products', 'Private Label Men''s Grooming Products | mybrandplease', 'Develop men''s grooming products with private label formulation, packaging and manufacturing support from mybrandplease.', 'private label mens grooming products, mens grooming manufacturer, grooming products', 'https://mybrandplease.com/private-label-mens-grooming-products/'
UNION ALL SELECT 'private-label-salon-products', 'mybrandplease | Private Label Salon Products', 'Launch salon-quality private label products with branding, packaging, and market-ready support from mybrandplease.', 'private label salon products, salon product manufacturer, professional hair care', 'https://mybrandplease.com/private-label-salon-products/'
UNION ALL SELECT 'private-label-spa-product', 'mybrandplease | Private Label Spa Product', 'Create spa-focused private label products with a clean, premium brand experience supported by mybrandplease.', 'private label spa product, spa product manufacturer, private label spa', 'https://mybrandplease.com/private-label-spa-product/'
UNION ALL SELECT 'third-party-cosmetic', 'mybrandplease | Third Party Cosmetic Manufacturing', 'Explore third party cosmetic manufacturing support for beauty brands looking to scale with reliable production and branding help.', 'third party cosmetic manufacturing, cosmetics manufacturer, private label beauty', 'https://mybrandplease.com/third-party-cosmetic/'
UNION ALL SELECT 'white-label-makeup', 'mybrandplease | White Label Makeup', 'Build a white label makeup line with flexible branding, packaging, and launch support from mybrandplease.', 'white label makeup, makeup manufacturer, private label makeup', 'https://mybrandplease.com/white-label-makeup/'
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
