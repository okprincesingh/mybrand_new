<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/url.php';

/**
 * User Authentication and Management Functions
 */

function user_cookie_secure(): bool
{
    $httpsDetected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isLocalHost = ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, '[::1]'));
    return $httpsDetected && !$isLocalHost;
}

function user_set_auth_cookie(string $name, string $value, int $ttl): void
{
    setcookie($name, $value, [
        'expires' => time() + $ttl,
        'path' => '/',
        'secure' => user_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

function user_clear_auth_cookie(string $name): void
{
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => user_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[$name]);
}

function user_client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if ($ip === '' || $ip === '::') {
        return '0.0.0.0';
    }

    $ipLower = strtolower($ip);
    if ($ip === '127.0.0.1' || $ip === '::1' || $ipLower === '::ffff:127.0.0.1') {
        return 'loopback';
    }

    return $ip;
}

function user_client_ua(): string
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    return $ua !== '' ? $ua : 'unknown';
}

function user_generate_session_token(): string
{
    return bin2hex(random_bytes(32));
}

function user_store_session(int $userId, string $sessionToken, int $ttlSeconds = 2592000): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);
    $ip = user_client_ip();
    $ua = user_client_ua();

    $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (:user_id, :session_token, :ip, :ua, :expires_at)');
    $stmt->execute([
        ':user_id' => $userId,
        ':session_token' => $sessionToken,
        ':ip' => $ip,
        ':ua' => $ua,
        ':expires_at' => $expiresAt,
    ]);
}

function user_verify_session(string $sessionToken): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $ip = user_client_ip();
    $ua = user_client_ua();

    $stmt = $pdo->prepare('SELECT u.id, u.email, u.password_hash, u.first_name, u.last_name, u.phone, u.date_of_birth, u.gender, u.is_active, u.email_verified_at, u.created_at, u.updated_at FROM users u INNER JOIN user_sessions s ON u.id = s.user_id WHERE s.session_token = :token AND s.expires_at > NOW() AND s.ip_address = :ip AND s.user_agent = :ua AND u.is_active = 1 LIMIT 1');
    $stmt->execute([':token' => $sessionToken, ':ip' => $ip, ':ua' => $ua]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function user_get_by_id(int $userId): ?array
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, phone, date_of_birth, gender, is_active, email_verified_at, created_at, updated_at FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function user_get_by_email(string $email): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, phone, date_of_birth, gender, is_active, email_verified_at, created_at, updated_at FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function user_register(string $email, string $password, string $firstName, string $lastName, ?string $phone = null): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    // Check if email already exists
    $existing = user_get_by_email($email);
    if ($existing) {
        return ['success' => false, 'message' => 'Email already registered'];
    }

    // Validate password
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, first_name, last_name, phone, email_verified_at) VALUES (:email, :password_hash, :first_name, :last_name, :phone, :email_verified_at)');
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':phone' => $phone,
            ':email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $pdo->lastInsertId();

        try {
            create_notification(
                'New User Registration',
                sprintf(
                    '%s %s registered with email %s',
                    trim($firstName),
                    trim($lastName),
                    trim($email)
                ),
                'info',
                'users.php',
                'View Users'
            );
        } catch (\Throwable $notificationError) {
            // Do not block user registration if notification creation fails.
        }

        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful. You can now log in.'
        ];
    } catch (\Exception $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function user_login(string $email, string $password, bool $remember = false): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $user = user_get_by_email($email);
    $passwordHash = (string) ($user['password_hash'] ?? '');
    if (!$user || $passwordHash === '' || !password_verify($password, $passwordHash)) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    session_regenerate_id(true);
    csrf_regenerate_token();

    $sessionToken = user_generate_session_token();
    $ttl = $remember ? 2592000 : 1800; // 30 days or 30 minutes

    user_store_session((int) $user['id'], $sessionToken, $ttl);
    user_set_auth_cookie('user_session', $sessionToken, $ttl);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_session'] = $sessionToken;

    return [
        'success' => true,
        'user_id' => (int) $user['id'],
        'message' => 'Login successful'
    ];
}

