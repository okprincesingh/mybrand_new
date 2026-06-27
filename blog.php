<?php
require_once __DIR__ . '/includes/blog.php';

$category = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$result = blog_get_posts([
  'category' => $category !== '' ? $category : null,
  'search' => $search !== '' ? $search : null,
], [
  'limit' => $perPage,
  'offset' => $offset,
]);

$posts = $result['items'];
$total = (int) ($result['total'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
}

$allLatest = blog_get_recent_posts(4);
$latestPost = $allLatest[0] ?? ($posts[0] ?? null);
$editorPicks = array_slice($allLatest, 1, 3);
if (!$editorPicks && count($posts) > 1) {
  $editorPicks = array_slice($posts, 1, 3);
}

$categories = blog_get_categories();

$meta = [
  'title' => 'Mybrandplease | Blog',
  'description' => 'Latest updates, skincare insights and beauty blogs from Mybrandplease.',
  'canonical' => 'blog.php' . (!empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : ''),
];
include 'includes/head.php';
include 'includes/header.php';
?>
 <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
        <h1 class="text-center">BLOG</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2">Blog</span></li>
        </ul>
      </div>
    </div>
  </div>
<section class="blog-redesign section-spacing-120 rr-ov-hidden">
  <div class="container rr-container-1350">
    <div class="row g-4 g-xl-5 align-items-start">
      <div class="col-lg-6">
        <h2 class="blog-headline">Latest Post</h2>
        <?php if ($latestPost): ?>
          <article class="featured-card">
            <a href="<?= htmlspecialchars(blog_link((string) $latestPost['slug']), ENT_QUOTES, 'UTF-8') ?>" class="featured-card__image-wrap">
              <img src="<?= htmlspecialchars(url((string) ($latestPost['featured_image'] ?: 'assets/imgs/inner/blog/blog-thumb2_1.jpg')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $latestPost['title'], ENT_QUOTES, 'UTF-8') ?>" class="featured-card__image">
            </a>
            <div class="featured-card__body">
              <h3 class="featured-card__title">
                <a href="<?= htmlspecialchars(blog_link((string) $latestPost['slug']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $latestPost['title'], ENT_QUOTES, 'UTF-8') ?></a>
              </h3>
              <div class="featured-card__meta">
                <span>By <?= htmlspecialchars((string) (($latestPost['author_name'] ?? '') !== '' ? $latestPost['author_name'] : 'Admin'), ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars(date('M d Y', strtotime((string) ($latestPost['published_at'] ?: 'now'))), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </article>
        <?php endif; ?>
      </div>

      <div class="col-lg-6">
        <h2 class="blog-headline">Editor&apos;s Picks</h2>
        <div class="editor-picks">
          <?php foreach ($editorPicks as $pick): ?>
            <article class="pick-item">
              <a href="<?= htmlspecialchars(blog_link((string) $pick['slug']), ENT_QUOTES, 'UTF-8') ?>" class="pick-item__thumb-wrap">
                <img src="<?= htmlspecialchars(url((string) (($pick['featured_image'] ?? '') !== '' ? $pick['featured_image'] : 'assets/imgs/inner/blog/blog-thumb2_2.jpg')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($pick['title'] ?? 'Blog Post'), ENT_QUOTES, 'UTF-8') ?>" class="pick-item__thumb">
              </a>
              <div class="pick-item__content">
                <h3><a href="<?= htmlspecialchars(blog_link((string) ($pick['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($pick['title'] ?? 'Untitled Post'), ENT_QUOTES, 'UTF-8') ?></a></h3>
                <p><?= htmlspecialchars((string) (($pick['excerpt'] ?? '') !== '' ? $pick['excerpt'] : 'Read the full insight from our editorial team.'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="pick-item__meta">
                  <span>By <?= htmlspecialchars((string) ($pick['author_name'] ?? 'Admin'), ENT_QUOTES, 'UTF-8') ?></span>
                  <span><?= htmlspecialchars(date('M d Y', strtotime((string) ($pick['published_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="blog-controls">
      <form method="get" action="<?= htmlspecialchars(url('blog.php'), ENT_QUOTES, 'UTF-8') ?>" class="blog-filters">
        <label for="blog-category">Filters</label>
        <select id="blog-category" name="category" onchange="this.form.submit()">
          <option value="" <?= $category === '' ? 'selected' : '' ?>>All</option>
          <?php foreach ($categories as $cat): $name = (string) ($cat['name'] ?? ''); if ($name === '') continue; ?>
            <option value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" <?= strcasecmp($category, $name) === 0 ? 'selected' : '' ?>>
              <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($search !== ''): ?>
          <input type="hidden" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
      </form>

      <form method="get" action="<?= htmlspecialchars(url('blog.php'), ENT_QUOTES, 'UTF-8') ?>" class="blog-search">
        <?php if ($category !== ''): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <input type="search" name="q" placeholder="Search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit"><i class="fa-regular fa-magnifying-glass"></i></button>
      </form>
    </div>

    <div class="row g-4">
      <?php if (!$posts): ?>
        <div class="col-12"><div class="alert alert-light border">No blog posts found.</div></div>
      <?php endif; ?>

      <?php foreach ($posts as $post): ?>
        <div class="col-xl-4 col-md-6">
          <article class="grid-card">
            <a href="<?= htmlspecialchars(blog_link((string) $post['slug']), ENT_QUOTES, 'UTF-8') ?>" class="grid-card__image-wrap">
              <img src="<?= htmlspecialchars(url((string) (($post['featured_image'] ?? '') !== '' ? $post['featured_image'] : 'assets/imgs/inner/blog/blog-thumb2_3.jpg')), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($post['title'] ?? 'Blog Post'), ENT_QUOTES, 'UTF-8') ?>" class="grid-card__image">
            </a>
            <div class="grid-card__body">
              <h3><a href="<?= htmlspecialchars(blog_link((string) $post['slug']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
              <div class="grid-card__meta">
                <span>By <?= htmlspecialchars((string) (($post['author_name'] ?? '') !== '' ? $post['author_name'] : 'Admin'), ENT_QUOTES, 'UTF-8') ?></span>
                <span><?= htmlspecialchars(date('M d Y', strtotime((string) ($post['published_at'] ?: 'now'))), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php
        $build = static function (int $p) use ($category, $search): string {
          $query = ['page' => $p];
          if ($category !== '') { $query['category'] = $category; }
          if ($search !== '') { $query['q'] = $search; }
          return url('blog.php') . '?' . http_build_query($query);
        };
      ?>
      <div class="pagination">
        <?php if ($page > 1): ?><a href="<?= htmlspecialchars($build($page - 1), ENT_QUOTES, 'UTF-8') ?>" class="prev"><i class="fa-solid fa-chevron-left"></i> Prev</a><?php endif; ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?><a href="<?= htmlspecialchars($build($p), ENT_QUOTES, 'UTF-8') ?>" class="page <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a><?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars($build($page + 1), ENT_QUOTES, 'UTF-8') ?>" class="next">Next<i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<style>
.blog-redesign { background: #f7f8fb; }
.blog-headline { font-size: 25px; font-weight: 700; margin-bottom: 18px; color: #212b42; font-family: var(--font_Playfair); }
.featured-card { background: #eef1f7; border-radius: 18px; overflow: hidden; }
.featured-card__image-wrap { display: block; padding: 18px 18px 0; }
.featured-card__image { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 14px; }
.featured-card__body { padding: 16px 20px 20px; }
.featured-card__title { margin: 0 0 10px; font-size: 25px; line-height: 1.3; font-family: var(--font_Playfair); }
.featured-card__title a { color: #1f2a44; text-decoration: none; }
.featured-card__meta { display: flex; justify-content: space-between; gap: 12px; color: #7a8296; font-size: 16px; font-family: var(--font_Lato); }
.editor-picks { display: grid; gap: 12px; }
.pick-item { display: grid; grid-template-columns: 220px 1fr; gap: 14px; }
.pick-item__thumb { width: 100%; height: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 10px; }
.pick-item__content h3 { margin: 0 0 5px; font-size: 20px; line-height: 1.25; font-family: var(--font_Playfair); }
.pick-item__content h3 a { color: #1f2a44; text-decoration: none; }
.pick-item__content p { margin: 0 0 5px; color: #677189; font-size: 16px; line-height: 1.45; font-family: var(--font_Lato); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.pick-item__meta { display: flex; justify-content: space-between; color: #7d8498; font-size: 14px; font-family: var(--font_Lato); }
.blog-controls { display: flex; justify-content: space-between; align-items: center; margin: 56px 0 28px; gap: 16px; }
.blog-filters { display: flex; align-items: center; gap: 14px; }
.blog-filters label { color: #8a93a7; font-size: 16px; font-weight: 600; font-family: var(--font_Lato); }
.blog-filters select { border: 0; background: transparent; color: #24304a; font-weight: 700; font-size: 16px; padding-right: 18px; font-family: var(--font_Lato); }
.blog-search { width: min(460px,100%); position: relative; }
.blog-search input { width: 100%; border: 1px solid #d9deea; border-radius: 12px; height: 54px; padding: 0 46px 0 46px; font-size: 16px; color: #2b3550; background: #fff; font-family: var(--font_Lato); }
.blog-search button { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: #8f98ad; font-size: 18px; }
.grid-card { width: 100%; background: #eef1f7; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
.grid-card__image-wrap { display: block; padding: 14px 14px 0; }
.grid-card__image { width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 8px; }
.grid-card__body { padding: 14px; display: flex; flex-direction: column; flex: 1; }
.grid-card__body h3 { margin: 0 0 10px; min-height: 90px; font-size: 25px; line-height: 1.25; font-family: var(--font_Playfair); }
.grid-card__body h3 a { color: #1f2a44; text-decoration: none; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.grid-card__meta { margin-top: auto; display: flex; justify-content: space-between; color: #7f879a; font-size: 14px; gap: 12px; font-family: var(--font_Lato); }
@media (max-width: 1199px) {
  .blog-headline { font-size: 28px; }
}
@media (max-width: 991px) {
  .featured-card__title { font-size: 24px; }
  .featured-card__meta { font-size: 13px; }
  .pick-item { grid-template-columns: 1fr; }
  .pick-item__content h3 { font-size: 24px; }
  .pick-item__content p { font-size: 15px; }
  .blog-controls { flex-direction: column; align-items: flex-start; }
  .blog-search { width: 100%; }
  .grid-card__body h3 { font-size: 24px; min-height: 70px; }
}
@media (max-width: 575px) {
  .blog-headline { font-size: 24px; }
  .featured-card__body { padding: 14px; }
  .blog-controls { margin: 34px 0 20px; }
  .grid-card__body { padding: 12px; }
  .grid-card__meta { flex-direction: column; gap: 2px; }
}
</style>

<?php include 'includes/footer.php'; ?>
