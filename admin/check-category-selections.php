<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();

$pdo = db();
if (!$pdo) {
    die("Database connection failed");
}

echo "<h2>Database Diagnostic - Category Selections</h2>";

// Check home_categories table
echo "<h3>Home Categories (home_categories table)</h3>";
$homeCats = db_fetch_all($pdo, 'SELECT * FROM home_categories ORDER BY sort_order ASC, id ASC');
if ($homeCats) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Category ID</th><th>Sort Order</th><th>Is Active</th></tr>";
    foreach ($homeCats as $cat) {
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>{$cat['category_id']}</td>";
        echo "<td>{$cat['sort_order']}</td>";
        echo "<td>{$cat['is_active']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No records found in home_categories table</p>";
}

// Check home_category_subcategories table
echo "<h3>Home Category Subcategories (home_category_subcategories table)</h3>";
$homeSubs = db_fetch_all($pdo, 'SELECT * FROM home_category_subcategories ORDER BY home_category_id ASC, sort_order ASC, id ASC');
if ($homeSubs) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Home Category ID</th><th>Subcategory ID</th><th>Sort Order</th></tr>";
    foreach ($homeSubs as $sub) {
        echo "<tr>";
        echo "<td>{$sub['id']}</td>";
        echo "<td>{$sub['home_category_id']}</td>";
        echo "<td>{$sub['subcategory_id']}</td>";
        echo "<td>{$sub['sort_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No records found in home_category_subcategories table</p>";
}

// Check catalog categories
echo "<h3>Catalog Categories (categories table)</h3>";
$cats = db_fetch_all($pdo, 'SELECT id, parent_id, name, slug FROM categories ORDER BY parent_id ASC, sort_order ASC, name ASC');
if ($cats) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Parent ID</th><th>Name</th><th>Slug</th></tr>";
    foreach ($cats as $cat) {
        echo "<tr>";
        echo "<td>{$cat['id']}</td>";
        echo "<td>" . ($cat['parent_id'] ?: 'NULL') . "</td>";
        echo "<td>{$cat['name']}</td>";
        echo "<td>{$cat['slug']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No categories found in catalog</p>";
}

echo "<hr><p><a href='manage-categories.php'>Back to Manage Categories</a></p>";
?>