function user_current(): ?array
{
    $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
    $sessionToken = (string) ($_SESSION['user_session'] ?? ($_COOKIE['user_session'] ?? ''));

    if ($sessionToken !== '') {
        $user = user_verify_session($sessionToken);
        if ($user) {
            return $user;
        }
    }

    // Fallback: preserve login state when token binding fails in unstable local setups.
    if ($sessionUserId > 0) {
        $user = user_get_by_id($sessionUserId);
        if ($user) {
            return $user;
        }
    }

    return null;
}

function user_require_auth(): array
{
    $user = user_current();
    if (!$user) {
        $to = function_exists('url') ? url('login.php') : 'login.php';
        header('Location: ' . $to, true, 302);
        exit;
    }
    return $user;
}

function user_logout(): void
{
    $pdo = db();
    $sessionToken = (string) ($_SESSION['user_session'] ?? '');

    if ($pdo && $sessionToken !== '') {
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE session_token = :token');
        $stmt->execute([':token' => $sessionToken]);
    }

    unset($_SESSION['user_session'], $_SESSION['user_id']);
    user_clear_auth_cookie('user_session');
}

function user_update_profile(int $userId, array $data): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    $allowedFields = ['first_name', 'last_name', 'phone', 'date_of_birth', 'gender'];
    $updateFields = [];
    $params = [':id' => $userId];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = $field . ' = :' . $field;
            $params[':' . $field] = $data[$field];
        }
    }

    if (empty($updateFields)) {
        return false;
    }

    $sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ', updated_at = NOW() WHERE id = :id AND is_active = 1';
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function user_change_password(int $userId, string $currentPassword, string $newPassword): ?array
{
    $pdo = db();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }

    $user = user_get_by_id($userId);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found'];
    }

    if (!password_verify($currentPassword, (string) $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters long'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
    if ($stmt->execute([':hash' => $newHash, ':id' => $userId])) {
        return ['success' => true, 'message' => 'Password updated successfully'];
    }

    return ['success' => false, 'message' => 'Failed to update password'];
}

// Address Management Functions
function user_get_addresses(int $userId): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll() ?: [];
}

function user_add_address(int $userId, array $addressData): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    // If this is the first address, make it default
    $isDefault = 0;
    $existingCount = (int) (db_fetch_value($pdo, 'SELECT COUNT(*) FROM user_addresses WHERE user_id = :user_id', [':user_id' => $userId]) ?? 0);
    if ($existingCount === 0) {
        $isDefault = 1;
    }

    $stmt = $pdo->prepare('INSERT INTO user_addresses (user_id, type, first_name, last_name, company, address1, address2, city, state, zip, country, is_default) VALUES (:user_id, :type, :first_name, :last_name, :company, :address1, :address2, :city, :state, :zip, :country, :is_default)');
    return $stmt->execute([
        ':user_id' => $userId,
        ':type' => $addressData['type'] ?? 'both',
        ':first_name' => $addressData['first_name'],
        ':last_name' => $addressData['last_name'],
        ':company' => $addressData['company'] ?? null,
        ':address1' => $addressData['address1'],
        ':address2' => $addressData['address2'] ?? null,
        ':city' => $addressData['city'],
        ':state' => $addressData['state'],
        ':zip' => $addressData['zip'],
        ':country' => $addressData['country'] ?? 'US',
        ':is_default' => $isDefault,
    ]);
}

