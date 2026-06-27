<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/blog.php';
$adminUser = admin_require_auth();
$title = 'Blog Posts';
$pdo = db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $action = (string) ($_POST['action'] ?? '');
  if ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
      db_execute($pdo, 'DELETE FROM blog_posts WHERE id = :id', [':id' => $id]);
      admin_flash('success', 'Blog deleted.');
    }
    header('Location: blogs.php');
    exit;
  }
}

$q = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'latest'));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where = ['1=1'];
$params = [];
if ($q !== '') {
  $where[] = '(title LIKE :q OR slug LIKE :q OR excerpt LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}
if ($category !== '') {
  $where[] = 'category = :cat';
  $params[':cat'] = $category;
}
if (in_array($status, ['draft', 'published'], true)) {
  $where[] = 'status = :st';
  $params[':st'] = $status;
}

$orderSql = 'published_at DESC, id DESC';
if ($sort === 'title_asc') $orderSql = 'title ASC';
if ($sort === 'title_desc') $orderSql = 'title DESC';
if ($sort === 'oldest') $orderSql = 'published_at ASC, id ASC';

$total = 0;
$rows = [];
$categories = [];

if ($pdo && blog_table_exists($pdo)) {
  $whereSql = implode(' AND ', $where);
  $total = (int) (db_fetch_value($pdo, "SELECT COUNT(*) FROM blog_posts WHERE {$whereSql}", $params) ?? 0);

  $sql = "SELECT id,title,slug,category,author_name,status,published_at,updated_at
          FROM blog_posts
          WHERE {$whereSql}
          ORDER BY {$orderSql}
          LIMIT :limit OFFSET :offset";
  $stmt = $pdo->prepare($sql);
  db_bind_values($stmt, $params);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll() ?: [];

  $categories = db_fetch_all($pdo, 'SELECT DISTINCT category AS name FROM blog_posts WHERE category IS NOT NULL AND category <> "" ORDER BY category ASC');
}

$totalPages = max(1, (int) ceil($total / $limit));
include __DIR__ . '/_layout_top.php';
?>
<div class="filter-bar">
  <form class="filter-row" method="get">
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search blog posts...">
    </div>
    <div class="filter-group">
      <label class="filter-label">Category</label>
      <select class="form-select" name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e((string) $cat['name']) ?>" <?= $category === (string) $cat['name'] ? 'selected' : '' ?>><?= e((string) $cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select class="form-select" name="status">
        <option value="">All status</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Sort By</label>
      <select class="form-select" name="sort">
        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>Latest</option>
        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
        <option value="title_asc" <?= $sort === 'title_asc' ? 'selected' : '' ?>>Title A-Z</option>
        <option value="title_desc" <?= $sort === 'title_desc' ? 'selected' : '' ?>>Title Z-A</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Actions</label>
      <div class="d-flex gap-2">
        <button class="btn btn-primary-modern">Apply Filters</button>
        <a class="btn btn-secondary-modern" href="blog-edit.php">Add Blog</a>
      </div>
    </div>
  </form>
</div>

<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">Blog Posts (<?= $total ?>)</h5>
    <div class="widget-actions">
      <button class="btn btn-outline-secondary btn-sm">Export</button>
    </div>
  </div>
      <div class="table-responsive">
    <table class="modern-table" style="width: 100%;">
      <thead>
        <tr>
          <th>Title</th>
          <th>Category</th>
          <th>Author</th>
          <th>Status</th>
          <th>Published</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$pdo || !blog_table_exists($pdo)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">`blog_posts` table not found. Run blog migration first.</td>
          </tr>
        <?php elseif (!$rows): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No blog posts found.</td>
          </tr>
        <?php else: foreach ($rows as $row): ?>
          <tr>
            <td><?= e((string) $row['title']) ?></td>
            <td><?= e((string) ($row['category'] ?: '-')) ?></td>
            <td><?= e((string) ($row['author_name'] ?: 'Admin')) ?></td>
            <td>
              <span class="status-badge <?= $row['status']==='published'?'status-published':'status-draft' ?>">
                <?= e((string) $row['status']) ?>
              </span>
            </td>
            <td><?= !empty($row['published_at']) ? e((string) date('Y-m-d H:i', strtotime((string) $row['published_at']))) : '-' ?></td>
            <td>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="blog-edit.php?id=<?= (int) $row['id'] ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this blog?');">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <div class="modern-pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="page-item <?= $p === $page ? 'active' : '' ?>" href="?<?= http_build_query(['q'=>$q,'category'=>$category,'status'=>$status,'sort'=>$sort,'page'=>$p]) ?>">
        <span class="page-link"><?= $p ?></span>
      </a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
