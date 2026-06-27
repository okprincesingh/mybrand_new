<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/security.php';

function catalog_fallback_categories(): array
{
    return [
        ['id' => 0, 'slug' => 'skin-care', 'name' => 'Skin Care', 'description' => 'Hydration, treatment and glow essentials', 'image' => 'assets/imgs/product/skin-care.webp', 'subcategories' => []],
        ['id' => 0, 'slug' => 'body-care', 'name' => 'Body Care', 'description' => 'Nourishing formulas for daily body wellness', 'image' => 'assets/imgs/product/body-care.webp', 'subcategories' => []],
        ['id' => 0, 'slug' => 'hair-care', 'name' => 'Hair Care', 'description' => 'Targeted care for strong and healthy hair', 'image' => 'assets/imgs/product/hair-care.webp', 'subcategories' => []],
    ];
}

function catalog_fallback_products(): array
{
    return [
        ['slug' => 'glycolic-acid-serum', 'name' => '10% Glycolic Acid + 2% Niacinamide Face Serum', 'category' => 'skin-care', 'subcategory' => '', 'price' => 12, 'rating' => 5.0, 'reviews' => 135, 'badge' => 'New', 'image' => 'assets/imgs/products/10_-Glycolic-Acid-1-1.jpg', 'description' => 'Refining overnight serum.'],
        ['slug' => 'avocado-conditioner', 'name' => 'Avocado Volumising Hair Conditioner', 'category' => 'hair-care', 'subcategory' => '', 'price' => 16, 'rating' => 4.9, 'reviews' => 92, 'badge' => '', 'image' => 'assets/imgs/products/Avocado-Volumising-Hair-Conditioner-1.jpg', 'description' => 'Lightweight conditioner.'],
    ];
}

