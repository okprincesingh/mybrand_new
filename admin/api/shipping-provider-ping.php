<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');

if (!function_exists('admin_current')) {
    require_once __DIR__ . '/../../includes/cms.php';
}

$admin = admin_current();
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf_or_fail();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$providerCode = strtolower(trim((string) ($input['provider_code'] ?? '')));

if ($providerCode === '') {
    echo json_encode(['success' => false, 'message' => 'Provider code is required']);
    exit;
}

$pdo = db();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

$stmt = $pdo->prepare('SELECT provider_code, provider_name, api_base_url, is_active FROM shipping_provider_configs WHERE provider_code = :provider_code LIMIT 1');
$stmt->execute([':provider_code' => $providerCode]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    echo json_encode(['success' => false, 'message' => 'Provider not found']);
    exit;
}

$baseUrl = trim((string) ($provider['api_base_url'] ?? ''));
if ($baseUrl === '') {
    echo json_encode(['success' => false, 'message' => 'API Base URL is empty']);
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'cURL extension is not enabled']);
    exit;
}

$testUrl = rtrim($baseUrl, '/') . '/';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$result = curl_exec($ch);
$error = curl_error($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Ping failed: ' . ($error ?: 'Unknown cURL error'),
        'provider' => $provider['provider_name'],
    ]);
    exit;
}

$ok = $status >= 200 && $status < 500;

echo json_encode([
    'success' => $ok,
    'message' => $ok ? ('Ping OK (HTTP ' . $status . ')') : ('Ping failed (HTTP ' . $status . ')'),
    'provider' => $provider['provider_name'],
    'http_status' => $status,
]);
