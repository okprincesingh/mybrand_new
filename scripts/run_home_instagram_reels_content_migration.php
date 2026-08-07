<?php
require_once __DIR__ . '/../includes/db.php';

$pdo = db();
if (!$pdo) {
    echo "Database connection error\n";
    exit(1);
}

$sql = file_get_contents(__DIR__ . '/../database/migration_home_instagram_reels_content.sql');
if (!$sql) {
    echo "Migration SQL file not found\n";
    exit(1);
}

$statements = array_filter(array_map('trim', explode(';', $sql)));
$success = 0;
$errors = 0;

foreach ($statements as $stmt) {
    if (empty($stmt) || strspn($stmt, "- \t\n\r") === strlen($stmt)) {
        continue;
    }
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            $success++;
        } else {
            echo "Error: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}

echo "Instagram Reels migration completed: {$success} succeeded, {$errors} errors.\n";
