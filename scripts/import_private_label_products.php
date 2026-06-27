<?php
require __DIR__ . '/../includes/db.php';

$manifestPath = __DIR__ . '/private_label_manifest.json';
if (!is_file($manifestPath)) {
    fwrite(STDERR, "Manifest not found: {$manifestPath}\n");
    exit(1);
}

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "DB connection failed.\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
    fwrite(STDERR, "Invalid manifest format.\n");
    exit(1);
}

function slugify_local(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}

function clean_category_name(string $name): string
{
    $name = trim($name);
    $name = str_replace(['_', '-'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    return $name;
}

function clean_product_name(string $name): string
{
    $name = trim($name);
    $name = str_replace(['_', '-'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    return $name;
}

function is_placeholder_product_name(string $name): bool
{
    $n = strtolower(trim($name));
    if ($n === '' || $n === 'your' || $n === 'logo' || $n === 'your logo' || $n === 'yourlogo') {
        return true;
    }
    return str_starts_with($n, 'your ')
        || str_starts_with($n, 'your logo')
        || str_contains($n, 'your logo');
}

function canonical_name(string $name): string
{
    $name = strtolower(trim($name));
    $name = str_replace(['&', ' and '], ' ', $name);
    $name = preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
    return $name;
}

function findOrCreateCategory(PDO $pdo, string $name, ?int $parentId): int
{
    $name = clean_category_name($name);
    $canonical = canonical_name($name);

    if ($parentId === null) {
        $rows = $pdo->query('SELECT id,name FROM categories WHERE parent_id IS NULL')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        $stmt = $pdo->prepare('SELECT id,name FROM categories WHERE parent_id = :pid');
        $stmt->execute([':pid' => $parentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    foreach ($rows as $row) {
        if (canonical_name((string) $row['name']) === $canonical) {
            return (int) $row['id'];
        }
    }

    $baseSlug = slugify_local($name);
    $slug = $baseSlug;
    $i = 2;
    while (true) {
        $check = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
        $check->execute([':slug' => $slug]);
        if (!$check->fetchColumn()) {
            break;
        }
        $slug = $baseSlug . '-' . $i;
        $i++;
    }

    if ($parentId === null) {
        $sort = (int) ($pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM categories WHERE parent_id IS NULL')->fetchColumn() ?: 0);
    } else {
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM categories WHERE parent_id = :pid');
        $stmt->execute([':pid' => $parentId]);
        $sort = (int) ($stmt->fetchColumn() ?: 0);
    }

    $stmt = $pdo->prepare('INSERT INTO categories (parent_id,name,slug,description,image_path,is_active,sort_order) VALUES (:pid,:name,:slug,:desc,:img,1,:sort)');
    $stmt->execute([
        ':pid' => $parentId,
        ':name' => $name,
        ':slug' => $slug,
        ':desc' => '',
        ':img' => null,
        ':sort' => $sort + 1,
    ]);

    return (int) $pdo->lastInsertId();
}

function uniqueProductSlug(PDO $pdo, string $preferred, ?int $existingId = null): string
{
    $base = slugify_local($preferred);
    $slug = $base;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM products WHERE slug = :slug LIMIT 1';
        $params = [':slug' => $slug];
        if ($existingId !== null) {
            $sql = 'SELECT id FROM products WHERE slug = :slug AND id != :id LIMIT 1';
            $params[':id'] = $existingId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $found = $stmt->fetchColumn();
        if (!$found) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

$selectBySource = $pdo->prepare('SELECT id FROM products WHERE short_description = :src LIMIT 1');
$selectBySlug = $pdo->prepare('SELECT id FROM products WHERE slug = :slug LIMIT 1');
$updateProduct = $pdo->prepare('UPDATE products SET category_id=:category_id,name=:name,slug=:slug,price=:price,stock=:stock,status=:status,featured_image=:featured_image,short_description=:short_description,description=:description,is_active=1 WHERE id=:id');
$insertProduct = $pdo->prepare('INSERT INTO products (category_id,name,slug,short_description,description,price,stock,status,featured_image,is_active) VALUES (:category_id,:name,:slug,:short_description,:description,:price,:stock,:status,:featured_image,1)');
$deleteImages = $pdo->prepare('DELETE FROM product_images WHERE product_id = :pid');
$insertImage = $pdo->prepare('INSERT INTO product_images (product_id,image_path,sort_order) VALUES (:pid,:img,:sort_order)');

$created = 0;
$updated = 0;

$pdo->beginTransaction();

try {
    foreach ($data['items'] as $item) {
        $topName = clean_category_name((string) ($item['top_category'] ?? 'General'));
        $subName = clean_category_name((string) ($item['sub_category'] ?? ''));

        $rawName = clean_product_name((string) ($item['name'] ?? ''));
        $fallbackName = clean_product_name((string) ($item['name_fallback'] ?? 'Product'));
        if ($fallbackName === '') {
            $fallbackName = 'Product';
        }

        $name = $rawName;
        if ($name === '' || is_placeholder_product_name($name)) {
            $name = $fallbackName;
        }

        $images = is_array($item['images'] ?? null) ? $item['images'] : [];
        if (!$images) {
            continue;
        }

        $sourceTag = '[PDF] ' . (string) ($item['pdf'] ?? '');

        $parentId = findOrCreateCategory($pdo, $topName, null);
        $categoryId = $parentId;
        if ($subName !== '') {
            $categoryId = findOrCreateCategory($pdo, $subName, $parentId);
        }

        $preferredSlug = (string) ($item['product_slug'] ?? slugify_local($name));

        $productId = null;
        $selectBySource->execute([':src' => $sourceTag]);
        $productId = $selectBySource->fetchColumn();

        if (!$productId) {
            $selectBySlug->execute([':slug' => $preferredSlug]);
            $productId = $selectBySlug->fetchColumn();
        }

        $featuredImage = (string) $images[0];
        $description = 'Imported from Private Label Products Line PDF catalog.';

        if ($productId) {
            $slug = uniqueProductSlug($pdo, $preferredSlug, (int) $productId);
            $updateProduct->execute([
                ':id' => (int) $productId,
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':price' => 6.99,
                ':stock' => 100,
                ':status' => 'published',
                ':featured_image' => $featuredImage,
                ':short_description' => $sourceTag,
                ':description' => $description,
            ]);
            $updated++;
        } else {
            $slug = uniqueProductSlug($pdo, $preferredSlug, null);
            $insertProduct->execute([
                ':category_id' => $categoryId,
                ':name' => $name,
                ':slug' => $slug,
                ':short_description' => $sourceTag,
                ':description' => $description,
                ':price' => 6.99,
                ':stock' => 100,
                ':status' => 'published',
                ':featured_image' => $featuredImage,
            ]);
            $productId = (int) $pdo->lastInsertId();
            $created++;
        }

        $deleteImages->execute([':pid' => (int) $productId]);

        $sort = 0;
        foreach ($images as $imgPath) {
            $imgPath = (string) $imgPath;
            if ($imgPath === '') {
                continue;
            }
            $insertImage->execute([
                ':pid' => (int) $productId,
                ':img' => $imgPath,
                ':sort_order' => $sort,
            ]);
            $sort++;
        }
    }

    $pdo->commit();
    echo "Import complete\n";
    echo "Created: {$created}\n";
    echo "Updated: {$updated}\n";
    echo "Skipped from manifest: " . count($data['skipped'] ?? []) . "\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}
?>
