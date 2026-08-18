<?php
/**
 * PHP CLI script to safely apply performance indexes to the active database.
 */

// Don't start web sessions when executing from CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../includes/db.php';

echo "========================================================\n";
echo " Database Performance Indexing Tool\n";
echo "========================================================\n\n";

$pdo = db();
if (!$pdo) {
    echo "ERROR: Unable to connect to database using db() configuration.\n";
    exit(1);
}

$dbName = DB_NAME;
echo "Connected to database: {$dbName}\n\n";

$indexes = [
    // [table, index_name, columns_sql]
    ['products', 'idx_products_cat_active_status_created', '(category_id, is_active, status, created_at)'],
    ['products', 'idx_products_cat_active_status_price', '(category_id, is_active, status, price)'],
    ['products', 'idx_products_active_status_created', '(is_active, status, created_at, id)'],
    ['products', 'idx_products_name', '(name)'],
    
    ['categories', 'idx_categories_parent_active_sort', '(parent_id, is_active, sort_order)'],
    ['categories', 'idx_categories_name', '(name)'],
    
    ['blog_posts', 'idx_blog_posts_status_published_id', '(status, published_at, id)'],
    ['blog_posts', 'idx_blog_posts_cat_status_published', '(category, status, published_at)'],
    ['blog_posts', 'idx_blog_posts_title', '(title)'],
    
    ['product_reviews', 'idx_product_reviews_prod_status_created', '(product_id, status, created_at)'],
    ['product_attributes', 'idx_product_attributes_prod_key', '(product_id, attribute_key)'],
    
    ['orders', 'idx_orders_user_created', '(user_id, created_at)'],
    ['orders', 'idx_orders_customer_created', '(customer_id, created_at)'],
    ['orders', 'idx_orders_status_created', '(status, created_at)'],
    ['orders', 'idx_orders_payment_status', '(payment_status)'],
    
    ['order_items', 'idx_order_items_order_product', '(order_id, product_id)'],
    ['user_wishlist', 'idx_user_wishlist_user_created', '(user_id, created_at)'],
    ['coupons', 'idx_coupons_code_active_dates', '(code, is_active, starts_at, expires_at)'],
    ['certificates', 'idx_certificates_cat_active_sort', '(category, is_active, sort_order)'],
    ['why_page_accordions', 'idx_why_page_accordions_page_active', '(page_id, is_active, sort_order)'],
    
    ['admin_sessions', 'idx_admin_sessions_token_revoked_expires', '(token_hash, revoked_at, expires_at)'],
    ['user_sessions', 'idx_user_sessions_token_expires', '(session_token, expires_at)'],

    ['home_cta_cards', 'idx_home_cta_cards_active_order', '(is_active, sort_order, id)'],
    ['home_cta_cards', 'idx_home_cta_cards_key', '(card_key)'],

    ['home_brand_builder_items', 'idx_brand_builder_items_sec_active_order', '(section_id, is_active, sort_order, id)'],
];

$successCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($indexes as [$table, $indexName, $columns]) {
    // Check if table exists
    $tableCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.tables 
        WHERE table_schema = :dbname AND table_name = :tbl
    ");
    $tableCheck->execute([':dbname' => $dbName, ':tbl' => $table]);
    if ((int) $tableCheck->fetchColumn() === 0) {
        echo "[-] Skipping index '{$indexName}': Table '{$table}' does not exist.\n";
        $skippedCount++;
        continue;
    }

    // Check if index already exists
    $idxCheck = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.statistics 
        WHERE table_schema = :dbname AND table_name = :tbl AND index_name = :idx
    ");
    $idxCheck->execute([':dbname' => $dbName, ':tbl' => $table, ':idx' => $indexName]);
    if ((int) $idxCheck->fetchColumn() > 0) {
        echo "[=] Index '{$indexName}' on table '{$table}' already exists.\n";
        $skippedCount++;
        continue;
    }

    // Create index
    try {
        $sql = "CREATE INDEX {$indexName} ON {$table} {$columns}";
        $pdo->exec($sql);
        echo "[+] Successfully created index '{$indexName}' on '{$table}'.\n";
        $successCount++;
    } catch (Throwable $e) {
        // Try fallback if MySQL throws duplicate key error
        if (str_contains($e->getMessage(), 'Duplicate key name')) {
            echo "[=] Index '{$indexName}' on table '{$table}' already exists (verified via MySQL error).\n";
            $skippedCount++;
        } else {
            echo "[!] Error creating index '{$indexName}' on '{$table}': " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
}

echo "\n--------------------------------------------------------\n";
echo "Indexing complete! Results:\n";
echo " - Created: {$successCount} new indexes\n";
echo " - Already present / Skipped: {$skippedCount}\n";
echo " - Errors: {$errorCount}\n";
echo "--------------------------------------------------------\n";
