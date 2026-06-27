<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/blog.php';
$adminUser = admin_require_auth();
$title = 'Blog Editor';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$data = [
  'title' => '',
  'slug' => '',
  'excerpt' => '',
  'content' => '',
  'meta_title' => '',
  'canonical_url' => '',
  'meta_keywords' => '',
  'meta_description' => '',
  'featured_image' => '',
  'category' => '',
  'author_name' => 'Admin',
  'tags' => '',
  'status' => 'draft',
  'published_at' => date('Y-m-d H:i:s'),
];

if ($pdo && blog_table_exists($pdo) && $id > 0) {
  $row = db_fetch_one($pdo, 'SELECT * FROM blog_posts WHERE id = :id LIMIT 1', [':id' => $id]);
  if ($row) {
    $data = array_merge($data, $row);
  }
}

if ($pdo && blog_table_exists($pdo) && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();

  $titleIn = trim((string) ($_POST['title'] ?? ''));
  $slug = slugify(trim((string) (($_POST['slug'] ?? '') !== '' ? $_POST['slug'] : $titleIn)));
  $category = trim((string) ($_POST['category'] ?? 'General'));
  $author = trim((string) ($_POST['author_name'] ?? 'Admin'));
  $featuredImage = trim((string) ($_POST['existing_featured_image'] ?? ''));
  $tags = trim((string) ($_POST['tags'] ?? ''));
  $excerpt = (string) ($_POST['excerpt'] ?? '');
  $content = (string) ($_POST['content'] ?? '');
  $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
  $canonicalUrl = trim((string) ($_POST['canonical_url'] ?? ''));
  $metaKeywords = trim((string) ($_POST['meta_keywords'] ?? ''));
  $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
  $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? (string) $_POST['status'] : 'draft';
  $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));
  $publishedAt = $publishedAtInput !== '' ? date('Y-m-d H:i:s', strtotime($publishedAtInput)) : date('Y-m-d H:i:s');

  if (!empty($_FILES['featured_image']['name'])) {
    $stored = store_uploaded_image($_FILES['featured_image'], 'blog', 5_000_000, false);
    if ($stored) {
      $featuredImage = (string) $stored['public_path'];
    } else {
      admin_flash('danger', 'Featured image upload failed. Please upload jpg, jpeg, png or webp (max 5MB).');
    }
  }

  if ($titleIn === '') {
    admin_flash('danger', 'Title is required.');
  } elseif ($slug === '') {
    admin_flash('danger', 'Slug is invalid.');
  } else {
    $existsParams = [':slug' => $slug];
    $existsSql = 'SELECT id FROM blog_posts WHERE slug = :slug';
    if ($id > 0) {
      $existsSql .= ' AND id <> :id';
      $existsParams[':id'] = $id;
    }
    $exists = db_fetch_one($pdo, $existsSql . ' LIMIT 1', $existsParams);

    if ($exists) {
      admin_flash('danger', 'Slug already exists. Please use another slug.');
    } else {
      if ($id > 0) {
        db_execute($pdo, 'UPDATE blog_posts SET title=:t,slug=:s,excerpt=:e,content=:c,meta_title=:mt,canonical_url=:cu,meta_keywords=:mk,meta_description=:md,featured_image=:fi,category=:cat,author_name=:a,tags=:tags,status=:st,published_at=:pa WHERE id=:id', [
          ':t'=>$titleIn, ':s'=>$slug, ':e'=>$excerpt, ':c'=>$content, ':mt'=>$metaTitle, ':cu'=>$canonicalUrl, ':mk'=>$metaKeywords, ':md'=>$metaDescription, ':fi'=>$featuredImage, ':cat'=>$category, ':a'=>$author, ':tags'=>$tags, ':st'=>$status, ':pa'=>$publishedAt, ':id'=>$id,
        ]);
      } else {
        db_execute($pdo, 'INSERT INTO blog_posts (title,slug,excerpt,content,meta_title,canonical_url,meta_keywords,meta_description,featured_image,category,author_name,tags,status,published_at) VALUES (:t,:s,:e,:c,:mt,:cu,:mk,:md,:fi,:cat,:a,:tags,:st,:pa)', [
          ':t'=>$titleIn, ':s'=>$slug, ':e'=>$excerpt, ':c'=>$content, ':mt'=>$metaTitle, ':cu'=>$canonicalUrl, ':mk'=>$metaKeywords, ':md'=>$metaDescription, ':fi'=>$featuredImage, ':cat'=>$category, ':a'=>$author, ':tags'=>$tags, ':st'=>$status, ':pa'=>$publishedAt,
        ]);
      }

      admin_flash('success', 'Blog saved.');
      header('Location: blogs.php');
      exit;
    }
  }

  $data = [
    'title' => $titleIn,
    'slug' => $slug,
    'excerpt' => $excerpt,
    'content' => $content,
    'meta_title' => $metaTitle,
    'canonical_url' => $canonicalUrl,
    'meta_keywords' => $metaKeywords,
    'meta_description' => $metaDescription,
    'featured_image' => $featuredImage,
    'category' => $category,
    'author_name' => $author,
    'tags' => $tags,
    'status' => $status,
    'published_at' => $publishedAt,
  ];
}

