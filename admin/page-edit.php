<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Page SEO Editor';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$previousSlug = '';
$pageData = ['title'=>'','slug'=>'','status'=>'draft','meta_title'=>'','meta_description'=>'','meta_keywords'=>'','canonical_url'=>''];

if ($pdo && $id > 0) {
    $stmt = $pdo->prepare('SELECT p.id,p.title,p.slug,p.status,pm.meta_title,pm.meta_description,pm.meta_keywords,pm.canonical_url FROM pages p LEFT JOIN page_meta pm ON pm.page_id=p.id WHERE p.id=:id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $pageData = array_merge($pageData, $row);
        $previousSlug = (string) ($row['slug'] ?? '');
    }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $titleIn = trim((string) ($_POST['title'] ?? ''));
    $slug = slugify(trim((string) (($_POST['slug'] ?? '') !== '' ? $_POST['slug'] : $titleIn)));
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

    $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
    $metaDesc = trim((string) ($_POST['meta_description'] ?? ''));
    $metaKeywords = trim((string) ($_POST['meta_keywords'] ?? ''));
    $canonical = trim((string) ($_POST['canonical_url'] ?? ''));

    if ($id > 0) {
        $u = $pdo->prepare('UPDATE pages SET title=:t, slug=:s, status=:st WHERE id=:id');
        $u->execute([':t' => $titleIn, ':s' => $slug, ':st' => $status, ':id' => $id]);
        $pid = $id;
    } else {
        $u = $pdo->prepare('INSERT INTO pages (title, slug, status) VALUES (:t, :s, :st)');
        $u->execute([':t' => $titleIn, ':s' => $slug, ':st' => $status]);
        $pid = (int) $pdo->lastInsertId();
    }

    $m = $pdo->prepare('INSERT INTO page_meta (page_id,meta_title,meta_description,meta_keywords,canonical_url) VALUES (:pid,:mt,:md,:mk,:cu) ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), meta_description=VALUES(meta_description), meta_keywords=VALUES(meta_keywords), canonical_url=VALUES(canonical_url)');
    $m->execute([':pid' => $pid, ':mt' => $metaTitle, ':md' => $metaDesc, ':mk' => $metaKeywords, ':cu' => $canonical]);

    cms_invalidate_page_cache($slug);
    if ($previousSlug !== '' && $previousSlug !== $slug) {
        cms_invalidate_page_cache($previousSlug);
    }

    admin_flash('success', 'Page SEO saved.');
    header('Location: pages.php');
    exit;
}

include __DIR__ . '/_layout_top.php';
?>
<form method="post" class="card card-body">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Page Name</label>
      <input name="title" class="form-control" value="<?= e($pageData['title']) ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Page Slug</label>
      <input name="slug" class="form-control" value="<?= e($pageData['slug']) ?>" placeholder="about, contact, shop">
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= $pageData['status']==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $pageData['status']==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
  </div>

  <hr>
  <h6>SEO Fields</h6>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Meta Title</label>
      <input class="form-control" name="meta_title" value="<?= e($pageData['meta_title']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Canonical URL</label>
      <input class="form-control" name="canonical_url" value="<?= e($pageData['canonical_url']) ?>" placeholder="https://example.com/about">
    </div>
    <div class="col-md-6">
      <label class="form-label">Meta Keywords</label>
      <input class="form-control" name="meta_keywords" value="<?= e($pageData['meta_keywords']) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Meta Description</label>
      <textarea class="form-control" name="meta_description" rows="4"><?= e($pageData['meta_description']) ?></textarea>
    </div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary">Save SEO</button>
    <a href="pages.php" class="btn btn-outline-secondary">Back</a>
  </div>
</form>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
