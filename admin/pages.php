<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'SEO Pages';
$pdo = db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db_execute($pdo, 'DELETE FROM pages WHERE id = :id', [':id' => $id]);
        cms_invalidate_page_cache();
        admin_flash('success', 'Page deleted.');
        header('Location: pages.php');
        exit;
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$group = trim((string)($_GET['group'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$rows = [];
$total = 0;
if ($pdo) {
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = '(title LIKE :q OR slug LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }
    if (in_array($status, ['draft', 'published'], true)) {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if ($group !== '') {
        $where[] = 'page_group = :grp';
        $params[':grp'] = $group;
    }
    $w = implode(' AND ', $where);
    $total = (int) (db_fetch_value($pdo, "SELECT COUNT(*) FROM pages WHERE $w", $params) ?? 0);

    $stmt = $pdo->prepare("SELECT * FROM pages WHERE $w ORDER BY updated_at DESC LIMIT :l OFFSET :o");
    db_bind_values($stmt, $params);
    $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll() ?: [];
}

$totalPages = max(1, (int)ceil($total / $limit));
include __DIR__ . '/_layout_top.php';
?>
<div class="filter-bar">
  <form class="filter-row" method="get">
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search pages...">
    </div>
    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select name="status" class="form-select">
        <option value="">All status</option>
        <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Group</label>
      <select name="group" class="form-select">
        <option value="">All groups</option>
        <option value="general" <?= $group==='general'?'selected':'' ?>>General</option>
        <option value="why_choose_us" <?= $group==='why_choose_us'?'selected':'' ?>>Why Choose Us</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Actions</label>
      <div class="d-flex gap-2">
        <button class="btn btn-primary-modern">Apply Filters</button>
        <a class="btn btn-secondary-modern" href="page-edit.php">Add SEO Page</a>
      </div>
    </div>
  </form>
</div>

<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">SEO Pages (<?= $total ?>)</h5>
    <div class="widget-actions">
      <button class="btn btn-outline-secondary btn-sm">Export</button>
    </div>
  </div>
      <div class="table-responsive">
    <table class="modern-table" style="width: 100%;">
      <thead>
        <tr>
          <th>Title</th>
          <th>Slug</th>
          <th>Group</th>
          <th>Status</th>
          <th>Updated</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= e((string)$r['title']) ?></td>
            <td><?= e((string)$r['slug']) ?></td>
            <td><?= e((string)($r['page_group'] ?? 'general')) ?></td>
            <td>
              <span class="status-badge <?= $r['status']==='published'?'status-published':'status-draft' ?>">
                <?= e((string)$r['status']) ?>
              </span>
            </td>
            <td><?= e((string)$r['updated_at']) ?></td>
            <td>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="page-edit.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete page?')">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No pages found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modern-pagination">
  <?php for($p=1;$p<=$totalPages;$p++): ?>
    <a class="page-item <?= $p===$page?'active':'' ?>" href="?<?= http_build_query(['q'=>$q,'status'=>$status,'group'=>$group,'page'=>$p]) ?>">
      <span class="page-link"><?= $p ?></span>
    </a>
  <?php endfor; ?>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
