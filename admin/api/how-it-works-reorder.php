<?php
require_once __DIR__ . '/../_init.php';
header('Content-Type: application/json');

$admin = admin_current();
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf_or_fail();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$type = trim((string) ($input['type'] ?? ''));
$order = (array) ($input['order'] ?? []);

if (!in_array($type, ['sections', 'accordions'], true) || empty($order)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reorder parameters.']);
    exit;
}

$pdo = db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$table = ($type === 'sections') ? 'how_it_works_sections' : 'how_it_works_accordions';
$stmt = $pdo->prepare("UPDATE {$table} SET sort_order = :sort_order WHERE id = :id");

foreach ($order as $index => $id) {
    $itemId = (int) $id;
    if ($itemId > 0) {
        $stmt->execute([
            ':sort_order' => $index + 1,
            ':id' => $itemId,
        ]);
    }
}

cms_invalidate_how_it_works_cache();

echo json_encode(['success' => true, 'message' => 'Order updated successfully.']);
