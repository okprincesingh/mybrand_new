<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

$pdo = db();
if (!$pdo) {
    echo "Database connection error\n";
    exit(1);
}

// Clear existing items in home_testimonials table
$pdo->exec("TRUNCATE TABLE home_testimonials");

$reviews = [
    ['platform' => 'tp', 'name' => 'Steve Marc', 'content' => 'Communication was clear and professional from the beginning, the team stayed responsive, and the products arrived on time with quality that met expectations.', 'rating' => 5, 'review_date' => '8 Mar 2026', 'sort_order' => 1, 'is_active' => 1],
    ['platform' => 'tp', 'name' => 'Zain Sheikh', 'content' => 'A professional long-term partner with strong expertise across formulation, packaging, design, compliance, and customer service.', 'rating' => 5, 'review_date' => '21 Feb 2026', 'sort_order' => 2, 'is_active' => 1],
    ['platform' => 'tp', 'name' => 'Meghana Ghosh', 'content' => 'mybrandplease supported the brand from concept to launch with guidance on ingredients, positioning, compliance, packaging, and market readiness.', 'rating' => 5, 'review_date' => '15 Feb 2026', 'sort_order' => 3, 'is_active' => 1],
    ['platform' => 'tp', 'name' => 'Yawovi Yevoudakor', 'content' => 'Good products, helpful customer service, and a pleasant purchase experience made it easy to return for another order.', 'rating' => 5, 'review_date' => '16 Oct 2025', 'sort_order' => 4, 'is_active' => 1],
    ['platform' => 'tp', 'name' => 'Elina', 'content' => 'The hair care range delivered top-shelf quality and made launching a new brand feel simple and successful.', 'rating' => 5, 'review_date' => '11 May 2025', 'sort_order' => 5, 'is_active' => 1],
    ['platform' => 'goog', 'name' => 'Priya Mehta', 'content' => 'Incredible service from start to finish. They handled formulation and labeling while keeping the MOQ practical for a startup brand.', 'rating' => 5, 'review_date' => '9 May 2026', 'sort_order' => 6, 'is_active' => 1],
    ['platform' => 'goog', 'name' => 'James Carter', 'content' => 'Exceptional quality control and a responsive team. The custom formulation matched the brief and gave us confidence to expand the line.', 'rating' => 5, 'review_date' => '1 May 2026', 'sort_order' => 7, 'is_active' => 1],
    ['platform' => 'goog', 'name' => 'Ananya Joshi', 'content' => 'The team guided us through each step of the private label process and helped the final products look premium.', 'rating' => 5, 'review_date' => '24 Apr 2026', 'sort_order' => 8, 'is_active' => 1],
    ['platform' => 'goog', 'name' => 'Rahul Sharma', 'content' => 'Top quality private label formulations with noticeable customer response after switching to mybrandplease.', 'rating' => 5, 'review_date' => '18 Apr 2026', 'sort_order' => 9, 'is_active' => 1],
    ['platform' => 'goog', 'name' => 'Nisha Kapoor', 'content' => 'Supportive communication, polished packaging, and dependable timelines made the launch process much smoother.', 'rating' => 5, 'review_date' => '12 Apr 2026', 'sort_order' => 10, 'is_active' => 1],
    ['platform' => 'ali', 'name' => 'Li Wei', 'content' => 'A strong B2B supplier for private label cosmetics with fast communication and reliable bulk order delivery.', 'rating' => 5, 'review_date' => '6 May 2026', 'sort_order' => 11, 'is_active' => 1],
    ['platform' => 'ali', 'name' => 'Maria Santos', 'content' => 'Custom branding was handled well, the products passed quality checks, and the pricing stayed competitive for reorder planning.', 'rating' => 5, 'review_date' => '28 Apr 2026', 'sort_order' => 12, 'is_active' => 1],
    ['platform' => 'ali', 'name' => 'Omar Khan', 'content' => 'Samples, packaging options, and production details were explained clearly, which helped us move forward with confidence.', 'rating' => 5, 'review_date' => '19 Apr 2026', 'sort_order' => 13, 'is_active' => 1],
    ['platform' => 'ali', 'name' => 'Sofia Martins', 'content' => 'The team responded quickly during sourcing and kept the order organized from product selection through dispatch.', 'rating' => 5, 'review_date' => '10 Apr 2026', 'sort_order' => 14, 'is_active' => 1],
    ['platform' => 'ali', 'name' => 'Daniel Roberts', 'content' => 'Reliable supplier experience with clear communication, good packaging quality, and consistent private label support.', 'rating' => 5, 'review_date' => '2 Apr 2026', 'sort_order' => 15, 'is_active' => 1],
];

$stmt = $pdo->prepare("INSERT INTO home_testimonials (platform, name, content, rating, review_date, sort_order, is_active) VALUES (:platform, :name, :content, :rating, :review_date, :sort_order, :is_active)");

foreach ($reviews as $r) {
    $stmt->execute($r);
}

cms_invalidate_home_testimonials_cache();
cms_invalidate_home_testimonials_content_cache();

echo "Successfully reseeded " . count($reviews) . " testimonials into home_testimonials table!\n";
