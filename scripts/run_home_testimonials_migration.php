<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = db();
    if (!$pdo) {
        throw new Exception("Could not connect to database");
    }

    $sql = file_get_contents(__DIR__ . '/../database/migration_home_testimonials.sql');
    if (!$sql) {
        throw new Exception("Could not read migration_home_testimonials.sql");
    }

    $pdo->exec($sql);
    echo "Testimonials migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
