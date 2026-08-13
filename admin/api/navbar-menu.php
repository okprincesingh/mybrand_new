<?php
require_once __DIR__ . '/../_init.php';
header('Content-Type: application/json; charset=UTF-8');

$admin = admin_current();
if (!$admin) {
    http_response_code(401);
    echo json_encode(response_error('Unauthorized', [], 401));
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function build_menu_tree(array $rows): array
{
    $tree = [];
    $itemsById = [];

    foreach ($rows as $row) {
        $id = (string) ($row['id'] ?? '');
        $parentId = $row['parent_id'] !== null ? (string) $row['parent_id'] : null;
        $itemsById[$id] = [
            'id' => $id,
            'parent_id' => $parentId,
            'title' => (string) $row['title'],
            'url' => (string) $row['url'],
            'sort_order' => (int) $row['sort_order'],
            'is_active' => (int) $row['is_active'],
            'children' => [],
        ];
    }

    foreach ($itemsById as $id => &$item) {
        if ($item['parent_id'] !== null && isset($itemsById[$item['parent_id']])) {
            $itemsById[$item['parent_id']]['children'][] = &$item;
        } else {
            $tree[] = &$item;
        }
    }
    unset($item);

    $sortChildren = function (array &$items) use (&$sortChildren): void {
        usort($items, static fn(array $a, array $b) => $a['sort_order'] <=> $b['sort_order'] ?: strcmp($a['id'], $b['id']));
        foreach ($items as &$item) {
            if (!empty($item['children'])) {
                $sortChildren($item['children']);
            }
        }
        unset($item);
    };
    $sortChildren($tree);

    return $tree;
}

function ensure_header_menu_exists(PDO $pdo): int
{
    $menuId = db_fetch_value($pdo, 'SELECT id FROM menus WHERE location_key = :location_key LIMIT 1', [':location_key' => 'header_main']);
    if ($menuId !== null) {
        return (int) $menuId;
    }

    $stmt = $pdo->prepare('INSERT INTO menus (name, location_key) VALUES (:name, :location_key)');
    $stmt->execute([
        ':name' => 'Header Main',
        ':location_key' => 'header_main',
    ]);
    return (int) $pdo->lastInsertId();
}

function normalize_sort_order(array &$items): void
{
    foreach ($items as $index => &$item) {
        $item['sort_order'] = $index;
        if (!empty($item['children']) && is_array($item['children'])) {
            normalize_sort_order($item['children']);
        }
    }
    unset($item);
}

function flatten_menu_items(array $items, ?string $parentId = null): array
{
    $flattened = [];
    foreach ($items as $item) {
        $flattened[] = [
            'id' => $item['id'] ?? '',
            'parent_id' => $parentId,
            'title' => trim((string) ($item['title'] ?? '')),
            'url' => trim((string) ($item['url'] ?? '')),
            'sort_order' => max(0, (int) ($item['sort_order'] ?? 0)),
            'is_active' => !empty($item['is_active']) ? 1 : 0,
        ];
        if (!empty($item['children']) && is_array($item['children'])) {
            $flattened = array_merge($flattened, flatten_menu_items($item['children'], $item['id']));
        }
    }
    return $flattened;
}

function resolve_parent_id(array $row, array $existingIds, array $insertedIds): ?int
{
    if (!isset($row['parent_id']) || $row['parent_id'] === null || $row['parent_id'] === '') {
        return null;
    }
    $parentId = (string) $row['parent_id'];
    if (is_numeric($parentId) && (int) $parentId > 0 && isset($existingIds[(int) $parentId])) {
        return (int) $parentId;
    }
    if (isset($insertedIds[$parentId])) {
        return (int) $insertedIds[$parentId];
    }
    return null;
}

if ($method === 'GET') {
    $rows = cms_get_menu_items('header_main', false);
    $menu = build_menu_tree($rows);
    echo json_encode(response_success(['items' => $menu]));
    exit;
}

if ($method === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = trim((string) ($payload['action'] ?? ''));

    if ($action !== 'save_menu') {
        echo json_encode(response_error('Invalid action', [], 400));
        exit;
    }

    $items = $payload['items'] ?? [];
    if (!is_array($items)) {
        echo json_encode(response_error('Invalid menu payload', [], 400));
        exit;
    }

    normalize_sort_order($items);
    $flattened = flatten_menu_items($items, null);
    if (empty($flattened)) {
        echo json_encode(response_error('No menu items provided', [], 400));
        exit;
    }

    $pdo = db();
    if (!$pdo) {
        echo json_encode(response_error('Database unavailable', [], 500));
        exit;
    }

    $menuId = ensure_header_menu_exists($pdo);
    $existingRows = db_fetch_all($pdo, 'SELECT id, url FROM menu_items WHERE menu_id = :menu_id', [':menu_id' => $menuId]);
    $existingIds = [];
    foreach ($existingRows as $row) {
        $existingIds[(int) $row['id']] = trim((string) $row['url']);
    }

    $insertedIds = [];
    $updateStmt = $pdo->prepare('UPDATE menu_items SET title = :title, parent_id = :parent_id, sort_order = :sort_order, is_active = :is_active, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND menu_id = :menu_id');
    $insertStmt = $pdo->prepare('INSERT INTO menu_items (menu_id, parent_id, title, url, sort_order, is_active) VALUES (:menu_id, :parent_id, :title, :url, :sort_order, :is_active)');

    try {
        $pdo->beginTransaction();
        $keptIds = [];
        foreach ($flattened as $row) {
            $rowId = (string) ($row['id'] ?? '');
            $title = trim((string) $row['title']);
            if ($title === '') {
                throw new RuntimeException('Each navbar item must have a label.');
            }

            $resolvedParentId = resolve_parent_id($row, $existingIds, $insertedIds);
            $parentIdValue = $resolvedParentId !== null ? $resolvedParentId : null;
            if (is_numeric($rowId) && (int) $rowId > 0 && isset($existingIds[(int) $rowId])) {
                $updateStmt->execute([
                    ':title' => $title,
                    ':parent_id' => $parentIdValue,
                    ':sort_order' => (int) $row['sort_order'],
                    ':is_active' => (int) $row['is_active'],
                    ':id' => (int) $rowId,
                    ':menu_id' => $menuId,
                ]);
                $keptIds[] = (int) $rowId;
                continue;
            }

            $url = trim((string) $row['url']);
            if ($url === '') {
                throw new RuntimeException('New navbar items require a URL.');
            }

            $insertStmt->execute([
                ':menu_id' => $menuId,
                ':parent_id' => $parentIdValue,
                ':title' => $title,
                ':url' => $url,
                ':sort_order' => (int) $row['sort_order'],
                ':is_active' => (int) $row['is_active'],
            ]);
            $insertedIds[$rowId] = (int) $pdo->lastInsertId();
            $keptIds[] = $insertedIds[$rowId];
        }

        if (!empty($keptIds)) {
            $placeholders = implode(',', array_fill(0, count($keptIds), '?'));
            $deleteStmt = $pdo->prepare("DELETE FROM menu_items WHERE menu_id = ? AND id NOT IN ($placeholders)");
            $deleteStmt->execute(array_merge([$menuId], $keptIds));
        } else {
            $deleteStmt = $pdo->prepare('DELETE FROM menu_items WHERE menu_id = ?');
            $deleteStmt->execute([$menuId]);
        }

        cms_invalidate_menu_cache('header_main');
        $pdo->commit();
        echo json_encode(response_success(null, 'Navbar menu saved successfully'));
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(response_error('Failed to save navbar menu: ' . $e->getMessage(), [], 500));
        exit;
    }
}

http_response_code(405);
echo json_encode(response_error('Method not allowed', [], 405));
exit;
