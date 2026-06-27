<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Product Editor';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$data = [
  'name' => '',
  'slug' => '',
  'category_id' => 0,
  'short_description' => '',
  'description' => '',
  'price' => '0.00',
  'stock' => 0,
  'status' => 'draft',
  'featured_image' => '',
  'offer_id' => null,
];

$existingImages = [];
$attrs = [];

if ($pdo && $id > 0) {
  $s = $pdo->prepare('SELECT * FROM products WHERE id = :id');
  $s->execute([':id' => $id]);
  $r = $s->fetch();
  if ($r) {
    $data = array_merge($data, $r);
  }

  $i = $pdo->prepare('SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order, id');
  $i->execute([':id' => $id]);
  $existingImages = $i->fetchAll() ?: [];

  $a = $pdo->prepare('SELECT * FROM product_attributes WHERE product_id = :pid ORDER BY id');
  $a->execute([':pid' => $id]);
  $attrs = $a->fetchAll() ?: [];
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();

  $name = trim((string)($_POST['name'] ?? ''));
  $slug = slugify(trim((string)(($_POST['slug'] ?? '') !== '' ? $_POST['slug'] : $name)));
  $category = (int)($_POST['category_id'] ?? 0);
  $subCategory = (int)($_POST['sub_category_id'] ?? 0);
  $finalCategoryId = $subCategory > 0 ? $subCategory : $category;

  $short = (string)($_POST['short_description'] ?? '');
  $desc = (string)($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $stock = (int)($_POST['stock'] ?? 0);
  $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';

  if ($id > 0) {
    $u = $pdo->prepare('UPDATE products SET category_id=:cid, name=:n, slug=:s, short_description=:sd, description=:d, price=:p, stock=:st, status=:status WHERE id=:id');
    $u->execute([':cid'=>$finalCategoryId?:null, ':n'=>$name, ':s'=>$slug, ':sd'=>$short, ':d'=>$desc, ':p'=>$price, ':st'=>$stock, ':status'=>$status, ':id'=>$id]);
    $pid = $id;
  } else {
    $u = $pdo->prepare('INSERT INTO products (category_id, name, slug, short_description, description, price, stock, status, is_active) VALUES (:cid,:n,:s,:sd,:d,:p,:st,:status,1)');
    $u->execute([':cid'=>$finalCategoryId?:null, ':n'=>$name, ':s'=>$slug, ':sd'=>$short, ':d'=>$desc, ':p'=>$price, ':st'=>$stock, ':status'=>$status]);
    $pid = (int)$pdo->lastInsertId();
  }

  if (!empty($_FILES['featured_image']['name'])) {
    $stored = store_uploaded_image($_FILES['featured_image'], 'products', 5_000_000);
    if ($stored) {
      $pdo->prepare('UPDATE products SET featured_image=:img WHERE id=:id')->execute([':img'=>$stored['public_path'], ':id'=>$pid]);
    }
  }

  if (!empty($_FILES['gallery']['name'][0])) {
    $maxSort = (int)(db_fetch_value($pdo, 'SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = :pid', [':pid' => $pid]) ?? -1);
    $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:pid,:img,:s)');

    foreach ($_FILES['gallery']['name'] as $idx => $nameFile) {
      if ($nameFile === '') continue;
      $file = [
        'name' => $_FILES['gallery']['name'][$idx],
        'type' => $_FILES['gallery']['type'][$idx],
        'tmp_name' => $_FILES['gallery']['tmp_name'][$idx],
        'error' => $_FILES['gallery']['error'][$idx],
        'size' => $_FILES['gallery']['size'][$idx],
      ];
      $stored = store_uploaded_image($file, 'products', 5_000_000);
      if ($stored) {
        $maxSort++;
        $imgStmt->execute([':pid' => $pid, ':img' => $stored['public_path'], ':s' => $maxSort]);
      }
    }
  }

  $pdo->prepare('DELETE FROM product_attributes WHERE product_id=:pid')->execute([':pid'=>$pid]);
  $attrKeys = $_POST['attr_key'] ?? [];
  $attrVals = $_POST['attr_value'] ?? [];
  $aStmt = $pdo->prepare('INSERT INTO product_attributes (product_id, attribute_key, attribute_value) VALUES (:pid,:k,:v)');
  foreach ($attrKeys as $idx => $key) {
    $k = trim((string)$key);
    $v = trim((string)($attrVals[$idx] ?? ''));
    if ($k !== '' && $v !== '') {
      $aStmt->execute([':pid'=>$pid, ':k'=>$k, ':v'=>$v]);
    }
  }

  admin_flash('success', 'Product saved.');
  header('Location: products.php');
  exit;
}

