<?php
/**
 * Section File Upload API
 * -----------------------
 * Handles instant file uploads (images/videos) for the section preview system.
 *
 * When the admin selects a file in an editor form, this endpoint stores the
 * file and returns the public path. The JS then updates the hidden existing_*
 * field and triggers a draft save + preview refresh — so the new file appears
 * in the preview instantly, without a page reload.
 *
 * Safety:
 *   - Admin auth required.
 *   - CSRF token required (sent via form field or header).
 *   - Uses the existing store_uploaded_image() / store_uploaded_video() helpers
 *     which validate MIME type, extension, and file size.
 *   - Files are stored in uploads/<subdir>/ with hashed names (no user-controlled filenames).
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/url.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Admin auth
if (!admin_current()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST with a file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF check (form-encoded since this is multipart/form-data)
$csrfToken = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($csrfToken === '' || !hash_equals(csrf_token(), $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Determine the field type (image or video) and target subdirectory.
$fieldType = (string) ($_POST['field_type'] ?? '');
$subdir = (string) ($_POST['subdir'] ?? '');
$fileField = (string) ($_POST['file_field'] ?? 'file');

if ($subdir === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing subdir']);
    exit;
}

if (!isset($_FILES[$fileField])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded (field: ' . htmlspecialchars($fileField, ENT_QUOTES, 'UTF-8') . ')']);
    exit;
}

$file = $_FILES[$fileField];

// Dispatch to the correct storage helper based on field type.
if ($fieldType === 'image') {
    $stored = store_uploaded_image($file, $subdir, 5_000_000, false);
} elseif ($fieldType === 'video') {
    $stored = store_uploaded_video($file, $subdir, 50_000_000, false);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid field_type. Use "image" or "video".']);
    exit;
}

if (!$stored) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'File upload failed. Please check the file type and size (images: jpg/jpeg/png/webp max 5MB; videos: mp4/webm/mov max 50MB).',
    ]);
    exit;
}

// Return the public path so JS can update the hidden field.
echo json_encode([
    'success' => true,
    'message' => 'File uploaded.',
    'public_path' => (string) $stored['public_path'],
    'file_url' => url((string) $stored['public_path']),
    'file_name' => (string) $stored['file_name'],
    'mime_type' => (string) $stored['mime_type'],
    'file_size' => (int) $stored['file_size'],
]);