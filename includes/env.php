<?php

function app_load_env(?string $path = null): void
{
    static $loaded = [];

    $path = $path ?: dirname(__DIR__) . '/.env';
    $realPath = realpath($path) ?: $path;
    if (isset($loaded[$realPath]) || !is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $equalsPos = strpos($line, '=');
        if ($equalsPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $equalsPos));
        $value = trim(substr($line, $equalsPos + 1));
        if ($key === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1) {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $quote = $value[0];
            $value = substr($value, 1, -1);
            if ($quote === '"') {
                $value = str_replace(['\n', '\r', '\t', '\"', '\\\\'], ["\n", "\r", "\t", '"', '\\'], $value);
            }
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    $loaded[$realPath] = true;
}

app_load_env();
