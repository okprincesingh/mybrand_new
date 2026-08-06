<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = db();
$sql = file_get_contents(__DIR__ . '/../database/migration_getting_started_content.sql');
$pdo->exec($sql);
echo "Database migration executed successfully!\n";
