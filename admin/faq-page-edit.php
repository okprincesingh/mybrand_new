<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'FAQ Page Editor';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$previousSlug = '';
$pageData = [
  'title'=>'','slug'=>'','status'=>'draft',
  'content'=>'',
  'meta_title'=>'','meta_description'=>'','meta_keywords'=>'','canonical_url'=>''
];
$accordionRows = [];

if ($pdo && $id > 0) {
  $row = db_fetch_one($pdo, 'SELECT p.*, pm.meta_title, pm.meta_description, pm.meta_keywords, pm.canonical_url FROM pages p LEFT JOIN page_meta pm ON pm.page_id = p.id WHERE p.id = :id AND p.page_group = :grp LIMIT 1', [':id'=>$id, ':grp'=>'faq']);
  if ($row) {
    $pageData = array_merge($pageData, $row);
    $previousSlug = (string) ($row['slug'] ?? '');
    $accordionRows = db_fetch_all($pdo, 'SELECT * FROM faq_page_accordions WHERE page_id = :pid ORDER BY sort_order ASC, id ASC', [':pid' => $id]);

    // Backward compatibility: migrate legacy JSON content to accordions in UI preview only.
    if (!$accordionRows && is_string($pageData['content']) && str_starts_with(trim($pageData['content']), '{')) {
      $decoded = json_decode((string) $pageData['content'], true);
      if (is_array($decoded) && !empty($decoded['accordion']) && is_array($decoded['accordion'])) {
        foreach ($decoded['accordion'] as $i => $acc) {
          if (!is_array($acc)) continue;
          $accordionRows[] = [
            'title' => (string) ($acc['title'] ?? ''),
            'body_html' => (string) ($acc['body_html'] ?? ''),
            'is_open' => !empty($acc['open']) ? 1 : 0,
            'is_active' => 1,
            'sort_order' => $i,
          ];
        }
      }
      if (is_array($decoded) && isset($decoded['breadcrumb']['page_title']) && $pageData['title'] === '') {
        $pageData['title'] = (string) $decoded['breadcrumb']['page_title'];
      }
      $pageData['content'] = '';
    }
  }
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_fail();
  $titleIn = trim((string)($_POST['title'] ?? ''));
  $slug = slugify(trim((string)(($_POST['slug'] ?? '') !== '' ? $_POST['slug'] : $titleIn)));
  $status = in_array($_POST['status'] ?? '', ['draft','published'], true) ? $_POST['status'] : 'draft';
  $content = (string)($_POST['content'] ?? '');

  $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
  $metaDesc = trim((string)($_POST['meta_description'] ?? ''));
  $metaKeywords = trim((string)($_POST['meta_keywords'] ?? ''));
  $canonical = trim((string)($_POST['canonical_url'] ?? ''));

  if ($id > 0) {
    db_execute($pdo, 'UPDATE pages SET title=:t, slug=:s, status=:st, content=:c, page_group=:grp, template_key=:tpl WHERE id=:id', [
      ':t'=>$titleIn, ':s'=>$slug, ':st'=>$status, ':c'=>$content, ':grp'=>'faq', ':tpl'=>'faq', ':id'=>$id
    ]);
    $pid = $id;
  } else {
    db_execute($pdo, 'INSERT INTO pages (title, slug, content, status, page_group, template_key) VALUES (:t,:s,:c,:st,:grp,:tpl)', [
      ':t'=>$titleIn, ':s'=>$slug, ':c'=>$content, ':st'=>$status, ':grp'=>'faq', ':tpl'=>'faq'
    ]);
    $pid = (int)$pdo->lastInsertId();
  }

  db_execute($pdo, 'INSERT INTO page_meta (page_id,meta_title,meta_description,meta_keywords,canonical_url) VALUES (:pid,:mt,:md,:mk,:cu) ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),meta_keywords=VALUES(meta_keywords),canonical_url=VALUES(canonical_url)', [
    ':pid'=>$pid, ':mt'=>$metaTitle, ':md'=>$metaDesc, ':mk'=>$metaKeywords, ':cu'=>$canonical
  ]);

  db_execute($pdo, 'DELETE FROM faq_page_accordions WHERE page_id = :pid', [':pid' => $pid]);

  $accRows = $_POST['accordion'] ?? [];
  if (is_array($accRows)) {
    $sort = 0;
    foreach ($accRows as $acc) {
      if (!is_array($acc)) continue;
      $accTitle = trim((string)($acc['title'] ?? ''));
      $accBody = (string)($acc['body_html'] ?? '');
      $accOpen = isset($acc['is_open']) ? 1 : 0;
      $accActive = isset($acc['is_active']) ? 1 : 0;
      if ($accTitle === '' && trim(strip_tags($accBody)) === '') {
        continue;
      }
      db_execute($pdo, 'INSERT INTO faq_page_accordions (page_id, title, body_html, is_open, is_active, sort_order) VALUES (:pid,:t,:b,:o,:a,:s)', [
        ':pid' => $pid,
        ':t' => $accTitle,
        ':b' => $accBody,
        ':o' => $accOpen,
        ':a' => $accActive,
        ':s' => $sort,
      ]);
      $sort++;
    }
  }

  cms_invalidate_page_cache($slug);
  if ($previousSlug !== '' && $previousSlug !== $slug) {
    cms_invalidate_page_cache($previousSlug);
  }

  admin_flash('success', 'FAQ page saved.');
  header('Location: faq-pages.php');
  exit;
}