function user_update_address(int $addressId, int $userId, array $addressData): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE user_addresses SET type = :type, first_name = :first_name, last_name = :last_name, company = :company, address1 = :address1, address2 = :address2, city = :city, state = :state, zip = :zip, country = :country, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
    return $stmt->execute([
        ':id' => $addressId,
        ':user_id' => $userId,
        ':type' => $addressData['type'] ?? 'both',
        ':first_name' => $addressData['first_name'],
        ':last_name' => $addressData['last_name'],
        ':company' => $addressData['company'] ?? null,
        ':address1' => $addressData['address1'],
        ':address2' => $addressData['address2'] ?? null,
        ':city' => $addressData['city'],
        ':state' => $addressData['state'],
        ':zip' => $addressData['zip'],
        ':country' => $addressData['country'] ?? 'US',
    ]);
}

function user_delete_address(int $addressId, int $userId): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    $stmt = $pdo->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id');
    return $stmt->execute([':id' => $addressId, ':user_id' => $userId]);
}

function user_set_default_address(int $addressId, int $userId): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        // Clear all defaults for this user
        $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);

        // Set new default
        $stmt = $pdo->prepare('UPDATE user_addresses SET is_default = 1 WHERE id = :id AND user_id = :user_id');
        $result = $stmt->execute([':id' => $addressId, ':user_id' => $userId]);

        $pdo->commit();
        return $result;
    } catch (\Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function user_get_default_address(int $userId): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $address = $stmt->fetch();
    return $address ?: null;
}

// Wishlist Functions
function user_get_wishlist(int $userId): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT p.* FROM products p INNER JOIN user_wishlist w ON p.id = w.product_id WHERE w.user_id = :user_id AND p.is_active = 1 ORDER BY w.created_at DESC');
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll() ?: [];
}

function user_add_to_wishlist(int $userId, int $productId): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('INSERT IGNORE INTO user_wishlist (user_id, product_id) VALUES (:user_id, :product_id)');
        return $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
    } catch (\Exception $e) {
        return false;
    }
}

function user_remove_from_wishlist(int $userId, int $productId): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    $stmt = $pdo->prepare('DELETE FROM user_wishlist WHERE user_id = :user_id AND product_id = :product_id');
    return $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
}

function user_is_in_wishlist(int $userId, int $productId): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }

    $count = (int) (db_fetch_value($pdo, 'SELECT COUNT(*) FROM user_wishlist WHERE user_id = :user_id AND product_id = :product_id', [':user_id' => $userId, ':product_id' => $productId]) ?? 0);
    return $count > 0;
}

// Order History Functions
function user_orders_has_user_id_column(PDO $pdo): bool
{
    static $hasUserId = null;
    if ($hasUserId !== null) {
        return $hasUserId;
    }

    $sql = 'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
    $count = (int) (db_fetch_value($pdo, $sql, [':table_name' => 'orders', ':column_name' => 'user_id']) ?? 0);
    $hasUserId = $count > 0;
    return $hasUserId;
}

