CREATE TABLE IF NOT EXISTS blog_posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT NULL,
    content LONGTEXT NULL,
    meta_title VARCHAR(255) NULL,
    canonical_url VARCHAR(255) NULL,
    meta_keywords VARCHAR(500) NULL,
    meta_description TEXT NULL,
    featured_image VARCHAR(255) NULL,
    category VARCHAR(120) NOT NULL DEFAULT 'General',
    author_name VARCHAR(120) NOT NULL DEFAULT 'Admin',
    tags VARCHAR(500) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_blog_posts_status_published (status, published_at),
    INDEX idx_blog_posts_category (category),
    INDEX idx_blog_posts_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, category, author_name, tags, status, published_at)
SELECT * FROM (
    SELECT
        'How to Choose the Perfect Foundation Shade',
        'how-to-choose-the-perfect-foundation-shade',
        'Find your undertone, test in natural light, and get a seamless match for every season.',
        '<p>Choosing the right foundation starts with understanding your undertone and skin type. Test shades on jawline and always check in natural light before finalizing.</p>',
        'assets/imgs/inner/blog/blog-thumb2_1.jpg',
        'Makeup Tips',
        'Benjamin',
        'foundation,makeup,beauty',
        'published',
        '2024-09-20 10:00:00'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'how-to-choose-the-perfect-foundation-shade');

INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, category, author_name, tags, status, published_at)
SELECT * FROM (
    SELECT
        'The Ultimate Guide to Long-Lasting Lip Color',
        'the-ultimate-guide-to-long-lasting-lip-color',
        'Prep lips, layer smartly, and lock the look for all-day wear.',
        '<p>Exfoliate, hydrate, line your lips and apply in thin layers. Blot between coats and set with translucent powder for better hold.</p>',
        'assets/imgs/inner/blog/blog-thumb2_2.jpg',
        'Skincare',
        'Benjamin',
        'lipstick,beauty,guide',
        'published',
        '2024-09-20 11:00:00'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'the-ultimate-guide-to-long-lasting-lip-color');

INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, category, author_name, tags, status, published_at)
SELECT * FROM (
    SELECT
        'Best Tips for Achieving a Dewy Fresh Look',
        'best-tips-for-achieving-a-dewy-fresh-look',
        'Use hydrating layers and cream formulas to get that natural glow.',
        '<p>Start with hydrated skin, use lightweight base products and add cream highlighter for a natural dewy finish.</p>',
        'assets/imgs/inner/blog/blog-thumb2_3.jpg',
        'Health',
        'Benjamin',
        'dewy,skin,glow',
        'published',
        '2024-09-20 12:00:00'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM blog_posts WHERE slug = 'best-tips-for-achieving-a-dewy-fresh-look');