function catalog_normalize_identity(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    $value = strtolower(trim($value));
    $value = str_replace(
        ["\u{2019}", "\u{2018}", "'", '&'],
        ['', '', '', ' and '],
        $value
    );
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function catalog_expand_aliases(array $aliases): array
{
    $map = [
        'especially-for-men' => ['men-s-care'],
        'men-s-care' => ['especially-for-men'],
        'aerosols' => ['aerosols-parfumes', 'aerosols-perfumes', 'fragrances', 'perfumes'],
        'aerosols-parfumes' => ['aerosols', 'aerosols-perfumes', 'fragrances', 'perfumes'],
        'aerosols-perfumes' => ['aerosols', 'aerosols-parfumes', 'fragrances', 'perfumes'],
        'fragrances' => ['aerosols', 'aerosols-parfumes', 'aerosols-perfumes', 'perfumes'],
        'perfumes' => ['aerosols', 'aerosols-parfumes', 'aerosols-perfumes', 'fragrances'],
        'vitamin-c' => ['skin-care-vitamin-c', 'beauty-products-vitamin-c'],
        'skin-care-vitamin-c' => ['vitamin-c'],
        'shampoo-conditioner-bars' => ['shampoo-and-conditioner-bars', 'hair-care-bars', 'bars'],
        'shampoo-and-conditioner-bars' => ['shampoo-conditioner-bars', 'hair-care-bars', 'bars'],
        'bath-body-scrub' => ['bath-and-body-scrub', 'body-care-body-scrubs', 'body-scrubs'],
        'bath-and-body-scrub' => ['bath-body-scrub', 'body-care-body-scrubs', 'body-scrubs'],
        'salt-soaks' => ['salts-soaks', 'salts-and-soaks', 'body-care-salts-soaks'],
        'salts-soaks' => ['salt-soaks', 'salts-and-soaks', 'body-care-salts-soaks'],
        'salts-and-soaks' => ['salt-soaks', 'salts-soaks', 'body-care-salts-soaks'],
    ];

    $expanded = [];
    foreach ($aliases as $alias) {
        $alias = trim((string) $alias);
        if ($alias === '') {
            continue;
        }
        $expanded[] = $alias;
        foreach ($map[$alias] ?? [] as $mappedAlias) {
            $expanded[] = $mappedAlias;
        }
    }

    return array_values(array_unique($expanded));
}

function catalog_item_matches_aliases(array $item, array $aliases): bool
{
    $aliases = catalog_expand_aliases($aliases);
    if (!$aliases) {
        return false;
    }

    $itemKeys = array_unique(array_filter([
        trim((string) ($item['slug'] ?? '')),
        trim((string) ($item['name'] ?? '')),
        catalog_normalize_identity((string) ($item['slug'] ?? '')),
        catalog_normalize_identity((string) ($item['name'] ?? '')),
    ]));

    foreach ($aliases as $alias) {
        $rawAlias = trim((string) $alias);
        if ($rawAlias === '') {
            continue;
        }
        $normalizedAlias = catalog_normalize_identity($rawAlias);
        if (in_array($rawAlias, $itemKeys, true) || ($normalizedAlias !== '' && in_array($normalizedAlias, $itemKeys, true))) {
            return true;
        }
    }

    return false;
}

function catalog_find_category_by_aliases(array $aliases): ?array
{
    foreach (catalog_categories() as $category) {
        if (catalog_item_matches_aliases($category, $aliases)) {
            return $category;
        }
    }

    return null;
}

function catalog_find_subcategory_by_aliases(array $category, array $aliases): ?array
{
    foreach ((array) ($category['subcategories'] ?? []) as $subcategory) {
        if (catalog_item_matches_aliases((array) $subcategory, $aliases)) {
            return (array) $subcategory;
        }
    }

    return null;
}

function catalog_categories(): array
{
    $pdo = db();
    if (!$pdo) {
        return catalog_fallback_categories();
    }

    $rows = db_fetch_all($pdo, 'SELECT id,parent_id,name,slug,description,image_path,sort_order FROM categories WHERE is_active = 1 ORDER BY parent_id ASC, sort_order ASC, name ASC');
    if (!$rows) {
        return catalog_fallback_categories();
    }

    $top = [];
    $subs = [];
    foreach ($rows as $r) {
        if (empty($r['parent_id'])) {
            $top[(int) $r['id']] = [
                'id' => (int) $r['id'],
                'slug' => (string) $r['slug'],
                'name' => (string) $r['name'],
                'description' => (string) ($r['description'] ?? ''),
                'image' => (string) ($r['image_path'] ?: 'assets/imgs/product/skin-care.webp'),
                'subcategories' => [],
            ];
        } else {
            $subs[] = $r;
        }
    }

    foreach ($subs as $s) {
        $pid = (int) $s['parent_id'];
        if (isset($top[$pid])) {
            $top[$pid]['subcategories'][] = [
                'id' => (int) $s['id'],
                'slug' => (string) $s['slug'],
                'name' => (string) $s['name'],
                'description' => (string) ($s['description'] ?? ''),
                'image' => (string) ($s['image_path'] ?: $top[$pid]['image']),
            ];
        }
    }

    return array_values($top);
}

function get_products(array $filters = [], array $pagination = []): array
{
    $pdo = db();
    if (!$pdo) {
        $items = catalog_fallback_products();
        $base = response_success(['items' => $items, 'total' => count($items)], 'Fallback catalog');
        return [
            'success' => $base['success'],
            'status' => $base['status'],
            'message' => $base['message'],
            'items' => $items,
            'total' => count($items),
        ];
    }

    $where = ['p.is_active = 1'];
    $params = [];

    if (!empty($filters['category'])) {
        $where[] = '(c.slug = :category_self OR pc.slug = :category_parent)';
        $params[':category_self'] = $filters['category'];
        $params[':category_parent'] = $filters['category'];
    }
    if (!empty($filters['subcategory'])) {
        $where[] = 'c.slug = :subcategory';
        $params[':subcategory'] = $filters['subcategory'];
    }
    if (!empty($filters['search'])) {
        $where[] = '(p.name LIKE :search_name OR p.short_description LIKE :search_short_description OR p.description LIKE :search_description)';
        $searchTerm = '%' . $filters['search'] . '%';
        $params[':search_name'] = $searchTerm;
        $params[':search_short_description'] = $searchTerm;
        $params[':search_description'] = $searchTerm;
    }
    if (!empty($filters['status'])) {
        $where[] = 'p.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['min_price'])) {
        $where[] = 'p.price >= :min_price';
        $params[':min_price'] = (float) $filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[] = 'p.price <= :max_price';
        $params[':max_price'] = (float) $filters['max_price'];
    }

    $sortBy = $filters['sort_by'] ?? 'p.created_at';
    $allowedSort = ['p.created_at', 'p.price', 'p.name'];
    if (!in_array($sortBy, $allowedSort, true)) {
        $sortBy = 'p.created_at';
    }
    $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');
    if (!in_array($sortDir, ['ASC', 'DESC'], true)) {
        $sortDir = 'DESC';
    }

    $limit = max(1, (int) ($pagination['limit'] ?? 9));
    $offset = max(0, (int) ($pagination['offset'] ?? 0));

    $whereSql = implode(' AND ', $where);

    $total = (int) (db_fetch_value(
        $pdo,
        "SELECT COUNT(*)
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         LEFT JOIN categories pc ON pc.id = c.parent_id
         WHERE {$whereSql}",
        $params
    ) ?? 0);

    $sql = "SELECT
              p.*,
              c.slug AS category_slug,
              c.name AS category_name,
              pc.slug AS parent_category_slug,
              pc.name AS parent_category_name,
              pi.first_image,
              COALESCE(pr.avg_rating, 4.5) AS avg_rating,
              COALESCE(pr.review_count, 0) AS review_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories pc ON pc.id = c.parent_id
            LEFT JOIN (
              SELECT product_id, SUBSTRING_INDEX(GROUP_CONCAT(image_path ORDER BY sort_order ASC, id ASC SEPARATOR ','), ',', 1) AS first_image
              FROM product_images
              GROUP BY product_id
            ) pi ON pi.product_id = p.id
            LEFT JOIN (
              SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
              FROM product_reviews
              WHERE status = 'approved'
              GROUP BY product_id
            ) pr ON pr.product_id = p.id
            WHERE {$whereSql}
            ORDER BY {$sortBy} {$sortDir}
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll() ?: [];

    $items = array_map(static function (array $r): array {
        $image = $r['featured_image'] ?: $r['first_image'] ?: 'assets/imgs/product/skin-care.webp';
        $categorySlug = (string) ($r['parent_category_slug'] ?: $r['category_slug'] ?: '');
        $subCategorySlug = (string) ($r['parent_category_slug'] ? $r['category_slug'] : '');

        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'name' => (string) $r['name'],
            'category' => $categorySlug,
            'subcategory' => $subCategorySlug,
            'price' => (float) $r['price'],
            'rating' => (float) ($r['avg_rating'] ?: 4.5),
            'reviews' => (int) ($r['review_count'] ?: 0),
            'badge' => $r['status'] === 'published' ? '' : 'Draft',
            'image' => (string) $image,
            'description' => (string) ($r['short_description'] ?: $r['description'] ?: ''),
        ];
    }, $rows);

    $base = response_success(['items' => $items, 'total' => $total], 'Catalog fetched');
    return [
        'success' => $base['success'],
        'status' => $base['status'],
        'message' => $base['message'],
        'items' => $items,
        'total' => $total,
    ];
}

