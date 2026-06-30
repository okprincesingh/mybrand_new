<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/enquiries.php';

$adminUser = admin_require_auth();
$title = 'Enquiries';
$pdo = db();

if ($pdo) {
    enquiries_ensure_table($pdo);
}

if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, ['new', 'contacted', 'closed'], true)) {
        $stmt = $pdo->prepare('UPDATE contact_enquiries SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
        admin_flash('success', 'Enquiry status updated.');
    }
    header('Location: enquiries.php');
    exit;
}

$type = trim((string) ($_GET['type'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$where = [];
$params = [];
if ($type !== '' && in_array($type, ['contact', 'consultation', 'product'], true)) {
    $where[] = 'enquiry_type = :type';
    $params[':type'] = $type;
}
if ($status !== '' && in_array($status, ['new', 'contacted', 'closed'], true)) {
    $where[] = 'status = :status';
    $params[':status'] = $status;
}

$sql = 'SELECT * FROM contact_enquiries';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$rows = [];
if ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

include __DIR__ . '/_layout_top.php';
?>
<div class="widget-card mb-4">
  <div class="widget-header">
    <h5 class="widget-title">Enquiries (<?= count($rows) ?>)</h5>
  </div>
  <form class="row g-2 align-items-end" method="get">
    <div class="col-md-3">
      <label class="form-label">Type</label>
      <select class="form-select" name="type">
        <option value="">All Types</option>
        <?php foreach (['contact' => 'Contact', 'consultation' => 'Consultation', 'product' => 'Product'] as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <option value="">All Statuses</option>
        <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-primary-modern" type="submit">Filter</button>
      <a class="btn btn-secondary-modern" href="enquiries.php">Reset</a>
    </div>
  </form>
</div>

<div class="widget-card">
  <div class="table-responsive">
    <table class="modern-table" style="width:100%;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Customer</th>
          <th>Details</th>
          <th>Mail</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <?php
            $statusClass = $row['status'] === 'closed' ? 'status-inactive' : ($row['status'] === 'contacted' ? 'status-active' : 'status-draft');
            $typeLabel = ucfirst((string) $row['enquiry_type']);
          ?>
          <tr>
            <td><?= e(date('d M Y, h:i A', strtotime((string) $row['created_at']))) ?></td>
            <td><span class="badge bg-primary"><?= e($typeLabel) ?></span></td>
            <td>
              <strong><?= e((string) $row['name']) ?></strong><br>
              <a href="mailto:<?= e((string) $row['email']) ?>"><?= e((string) $row['email']) ?></a>
              <?php if (!empty($row['phone'])): ?><br><span class="text-muted"><?= e((string) $row['phone']) ?></span><?php endif; ?>
              <?php if (!empty($row['country'])): ?><br><span class="text-muted"><?= e((string) $row['country']) ?></span><?php endif; ?>
            </td>
            <td style="min-width:280px;">
              <?php if (!empty($row['subject'])): ?><strong><?= e((string) $row['subject']) ?></strong><br><?php endif; ?>
              <?php if (!empty($row['product_id'])): ?><span class="text-muted">Product:</span> <?= e((string) $row['product_id']) ?><br><?php endif; ?>
              <?php if (!empty($row['bulk_quantity'])): ?><span class="text-muted">Qty:</span> <?= e((string) $row['bulk_quantity']) ?><br><?php endif; ?>
              <?php if (!empty($row['address'])): ?><span class="text-muted">Address:</span> <?= nl2br(e((string) $row['address'])) ?><br><?php endif; ?>
              <?php if (!empty($row['message'])): ?><span class="text-muted">Message:</span> <?= nl2br(e((string) $row['message'])) ?><br><?php endif; ?>
              <?php if (!empty($row['requirements'])): ?><span class="text-muted">Requirements:</span> <?= nl2br(e((string) $row['requirements'])) ?><?php endif; ?>
            </td>
            <td>
              <span class="badge <?= !empty($row['admin_mail_sent']) ? 'bg-success' : 'bg-danger' ?>">Admin <?= !empty($row['admin_mail_sent']) ? 'sent' : 'failed' ?></span><br>
              <span class="badge <?= !empty($row['user_mail_sent']) ? 'bg-success' : 'bg-warning text-dark' ?> mt-1">User <?= !empty($row['user_mail_sent']) ? 'sent' : 'failed' ?></span>
              <?php if (!empty($row['mail_status'])): ?>
                <div class="small text-muted mt-2" style="max-width:220px;white-space:normal;"><?= e((string) $row['mail_status']) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="status-badge <?= e($statusClass) ?>"><?= e((string) $row['status']) ?></span></td>
            <td>
              <form method="post" class="d-flex flex-column gap-2">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-outline-primary btn-sm" name="status" value="contacted" type="submit">Contacted</button>
                <button class="btn btn-outline-success btn-sm" name="status" value="closed" type="submit">Closed</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No enquiries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
