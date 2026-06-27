<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'FAQ Pages';
$pdo = db();

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    if (($_POST['action'] ?? '') === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM pages WHERE id = :id AND page_group = :grp', [':id' => $id, ':grp' => 'faq']);
            cms_invalidate_page_cache();
            admin_flash('success', 'FAQ page deleted.');
        }
        header('Location: faq-pages.php');
        exit;
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = ['page_group = :grp'];
$params = [':grp' => 'faq'];
if ($q !== '') {
    $where[] = '(title LIKE :q OR slug LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if (in_array($status, ['draft', 'published'], true)) {
    $where[] = 'status = :st';
    $params[':st'] = $status;
}

$rows = $pdo ? db_fetch_all($pdo, 'SELECT * FROM pages WHERE ' . implode(' AND ', $where) . ' ORDER BY updated_at DESC, id DESC', $params) : [];

include __DIR__ . '/_layout_top.php';
?>
<div class="filter-bar">
  <form class="filter-row" method="get">
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search FAQ pages...">
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
      <label class="filter-label">Actions</label>
      <div class="d-flex gap-2">
        <button class="btn btn-primary-modern">Apply Filters</button>
        <a class="btn btn-secondary-modern" href="faq-page-edit.php">Add FAQ Page</a>
      </div>
    </div>
  </form>
</div>

<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">FAQ Pages (<?= count($rows) ?>)</h5>
    <div class="widget-actions">
      <button class="btn btn-outline-secondary btn-sm">Export</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Slug</th>
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
            <td>
              <span class="status-badge <?= $r['status']==='published'?'status-published':'status-draft' ?>">
                <?= e((string)$r['status']) ?>
              </span>
            </td>
            <td><?= e((string)$r['updated_at']) ?></td>
            <td>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="faq-page-edit.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this page?');">
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
            <td colspan="5" class="text-center text-muted py-4">No FAQ pages found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>

