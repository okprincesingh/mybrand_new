<?php
require_once __DIR__ . '/env.php';

function captcha_site_key(): string
{
    $key = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: getenv('GOOGLE_RECAPTCHA_SITE_KEY') ?: ''));
    return $key;
}

function captcha_secret_key(): string
{
    $key = trim((string) (getenv('RECAPTCHA_SECRET_KEY') ?: getenv('GOOGLE_RECAPTCHA_SECRET_KEY') ?: ''));
    return $key;
}

function captcha_enabled(): bool
{
    return captcha_site_key() !== '' && captcha_secret_key() !== '';
}

function captcha_render(string $extraClass = ''): string
{
    if (!captcha_enabled()) {
        return '';
    }

    $class = trim('g-recaptcha ' . $extraClass);
    return '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" data-sitekey="' . htmlspecialchars(captcha_site_key(), ENT_QUOTES, 'UTF-8') . '"></div>';
}

function captcha_head_script(): string
{
    if (!captcha_enabled()) {
        return '';
    }

    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

function captcha_verify_response(?string &$error = null): bool
{
    $error = null;
    if (!captcha_enabled()) {
        return true;
    }

    $token = isset($_POST['g-recaptcha-response']) && is_string($_POST['g-recaptcha-response'])
        ? trim($_POST['g-recaptcha-response'])
        : '';
    if ($token === '') {
        $error = 'Please complete the captcha verification.';
        return false;
    }

    $postData = http_build_query([
        'secret' => captcha_secret_key(),
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $response = false;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 10,
            ],
        ]);
        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    }

    if (!is_string($response) || trim($response) === '') {
        $error = 'Captcha verification is temporarily unavailable. Please try again.';
        return false;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['success'])) {
        $error = 'Captcha verification failed. Please try again.';
        return false;
    }

    return true;
}
