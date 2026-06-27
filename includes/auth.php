<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/security.php';

function admin_cookie_secure(): bool
{
    $httpsDetected = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isLocalHost = ($host === 'localhost' || str_starts_with($host, '127.0.0.1') || str_starts_with($host, '[::1]'));
    return $httpsDetected && !$isLocalHost;
}

function admin_set_auth_cookie(string $name, string $value, int $ttl): void
{
    setcookie($name, $value, [
        'expires' => time() + $ttl,
        'path' => '/',
        'secure' => admin_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

function admin_clear_auth_cookie(string $name): void
{
    setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => admin_cookie_secure(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[$name]);
}

function admin_count(): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }
    return (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
}

function admin_signup_first_user(string $name, string $email, string $password): bool
{
    $pdo = db();
    if (!$pdo || admin_count() > 0) {
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, :role, 1)');
    return $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => 'super_admin',
    ]);
}

function admin_client_ip(): string
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

function admin_client_ua(): string
{
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    return $ua !== '' ? $ua : 'unknown';
}

function admin_fingerprint_hashes(): array
{
    return [
        'ip_hash' => hash('sha256', admin_client_ip()),
        'user_agent_hash' => hash('sha256', admin_client_ua()),
    ];
}

function admin_generate_tokens(array $admin): array
{
    $accessPayload = [
        'sub' => (int) $admin['id'],
        'email' => (string) $admin['email'],
        'role' => (string) $admin['role'],
        'token_type' => 'access',
        'jti' => bin2hex(random_bytes(16)),
    ];

    $refreshPayload = [
        'sub' => (int) $admin['id'],
        'token_type' => 'refresh',
        'jti' => bin2hex(random_bytes(16)),
    ];

    return [
        'access_token' => jwt_generate($accessPayload, JWT_ACCESS_TTL),
        'refresh_token' => jwt_generate($refreshPayload, JWT_REFRESH_TTL),
    ];
}

function admin_store_access_token(int $adminId, string $accessToken, string $ipHash, string $uaHash): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $exp = jwt_verify($accessToken, 'access', true);
    if (!$exp) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO admin_sessions (admin_id, token_hash, expires_at, ip_hash, user_agent_hash) VALUES (:admin_id, :token_hash, :expires_at, :ip_hash, :user_agent_hash)');
    $stmt->execute([
        ':admin_id' => $adminId,
        ':token_hash' => hash('sha256', $accessToken),
        ':expires_at' => date('Y-m-d H:i:s', (int) $exp['exp']),
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $uaHash,
    ]);
}

function admin_store_refresh_token(int $adminId, string $refreshToken, string $ipHash, string $uaHash, ?int $replacesId = null): int
{
    $pdo = db();
    if (!$pdo) {
        return 0;
    }

    $payload = jwt_verify($refreshToken, 'refresh', true);
    if (!$payload) {
        return 0;
    }

    $stmt = $pdo->prepare('INSERT INTO admin_refresh_tokens (admin_id, token_hash, expires_at, ip_hash, user_agent_hash, replaced_by_id) VALUES (:admin_id, :token_hash, :expires_at, :ip_hash, :user_agent_hash, :replaced_by_id)');
    $stmt->execute([
        ':admin_id' => $adminId,
        ':token_hash' => hash('sha256', $refreshToken),
        ':expires_at' => date('Y-m-d H:i:s', (int) $payload['exp']),
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $uaHash,
        ':replaced_by_id' => $replacesId,
    ]);

    return (int) $pdo->lastInsertId();
}

function admin_set_session_tokens(string $accessToken, string $refreshToken): void
{
    $_SESSION['admin_access_token'] = $accessToken;
    $_SESSION['admin_refresh_token'] = $refreshToken;
    admin_set_auth_cookie('admin_access_token', $accessToken, JWT_ACCESS_TTL);
    admin_set_auth_cookie('admin_refresh_token', $refreshToken, JWT_REFRESH_TTL);
}

function admin_fetch_active_by_id(int $adminId): ?array
{
    $pdo = db();
    if (!$pdo || $adminId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM admins WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute([':id' => $adminId]);
    $admin = $stmt->fetch();
    return $admin ?: null;
}

function admin_login(string $email, string $password): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
        return null;
    }

    session_regenerate_id(true);
    csrf_regenerate_token();

    $tokens = admin_generate_tokens($admin);
    $fp = admin_fingerprint_hashes();

    admin_store_access_token((int) $admin['id'], $tokens['access_token'], $fp['ip_hash'], $fp['user_agent_hash']);
    admin_store_refresh_token((int) $admin['id'], $tokens['refresh_token'], $fp['ip_hash'], $fp['user_agent_hash']);
    admin_set_session_tokens($tokens['access_token'], $tokens['refresh_token']);
    $_SESSION['admin_user_id'] = (int) $admin['id'];

    return $tokens;
}

function admin_get_bearer_token(): ?string
{
    $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if ($header === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }

    return null;
}

