<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Notifications';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            mark_notification_as_read($id);
        }
        header('Location: notifications.php');
        exit;
    }
    if ($action === 'mark_all_read') {
        mark_all_notifications_as_read();
        header('Location: notifications.php');
        exit;
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$read = trim((string)($_GET['read'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(title LIKE :q OR message LIKE :q2)';
    $params[':q'] = '%' . $q . '%';
    $params[':q2'] = '%' . $q . '%';
}
if (in_array($type, ['info', 'success', 'warning', 'error'], true)) {
    $where[] = 'type = :type';
    $params[':type'] = $type;
}
if ($read === 'unread') {
    $where[] = 'is_read = 0';
}
if ($read === 'read') {
    $where[] = 'is_read = 1';
}
if ($dateFrom !== '') {
    $where[] = 'DATE(created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'DATE(created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$notifications = [];
if ($pdo && ensure_admin_notifications_table($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM admin_notifications {$whereSql} ORDER BY created_at DESC LIMIT 300");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $notifications = $stmt->fetchAll() ?: [];
}

include __DIR__ . '/_layout_top.php';
?>

<div class="widget-card mb-3">
  <div class="widget-header">
    <h5 class="widget-title mb-0">All Notifications</h5>
    <form method="post" class="d-inline">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn-sm btn-primary">Mark All Read</button>
    </form>
  </div>

  <form method="get" class="filter-row mb-3" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;">
    <div class="filter-group">
      <label class="filter-label">Search</label>
      <input type="text" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search title/message">
    </div>
    <div class="filter-group">
      <label class="filter-label">Type</label>
      <select class="form-select" name="type">
        <option value="">All</option>
        <?php foreach (['info','success','warning','error'] as $t): ?>
          <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">Status</label>
      <select class="form-select" name="read">
        <option value="">All</option>
        <option value="unread" <?= $read === 'unread' ? 'selected' : '' ?>>Unread</option>
        <option value="read" <?= $read === 'read' ? 'selected' : '' ?>>Read</option>
      </select>
    </div>
    <div class="filter-group">
      <label class="filter-label">From</label>
      <input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>">
    </div>
    <div class="filter-group">
      <label class="filter-label">To</label>
      <input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>">
    </div>
    <div class="filter-group d-flex gap-2">
      <button type="submit" class="btn btn-primary">Filter</button>
      <a href="notifications.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="modern-table">
      <thead>
        <tr>
          <th>Title</th>
          <th>Message</th>
          <th>Type</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($notifications as $n): ?>
          <tr>
            <td><strong><?= e((string)$n['title']) ?></strong></td>
            <td style="max-width:420px;"><?= e((string)$n['message']) ?></td>
            <td><span class="status-badge status-<?= e((string)$n['type']) ?>"><?= e((string)$n['type']) ?></span></td>
            <td><?= e((string)$n['created_at']) ?></td>
            <td><?= (int)$n['is_read'] === 0 ? '<span class="status-badge status-pending">Unread</span>' : '<span class="status-badge status-active">Read</span>' ?></td>
            <td>
              <div class="d-flex gap-2">
                <?php if (!empty($n['action_url'])): ?>
                  <a class="btn btn-sm btn-primary" href="<?= e((string)$n['action_url']) ?>"><?= e((string)($n['action_text'] ?: 'View')) ?></a>
                <?php endif; ?>
                <?php if ((int)$n['is_read'] === 0): ?>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="mark_read">
                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Mark Read</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$notifications): ?>
          <tr><td colspan="6" class="text-center text-muted">No notifications found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_bottom.php'; ?>
