<?php
require_once __DIR__ . '/db.php';

function jwt_b64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function jwt_b64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function jwt_generate(array $payload, ?int $ttl = null): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $ttl = $ttl ?? JWT_ACCESS_TTL;
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttl;

    $h = jwt_b64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES) ?: '{}');
    $p = jwt_b64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}');
    $signature = hash_hmac('sha256', $h . '.' . $p, JWT_SECRET, true);
    $s = jwt_b64url_encode($signature);

    return $h . '.' . $p . '.' . $s;
}

function jwt_verify(string $jwt, ?string $expectedType = null, bool $ignoreExpiry = false): ?array
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }

    [$h, $p, $s] = $parts;
    $calc = jwt_b64url_encode(hash_hmac('sha256', $h . '.' . $p, JWT_SECRET, true));
    if (!hash_equals($calc, $s)) {
        return null;
    }

    $payload = json_decode(jwt_b64url_decode($p), true);
    if (!is_array($payload)) {
        return null;
    }

    if (!$ignoreExpiry && ($payload['exp'] ?? 0) < time()) {
        return null;
    }

    if ($expectedType !== null && (($payload['token_type'] ?? '') !== $expectedType)) {
        return null;
    }

    return $payload;
}
