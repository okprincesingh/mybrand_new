<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Categories';
$pdo = db();
if ($pdo && $_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf_or_fail();
    $action=$_POST['action']??'';
    if($action==='save'){
      $id=(int)($_POST['id']??0);$name=trim((string)$_POST['name']);$slug=slugify(trim((string)($_POST['slug']?:$name)));$parent=(int)($_POST['parent_id']??0);$sortOrder=(int)($_POST['sort_order']??0);$shopHeading=trim((string)($_POST['shop_heading']??''));$shopSubtitle=trim((string)($_POST['shop_subtitle']??''));
      $imagePath = trim((string)($_POST['existing_image_path'] ?? ''));
      if (!empty($_FILES['image']['name'])) {
        $stored = store_uploaded_image($_FILES['image'], 'categories', 5_000_000, false);
        if ($stored) {
          $imagePath = (string)$stored['public_path'];
        } else {
          admin_flash('danger', 'Image upload failed. Please upload jpg, jpeg, png or webp (max 5MB).');
          header('Location: categories.php' . ($id > 0 ? '?edit=' . $id : ''));
          exit;
        }
      }
      if($id>0){$st=$pdo->prepare('UPDATE categories SET parent_id=:p,name=:n,slug=:s,description=:d,is_active=:a,image_path=:img,sort_order=:so WHERE id=:id');$st->execute([':p'=>$parent?:null,':n'=>$name,':s'=>$slug,':d'=>(string)$_POST['description'],':a'=>(int)!empty($_POST['is_active']),':img'=>$imagePath,':so'=>$sortOrder,':id'=>$id]);$categoryId=$id;}
      else {$st=$pdo->prepare('INSERT INTO categories (parent_id,name,slug,description,is_active,image_path,sort_order) VALUES (:p,:n,:s,:d,:a,:img,:so)');$st->execute([':p'=>$parent?:null,':n'=>$name,':s'=>$slug,':d'=>(string)$_POST['description'],':a'=>(int)!empty($_POST['is_active']),':img'=>$imagePath,':so'=>$sortOrder]);$categoryId=(int)$pdo->lastInsertId();}
      if (!empty($categoryId)) {
        $k1 = 'category_shop_heading_' . $categoryId;
        $k2 = 'category_shop_subtitle_' . $categoryId;
        $set = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:k,:v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $set->execute([':k'=>$k1,':v'=>$shopHeading]);
        $set->execute([':k'=>$k2,':v'=>$shopSubtitle]);
        cms_invalidate_settings_cache($k1);
        cms_invalidate_settings_cache($k2);
      }
      admin_flash('success','Category saved.'); header('Location: categories.php'); exit;
    }
    if($action==='delete'){ $id=(int)$_POST['id']; $pdo->prepare('DELETE FROM categories WHERE id=:id')->execute([':id'=>$id]); if($id>0){$k1='category_shop_heading_'.$id;$k2='category_shop_subtitle_'.$id;$pdo->prepare('DELETE FROM site_settings WHERE setting_key IN (:k1,:k2)')->execute([':k1'=>$k1,':k2'=>$k2]);cms_invalidate_settings_cache($k1);cms_invalidate_settings_cache($k2);} admin_flash('success','Category deleted.'); header('Location: categories.php'); exit; }
}
$editId=(int)($_GET['edit']??0);$edit=['id'=>0,'name'=>'','slug'=>'','parent_id'=>0,'description'=>'','is_active'=>1,'image_path'=>'','sort_order'=>0,'shop_heading'=>'','shop_subtitle'=>''];
if($pdo && $editId>0){$s=$pdo->prepare('SELECT * FROM categories WHERE id=:id');$s->execute([':id'=>$editId]);$r=$s->fetch();if($r){$edit=$r;$edit['shop_heading']=(string)(cms_get_setting('category_shop_heading_'.$editId,'') ?? '');$edit['shop_subtitle']=(string)(cms_get_setting('category_shop_subtitle_'.$editId,'') ?? '');}}

$filterQ = trim((string)($_GET['q'] ?? ''));
$filterStatus = (string)($_GET['status'] ?? 'all');
$filterType = (string)($_GET['type'] ?? 'all');

