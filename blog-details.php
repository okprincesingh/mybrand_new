<?php
require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/content-loader.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$post = $slug !== '' ? blog_get_post_by_slug($slug) : null;
if (!$post) {
  $fallback = blog_get_posts([], ['limit' => 1, 'offset' => 0]);
  $post = $fallback['items'][0] ?? null;
}
if (!$post) {
  http_response_code(404);
  exit('Post not found');
}

$recentPosts = blog_get_recent_posts(3, (string) $post['slug']);
$categories = blog_get_categories();
$rawExcerpt = (string) ($post['excerpt'] ?? '');
$rawContent = (string) ($post['content'] ?? '');
$excerptHtml = $rawExcerpt !== ''
  ? sanitize_rich_html(normalize_wordpress_rich_html(html_entity_decode($rawExcerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
  : '';
$contentHtml = $rawContent !== ''
  ? sanitize_rich_html(normalize_wordpress_rich_html(html_entity_decode($rawContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')))
  : '';

$meta = [
  'title' => 'Mybrandplease | ' . (string) $post['title'],
  'description' => (string) ($post['excerpt'] ?: 'Mybrandplease blog details'),
  'canonical' => 'blog-details.php?slug=' . urlencode((string) $post['slug']),
];
include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
      <h1 class="text-center"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><a href="<?= htmlspecialchars(url('blog.php'), ENT_QUOTES, 'UTF-8') ?>" class="breadcumb-wrapper__items-list-title">Blog</a></li>
        <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
        <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></span></li>
      </ul>
    </div>
  </div>
</div>

<div class="blog-details section-spacing-120 rr-ov-hidden">
  <div class="container rr-container-1350">
    <div class="row g-5 justify-content-center">
      <div class="col-12 col-xl-8 col-lg-8">
        <div class="blog-details-wrapper">
          <div class="blog-details__thumb mb-4">
            <img src="<?= htmlspecialchars(url((string) ($post['featured_image'] ?: 'assets/imgs/inner/blog-details/blog-details-thumb1_1.jpg')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <ul class="blog-details-wrapper-items__post mb-3">
            <li class="blog-details-wrapper-items__post-date"><i class="fa-regular fa-user me-2"></i> <?= htmlspecialchars((string) ($post['author_name'] ?: 'Admin'), ENT_QUOTES, 'UTF-8') ?></li>
            <li class="blog-details-wrapper-items__post-date"><i class="fa-regular fa-calendar me-2"></i> <?= htmlspecialchars(date('d M, Y', strtotime((string) ($post['published_at'] ?: 'now'))), ENT_QUOTES, 'UTF-8') ?></li>
            <!-- <li class="blog-details-wrapper-items__post-date"><i class="fa-regular fa-folder"></i> <?= htmlspecialchars((string) ($post['category'] ?: 'General'), ENT_QUOTES, 'UTF-8') ?></li> -->
          </ul>

          <div class="blog-details-wrapper-items-content">
            <!-- <div class="blog-details-wrapper-items-content__title"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></div> -->
            <?php if ($excerptHtml !== ''): ?><div class="cms-richtext blog-details-wrapper-items-content__text"><?= $excerptHtml ?></div><?php endif; ?>
            <div class="cms-richtext"><?= $contentHtml ?></div>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-4 col-lg-4">
        <div class="main-sidebar2">
          <div class="main-sidebar2-widget">
            <div class="main-sidebar2-widget__heading"><div class="main-sidebar2-widget__heading-title">Search Here</div></div>
            <div class="main-sidebar2-widget__search-widget">
              <form action="<?= htmlspecialchars(url('blog.php'), ENT_QUOTES, 'UTF-8') ?>" method="get">
                <input type="text" name="q" placeholder="Search Blog">
                <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
              </form>
            </div>
          </div>

          <div class="main-sidebar2-widget">
            <div class="main-sidebar2-widget__heading"><div class="main-sidebar2-widget__heading-title">Category</div></div>
            <div class="main-sidebar2-widget__categories">
              <ul class="main-sidebar2-widget__categories-items">
                <?php foreach ($categories as $cat): ?>
                  <li class="main-sidebar2-widget__categories-items-text"><a href="<?= htmlspecialchars(url('blog.php?category=' . urlencode((string) $cat['name'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $cat['name'], ENT_QUOTES, 'UTF-8') ?></a> <span>(<?= (int) $cat['count'] ?>)</span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

          <div class="main-sidebar2-widget">
            <div class="main-sidebar2-widget__heading"><div class="main-sidebar2-widget__heading-title">Recent Blog</div></div>
            <div class="main-sidebar2-widget__post">
              <?php foreach ($recentPosts as $recent): ?>
                <div class="main-sidebar2-widget__post-items mb-3">
                  <div class="main-sidebar2-widget__post-items-thumb"><img src="<?= htmlspecialchars(url((string) ($recent['featured_image'] ?: 'assets/imgs/inner/blog-standard/blog-standard-thumb1_4.jpg')), ENT_QUOTES, 'UTF-8') ?>" alt="thumb"></div>
                  <div class="main-sidebar2-widget__post-items-content">
                    <ul class="main-sidebar2-widget__post-items-content-post"><li class="main-sidebar2-widget__post-items-content-post-date"><?= htmlspecialchars(date('F d, Y', strtotime((string) ($recent['published_at'] ?: 'now'))), ENT_QUOTES, 'UTF-8') ?></li></ul>
                    <div class="main-sidebar2-widget__post-items-content-title"><a href="<?= htmlspecialchars(blog_link((string) $recent['slug']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $recent['title'], ENT_QUOTES, 'UTF-8') ?></a></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
