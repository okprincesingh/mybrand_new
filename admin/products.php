<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Products';
$pdo = db();

if($pdo && $_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf_or_fail();
  $action=$_POST['action']??'';
  if($action==='delete'){
    $id=(int)($_POST['id'] ?? 0);
    if($id > 0){
      try {
        $pdo->prepare('DELETE FROM products WHERE id=:id')->execute([':id'=>$id]);
        admin_flash('success','Product deleted.');
      } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000') {
          $pdo->prepare('UPDATE products SET is_active = 0, status = :status WHERE id = :id')
              ->execute([':status' => 'draft', ':id' => $id]);
          admin_flash('info','Product is used in orders, so it was archived instead of deleted.');
        } else {
          admin_flash('error','Unable to delete product: ' . $e->getMessage());
        }
      }
    }
    header('Location: products.php');
    exit;
  }
}

$q=trim((string)($_GET['q']??''));$cat=(int)($_GET['category_id']??0);$subCat=(int)($_GET['sub_category_id']??0);$status=trim((string)($_GET['status']??''));$sort=$_GET['sort']??'updated_desc';$page=max(1,(int)($_GET['page']??1));$limit=10;$offset=($page-1)*$limit;
$where=['1=1'];$params=[];
if($q!==''){ $where[]='(p.name LIKE :q OR p.slug LIKE :q)';$params[':q']="%$q%"; }
if($subCat>0){
  $where[]='p.category_id=:sub_cid';
  $params[':sub_cid']=$subCat;
}elseif($cat>0){
  $where[]='(p.category_id=:cid OR c.parent_id=:cid_parent)';
  $params[':cid']=$cat;
  $params[':cid_parent']=$cat;
}
if(in_array($status,['draft','published'],true)){ $where[]='p.status=:st';$params[':st']=$status; }
$order='p.updated_at DESC'; if($sort==='price_asc')$order='p.price ASC'; if($sort==='price_desc')$order='p.price DESC'; if($sort==='name_asc')$order='p.name ASC';
$w=implode(' AND ',$where);$total=0;$rows=[];
if($pdo){
  $c=$pdo->prepare("SELECT COUNT(*)
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    WHERE $w");
  $c->execute($params);$total=(int)$c->fetchColumn();
  $s=$pdo->prepare("SELECT
      p.*,
      (SELECT pi.image_path FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.sort_order ASC, pi.id ASC LIMIT 1) AS first_image_path,
      c.name AS category_name,
      c.parent_id AS category_parent_id,
      pc.name AS parent_category_name
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN categories pc ON pc.id=c.parent_id
    WHERE $w
    ORDER BY $order
    LIMIT :l OFFSET :o");
  foreach($params as $k=>$v){$s->bindValue($k,$v);} $s->bindValue(':l',$limit,PDO::PARAM_INT);$s->bindValue(':o',$offset,PDO::PARAM_INT);$s->execute();$rows=$s->fetchAll()?:[];
}
$cats=$pdo?$pdo->query('SELECT id,name FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY sort_order ASC, name ASC')->fetchAll():[];
$subCats=$pdo?$pdo->query('SELECT id,parent_id,name FROM categories WHERE is_active=1 AND parent_id IS NOT NULL ORDER BY sort_order ASC, name ASC')->fetchAll():[];
$totalPages=max(1,(int)ceil($total/$limit));
include __DIR__ . '/_layout_top.php';
?>
<div class="filter-bar">
  <form class="filter-row" method="get">
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search products...">
    </div>
    <div class="filter-group">
      <label class="filter-label">Category</label>
      <select class="form-select" name="category_id" id="filter_category_id">
        <option value="0">All categories</option>
        <?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Sub-category</label>
      <select class="form-select" name="sub_category_id" id="filter_sub_category_id">
        <option value="0">All sub-categories</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select class="form-select" name="status">
        <option value="">All status</option>
        <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Sort By</label>
      <select class="form-select" name="sort">
        <option value="updated_desc" <?= $sort==='updated_desc'?'selected':'' ?>>Latest</option>
        <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price Low-High</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price High-Low</option>
        <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>Name A-Z</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Actions</label>
      <div class="d-flex gap-2">
        <button class="btn btn-primary-modern">Apply Filters</button>
        <a class="btn btn-secondary-modern" href="product-edit.php">Add Product</a>
      </div>
    </div>
  </form>
</div>

<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">Products (<?= $total ?>)</h5>
    <div class="widget-actions">
      <button class="btn btn-outline-secondary btn-sm">Export</button>
    </div>
  </div>
      <div class="table-responsive">
    <table class="modern-table" style="width: 100%;">
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Sub-category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td>
              <?php
                $imgPath = trim((string)($r['featured_image'] ?? ''));
                if($imgPath === ''){ $imgPath = trim((string)($r['first_image_path'] ?? '')); }
                $imgSrc = '';
                if($imgPath !== ''){
                  if (preg_match('#^https?://#i', $imgPath) || str_starts_with($imgPath, '/')) {
                    $imgSrc = $imgPath;
                  } else {
                    $imgSrc = '../' . ltrim($imgPath, '/');
                  }
                }
              ?>
              <?php if($imgSrc !== ''): ?>
                <img src="<?= e($imgSrc) ?>" alt="<?= e($r['name']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
              <?php else: ?>
                <div style="width:56px;height:56px;border-radius:8px;border:1px solid #eee;background:#f8f9fa;color:#6c757d;display:flex;align-items:center;justify-content:center;font-size:11px;">No image</div>
              <?php endif; ?>
            </td>
            <td><?= e($r['name']) ?></td>
            <td><?= e(!empty($r['category_parent_id'])?($r['parent_category_name']??'-'):($r['category_name']??'-')) ?></td>
            <td><?= e(!empty($r['category_parent_id'])?($r['category_name']??'-'):'-') ?></td>
            <td><?= number_format((float)$r['price'],2) ?></td>
            <td><?= (int)$r['stock'] ?></td>
            <td>
              <span class="status-badge <?= $r['status']==='published'?'status-published':'status-draft' ?>">
                <?= e($r['status']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="product-edit.php?id=<?= (int)$r['id'] ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete product?');">
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
            <td colspan="8" class="text-center text-muted py-4">No products found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modern-pagination">
  <?php for($p=1;$p<=$totalPages;$p++): ?>
    <a class="page-item <?= $p===$page?'active':'' ?>" href="?<?= http_build_query(['q'=>$q,'category_id'=>$cat,'sub_category_id'=>$subCat,'status'=>$status,'sort'=>$sort,'page'=>$p]) ?>">
      <span class="page-link"><?= $p ?></span>
    </a>
  <?php endfor; ?>
</div>
<script>
  (function () {
    const subCategories = <?= json_encode(array_map(static function($row){ return ['id'=>(int)$row['id'],'parent_id'=>(int)$row['parent_id'],'name'=>(string)$row['name']]; }, $subCats), JSON_UNESCAPED_UNICODE) ?>;
    const categorySelect = document.getElementById('filter_category_id');
    const subCategorySelect = document.getElementById('filter_sub_category_id');
    const selectedSubCategoryId = <?= (int)$subCat ?>;

    function renderSubCategories() {
      if (!categorySelect || !subCategorySelect) return;
      const parentId = parseInt(categorySelect.value || '0', 10);
      subCategorySelect.innerHTML = '<option value="0">All sub-categories</option>';
      if (!parentId) return;

      subCategories.forEach(function (item) {
        if (parseInt(item.parent_id, 10) !== parentId) return;
        const opt = document.createElement('option');
        opt.value = String(item.id);
        opt.textContent = item.name;
        if (parseInt(item.id, 10) === selectedSubCategoryId) opt.selected = true;
        subCategorySelect.appendChild(opt);
      });
    }

    if (categorySelect && subCategorySelect) {
      categorySelect.addEventListener('change', function () {
        subCategorySelect.value = '0';
        renderSubCategories();
      });
      renderSubCategories();
    }
  })();
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
