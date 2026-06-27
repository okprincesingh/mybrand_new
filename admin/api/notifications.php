<?php
require_once __DIR__ . '/../_init.php';
header('Content-Type: application/json');

$admin = admin_current();
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET') {
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 12)));
    $notifications = get_all_notifications($limit);
    $counts = get_notification_count();
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)($counts['unread'] ?? 0),
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = trim((string) ($input['action'] ?? ''));

    if ($action === 'mark_read') {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification id']);
            exit;
        }
        $ok = mark_notification_as_read($id);
        echo json_encode(['success' => (bool) $ok]);
        exit;
    }

    if ($action === 'mark_all_read') {
        $ok = mark_all_notifications_as_read();
        echo json_encode(['success' => (bool) $ok]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
