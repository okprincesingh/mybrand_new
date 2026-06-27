<?php
require_once __DIR__ . '/includes/why-page-template.php';
require_once __DIR__ . '/includes/our-certificates-template.php';

$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === 'our-certificates') {
  render_our_certificates_page();
  exit;
}

if ($slug === '') {
  $pages = cms_get_why_choose_pages(true);
  if (!empty($pages[0]['slug'])) {
    render_why_choose_page((string) $pages[0]['slug']);
    exit;
  }
  http_response_code(404);
  require_once __DIR__ . '/404.php';
  exit;
}

render_why_choose_page($slug);
