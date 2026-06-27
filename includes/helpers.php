<?php

function response_format(bool $success, string $message = '', $data = null, array $errors = [], int $status = 200): array
{
    return [
        'success' => $success,
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'errors' => $errors,
    ];
}

function response_success($data = null, string $message = 'OK', int $status = 200): array
{
    return response_format(true, $message, $data, [], $status);
}

function response_error(string $message = 'Error', array $errors = [], int $status = 400, $data = null): array
{
    return response_format(false, $message, $data, $errors, $status);
}

function response_json(array $payload, ?int $status = null): void
{
    $httpStatus = $status ?? (int) ($payload['status'] ?? 200);
    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function validate_required_fields(array $source, array $fields): array
{
    $errors = [];
    foreach ($fields as $field) {
        $value = $source[$field] ?? null;
        if (!is_string($value) || trim($value) === '') {
            $errors[$field] = 'This field is required.';
        }
    }
    return $errors;
}

function validate_email_value(?string $email): bool
{
    return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_slug_value(?string $slug): bool
{
    return is_string($slug) && (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

function validate_enum_value($value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

function validate_max_length(?string $value, int $max): bool
{
    if (!is_string($value)) {
        return false;
    }
    $len = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    return $len <= $max;
}

function db_bind_values(PDOStatement $stmt, array $params): void
{
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if (is_int($value)) {
            $type = PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            $type = PDO::PARAM_BOOL;
        } elseif ($value === null) {
            $type = PDO::PARAM_NULL;
        }
        $stmt->bindValue($key, $value, $type);
    }
}

function db_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function db_fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_fetch_value(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    $stmt->execute();
    $value = $stmt->fetchColumn();
    return $value === false ? null : $value;
}

function db_execute(PDO $pdo, string $sql, array $params = []): bool
{
    $stmt = $pdo->prepare($sql);
    db_bind_values($stmt, $params);
    return $stmt->execute();
}