$parentCats = $pdo ? $pdo->query('SELECT id,name FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY name')->fetchAll() : [];
$subCats = $pdo ? $pdo->query('SELECT id,parent_id,name FROM categories WHERE is_active=1 AND parent_id IS NOT NULL ORDER BY name')->fetchAll() : [];

$selectedSubCategoryId = 0;
$selectedParentCategoryId = 0;
if ($pdo && (int)$data['category_id'] > 0) {
  $catRow = db_fetch_one($pdo, 'SELECT id,parent_id FROM categories WHERE id = :id LIMIT 1', [':id' => (int)$data['category_id']]);
  if ($catRow) {
    if (!empty($catRow['parent_id'])) {
      $selectedSubCategoryId = (int)$catRow['id'];
      $selectedParentCategoryId = (int)$catRow['parent_id'];
    } else {
      $selectedParentCategoryId = (int)$catRow['id'];
    }
  }
}

if (!$attrs) {
  $attrs = [
    ['attribute_key' => '', 'attribute_value' => ''],
    ['attribute_key' => '', 'attribute_value' => ''],
  ];
}

include __DIR__ . '/_layout_top.php';
?>
<div class="form-section">
  <h5 class="mb-4"><?= $id ? 'Edit Product' : 'Add New Product' ?></h5>
  <form method="post" enctype="multipart/form-data" class="form-row">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="form-group">
      <label class="form-label">Product Name</label>
      <input name="name" class="form-control" value="<?= e((string)$data['name']) ?>" required>
    </div>
    
    <div class="form-group">
      <label class="form-label">Slug</label>
      <input name="slug" class="form-control" value="<?= e((string)$data['slug']) ?>">
    </div>
    
    <div class="form-group">
      <label class="form-label">Category</label>
      <select name="category_id" id="category_id" class="form-select">
        <option value="0">Select Category</option>
        <?php foreach($parentCats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $selectedParentCategoryId===(int)$c['id']?'selected':'' ?>><?= e((string)$c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    
    <div class="form-group">
      <label class="form-label">Sub-category</label>
      <select name="sub_category_id" id="sub_category_id" class="form-select">
        <option value="0">Select Sub-category</option>
      </select>
    </div>
    
    <div class="form-group">
      <label class="form-label">Price</label>
      <input name="price" type="number" step="0.01" class="form-control" value="<?= e((string)$data['price']) ?>">
    </div>
    
    <div class="form-group">
      <label class="form-label">Stock</label>
      <input name="stock" type="number" class="form-control" value="<?= (int)$data['stock'] ?>">
    </div>
    
    <div class="form-group">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= $data['status']==='draft'?'selected':'' ?>>Draft</option>
        <option value="published" <?= $data['status']==='published'?'selected':'' ?>>Published</option>
      </select>
    </div>
    
    <div class="form-group">
      <label class="form-label">Short Description</label>
      <textarea id="product_short_description" name="short_description" class="form-control" rows="4"><?= e((string)$data['short_description']) ?></textarea>
    </div>
    
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea id="product_description" name="description" class="form-control" rows="6"><?= e((string)$data['description']) ?></textarea>
    </div>
    
    <div class="form-group">
      <label class="form-label">Featured Image</label>
      <input type="file" name="featured_image" id="featured_image_input" accept="image/jpeg,image/png,image/webp" class="form-control">
      <div class="mt-3 d-flex gap-3 align-items-start flex-wrap">
        <?php if (!empty($data['featured_image'])): ?>
          <div class="position-relative">
            <img id="featured_existing" src="<?= e(url((string)$data['featured_image'])) ?>" alt="featured" style="width:120px;height:120px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
            <span class="badge bg-primary position-absolute" style="top: -8px; right: -8px;">Current</span>
          </div>
        <?php endif; ?>
        <img id="featured_preview" src="" alt="featured preview" style="display:none;width:120px;height:120px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
      </div>
    </div>
    
    <div class="form-group">
      <label class="form-label">Gallery Images</label>
      <input type="file" name="gallery[]" id="gallery_input" accept="image/jpeg,image/png,image/webp" multiple class="form-control">
      <div id="gallery_preview" class="mt-3 d-flex gap-3 flex-wrap"></div>
      <?php if($existingImages): ?>
        <div class="mt-3">
          <small class="text-muted">Existing Gallery:</small>
          <div class="d-flex gap-3 flex-wrap mt-2">
            <?php foreach($existingImages as $im): ?>
              <div class="position-relative">
                <img src="<?= e(url((string)$im['image_path'])) ?>" alt="gallery" style="width:100px;height:100px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                <span class="badge bg-secondary position-absolute" style="top: -8px; right: -8px;">Existing</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
</div>

<div class="form-section mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Additional Information (Attributes)</h6>
    <button type="button" class="btn btn-primary-modern btn-sm" id="add_attr_btn">
      <i class="bi bi-plus-circle me-2"></i>Add Attribute
    </button>
  </div>

  <div class="table-responsive">
    <table class="modern-table" id="attrs_table">
      <thead>
        <tr>
          <th style="width:40%">Attribute Name</th>
          <th style="width:55%">Attribute Value</th>
          <th style="width:5%">Action</th>
        </tr>
      </thead>
      <tbody id="attrs_body">
        <?php foreach($attrs as $a): ?>
          <tr>
            <td><input name="attr_key[]" class="form-control" value="<?= e((string)$a['attribute_key']) ?>" placeholder="e.g. Skin Type"></td>
            <td><input name="attr_value[]" class="form-control" value="<?= e((string)$a['attribute_value']) ?>" placeholder="e.g. All Skin Types"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm remove-attr" title="Remove"><i class="bi bi-trash"></i></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="mt-4 d-flex gap-3">
    <button type="submit" class="btn btn-primary-modern">Save Product</button>
    <a class="btn btn-secondary-modern" href="products.php">Back to Products</a>
  </div>
</div>
</form>

<script>
(function () {
  const subCats = <?= json_encode($subCats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const selectedSub = <?= (int)$selectedSubCategoryId ?>;
  const categorySelect = document.getElementById('category_id');
  const subCategorySelect = document.getElementById('sub_category_id');

  function renderSubCategories(parentId) {
    if (!subCategorySelect) return;
    subCategorySelect.innerHTML = '<option value="0">Select Sub-category</option>';
    const pid = parseInt(parentId || '0', 10);
    if (!pid) return;

    subCats.forEach(function (cat) {
      if (parseInt(cat.parent_id, 10) !== pid) return;
      const opt = document.createElement('option');
      opt.value = String(cat.id);
      opt.textContent = cat.name;
      if (selectedSub && parseInt(cat.id, 10) === selectedSub) {
        opt.selected = true;
      }
      subCategorySelect.appendChild(opt);
    });
  }

  if (categorySelect) {
    renderSubCategories(categorySelect.value);
    categorySelect.addEventListener('change', function () {
      renderSubCategories(this.value);
    });
  }

  const featuredInput = document.getElementById('featured_image_input');
  const featuredPreview = document.getElementById('featured_preview');
  const galleryInput = document.getElementById('gallery_input');
  const galleryPreview = document.getElementById('gallery_preview');

  if (featuredInput && featuredPreview) {
    featuredInput.addEventListener('change', function () {
      const file = this.files && this.files[0];
      if (!file) {
        featuredPreview.style.display = 'none';
        featuredPreview.src = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = function (e) {
        featuredPreview.src = e.target.result;
        featuredPreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  }

  if (galleryInput && galleryPreview) {
    galleryInput.addEventListener('change', function () {
      galleryPreview.innerHTML = '';
      const files = this.files ? Array.from(this.files) : [];
      files.forEach(function (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.alt = 'gallery preview';
          img.style.width = '88px';
          img.style.height = '88px';
          img.style.objectFit = 'cover';
          img.style.borderRadius = '8px';
          img.style.border = '1px solid #ddd';
          galleryPreview.appendChild(img);
        };
        reader.readAsDataURL(file);
      });
    });
  }

  const attrsBody = document.getElementById('attrs_body');
  const addAttrBtn = document.getElementById('add_attr_btn');
  if (addAttrBtn && attrsBody) {
    addAttrBtn.addEventListener('click', function () {
      const tr = document.createElement('tr');
      tr.innerHTML = '<td><input name="attr_key[]" class="form-control" placeholder="e.g. Skin Type"></td><td><input name="attr_value[]" class="form-control" placeholder="e.g. All Skin Types"></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-attr">X</button></td>';
      attrsBody.appendChild(tr);
    });

    attrsBody.addEventListener('click', function (e) {
      const btn = e.target.closest('.remove-attr');
      if (!btn) return;
      const row = btn.closest('tr');
      if (row) row.remove();
    });
  }
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
const productForm = document.querySelector('form.form-row');
if (productForm) {
  productForm.addEventListener('submit', function () {
    if (window.tinymce) {
      tinymce.triggerSave();
    }
  });
}

if (window.tinymce) {
  tinymce.init({
    base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
    suffix: '.min',
    selector: '#product_short_description, #product_description',
    height: 260,
    menubar: false,
    branding: false,
    plugins: 'advlist autolink lists link image table code fullscreen preview searchreplace wordcount',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | code fullscreen preview',
    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre',
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
    setup: function (editor) {
      editor.on('change input undo redo', function () {
        editor.save();
      });
    }
  });
}
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>

