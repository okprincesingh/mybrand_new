<?php
require_once __DIR__ . '/env.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_regenerate_token(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_request_token(): string
{
    $token = $_POST['csrf_token'] ?? '';
    if (is_string($token) && $token !== '') {
        return $token;
    }

    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (is_string($headerToken) && $headerToken !== '') {
        return $headerToken;
    }

    return '';
}

function verify_csrf_or_fail(): void
{
    $token = csrf_request_token();
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function enforce_csrf_on_post(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        verify_csrf_or_fail();
    }
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_flash_get(): ?array
{
    if (!isset($_SESSION['admin_flash'])) {
        return null;
    }
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
    return $flash;
}

function validate_uploaded_image(array $file, int $maxBytes = 5_000_000): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Upload failed.';
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return 'File too large.';
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        return 'Invalid upload.';
    }

    $name = (string) ($file['name'] ?? '');
    if ($name === '') {
        return 'Invalid file name.';
    }

    $base = basename($name);
    $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    $nameWithoutExt = pathinfo($base, PATHINFO_FILENAME);
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowedExt, true)) {
        return 'Invalid image extension.';
    }

    // Block filenames such as payload.php.jpg (double extension trick).
    if (strpos($nameWithoutExt, '.') !== false) {
        return 'Invalid file name.';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimeToExt = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    if (!isset($allowedMimeToExt[$mime])) {
        return 'Invalid image format.';
    }

    if (!in_array($extension, $allowedMimeToExt[$mime], true)) {
        return 'File extension does not match MIME type.';
    }

    return null;
}

function upload_storage_dir(string $subdir, bool $preferPrivate = false): string
{
    $subdir = trim($subdir, "/\\");
    $privateRoot = getenv('PRIVATE_UPLOAD_ROOT') ?: (__DIR__ . '/../storage/private_uploads');

    if ($preferPrivate && $privateRoot !== '') {
        $candidate = rtrim($privateRoot, "/\\") . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($candidate)) {
            @mkdir($candidate, 0755, true);
        }
        if (is_dir($candidate) && is_writable($candidate)) {
            return $candidate;
        }
    }

    $fallback = __DIR__ . '/../uploads/' . $subdir;
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0755, true);
    }
    return $fallback;
}

function store_uploaded_image(array $file, string $subdir, int $maxBytes = 5_000_000, bool $preferPrivate = false): ?array
{
    $error = validate_uploaded_image($file, $maxBytes);
    if ($error !== null) {
        return null;
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $hashedName = hash('sha256', random_bytes(32) . microtime(true) . (string) ($file['name'] ?? ''));
    $newFileName = $hashedName . '.' . $ext;
    $targetDir = upload_storage_dir($subdir, $preferPrivate);
    $targetPath = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR . $newFileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $targetPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    return [
        'file_name' => $newFileName,
        'mime_type' => $mime,
        'file_size' => (int) filesize($targetPath),
        'public_path' => $preferPrivate ? '' : ('uploads/' . trim($subdir, "/\\") . '/' . $newFileName),
        'absolute_path' => $targetPath,
    ];
}

function validate_uploaded_video(array $file, int $maxBytes = 50_000_000): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Upload failed.';
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return 'File too large.';
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
        return 'Invalid upload.';
    }

    $name = (string) ($file['name'] ?? '');
    if ($name === '') {
        return 'Invalid file name.';
    }

    $base = basename($name);
    $extension = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    $allowedExt = ['mp4', 'webm', 'mov'];
    if (!in_array($extension, $allowedExt, true)) {
        return 'Invalid video extension.';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimeToExt = [
        'video/mp4' => ['mp4'],
        'video/webm' => ['webm'],
        'video/quicktime' => ['mov'],
    ];

    if (!isset($allowedMimeToExt[$mime])) {
        return 'Invalid video format.';
    }

    if (!in_array($extension, $allowedMimeToExt[$mime], true)) {
        return 'File extension does not match MIME type.';
    }

    return null;
}

function store_uploaded_video(array $file, string $subdir, int $maxBytes = 50_000_000, bool $preferPrivate = false): ?array
{
    $error = validate_uploaded_video($file, $maxBytes);
    if ($error !== null) {
        return null;
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $hashedName = hash('sha256', random_bytes(32) . microtime(true) . (string) ($file['name'] ?? ''));
    $newFileName = $hashedName . '.' . $ext;
    $targetDir = upload_storage_dir($subdir, $preferPrivate);
    $targetPath = rtrim($targetDir, "/\\") . DIRECTORY_SEPARATOR . $newFileName;

    if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $targetPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    return [
        'file_name' => $newFileName,
        'mime_type' => $mime,
        'file_size' => (int) filesize($targetPath),
        'public_path' => $preferPrivate ? '' : ('uploads/' . trim($subdir, "/\\") . '/' . $newFileName),
        'absolute_path' => $targetPath,
    ];
}


function admin_flash_set(string $type, string $message): void
{
    admin_flash($type, $message);
}