include __DIR__ . '/_layout_top.php';
?>
<form method="post" enctype="multipart/form-data" class="card card-body">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

  <?php if (!$pdo || !blog_table_exists($pdo)): ?>
    <div class="alert alert-warning">`blog_posts` table not found. Please run `database/migration_blog_posts.sql` first.</div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="<?= e((string) $data['title']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Slug</label><input name="slug" class="form-control" value="<?= e((string) $data['slug']) ?>" placeholder="blog-post-slug"></div>
    <div class="col-md-6"><label class="form-label">Meta Title</label><input name="meta_title" class="form-control" value="<?= e((string) $data['meta_title']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Canonical URL</label><input name="canonical_url" class="form-control" value="<?= e((string) $data['canonical_url']) ?>" placeholder="https://example.com/blog/your-post"></div>
    <div class="col-md-6"><label class="form-label">Meta Keywords</label><input name="meta_keywords" class="form-control" value="<?= e((string) $data['meta_keywords']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Meta Description</label><textarea name="meta_description" class="form-control" rows="3"><?= e((string) $data['meta_description']) ?></textarea></div>


    <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="<?= e((string) $data['category']) ?>" placeholder="Skincare"></div>
    <div class="col-md-4"><label class="form-label">Author Name</label><input name="author_name" class="form-control" value="<?= e((string) $data['author_name']) ?>"></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?= (string)$data['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= (string)$data['status']==='published'?'selected':'' ?>>Published</option></select></div>

    <div class="col-md-6">
      <label class="form-label">Featured Image Upload</label>
      <input type="hidden" name="existing_featured_image" value="<?= e((string) $data['featured_image']) ?>">
      <input type="file" name="featured_image" id="featured_image_input" class="form-control" accept="image/jpeg,image/png,image/webp">
      <div class="mt-2 d-flex gap-2 align-items-start flex-wrap">
        <?php if (!empty($data['featured_image'])): ?>
          <img id="featured_existing" src="<?= e(url((string) $data['featured_image'])) ?>" alt="featured" style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
        <?php endif; ?>
        <img id="featured_preview" src="" alt="featured preview" style="display:none;width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #ddd;">
      </div>
      <small class="text-muted d-block mt-1">Allowed: jpg, jpeg, png, webp (max 5MB)</small>
    </div>

    <div class="col-md-6"><label class="form-label">Published At</label><input name="published_at" type="datetime-local" class="form-control" value="<?= e(date('Y-m-d\\TH:i', strtotime((string) ($data['published_at'] ?: 'now')))) ?>"></div>

    <div class="col-12"><label class="form-label">Tags (comma separated)</label><input name="tags" class="form-control" value="<?= e((string) $data['tags']) ?>" placeholder="skincare, tips, beauty"></div>
    
    <div class="col-12"><label class="form-label">Short Description (TinyMCE)</label><textarea class="form-control js-editor" name="excerpt" rows="6"><?= e((string) $data['excerpt']) ?></textarea></div>
    <div class="col-12"><label class="form-label">Long Description (TinyMCE)</label><textarea class="form-control js-editor" name="content" rows="12"><?= e((string) $data['content']) ?></textarea></div>
  </div>

  <div class="mt-3">
    <button class="btn btn-primary">Save Blog</button>
    <a href="blogs.php" class="btn btn-outline-secondary">Back</a>
  </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  document.querySelectorAll('.js-editor').forEach(function (el, idx) {
    if (!el.id) {
      el.id = 'blog_editor_' + idx;
    }
  });

  if (window.tinymce) {
    tinymce.init({
      base_url: "https://cdn.jsdelivr.net/npm/tinymce@6.8.3",
      suffix: ".min",
      selector: '.js-editor',
      height: 280,
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

  const form = document.querySelector('form.card.card-body');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinymce) {
        tinymce.triggerSave();
      }
    });
  }

  const featuredInput = document.getElementById('featured_image_input');
  const featuredPreview = document.getElementById('featured_preview');
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
})();
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>

