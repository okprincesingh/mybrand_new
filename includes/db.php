<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    $httpsDetected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isLocalHost = ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, '[::1]'));
    $secure = $httpsDetected && !$isLocalHost;
    $savePath = (string) session_save_path();
    if ($savePath === '' || !is_dir($savePath) || !is_writable($savePath)) {
        $fallbackSessionDir = __DIR__ . '/../storage/sessions';
        if (!is_dir($fallbackSessionDir)) {
            @mkdir($fallbackSessionDir, 0755, true);
        }
        if (is_dir($fallbackSessionDir) && is_writable($fallbackSessionDir)) {
            session_save_path($fallbackSessionDir);
        }
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $secure ? '1' : '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mybrandplease');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change_this_super_secret_key_please');
define('JWT_ACCESS_TTL', 60 * 15);
define('JWT_REFRESH_TTL', 60 * 60 * 24 * 30);

function db(): ?PDO
{
    static $pdo = false;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    if ($pdo === null) {
        return null;
    }

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        $pdo = null;
        return null;
    }
}

// Notification functions
function ensure_admin_notifications_table(PDO $pdo): bool {
    try {
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS admin_notifications (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM(\'info\',\'success\',\'warning\',\'error\') NOT NULL DEFAULT \'info\',
                action_url VARCHAR(255) NULL,
                action_text VARCHAR(100) NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read_at TIMESTAMP NULL,
                INDEX idx_notifications_read (is_read),
                INDEX idx_notifications_type (type),
                INDEX idx_notifications_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function create_notification($title, $message, $type = 'info', $action_url = null, $action_text = null) {
    $pdo = db();
    if (!$pdo) return false;
    if (!ensure_admin_notifications_table($pdo)) return false;
    
    $stmt = $pdo->prepare('
        INSERT INTO admin_notifications (title, message, type, action_url, action_text)
        VALUES (:title, :message, :type, :action_url, :action_text)
    ');
    return $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':type' => $type,
        ':action_url' => $action_url,
        ':action_text' => $action_text
    ]);
}

function get_unread_notifications($limit = 10) {
    $pdo = db();
    if (!$pdo) return [];
    if (!ensure_admin_notifications_table($pdo)) return [];
    
    $stmt = $pdo->prepare('
        SELECT * FROM admin_notifications 
        WHERE is_read = 0 
        ORDER BY created_at DESC 
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_all_notifications($limit = 50) {
    $pdo = db();
    if (!$pdo) return [];
    if (!ensure_admin_notifications_table($pdo)) return [];
    
    $stmt = $pdo->prepare('
        SELECT * FROM admin_notifications 
        ORDER BY created_at DESC 
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function mark_notification_as_read($notification_id) {
    $pdo = db();
    if (!$pdo) return false;
    if (!ensure_admin_notifications_table($pdo)) return false;
    
    $stmt = $pdo->prepare('
        UPDATE admin_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE id = :id
    ');
    return $stmt->execute([':id' => $notification_id]);
}

function mark_all_notifications_as_read() {
    $pdo = db();
    if (!$pdo) return false;
    if (!ensure_admin_notifications_table($pdo)) return false;
    
    $stmt = $pdo->prepare('
        UPDATE admin_notifications 
        SET is_read = 1, read_at = NOW() 
        WHERE is_read = 0
    ');
    return $stmt->execute();
}

function delete_notification($notification_id) {
    $pdo = db();
    if (!$pdo) return false;
    if (!ensure_admin_notifications_table($pdo)) return false;
    
    $stmt = $pdo->prepare('DELETE FROM admin_notifications WHERE id = :id');
    return $stmt->execute([':id' => $notification_id]);
}

function get_notification_count() {
    $pdo = db();
    if (!$pdo) return ['total' => 0, 'unread' => 0];
    if (!ensure_admin_notifications_table($pdo)) return ['total' => 0, 'unread' => 0];
    
    $stmt = $pdo->query('SELECT COUNT(*) as total, SUM(is_read = 0) as unread FROM admin_notifications');
    return $stmt->fetch() ?: ['total' => 0, 'unread' => 0];
}
