<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/url.php';

function blog_fallback_posts(): array
{
    return [
        [
            'id' => 1,
            'title' => 'How to Choose the Perfect Foundation Shade',
            'slug' => 'how-to-choose-the-perfect-foundation-shade',
            'excerpt' => 'Find your undertone, test in natural light, and get a seamless match for every season.',
            'content' => '<p>Choosing the right foundation starts with understanding your undertone and skin type. Test shades on jawline and always check in natural light before finalizing.</p>',
            'featured_image' => 'assets/imgs/inner/blog/blog-thumb2_1.jpg',
            'category' => 'Makeup Tips',
            'author_name' => 'Benjamin',
            'published_at' => '2024-09-20 10:00:00',
            'status' => 'published',
            'tags' => 'foundation,makeup,beauty',
        ],
        [
            'id' => 2,
            'title' => 'The Ultimate Guide to Long-Lasting Lip Color',
            'slug' => 'the-ultimate-guide-to-long-lasting-lip-color',
            'excerpt' => 'Prep lips, layer smartly, and lock the look for all-day wear.',
            'content' => '<p>Exfoliate, hydrate, line your lips and apply in thin layers. Blot between coats and set with translucent powder for better hold.</p>',
            'featured_image' => 'assets/imgs/inner/blog/blog-thumb2_2.jpg',
            'category' => 'Skincare',
            'author_name' => 'Benjamin',
            'published_at' => '2024-09-20 11:00:00',
            'status' => 'published',
            'tags' => 'lipstick,beauty,guide',
        ],
        [
            'id' => 3,
            'title' => 'Best Tips for Achieving a Dewy Fresh Look',
            'slug' => 'best-tips-for-achieving-a-dewy-fresh-look',
            'excerpt' => 'Use hydrating layers and cream formulas to get that natural glow.',
            'content' => '<p>Start with hydrated skin, use lightweight base products and add cream highlighter for a natural dewy finish.</p>',
            'featured_image' => 'assets/imgs/inner/blog/blog-thumb2_3.jpg',
            'category' => 'Health',
            'author_name' => 'Benjamin',
            'published_at' => '2024-09-20 12:00:00',
            'status' => 'published',
            'tags' => 'dewy,skin,glow',
        ],
    ];
}

function blog_table_exists(PDO $pdo): bool
{
    static $checked = null;
    if ($checked !== null) {
        return $checked;
    }

    $row = db_fetch_one($pdo, "SHOW TABLES LIKE 'blog_posts'");
    $checked = is_array($row);
    return $checked;
}

function blog_get_posts(array $filters = [], array $pagination = []): array
{
    $pdo = db();
    $fallback = blog_fallback_posts();

    if (!$pdo || !blog_table_exists($pdo)) {
        $items = array_values(array_filter($fallback, static function (array $post) use ($filters): bool {
            if (($post['status'] ?? '') !== 'published') {
                return false;
            }
            if (!empty($filters['category']) && strcasecmp((string) ($post['category'] ?? ''), (string) $filters['category']) !== 0) {
                return false;
            }
            if (!empty($filters['search'])) {
                $needle = strtolower((string) $filters['search']);
                $hay = strtolower((string) ($post['title'] . ' ' . $post['excerpt']));
                if (strpos($hay, $needle) === false) {
                    return false;
                }
            }
            return true;
        }));

        $total = count($items);
        $limit = max(1, (int) ($pagination['limit'] ?? 9));
        $offset = max(0, (int) ($pagination['offset'] ?? 0));
        $items = array_slice($items, $offset, $limit);
        return ['items' => $items, 'total' => $total];
    }

    $where = ['status = :st'];
    $params = [':st' => 'published'];

    if (!empty($filters['category'])) {
        $where[] = 'category = :cat';
        $params[':cat'] = (string) $filters['category'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(title LIKE :q_title OR excerpt LIKE :q_excerpt OR content LIKE :q_content)';
        $like = '%' . $filters['search'] . '%';
        $params[':q_title'] = $like;
        $params[':q_excerpt'] = $like;
        $params[':q_content'] = $like;
    }

    $limit = max(1, (int) ($pagination['limit'] ?? 9));
    $offset = max(0, (int) ($pagination['offset'] ?? 0));
    $whereSql = implode(' AND ', $where);

    $total = (int) (db_fetch_value($pdo, "SELECT COUNT(*) FROM blog_posts WHERE {$whereSql}", $params) ?? 0);

    $sql = "SELECT id,title,slug,excerpt,content,featured_image,category,author_name,published_at,status,tags
            FROM blog_posts
            WHERE {$whereSql}
            ORDER BY published_at DESC, id DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll() ?: [],
        'total' => $total,
    ];
}

function blog_get_post_by_slug(string $slug): ?array
{
    if (!validate_slug_value($slug)) {
        return null;
    }

    $pdo = db();
    if (!$pdo || !blog_table_exists($pdo)) {
        foreach (blog_fallback_posts() as $post) {
            if ((string) $post['slug'] === $slug) {
                return $post;
            }
        }
        return null;
    }

    return db_fetch_one($pdo, 'SELECT id,title,slug,excerpt,content,featured_image,category,author_name,published_at,status,tags FROM blog_posts WHERE slug = :slug AND status = :st LIMIT 1', [
        ':slug' => $slug,
        ':st' => 'published',
    ]);
}

function blog_get_categories(): array
{
    $pdo = db();
    if (!$pdo || !blog_table_exists($pdo)) {
        $counts = [];
        foreach (blog_fallback_posts() as $post) {
            $cat = (string) ($post['category'] ?? 'General');
            $counts[$cat] = ($counts[$cat] ?? 0) + 1;
        }
        $out = [];
        foreach ($counts as $name => $count) {
            $out[] = ['name' => $name, 'count' => $count];
        }
        return $out;
    }

    return db_fetch_all($pdo, 'SELECT category AS name, COUNT(*) AS count FROM blog_posts WHERE status = :st GROUP BY category ORDER BY category ASC', [':st' => 'published']);
}

function blog_get_recent_posts(int $limit = 3, ?string $excludeSlug = null): array
{
    $limit = max(1, $limit);
    $pdo = db();
    if (!$pdo || !blog_table_exists($pdo)) {
        $items = blog_fallback_posts();
        if ($excludeSlug) {
            $items = array_values(array_filter($items, static fn(array $it): bool => (string) $it['slug'] !== $excludeSlug));
        }
        return array_slice($items, 0, $limit);
    }

    $sql = 'SELECT id,title,slug,featured_image,published_at FROM blog_posts WHERE status = :st';
    $params = [':st' => 'published'];
    if ($excludeSlug) {
        $sql .= ' AND slug <> :slug';
        $params[':slug'] = $excludeSlug;
    }
    $sql .= ' ORDER BY published_at DESC, id DESC LIMIT :lim';

    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function blog_link(string $slug): string
{
    return url('blog-details.php?slug=' . urlencode($slug));
}
