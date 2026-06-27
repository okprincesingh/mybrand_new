<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title='Reviews';
$pdo=db();
if($pdo && $_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf_or_fail();
  $id=(int)($_POST['id']??0);$status=$_POST['status']??'';
  if(in_array($status,['approved','rejected','pending'],true)){
    $pdo->prepare('UPDATE product_reviews SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$id]);
    admin_flash('success','Review status updated.');
  }
  header('Location: reviews.php');exit;
}
$rows=$pdo?$pdo->query('SELECT r.*,p.name product_name FROM product_reviews r LEFT JOIN products p ON p.id=r.product_id ORDER BY r.created_at DESC')->fetchAll():[];
include __DIR__ . '/_layout_top.php';
?>
<div class="widget-card">
  <div class="widget-header">
    <h5 class="widget-title">Product Reviews (<?= count($rows) ?>)</h5>
    <div class="widget-actions">
      <button class="btn btn-outline-secondary btn-sm">Export</button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="modern-table" style="width: 100%;">
      <thead>
        <tr>
          <th>Product</th>
          <th>Reviewer</th>
          <th>Rating</th>
          <th>Review</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= e($r['product_name']??'-') ?></td>
            <td><?= e($r['reviewer_name']) ?></td>
            <td>
              <span class="badge bg-primary"><?= (int)$r['rating'] ?>/5</span>
            </td>
            <td><?= e($r['review_text']) ?></td>
            <td>
              <span class="status-badge <?= $r['status']==='approved'?'status-active':($r['status']==='rejected'?'status-inactive':'status-draft') ?>">
                <?= e($r['status']) ?>
              </span>
            </td>
            <td>
              <div class="d-flex gap-2">
                <form method="post" class="d-inline" onsubmit="return confirm('Update review status?')">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button name="status" value="approved" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-check-circle"></i> Approve
                  </button>
                  <button name="status" value="rejected" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle"></i> Reject
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">No reviews found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