function catalog_products(): array
{
    return get_products([], ['limit' => 1000, 'offset' => 0])['items'];
}

function catalog_related_products(string $currentSlug, ?string $category = null, ?string $subcategory = null, int $limit = 4): array
{
    $limit = max(1, min(24, $limit));
    $currentSlug = trim($currentSlug);
    $category = $category !== null ? trim($category) : null;
    $subcategory = $subcategory !== null ? trim($subcategory) : null;

    $pdo = db();
    if (!$pdo) {
        $fallback = array_values(array_filter(catalog_fallback_products(), static function (array $item) use ($currentSlug): bool {
            return (string) ($item['slug'] ?? '') !== $currentSlug;
        }));
        return array_slice($fallback, 0, $limit);
    }

    $where = ['p.is_active = 1', 'p.status = :status', 'p.slug <> :current_slug'];
    $params = [
        ':status' => 'published',
        ':current_slug' => $currentSlug,
    ];

    if ($subcategory !== null && $subcategory !== '') {
        $where[] = 'c.slug = :subcategory_slug';
        $params[':subcategory_slug'] = $subcategory;
    } elseif ($category !== null && $category !== '') {
        $where[] = '(pc.slug = :category_slug OR c.slug = :category_slug_self)';
        $params[':category_slug'] = $category;
        $params[':category_slug_self'] = $category;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "SELECT
              p.id,
              p.slug,
              p.name,
              p.price,
              p.short_description,
              p.description,
              p.featured_image,
              c.slug AS category_slug,
              c.name AS category_name,
              pc.slug AS parent_category_slug,
              pc.name AS parent_category_name,
              pi.first_image,
              COALESCE(pr.avg_rating, 4.5) AS avg_rating,
              COALESCE(pr.review_count, 0) AS review_count
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories pc ON pc.id = c.parent_id
            LEFT JOIN (
              SELECT product_id, SUBSTRING_INDEX(GROUP_CONCAT(image_path ORDER BY sort_order ASC, id ASC SEPARATOR ','), ',', 1) AS first_image
              FROM product_images
              GROUP BY product_id
            ) pi ON pi.product_id = p.id
            LEFT JOIN (
              SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
              FROM product_reviews
              WHERE status = 'approved'
              GROUP BY product_id
            ) pr ON pr.product_id = p.id
            WHERE {$whereSql}
            ORDER BY p.updated_at DESC, p.id DESC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll() ?: [];

    return array_map(static function (array $r): array {
        $image = (string) ($r['featured_image'] ?: $r['first_image'] ?: 'assets/imgs/product/skin-care.webp');
        $categorySlug = (string) ($r['parent_category_slug'] ?: $r['category_slug'] ?: '');
        $subCategorySlug = (string) ($r['parent_category_slug'] ? $r['category_slug'] : '');

        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'name' => (string) $r['name'],
            'category' => $categorySlug,
            'subcategory' => $subCategorySlug,
            'price' => (float) $r['price'],
            'rating' => (float) ($r['avg_rating'] ?: 4.5),
            'reviews' => (int) ($r['review_count'] ?: 0),
            'badge' => '',
            'image' => $image,
            'description' => (string) ($r['short_description'] ?: $r['description'] ?: ''),
        ];
    }, $rows);
}

