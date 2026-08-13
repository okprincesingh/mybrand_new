<?php
session_start();
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/url.php';
require_once __DIR__ . '/includes/security.php';

function google_auth_redirect_uri(): string
{
    return url('google-auth.php');
}

function google_auth_error(string $message): void
{
    $_SESSION['google_auth_error'] = $message;
    header('Location: ' . url('login.php'));
    exit;
}

function google_auth_http_post(string $url, array $fields): ?array
{
    $body = http_build_query($fields);
    $headers = ['Content-Type: application/x-www-form-urlencoded'];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 20,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)
            ? (int) $m[1]
            : 0;
    }

    if (!is_string($raw) || $raw === '' || $status < 200 || $status >= 300) {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function google_auth_http_get_json(string $url, string $accessToken): ?array
{
    $headers = ['Authorization: Bearer ' . $accessToken];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        $status = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)
            ? (int) $m[1]
            : 0;
    }

    if (!is_string($raw) || $raw === '' || $status < 200 || $status >= 300) {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

$clientId = trim((string) (getenv('GOOGLE_AUTH_CLIENT_ID') ?: getenv('GOOGLE_CALENDAR_CLIENT_ID')));
$clientSecret = trim((string) (getenv('GOOGLE_AUTH_CLIENT_SECRET') ?: getenv('GOOGLE_CALENDAR_CLIENT_SECRET')));

if ($clientId === '' || $clientSecret === '') {
    google_auth_error('Google sign in is not configured yet.');
}

$error = trim((string) ($_GET['error'] ?? ''));
if ($error !== '') {
    google_auth_error('Google sign in was cancelled or denied.');
}

$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '') {
    $state = bin2hex(random_bytes(24));
    $_SESSION['google_auth_state'] = $state;
    $_SESSION['google_auth_redirect'] = trim((string) ($_GET['redirect'] ?? 'user-dashboard.php'));

    $params = [
        'client_id' => $clientId,
        'redirect_uri' => google_auth_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ];

    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

$state = trim((string) ($_GET['state'] ?? ''));
$expectedState = (string) ($_SESSION['google_auth_state'] ?? '');
unset($_SESSION['google_auth_state']);

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    google_auth_error('Invalid Google sign in request. Please try again.');
}

$tokenResponse = google_auth_http_post('https://oauth2.googleapis.com/token', [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => google_auth_redirect_uri(),
    'grant_type' => 'authorization_code',
]);

$accessToken = is_array($tokenResponse) ? (string) ($tokenResponse['access_token'] ?? '') : '';
if ($accessToken === '') {
    google_auth_error('Could not complete Google sign in. Please try again.');
}

$profile = google_auth_http_get_json('https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);
if (!$profile || empty($profile['verified_email'])) {
    google_auth_error('Google account email is not verified.');
}

$email = (string) ($profile['email'] ?? '');
$firstName = (string) ($profile['given_name'] ?? '');
$lastName = (string) ($profile['family_name'] ?? '');
$result = user_login_or_register_google($email, $firstName, $lastName);

if (!$result || empty($result['success'])) {
    google_auth_error((string) ($result['message'] ?? 'Google sign in failed.'));
}

$redirect = trim((string) ($_SESSION['google_auth_redirect'] ?? 'user-dashboard.php'));
unset($_SESSION['google_auth_redirect']);
if ($redirect === '' || preg_match('#^(https?:)?//#i', $redirect) || str_contains($redirect, '..')) {
    $redirect = 'user-dashboard.php';
}

header('Location: ' . url($redirect));
exit;
