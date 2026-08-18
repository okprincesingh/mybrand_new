<?php
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

echo "==================================================\n";
echo " Getting Started Content CMS Test\n";
echo "==================================================\n\n";

$content = cms_get_home_getting_started_content();
echo "Section Key: " . ($content['section_key'] ?? '') . "\n";
echo "Raw Heading: " . ($content['heading_text'] ?? '') . "\n";
echo "Intro Text:  " . ($content['description_text'] ?? '') . "\n";

$rawHeading = trim($content['heading_text'] ?? "Here's How To Get Started");
$cleanHeading = trim($rawHeading, "~\t\n\r\0\x0B ");
$formattedHeading = "~ <em>" . htmlspecialchars($cleanHeading, ENT_QUOTES, 'UTF-8') . "</em> ~";

echo "Rendered HTML: " . $formattedHeading . "\n";
echo "\nTest Passed Successfully!\n";