function catalog_filtered_products(?string $category = null, ?string $subcategory = null, ?string $search = null): array
{
    $result = get_products([
        'category' => $category,
        'subcategory' => $subcategory,
        'search' => $search,
        'status' => 'published',
    ], ['limit' => 1000, 'offset' => 0]);

    return $result['items'];
}

function get_product_by_slug(string $slug): ?array
{
    $pdo = db();
    if (!$pdo) {
        foreach (catalog_fallback_products() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    $r = db_fetch_one($pdo, "SELECT
          p.*,
          c.slug AS category_slug,
          c.name AS category_name,
          pc.slug AS parent_category_slug,
          pc.name AS parent_category_name,
          COALESCE(pr.avg_rating, 4.8) AS avg_rating,
          COALESCE(pr.review_count, 0) AS review_count,
          imgs.images_csv
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN categories pc ON pc.id = c.parent_id
        LEFT JOIN (
          SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
          FROM product_reviews
          WHERE status = 'approved'
          GROUP BY product_id
        ) pr ON pr.product_id = p.id
        LEFT JOIN (
          SELECT product_id, GROUP_CONCAT(image_path ORDER BY sort_order ASC, id ASC SEPARATOR ',') AS images_csv
          FROM product_images
          GROUP BY product_id
        ) imgs ON imgs.product_id = p.id
        WHERE p.slug = :slug
        LIMIT 1", [':slug' => $slug]);
    if (!$r) {
        return null;
    }

    $images = [];
    if (!empty($r['images_csv'])) {
        $images = array_values(array_filter(array_map('trim', explode(',', (string) $r['images_csv']))));
    }

    $attributes = db_fetch_all($pdo, 'SELECT attribute_key, attribute_value FROM product_attributes WHERE product_id = :pid ORDER BY id ASC', [
        ':pid' => (int) $r['id'],
    ]);

    $categorySlug = (string) ($r['parent_category_slug'] ?: $r['category_slug'] ?: '');
    $subCategorySlug = (string) ($r['parent_category_slug'] ? $r['category_slug'] : '');

    return [
        'id' => (int) $r['id'],
        'slug' => (string) $r['slug'],
        'name' => (string) $r['name'],
        'category' => $categorySlug,
        'subcategory' => $subCategorySlug,
        'price' => (float) $r['price'],
        'rating' => (float) ($r['avg_rating'] ?: 4.8),
        'reviews' => (int) ($r['review_count'] ?: 0),
        'badge' => '',
        'image' => (string) ($r['featured_image'] ?: ($images[0] ?? 'assets/imgs/product/skin-care.webp')),
        'gallery' => array_values(array_map('strval', $images)),
        'short_description' => (string) ($r['short_description'] ?: ''),
        'description' => (string) ($r['description'] ?: $r['short_description'] ?: ''),
        'attributes' => array_map(static function (array $row): array {
            return [
                'key' => (string) ($row['attribute_key'] ?? ''),
                'value' => (string) ($row['attribute_value'] ?? ''),
            ];
        }, $attributes),
    ];
}

function catalog_find_category(string $slug): ?array
{
    foreach (catalog_categories() as $category) {
        if ($category['slug'] === $slug) {
            return $category;
        }
    }

    return catalog_find_category_by_aliases([$slug]);
}

function catalog_find_product(string $slug): ?array
{
    return get_product_by_slug($slug);
}

function catalog_product_link(string $slug): string
{
    return url('product-details.php?slug=' . urlencode($slug));
}

function catalog_shop_link(?string $category = null, ?string $subcategory = null): string
{
    $query = [];
    if ($category) {
        $query['category'] = $category;
    }
    if ($subcategory) {
        $query['subcategory'] = $subcategory;
    }

    return url('shop.php') . ($query ? ('?' . http_build_query($query)) : '');
}

function catalog_subcategory_page_link(string $categorySlug, string $subcategoryNameOrSlug): string
{
    $categorySlug = trim($categorySlug);
    $subcategoryNameOrSlug = trim($subcategoryNameOrSlug);
    if ($categorySlug === '') {
        return url('shop.php');
    }
    if ($subcategoryNameOrSlug === '') {
        return catalog_shop_link($categorySlug);
    }

    return url(rawurlencode($categorySlug) . '/' . rawurlencode($subcategoryNameOrSlug) . '/');
}