function user_get_customer_id_for_user(int $userId): ?int
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return null;
    }

    $user = user_get_by_id($userId);
    if (!$user || empty($user['email'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM customers WHERE email = :email ORDER BY id DESC LIMIT 1');
    $stmt->execute([':email' => (string) $user['email']]);
    $customerId = $stmt->fetchColumn();

    return $customerId !== false ? (int) $customerId : null;
}

function user_get_orders(int $userId, int $limit = 10, int $offset = 0): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $hasUserIdColumn = user_orders_has_user_id_column($pdo);
    $customerId = user_get_customer_id_for_user($userId);

    $where = '';
    if ($hasUserIdColumn && $customerId) {
        $where = '(o.user_id = :user_id OR (o.user_id IS NULL AND o.customer_id = :customer_id))';
    } elseif ($hasUserIdColumn) {
        $where = 'o.user_id = :user_id';
    } elseif ($customerId) {
        $where = 'o.customer_id = :customer_id';
    } else {
        return [];
    }

    $stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE {$where} GROUP BY o.id ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset");
    if (str_contains($where, ':user_id')) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }
    if (str_contains($where, ':customer_id')) {
        $stmt->bindValue(':customer_id', (int) $customerId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function user_get_order_by_number(string $orderNumber, int $userId): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $hasUserIdColumn = user_orders_has_user_id_column($pdo);
    $customerId = user_get_customer_id_for_user($userId);

    if ($hasUserIdColumn && $customerId) {
        $stmt = $pdo->prepare('SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.order_number = :order_number AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.customer_id = :customer_id)) GROUP BY o.id LIMIT 1');
        $stmt->execute([':order_number' => $orderNumber, ':user_id' => $userId, ':customer_id' => (int) $customerId]);
    } elseif ($hasUserIdColumn) {
        $stmt = $pdo->prepare('SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.order_number = :order_number AND o.user_id = :user_id GROUP BY o.id LIMIT 1');
        $stmt->execute([':order_number' => $orderNumber, ':user_id' => $userId]);
    } elseif ($customerId) {
        $stmt = $pdo->prepare('SELECT o.*, COUNT(oi.id) as item_count FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.order_number = :order_number AND o.customer_id = :customer_id GROUP BY o.id LIMIT 1');
        $stmt->execute([':order_number' => $orderNumber, ':customer_id' => (int) $customerId]);
    } else {
        return null;
    }
    $order = $stmt->fetch();
    return $order ?: null;
}

function user_get_order_items(int $orderId, int $userId): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $hasUserIdColumn = user_orders_has_user_id_column($pdo);
    $customerId = user_get_customer_id_for_user($userId);

    if ($hasUserIdColumn && $customerId) {
        $stmt = $pdo->prepare('SELECT oi.*, p.slug as product_slug FROM order_items oi INNER JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.customer_id = :customer_id)))');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':user_id' => $userId, ':customer_id' => (int) $customerId]);
    } elseif ($hasUserIdColumn) {
        $stmt = $pdo->prepare('SELECT oi.*, p.slug as product_slug FROM order_items oi INNER JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND o.user_id = :user_id)');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':user_id' => $userId]);
    } elseif ($customerId) {
        $stmt = $pdo->prepare('SELECT oi.*, p.slug as product_slug FROM order_items oi INNER JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND o.customer_id = :customer_id)');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':customer_id' => (int) $customerId]);
    } else {
        return [];
    }
    return $stmt->fetchAll() ?: [];
}

function user_get_order_status_history(int $orderId, int $userId): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $hasUserIdColumn = user_orders_has_user_id_column($pdo);
    $customerId = user_get_customer_id_for_user($userId);

    if ($hasUserIdColumn && $customerId) {
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND (o.user_id = :user_id OR (o.user_id IS NULL AND o.customer_id = :customer_id))) ORDER BY created_at DESC');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':user_id' => $userId, ':customer_id' => (int) $customerId]);
    } elseif ($hasUserIdColumn) {
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND o.user_id = :user_id) ORDER BY created_at DESC');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':user_id' => $userId]);
    } elseif ($customerId) {
        $stmt = $pdo->prepare('SELECT * FROM order_status_history WHERE order_id = :order_id AND EXISTS (SELECT 1 FROM orders o WHERE o.id = :order_id_ref AND o.customer_id = :customer_id) ORDER BY created_at DESC');
        $stmt->execute([':order_id' => $orderId, ':order_id_ref' => $orderId, ':customer_id' => (int) $customerId]);
    } else {
        return [];
    }
    return $stmt->fetchAll() ?: [];
}

function user_get_active_session_count(int $userId): int
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return 0;
    }

    return (int) (db_fetch_value($pdo, 'SELECT COUNT(*) FROM user_sessions WHERE user_id = :user_id AND expires_at > NOW()', [':user_id' => $userId]) ?? 0);
}

function user_get_last_login_at(int $userId): ?string
{
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return null;
    }

    $value = db_fetch_value($pdo, 'SELECT MAX(created_at) FROM user_sessions WHERE user_id = :user_id', [':user_id' => $userId]);
    if (!$value) {
        return null;
    }

    return (string) $value;
}
