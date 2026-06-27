<?php
/**
 * Wishlist API Endpoint
 * Handles wishlist operations: get, add, remove, toggle, clear, replace
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/user.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = isset($input['action']) ? trim((string) $input['action']) : '';

    switch ($action) {
        case 'add':
            echo json_encode(wishlist_api_add($input));
            break;
        case 'remove':
            echo json_encode(wishlist_api_remove($input));
            break;
        case 'toggle':
            echo json_encode(wishlist_api_toggle($input));
            break;
        case 'clear':
            echo json_encode(wishlist_api_clear());
            break;
        case 'replace':
            echo json_encode(wishlist_api_replace($input));
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(wishlist_api_get());
}

function wishlist_get_logged_user_id(): int
{
    $user = user_current();
    if (!$user || empty($user['id'])) {
        return 0;
    }
    return (int) $user['id'];
}

function wishlist_get_guest_slugs(): array
{
    if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }

    $slugs = [];
    foreach ($_SESSION['wishlist'] as $slug) {
        $s = trim((string) $slug);
        if ($s !== '') {
            $slugs[] = $s;
        }
    }

    return array_values(array_unique($slugs));
}

function wishlist_set_guest_slugs(array $slugs): void
{
    $clean = [];
    foreach ($slugs as $slug) {
        $s = trim((string) $slug);
        if ($s !== '') {
            $clean[] = $s;
        }
    }
    $_SESSION['wishlist'] = array_values(array_unique($clean));
}

function wishlist_normalize_item(array $item): array
{
    $price = isset($item['price']) ? (float) $item['price'] : 0;
    return [
        'slug' => trim((string) ($item['slug'] ?? '')),
        'title' => trim((string) ($item['title'] ?? 'Product')),
        'price' => $price,
        'image' => trim((string) ($item['image'] ?? '')),
        'link' => trim((string) ($item['link'] ?? '')),
        'quantity' => 1,
    ];
}

function wishlist_products_for_slugs(array $slugs): array
{
    $items = [];
    foreach ($slugs as $slug) {
        $product = catalog_find_product((string) $slug);
        if (!$product) {
            continue;
        }

        $items[] = [
            'slug' => (string) $product['slug'],
            'title' => (string) $product['name'],
            'price' => (float) ($product['price'] ?? 0),
            'image' => (string) ($product['image'] ?? ''),
            'link' => catalog_product_link((string) $product['slug']),
            'quantity' => 1,
        ];
    }

    return $items;
}

function wishlist_get_db_slugs(int $userId): array
{
    $rows = user_get_wishlist($userId);
    $slugs = [];
    foreach ($rows as $row) {
        $slug = trim((string) ($row['slug'] ?? ''));
        if ($slug !== '') {
            $slugs[] = $slug;
        }
    }
    return array_values(array_unique($slugs));
}

function wishlist_api_get(): array
{
    $userId = wishlist_get_logged_user_id();
    if ($userId > 0) {
        $items = wishlist_products_for_slugs(wishlist_get_db_slugs($userId));
    } else {
        $items = wishlist_products_for_slugs(wishlist_get_guest_slugs());
    }

    return [
        'success' => true,
        'message' => 'Wishlist retrieved',
        'data' => [
            'items' => $items,
            'count' => count($items),
        ],
    ];
}

function wishlist_api_add(array $data): array
{
    $slug = trim((string) ($data['slug'] ?? ''));
    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    $product = catalog_find_product($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }

    $userId = wishlist_get_logged_user_id();
    if ($userId > 0) {
        user_add_to_wishlist($userId, (int) $product['id']);
    } else {
        $slugs = wishlist_get_guest_slugs();
        $slugs[] = $slug;
        wishlist_set_guest_slugs($slugs);
    }

    return wishlist_api_get();
}

function wishlist_api_remove(array $data): array
{
    $slug = trim((string) ($data['slug'] ?? ''));
    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    $product = catalog_find_product($slug);
    $userId = wishlist_get_logged_user_id();

    if ($userId > 0) {
        if ($product) {
            user_remove_from_wishlist($userId, (int) $product['id']);
        }
    } else {
        $slugs = array_values(array_filter(wishlist_get_guest_slugs(), function ($s) use ($slug) {
            return $s !== $slug;
        }));
        wishlist_set_guest_slugs($slugs);
    }

    return wishlist_api_get();
}

function wishlist_api_toggle(array $data): array
{
    $slug = trim((string) ($data['slug'] ?? ''));
    if ($slug === '') {
        return ['success' => false, 'message' => 'Product slug is required'];
    }

    $product = catalog_find_product($slug);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }

    $userId = wishlist_get_logged_user_id();
    $isAdded = false;

    if ($userId > 0) {
        $inWishlist = user_is_in_wishlist($userId, (int) $product['id']);
        if ($inWishlist) {
            user_remove_from_wishlist($userId, (int) $product['id']);
            $isAdded = false;
        } else {
            user_add_to_wishlist($userId, (int) $product['id']);
            $isAdded = true;
        }
    } else {
        $slugs = wishlist_get_guest_slugs();
        if (in_array($slug, $slugs, true)) {
            $slugs = array_values(array_filter($slugs, function ($s) use ($slug) { return $s !== $slug; }));
            $isAdded = false;
        } else {
            $slugs[] = $slug;
            $isAdded = true;
        }
        wishlist_set_guest_slugs($slugs);
    }

    $result = wishlist_api_get();
    $result['data']['added'] = $isAdded;
    return $result;
}

function wishlist_api_clear(): array
{
    $userId = wishlist_get_logged_user_id();
    if ($userId > 0) {
        $pdo = db();
        if ($pdo) {
            $stmt = $pdo->prepare('DELETE FROM user_wishlist WHERE user_id = :uid');
            $stmt->execute([':uid' => $userId]);
        }
    } else {
        wishlist_set_guest_slugs([]);
    }

    return wishlist_api_get();
}

function wishlist_api_replace(array $data): array
{
    $items = $data['items'] ?? [];
    if (!is_array($items)) {
        $items = [];
    }

    $normalized = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $normalized[] = wishlist_normalize_item($item);
    }

    $slugs = [];
    foreach ($normalized as $item) {
        if ($item['slug'] !== '') {
            $slugs[] = $item['slug'];
        }
    }
    $slugs = array_values(array_unique($slugs));

    $userId = wishlist_get_logged_user_id();
    if ($userId > 0) {
        $pdo = db();
        if ($pdo) {
            $pdo->prepare('DELETE FROM user_wishlist WHERE user_id = :uid')->execute([':uid' => $userId]);
            foreach ($slugs as $slug) {
                $product = catalog_find_product($slug);
                if ($product) {
                    user_add_to_wishlist($userId, (int) $product['id']);
                }
            }
        }
    } else {
        wishlist_set_guest_slugs($slugs);
    }

    return wishlist_api_get();
}
?>
