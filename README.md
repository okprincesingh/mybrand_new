# MyBrand Website and Dashboard Documentation

## Overview
This project is a PHP + MySQL e-commerce/CMS website with:
- Public storefront pages
- User account dashboard (orders, profile, addresses, wishlist, settings)
- Admin dashboard/CMS for content, catalog, users, and orders
- Session-based cart and checkout APIs

Core stack:
- PHP (procedural modules)
- MySQL via PDO (`includes/db.php`)
- Bootstrap (admin UI), custom CSS/SCSS (website UI)
- Session + cookie authentication for users and admins

## High-Level Architecture
- `includes/` contains reusable business logic: DB, auth, CMS, catalog, security helpers.
- Root `.php` files are public website and user account pages.
- `admin/` contains authenticated CMS/admin modules.
- `api/` exposes JSON endpoints for cart and order placement.
- `database/` contains schema, migrations, and seed SQL.
- `assets/` contains frontend styles/scripts/images/fonts.

Typical request flow:
1. Page includes shared modules from `includes/`.
2. Module reads/writes MySQL through `db()` helper.
3. Authentication checks run (`user_require_auth()` / `admin_require_auth()`).
4. UI renders via page templates and CSS.

## Folder and File Structure

### Root (Public + User Account)
- `index.php`: homepage.
- Catalog/shop:
  - `shop.php`, `product-details.php`, `collections.php`
- Cart/checkout/order:
  - `cart.php`, `checkout.php`, `order-success.php`
- Auth:
  - `login.php`, `register.php`, `logout.php`
- User dashboard pages:
  - `user-dashboard.php`
  - `user-orders.php`
  - `user-profile.php`
  - `user-addresses.php`
  - `user-wishlist.php`
  - `user-settings.php`
- Content pages:
  - `about.php`, `contact.php`, `blog.php`, `blog-details.php`, `how-it-works.php`, `404.php`, etc.

### `admin/` (CMS/Admin Panel)
- Auth/session:
  - `login.php`, `signup.php`, `logout.php`, `_init.php`
- Layout:
  - `_layout_top.php`, `_layout_bottom.php`
- Dashboard:
  - `dashboard.php`
- Home section CMS:
  - `home-slider.php`, `home-testimonials.php`, `home-offices.php`
- Content CMS:
  - `why-pages.php`, `why-page-edit.php`
  - `blogs.php`, `blog-edit.php`
  - `pages.php`, `page-edit.php`
- Catalog CMS:
  - `products.php`, `product-edit.php`
  - `categories.php`, `reviews.php`
- Commerce/admin ops:
  - `orders.php`, `users.php`

### `includes/` (Core Modules)
- `db.php`: session bootstrap + PDO connection + environment DB config.
- `auth.php`: admin auth, JWT access/refresh tokens, token/session revocation.
- `user.php`: user auth, user sessions, profile/address/wishlist/order helpers.
- `catalog.php`: product/category retrieval and cart helpers consumed by pages/APIs.
- `cms.php`: page/menu/settings/home content loaders with cache invalidation.
- `security.php`: CSRF, validation/sanitization/security helpers.
- Shared template pieces: `head.php`, `header.php`, `footer.php`.

### `api/`
- `cart.php`: add/update/remove/clear/get cart.
- `order.php`: create order from checkout payload.

### `database/`
- `schema.sql`, `schema_users.sql`, `schema_mybrandplease.sql`
- Multiple migration files for features (auth tokens, cart/orders, blog, home dynamic content, etc.)
- Seed and verification scripts.

### Assets/Storage
- `assets/`: CSS/SCSS/JS/vendor libs/images/fonts.
- `uploads/`: uploaded files (e.g., testimonial images).
- `storage/sessions/`: fallback PHP session storage.

## Authentication and Security Model

### Admin Authentication
Implemented in `includes/auth.php`:
- Login validates admin credentials from `admins` table.
- Generates JWT access + refresh tokens.
- Stores hashed tokens in `admin_sessions` and `admin_refresh_tokens`.
- Binds sessions to IP hash + User-Agent hash.
- Sets secure/httponly/samesite cookies.
- Supports token refresh and token revocation on logout.
- `admin_require_auth()` protects admin pages.

### User Authentication
Implemented in `includes/user.php`:
- Login creates random `user_session` token stored in `user_sessions` table.
- Session validation checks expiry, IP, User-Agent, active user status.
- `user_require_auth()` protects user account pages.
- Logout deletes the current session token.

### CSRF and Input Safety
- `enforce_csrf_on_post()` is called in admin `_init.php`.
- Admin forms include CSRF tokens and call `verify_csrf_or_fail()`.
- Output escaping generally uses `e()` / `htmlspecialchars()`.

## User Dashboard Modules and Features

### 1) Dashboard (`user-dashboard.php`)
- Requires logged-in user.
- Shows:
  - total orders
  - wishlist count
  - address count
  - member-since date
- Shows latest orders and links to details.
- Shows quick actions (shop, wishlist, addresses, profile).
- Shows security block (email verification state, password last update).

### 2) Orders (`user-orders.php`)
- Lists user orders (up to 50 in list view).
- Single-order view via `?order=ORDER_NUMBER`:
  - order summary
  - shipping/billing details
  - item rows
  - totals breakdown
  - status history timeline