$rows = [];
if ($pdo) {
  $sql = 'SELECT c.*,p.name parent_name FROM categories c LEFT JOIN categories p ON p.id=c.parent_id WHERE 1=1';
  $params = [];
  if ($filterQ !== '') {
    $sql .= ' AND (c.name LIKE :q_name OR c.slug LIKE :q_slug)';
    $params[':q_name'] = '%' . $filterQ . '%';
    $params[':q_slug'] = '%' . $filterQ . '%';
  }
  if ($filterStatus === 'active') {
    $sql .= ' AND c.is_active = 1';
  } elseif ($filterStatus === 'inactive') {
    $sql .= ' AND c.is_active = 0';
  }
  if ($filterType === 'main') {
    $sql .= ' AND c.parent_id IS NULL';
  } elseif ($filterType === 'sub') {
    $sql .= ' AND c.parent_id IS NOT NULL';
  }
  $sql .= ' ORDER BY c.parent_id ASC, c.sort_order ASC, c.name ASC';
  $st = $pdo->prepare($sql);
  foreach ($params as $k => $v) {
    $st->bindValue($k, $v, PDO::PARAM_STR);
  }
  $st->execute();
  $rows = $st->fetchAll() ?: [];
}

$parents=$pdo?$pdo->query('SELECT id,name FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC, name ASC')->fetchAll():[];
include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-12">
    <div class="form-section">
      <h5 class="mb-3"><?= $editId?'Edit':'Add' ?> Category</h5>
      <form method="post" enctype="multipart/form-data" class="form-row">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <input type="hidden" name="existing_image_path" value="<?= e((string)$edit['image_path']) ?>">

        <div class="form-group">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" value="<?= e($edit['name']) ?>" required>
        </div>
        
        <div class="form-group">
          <label class="form-label">Slug</label>
          <input name="slug" class="form-control" value="<?= e($edit['slug']) ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= (int)$edit['sort_order'] ?>">
        </div>
        
        <div class="form-group">
          <label class="form-label">Parent Category</label>
          <select name="parent_id" class="form-select">
            <option value="0">None (Main Category)</option>
            <?php foreach($parents as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)$edit['parent_id']===(int)$p['id']?'selected':'' ?>>
                <?= e($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div class="form-group">
          <label class="form-label">Category / Sub-category Image</label>
          <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if ((string)$edit['image_path'] !== ''): ?>
            <div class="mt-2">
              <img src="<?= e(url((string)$edit['image_path'])) ?>" alt="" style="width:120px;height:80px;object-fit:cover;border-radius:10px;border:1px solid var(--border);">
            </div>
          <?php endif; ?>
        </div>
        
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= e($edit['description']) ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Shop Category Heading (Optional)</label>
          <input name="shop_heading" class="form-control" value="<?= e((string)($edit['shop_heading'] ?? '')) ?>" placeholder="e.g. Private Label Skin Care Products">
        </div>

        <div class="form-group">
          <label class="form-label">Shop Category Subtitle (Optional)</label>
          <input name="shop_subtitle" class="form-control" value="<?= e((string)($edit['shop_subtitle'] ?? '')) ?>" placeholder="e.g. Shop our Product Samples Below!">
        </div>
        
        <div class="form-group">
          <label class="form-label">Status</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= !empty($edit['is_active'])?'checked':'' ?>>
            <label class="form-check-label">Active</label>
          </div>
        </div>
        
        <div class="form-group">
          <div class="d-flex gap-2">
            <button class="btn btn-primary-modern">Save Category</button>
            <?php if($editId): ?>
              <a href="categories.php" class="btn btn-secondary-modern">Cancel</a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <div class="col-lg-12">
    <div class="widget-card">
      <div class="widget-header">
        <h5 class="widget-title">Categories (<?= count($rows) ?>)</h5>
      </div>

      <form method="get" class="row g-2 mb-3">
        <div class="col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Search name or slug" value="<?= e($filterQ) ?>">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="all" <?= $filterStatus==='all'?'selected':'' ?>>All Status</option>
            <option value="active" <?= $filterStatus==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $filterStatus==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div class="col-md-3">
          <select name="type" class="form-select">
            <option value="all" <?= $filterType==='all'?'selected':'' ?>>All Types</option>
            <option value="main" <?= $filterType==='main'?'selected':'' ?>>Main Category</option>
            <option value="sub" <?= $filterType==='sub'?'selected':'' ?>>Sub-category</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-primary-modern w-100">Filter</button>
          <a href="categories.php" class="btn btn-secondary-modern">Reset</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="modern-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Parent</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($rows as $r): ?>
              <tr>
                <td>
                  <?php if ((string)($r['image_path'] ?? '') !== ''): ?>
                    <img src="<?= e(url((string)$r['image_path'])) ?>" alt="" style="width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                  <?php else: ?>
                    <span class="text-muted">No image</span>
                  <?php endif; ?>
                </td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['slug']) ?></td>
                <td><?= e($r['parent_name']??'-') ?></td>
                <td>
                  <span class="status-badge <?= !empty($r['is_active'])?'status-active':'status-inactive' ?>">
                    <?= !empty($r['is_active'])?'Active':'Inactive' ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="categories.php?edit=<?= (int)$r['id'] ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form class="d-inline" method="post" onsubmit="return confirm('Delete category?');">
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
            <?php if(!$rows): ?>
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No categories found for selected filters.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