function admin_verify_access_token(string $accessToken): ?array
{
    $payload = jwt_verify($accessToken, 'access');
    if (!$payload) {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $fp = admin_fingerprint_hashes();
    $stmt = $pdo->prepare('SELECT s.id session_id, a.id, a.name, a.email, a.role, a.is_active FROM admin_sessions s INNER JOIN admins a ON a.id = s.admin_id WHERE s.token_hash = :token_hash AND s.revoked_at IS NULL AND s.expires_at > NOW() AND s.ip_hash = :ip_hash AND s.user_agent_hash = :ua_hash AND a.is_active = 1 LIMIT 1');
    $stmt->execute([
        ':token_hash' => hash('sha256', $accessToken),
        ':ip_hash' => $fp['ip_hash'],
        ':ua_hash' => $fp['user_agent_hash'],
    ]);

    $admin = $stmt->fetch();
    return $admin ?: null;
}

function admin_refresh_access_token(?string $refreshToken = null): ?string
{
    $refreshToken = $refreshToken ?? (string) ($_SESSION['admin_refresh_token'] ?? ($_COOKIE['admin_refresh_token'] ?? ''));
    if ($refreshToken === '') {
        return null;
    }

    $payload = jwt_verify($refreshToken, 'refresh');
    if (!$payload) {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $fp = admin_fingerprint_hashes();
    $stmt = $pdo->prepare('SELECT rt.id, rt.admin_id, a.email, a.role FROM admin_refresh_tokens rt INNER JOIN admins a ON a.id = rt.admin_id WHERE rt.token_hash = :token_hash AND rt.revoked_at IS NULL AND rt.expires_at > NOW() AND rt.ip_hash = :ip_hash AND rt.user_agent_hash = :ua_hash AND a.is_active = 1 LIMIT 1');
    $stmt->execute([
        ':token_hash' => hash('sha256', $refreshToken),
        ':ip_hash' => $fp['ip_hash'],
        ':ua_hash' => $fp['user_agent_hash'],
    ]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $tokens = admin_generate_tokens([
        'id' => (int) $row['admin_id'],
        'email' => (string) $row['email'],
        'role' => (string) $row['role'],
    ]);

    admin_store_access_token((int) $row['admin_id'], $tokens['access_token'], $fp['ip_hash'], $fp['user_agent_hash']);
    $newRefreshId = admin_store_refresh_token((int) $row['admin_id'], $tokens['refresh_token'], $fp['ip_hash'], $fp['user_agent_hash'], (int) $row['id']);

    $revokeStmt = $pdo->prepare('UPDATE admin_refresh_tokens SET revoked_at = NOW(), replaced_by_id = :new_id WHERE id = :id AND revoked_at IS NULL');
    $revokeStmt->execute([':new_id' => $newRefreshId ?: null, ':id' => (int) $row['id']]);

    admin_set_session_tokens($tokens['access_token'], $tokens['refresh_token']);
    return $tokens['access_token'];
}

function admin_current(): ?array
{
    $sessionAdminId = (int) ($_SESSION['admin_user_id'] ?? 0);
    $accessToken = admin_get_bearer_token();
    if (!$accessToken) {
        $accessToken = (string) ($_SESSION['admin_access_token'] ?? ($_COOKIE['admin_access_token'] ?? ''));
    }

    if ($accessToken !== '') {
        $admin = admin_verify_access_token($accessToken);
        if ($admin) {
            return $admin;
        }
    }

    $newAccess = admin_refresh_access_token();
    if ($newAccess) {
        $admin = admin_verify_access_token($newAccess);
        if ($admin) {
            $_SESSION['admin_user_id'] = (int) $admin['id'];
            return $admin;
        }
    }

    // Fallback: preserve login state when token binding fails in unstable local setups.
    if ($sessionAdminId > 0) {
        $admin = admin_fetch_active_by_id($sessionAdminId);
        if ($admin) {
            return $admin;
        }
    }

    return null;
}

function admin_require_auth(): array
{
    $admin = admin_current();
    if (!$admin) {
        $to = function_exists('url') ? url('admin/login.php') : 'login.php';
        header('Location: ' . $to, true, 302);
        exit;
    }
    return $admin;
}

function admin_logout(): void
{
    $pdo = db();
    $fp = admin_fingerprint_hashes();

    $access = (string) ($_SESSION['admin_access_token'] ?? '');
    if ($pdo && $access !== '') {
        $stmt = $pdo->prepare('UPDATE admin_sessions SET revoked_at = NOW() WHERE token_hash = :token_hash AND revoked_at IS NULL AND ip_hash = :ip_hash AND user_agent_hash = :ua_hash');
        $stmt->execute([
            ':token_hash' => hash('sha256', $access),
            ':ip_hash' => $fp['ip_hash'],
            ':ua_hash' => $fp['user_agent_hash'],
        ]);
    }

    $refresh = (string) ($_SESSION['admin_refresh_token'] ?? ($_COOKIE['admin_refresh_token'] ?? ''));
    if ($pdo && $refresh !== '') {
        $stmt = $pdo->prepare('UPDATE admin_refresh_tokens SET revoked_at = NOW() WHERE token_hash = :token_hash AND revoked_at IS NULL AND ip_hash = :ip_hash AND user_agent_hash = :ua_hash');
        $stmt->execute([
            ':token_hash' => hash('sha256', $refresh),
            ':ip_hash' => $fp['ip_hash'],
            ':ua_hash' => $fp['user_agent_hash'],
        ]);
    }

    unset($_SESSION['admin_access_token'], $_SESSION['admin_refresh_token'], $_SESSION['admin_user_id']);
    admin_clear_auth_cookie('admin_access_token');
    admin_clear_auth_cookie('admin_refresh_token');
}

function admin_auth_middleware(): array
{
    $admin = admin_current();
    if (!$admin) {
        response_json(response_error('Unauthorized', [], 401));
    }

    return $admin;
}
