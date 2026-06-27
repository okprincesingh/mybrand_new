<?php

function cache_root_dir(): string
{
    $dir = __DIR__ . '/../storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function cache_file_path(string $key): string
{
    return cache_root_dir() . '/' . sha1($key) . '.cache.php';
}

function cache_get(string $key)
{
    $file = cache_file_path($key);
    if (!is_file($file)) {
        return null;
    }

    $payload = @unserialize((string) @file_get_contents($file));
    if (!is_array($payload)) {
        @unlink($file);
        return null;
    }

    $expiresAt = (int) ($payload['expires_at'] ?? 0);
    if ($expiresAt > 0 && $expiresAt < time()) {
        @unlink($file);
        return null;
    }

    return $payload['data'] ?? null;
}

function cache_set(string $key, $data, int $ttlSeconds = 300): void
{
    $file = cache_file_path($key);
    $payload = [
        'key' => $key,
        'expires_at' => time() + max(1, $ttlSeconds),
        'data' => $data,
    ];
    @file_put_contents($file, serialize($payload), LOCK_EX);
}

function cache_delete(string $key): void
{
    $file = cache_file_path($key);
    if (is_file($file)) {
        @unlink($file);
    }
}

function cache_clear_prefix(string $prefix): void
{
    $dir = cache_root_dir();
    $files = glob($dir . '/*.cache.php') ?: [];
    foreach ($files as $file) {
        $payload = @unserialize((string) @file_get_contents($file));
        if (!is_array($payload)) {
            @unlink($file);
            continue;
        }

        $cacheKey = (string) ($payload['key'] ?? '');
        if ($cacheKey !== '' && strncmp($cacheKey, $prefix, strlen($prefix)) === 0) {
            @unlink($file);
        }
    }
}