- Cancel action in UI is currently placeholder (no real cancel API call yet).

### 3) Profile (`user-profile.php`)
- Updates basic profile fields:
  - first/last name, phone, DOB, gender
- Uses `user_update_profile()`.
- Displays account metadata (status, verification, last login, created date).

### 4) Addresses (`user-addresses.php`)
- Full address CRUD:
  - add
  - edit
  - delete
  - set default
- Uses `user_addresses` table helpers in `includes/user.php`.
- Supports address type: `both`, `billing`, `shipping`.

### 5) Wishlist (`user-wishlist.php`)
- Displays wishlist items for logged user.
- Frontend actions:
  - remove from wishlist
  - add single/all wishlist items to cart
- Uses AJAX calls to APIs.

### 6) Settings (`user-settings.php`)
- Password change with validation.
- Displays security summary.
- Account deletion flow is currently placeholder (confirmation UI only; no hard delete implemented).

## Admin Dashboard and Module Work

### Main Dashboard (`admin/dashboard.php`)
Provides operational snapshot:
- top-level counts: pages, products, categories, reviews, users, orders
- recent users table
- recent products table
- recent orders table
- status overview cards (published/draft products, pending/completed orders)

### Users Module (`admin/users.php`)
- Lists users from `users` table.
- Activate/deactivate user (`is_active` toggle).

### Orders Module (`admin/orders.php`)
- Lists orders joined with customer data.
- Updates order status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`).
- Logs each status change in `order_status_history`.

### Products Module (`admin/products.php` + `admin/product-edit.php`)
- Product listing with filters:
  - search
  - category/subcategory
  - status
  - sort
- Product create/edit/delete.

### Categories Module (`admin/categories.php`)
- Category create/edit/delete.
- Supports parent-child category hierarchy.
- Maintains slug, status, description, image path.

### Reviews Module (`admin/reviews.php`)
- Review moderation/management (based on `product_reviews`).

### Blog Module (`admin/blogs.php` + `admin/blog-edit.php`)
- Blog list, filters, CRUD.
- Works with `blog_posts` table.

### SEO Pages Module (`admin/pages.php` + `admin/page-edit.php`)
- CRUD for pages and metadata.
- Supports page groups (`general`, `why_choose_us`).
- Calls CMS cache invalidation after updates/deletes.

### Why Choose Us Module (`admin/why-pages.php` + `admin/why-page-edit.php`)
- Manages specialized page group content and accordions.

### Home Content Modules
- `home-slider.php`: hero slides
- `home-testimonials.php`: testimonial cards
- `home-offices.php`: office/contact blocks
- Data served via CMS helpers with fallback values if DB rows are missing.

## API and Checkout Workflow

### Cart API (`api/cart.php`)
Actions:
- `add`
- `update`
- `remove`
- `clear`
- GET returns full cart summary

Behavior:
- Cart kept in `$_SESSION['cart']`.
- Also persists by `session_id` into `cart` DB table.

### Order API (`api/order.php`)
- Requires authenticated user (`user_current()`).
- Validates billing payload.
- Reads session cart and computes totals.
- Creates/updates `customers` record.
- Syncs user profile and addresses.
- Creates `orders`, `order_items`, and `order_status_history` rows in one DB transaction.
- Clears cart after successful order.

### Checkout Page (`checkout.php`)
- Requires user login.
- Prefills billing from default address/user profile.
- Submits JSON to `api/order.php`.
- Redirects to `order-success.php?order=...` on success.

## Database Modules (Main Tables)
- Auth/admin: `admins`, `admin_sessions`, `admin_refresh_tokens`
- CMS/content: `pages`, `page_meta`, `page_sections`, `page_section_items`, `site_settings`, `menus`, `menu_items`, `footer_sections`, `footer_links`, `home_slides`, `home_testimonials`, `home_offices`, `why_page_accordions`
- Catalog: `categories`, `products`, `product_images`, `product_attributes`, `product_reviews`, `offers`
- User/account: `users`, `user_sessions`, `user_addresses`, `user_wishlist`
- Commerce: `cart`, `customers`, `orders`, `order_items`, `order_status_history`

## Setup Notes
1. Create MySQL database (default expected name: `mybrandplease`).
2. Configure DB env vars if not using defaults:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
3. Import schema/migrations from `database/`.
4. Ensure PHP can write `storage/sessions/`.
5. Run under Apache/XAMPP with document root at project folder.

## Important Observations
- User dashboard pages call `session_start()` directly; `includes/db.php` also manages session bootstrapping. Keep this behavior consistent when refactoring.
- `user-wishlist.php` calls `api/user.php`, but that file is not present in current tree, so wishlist AJAX remove/clear depends on a missing endpoint.
- Some user actions are intentionally placeholder UI flows (order cancel, account delete actual execution).

## Suggested Next Improvements
1. Add missing `api/user.php` for wishlist/account actions.
2. Centralize route-level auth/session bootstrap to avoid repeated `session_start()` patterns.
3. Add role/permission checks for sensitive admin actions.
4. Add automated tests for cart/order transaction flow and admin order-status transitions.
5. Add a migration runner script and deployment checklist.