if (!$accordionRows) {
  $accordionRows = [
    ['title' => 'Introduction', 'body_html' => '<p>Type your accordion content.</p>', 'is_open' => 1, 'is_active' => 1, 'sort_order' => 0],
  ];
}

include __DIR__ . '/_layout_top.php';
?>
<form method="post" class="card card-body" id="faqPageForm">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Page Name (Breadcrumb Title)</label><input class="form-control" name="title" value="<?= e((string)$pageData['title']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?= e((string)$pageData['slug']) ?>" placeholder="<?= e((string)$pageData['slug']) ?>"></div>

    <h6>SEO Fields</h6>
    <div class="col-md-6"><label class="form-label">Meta Title</label><input class="form-control" name="meta_title" value="<?= e((string)$pageData['meta_title']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Canonical URL</label><input class="form-control" name="canonical_url" value="<?= e((string)$pageData['canonical_url']) ?>" placeholder="https://example.com/why-page"></div>
    <div class="col-md-6"><label class="form-label">Meta Keywords</label><input class="form-control" name="meta_keywords" value="<?= e((string)$pageData['meta_keywords']) ?>"></div>
    <div class="col-md-6"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="4"><?= e((string)$pageData['meta_description']) ?></textarea></div>
  
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?= $pageData['status']==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $pageData['status']==='published'?'selected':'' ?>>Published</option></select></div>
    <div class="col-12"><label class="form-label">Top Description (TinyMCE)</label><textarea class="form-control js-editor" name="content" rows="8"><?= e((string)$pageData['content']) ?></textarea></div>
  </div>

  <hr>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Accordions (Multiple)</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addAccordionBtn">Add Accordion</button>
  </div>
  <div id="accordionContainer" class="d-grid gap-3">
    <?php foreach ($accordionRows as $i => $acc): ?>
      <div class="border rounded p-3 accordion-item-admin">
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Title</label><input class="form-control" name="accordion[<?= (int)$i ?>][title]" value="<?= e((string)($acc['title'] ?? '')) ?>"></div>
          <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="accordion[<?= (int)$i ?>][is_open]" <?= !empty($acc['is_open']) ? 'checked' : '' ?>><label class="form-check-label">Open</label></div></div>
          <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="accordion[<?= (int)$i ?>][is_active]" <?= !isset($acc['is_active']) || !empty($acc['is_active']) ? 'checked' : '' ?>><label class="form-check-label">Active</label></div></div>
          <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger remove-accordion">X</button></div>
          <div class="col-12"><label class="form-label">Content (TinyMCE)</label><textarea class="form-control js-editor" name="accordion[<?= (int)$i ?>][body_html]" rows="6"><?= e((string)($acc['body_html'] ?? '')) ?></textarea></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

 

  <div class="mt-3"><button class="btn btn-primary">Save FAQ Page</button> <a href="faq-pages.php" class="btn btn-outline-secondary">Back</a></div>
</form>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
(function () {
  let editorCounter = 0;

  function ensureEditorId(el) {
    if (!el) return;
    if (!el.id) {
      el.id = 'faq_editor_' + (editorCounter++);
    }
  }

  function initEditor(el) {
    ensureEditorId(el);
    if (!el || !window.tinymce) return;
    if (tinymce.get(el.id)) return;

    tinymce.init({
      base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3',
      suffix: '.min',
      selector: '#' + el.id,
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

  document.querySelectorAll('.js-editor').forEach(initEditor);

  const form = document.getElementById('faqPageForm');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinymce) {
        tinymce.triggerSave();
      }
    });
  }

  const container = document.getElementById('accordionContainer');
  const addBtn = document.getElementById('addAccordionBtn');

  function nextIndex() {
    return container.querySelectorAll('.accordion-item-admin').length;
  }

  addBtn.addEventListener('click', function () {
    const idx = nextIndex();
    const wrapper = document.createElement('div');
    wrapper.className = 'border rounded p-3 accordion-item-admin';
    wrapper.innerHTML = `
      <div class="row g-3">
        <div class="col-md-7"><label class="form-label">Title</label><input class="form-control" name="accordion[${idx}][title]"></div>
        <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="accordion[${idx}][is_open]"><label class="form-check-label">Open</label></div></div>
        <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="accordion[${idx}][is_active]" checked><label class="form-check-label">Active</label></div></div>
        <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-sm btn-outline-danger remove-accordion">X</button></div>
        <div class="col-12"><label class="form-label">Content (TinyMCE)</label><textarea class="form-control js-editor" name="accordion[${idx}][body_html]" rows="6"></textarea></div>
      </div>
    `;
    container.appendChild(wrapper);
    initEditor(wrapper.querySelector('.js-editor'));
  });

  container.addEventListener('click', function (e) {
    const btn = e.target.closest('.remove-accordion');
    if (!btn) return;
    const item = btn.closest('.accordion-item-admin');
    if (!item) return;

    item.querySelectorAll('.js-editor').forEach(function (el) {
      if (el.id && window.tinymce) {
        const instance = tinymce.get(el.id);
        if (instance) instance.remove();
      }
    });

    item.remove();
  });
})();
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>



