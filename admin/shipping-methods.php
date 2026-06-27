<?php
require_once __DIR__ . '/_init.php';
$adminUser = admin_require_auth();
$title = 'Shipping Methods';
$pdo = db();

if (!$pdo) {
    admin_flash_set('error', 'Database connection failed.');
    include __DIR__ . '/_layout_top.php';
    echo '<div class="alert alert-danger">Database unavailable.</div>';
    include __DIR__ . '/_layout_bottom.php';
    exit;
}

$pdo->exec("CREATE TABLE IF NOT EXISTS shipping_zones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_name VARCHAR(120) NOT NULL,
  country_code VARCHAR(5) NOT NULL DEFAULT 'IN',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shipping_zone_name_country (zone_name, country_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS shipping_zone_states (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_id BIGINT UNSIGNED NOT NULL,
  state_name VARCHAR(100) NOT NULL,
  state_code VARCHAR(20) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_zone_state (zone_id, state_name),
  INDEX idx_zone_state_name (state_name),
  CONSTRAINT fk_shipping_zone_states_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS shipping_provider_configs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_code VARCHAR(50) NOT NULL UNIQUE,
  provider_name VARCHAR(120) NOT NULL,
  api_base_url VARCHAR(255) NULL,
  api_key VARCHAR(255) NULL,
  api_secret VARCHAR(255) NULL,
  token VARCHAR(500) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("INSERT INTO shipping_provider_configs (provider_code, provider_name, api_base_url, is_active)
VALUES ('delhivery', 'Delhivery', 'https://track.delhivery.com', 0), ('shiprocket', 'Shiprocket', 'https://apiv2.shiprocket.in', 0)
ON DUPLICATE KEY UPDATE provider_name = VALUES(provider_name), api_base_url = VALUES(api_base_url)");

function shipping_clean_number($value, int $precision = 2): ?float
{
    if ($value === null || $value === '' || !is_numeric($value)) return null;
    return round((float) $value, $precision);
}

function shipping_parse_states(string $csv): array
{
    $states = array_filter(array_map('trim', explode(',', $csv)));
    return array_values(array_unique($states));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'save_zone') {
        $zoneId = (int) ($_POST['zone_id'] ?? 0);
        $zoneName = trim((string) ($_POST['zone_name'] ?? ''));
        $countryCode = strtoupper(trim((string) ($_POST['country_code'] ?? 'IN')));
        $statesCsv = trim((string) ($_POST['zone_states_csv'] ?? ''));

        if ($zoneName === '') {
            admin_flash_set('error', 'Zone name is required.');
            header('Location: shipping-methods.php');
            exit;
        }

        $pdo->beginTransaction();
        try {
            if ($zoneId > 0) {
                $stmt = $pdo->prepare('UPDATE shipping_zones SET zone_name = :zone_name, country_code = :country_code WHERE id = :id');
                $stmt->execute([':zone_name' => $zoneName, ':country_code' => $countryCode, ':id' => $zoneId]);
                $pdo->prepare('DELETE FROM shipping_zone_states WHERE zone_id = :zone_id')->execute([':zone_id' => $zoneId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO shipping_zones (zone_name, country_code, is_active) VALUES (:zone_name, :country_code, 1)');
                $stmt->execute([':zone_name' => $zoneName, ':country_code' => $countryCode]);
                $zoneId = (int) $pdo->lastInsertId();
            }

            $states = shipping_parse_states($statesCsv);
            if (!empty($states)) {
                $ins = $pdo->prepare('INSERT INTO shipping_zone_states (zone_id, state_name) VALUES (:zone_id, :state_name)');
                foreach ($states as $state) {
                    $ins->execute([':zone_id' => $zoneId, ':state_name' => $state]);
                }
            }

            $pdo->commit();
            admin_flash_set('success', $zoneId > 0 ? 'Shipping zone saved.' : 'Shipping zone created.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            admin_flash_set('error', 'Failed to save zone.');
        }

        header('Location: shipping-methods.php');
        exit;
    }

    if ($action === 'delete_zone') {
        $zoneId = (int) ($_POST['zone_id'] ?? 0);
        if ($zoneId > 0) {
            $pdo->prepare('UPDATE shipping_methods SET zone_id = NULL WHERE zone_id = :zone_id')->execute([':zone_id' => $zoneId]);
            $pdo->prepare('DELETE FROM shipping_zones WHERE id = :id')->execute([':id' => $zoneId]);
            admin_flash_set('success', 'Zone deleted.');
        }
        header('Location: shipping-methods.php');
        exit;
    }

    if ($action === 'save_provider') {
        $providerCode = trim((string) ($_POST['provider_code'] ?? ''));
        $apiBaseUrl = trim((string) ($_POST['api_base_url'] ?? ''));
        $apiKey = trim((string) ($_POST['api_key'] ?? ''));
        $apiSecret = trim((string) ($_POST['api_secret'] ?? ''));
        $token = trim((string) ($_POST['token'] ?? ''));
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        if ($providerCode === '') {
            admin_flash_set('error', 'Provider code is required.');
            header('Location: shipping-methods.php');
            exit;
        }

        $stmt = $pdo->prepare('UPDATE shipping_provider_configs SET api_base_url = :api_base_url, api_key = :api_key, api_secret = :api_secret, token = :token, is_active = :is_active WHERE provider_code = :provider_code');
        $stmt->execute([
            ':api_base_url' => $apiBaseUrl,
            ':api_key' => $apiKey,
            ':api_secret' => $apiSecret,
            ':token' => $token,
            ':is_active' => $isActive,
            ':provider_code' => $providerCode,
        ]);

        admin_flash_set('success', 'Provider configuration updated.');
        header('Location: shipping-methods.php?provider=' . urlencode($providerCode));
        exit;
    }

    if ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM shipping_methods WHERE id = :id')->execute([':id' => $id]);
        admin_flash_set('success', 'Shipping method deleted.');
        header('Location: shipping-methods.php');
        exit;
    }

    if ($action === 'toggle_status' && $id > 0) {
        $stmt = $pdo->prepare('SELECT status FROM shipping_methods WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $current = (string) ($stmt->fetchColumn() ?: 'inactive');
        $next = $current === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE shipping_methods SET status = :status WHERE id = :id')->execute([':status' => $next, ':id' => $id]);
        admin_flash_set('success', 'Shipping method status updated.');
        header('Location: shipping-methods.php');
        exit;
    }

    if ($action === 'save') {
        $methodName = trim((string) ($_POST['method_name'] ?? ''));
        $shippingType = trim((string) ($_POST['shipping_type'] ?? 'flat_rate'));
        $cost = shipping_clean_number($_POST['cost'] ?? null, 2);
        $minOrderAmount = shipping_clean_number($_POST['min_order_amount'] ?? null, 2);
        $weightMin = shipping_clean_number($_POST['weight_min'] ?? null, 3);
        $weightMax = shipping_clean_number($_POST['weight_max'] ?? null, 3);
        $priceMin = shipping_clean_number($_POST['price_min'] ?? null, 2);
        $priceMax = shipping_clean_number($_POST['price_max'] ?? null, 2);
        $status = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';
        $priority = max(0, (int) ($_POST['priority'] ?? 0));
        $estimatedDays = max(0, (int) ($_POST['estimated_delivery_days'] ?? 0)) ?: null;
        $zoneId = max(0, (int) ($_POST['zone_id'] ?? 0)) ?: null;
        $zoneStates = trim((string) ($_POST['zone_states'] ?? ''));
        $rateSource = (($_POST['rate_source'] ?? 'manual') === 'api') ? 'api' : 'manual';
        $providerCode = trim((string) ($_POST['provider_code'] ?? '')) ?: null;
        $providerServiceCode = trim((string) ($_POST['provider_service_code'] ?? '')) ?: null;
        $cacheTtl = max(60, (int) ($_POST['cache_ttl_seconds'] ?? 300));

        $errors = [];
        if ($methodName === '') $errors[] = 'Method name is required.';
        if ($rateSource === 'api' && !$providerCode) $errors[] = 'Select provider for API source.';
        if ($shippingType === 'flat_rate' && ($cost === null || $cost < 0)) $errors[] = 'Cost is required for flat rate.';
        if ($shippingType === 'free_shipping' && ($minOrderAmount === null || $minOrderAmount < 0)) $errors[] = 'Min order amount required for free shipping.';
        if ($shippingType === 'weight_based' && ($weightMin === null || $weightMax === null || $weightMin > $weightMax)) $errors[] = 'Valid weight range required.';
        if ($shippingType === 'price_based' && ($priceMin === null || $priceMax === null || $priceMin > $priceMax)) $errors[] = 'Valid price range required.';

        if (!empty($errors)) {
            admin_flash_set('error', implode(' ', $errors));
            header('Location: shipping-methods.php' . ($id > 0 ? '?edit=' . $id : ''));
            exit;
        }

        if ($shippingType === 'free_shipping') $cost = 0.00;

        $params = [
            ':method_name' => $methodName,
            ':shipping_type' => $shippingType,
            ':cost' => $cost ?? 0,
            ':min_order_amount' => $minOrderAmount,
            ':weight_min' => $weightMin,
            ':weight_max' => $weightMax,
            ':price_min' => $priceMin,
            ':price_max' => $priceMax,
            ':status' => $status,
            ':priority' => $priority,
            ':estimated_delivery_days' => $estimatedDays,
            ':zone_states' => $zoneStates !== '' ? $zoneStates : null,
            ':zone_id' => $zoneId,
            ':rate_source' => $rateSource,
            ':provider_code' => $providerCode,
            ':provider_service_code' => $providerServiceCode,
            ':cache_ttl_seconds' => $cacheTtl,
        ];

        if ($id > 0) {
            $sql = 'UPDATE shipping_methods SET method_name=:method_name, shipping_type=:shipping_type, cost=:cost, min_order_amount=:min_order_amount, weight_min=:weight_min, weight_max=:weight_max, price_min=:price_min, price_max=:price_max, status=:status, priority=:priority, estimated_delivery_days=:estimated_delivery_days, zone_states=:zone_states, zone_id=:zone_id, rate_source=:rate_source, provider_code=:provider_code, provider_service_code=:provider_service_code, cache_ttl_seconds=:cache_ttl_seconds WHERE id=:id';
            $params[':id'] = $id;
            $pdo->prepare($sql)->execute($params);
            admin_flash_set('success', 'Shipping method updated.');
        } else {
            $sql = 'INSERT INTO shipping_methods (method_name, shipping_type, cost, min_order_amount, weight_min, weight_max, price_min, price_max, status, priority, estimated_delivery_days, zone_states, zone_id, rate_source, provider_code, provider_service_code, cache_ttl_seconds) VALUES (:method_name,:shipping_type,:cost,:min_order_amount,:weight_min,:weight_max,:price_min,:price_max,:status,:priority,:estimated_delivery_days,:zone_states,:zone_id,:rate_source,:provider_code,:provider_service_code,:cache_ttl_seconds)';
            $pdo->prepare($sql)->execute($params);
            admin_flash_set('success', 'Shipping method created.');
        }

        header('Location: shipping-methods.php');
        exit;
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editZoneId = (int) ($_GET['edit_zone'] ?? 0);
$providerCodeParam = trim((string) ($_GET['provider'] ?? ''));

$edit = [
    'id' => 0, 'method_name' => '', 'shipping_type' => 'flat_rate', 'cost' => '0.00', 'min_order_amount' => '', 'weight_min' => '', 'weight_max' => '', 'price_min' => '', 'price_max' => '', 'status' => 'active', 'priority' => 0, 'estimated_delivery_days' => '', 'zone_states' => '', 'zone_id' => '', 'rate_source' => 'manual', 'provider_code' => '', 'provider_service_code' => '', 'cache_ttl_seconds' => 300,
];
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM shipping_methods WHERE id=:id');
    $stmt->execute([':id' => $editId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $edit = $row;
}

$editZone = ['id' => 0, 'zone_name' => '', 'country_code' => 'IN', 'states_csv' => ''];
if ($editZoneId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM shipping_zones WHERE id=:id');
    $stmt->execute([':id' => $editZoneId]);
    $zone = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($zone) {
        $editZone = $zone;
        $states = $pdo->prepare('SELECT state_name FROM shipping_zone_states WHERE zone_id=:zone_id ORDER BY state_name ASC');
        $states->execute([':zone_id' => $editZoneId]);
        $editZone['states_csv'] = implode(', ', array_map(static fn($r) => (string) $r['state_name'], $states->fetchAll(PDO::FETCH_ASSOC) ?: []));
    }
}

$zones = $pdo->query('SELECT z.*, (SELECT COUNT(*) FROM shipping_zone_states s WHERE s.zone_id=z.id) AS state_count FROM shipping_zones z ORDER BY z.zone_name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
$providers = $pdo->query('SELECT * FROM shipping_provider_configs ORDER BY provider_name ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
$methods = $pdo->query('SELECT m.*, z.zone_name FROM shipping_methods m LEFT JOIN shipping_zones z ON z.id=m.zone_id ORDER BY m.priority ASC, m.id ASC')->fetchAll(PDO::FETCH_ASSOC) ?: [];

$providerEdit = null;
if ($providerCodeParam !== '') {
    foreach ($providers as $providerRow) {
        if ((string) $providerRow['provider_code'] === $providerCodeParam) {
            $providerEdit = $providerRow;
            break;
        }
    }
}
if (!$providerEdit && !empty($providers)) {
    $providerEdit = $providers[0];
}

include __DIR__ . '/_layout_top.php';
?>
<div class="row g-4">
  <div class="col-lg-12">
    <div class="form-section">
      <h5 class="mb-3"><?= $editId > 0 ? 'Edit' : 'Add' ?> Shipping Method</h5>
      <form method="post" id="shipping-form" class="form-row" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
        <div class="form-group"><label class="form-label">Method Name</label><input type="text" name="method_name" id="method_name" class="form-control" value="<?= e((string) $edit['method_name']) ?>" required></div>
        <div class="form-group"><label class="form-label">Shipping Type</label><select name="shipping_type" id="shipping_type" class="form-select"><option value="flat_rate" <?= ($edit['shipping_type'] ?? '') === 'flat_rate' ? 'selected' : '' ?>>Flat Rate</option><option value="free_shipping" <?= ($edit['shipping_type'] ?? '') === 'free_shipping' ? 'selected' : '' ?>>Free Shipping</option><option value="weight_based" <?= ($edit['shipping_type'] ?? '') === 'weight_based' ? 'selected' : '' ?>>Weight Based</option><option value="price_based" <?= ($edit['shipping_type'] ?? '') === 'price_based' ? 'selected' : '' ?>>Price Based</option></select></div>
        <div class="form-group shipping-field" data-types="flat_rate,weight_based,price_based"><label class="form-label">Cost</label><input type="number" min="0" step="0.01" name="cost" id="cost" class="form-control" value="<?= e((string) $edit['cost']) ?>"></div>
        <div class="form-group shipping-field" data-types="free_shipping"><label class="form-label">Min Order Amount</label><input type="number" min="0" step="0.01" name="min_order_amount" id="min_order_amount" class="form-control" value="<?= e((string) ($edit['min_order_amount'] ?? '')) ?>"></div>
        <div class="form-group shipping-field" data-types="weight_based"><label class="form-label">Weight Min</label><input type="number" min="0" step="0.001" name="weight_min" id="weight_min" class="form-control" value="<?= e((string) ($edit['weight_min'] ?? '')) ?>"></div>
        <div class="form-group shipping-field" data-types="weight_based"><label class="form-label">Weight Max</label><input type="number" min="0" step="0.001" name="weight_max" id="weight_max" class="form-control" value="<?= e((string) ($edit['weight_max'] ?? '')) ?>"></div>
        <div class="form-group shipping-field" data-types="price_based"><label class="form-label">Price Min</label><input type="number" min="0" step="0.01" name="price_min" id="price_min" class="form-control" value="<?= e((string) ($edit['price_min'] ?? '')) ?>"></div>
        <div class="form-group shipping-field" data-types="price_based"><label class="form-label">Price Max</label><input type="number" min="0" step="0.01" name="price_max" id="price_max" class="form-control" value="<?= e((string) ($edit['price_max'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Zone</label><select name="zone_id" class="form-select"><option value="">All Zones</option><?php foreach ($zones as $z): ?><option value="<?= (int) $z['id'] ?>" <?= (int) ($edit['zone_id'] ?? 0) === (int) $z['id'] ? 'selected' : '' ?>><?= e((string) $z['zone_name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Fallback States CSV</label><input type="text" name="zone_states" class="form-control" value="<?= e((string) ($edit['zone_states'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Rate Source</label><select name="rate_source" id="rate_source" class="form-select"><option value="manual" <?= ($edit['rate_source'] ?? 'manual') === 'manual' ? 'selected' : '' ?>>Manual</option><option value="api" <?= ($edit['rate_source'] ?? 'manual') === 'api' ? 'selected' : '' ?>>API</option></select></div>
        <div class="form-group api-field"><label class="form-label">Provider</label><select name="provider_code" class="form-select"><option value="">Select Provider</option><?php foreach ($providers as $p): ?><option value="<?= e((string) $p['provider_code']) ?>" <?= ($edit['provider_code'] ?? '') === $p['provider_code'] ? 'selected' : '' ?>><?= e((string) $p['provider_name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group api-field"><label class="form-label">Provider Service Code</label><input type="text" name="provider_service_code" class="form-control" value="<?= e((string) ($edit['provider_service_code'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Cache TTL (seconds)</label><input type="number" min="60" name="cache_ttl_seconds" class="form-control" value="<?= (int) ($edit['cache_ttl_seconds'] ?? 300) ?>"></div>
        <div class="form-group"><label class="form-label">Priority</label><input type="number" min="0" name="priority" class="form-control" value="<?= (int) ($edit['priority'] ?? 0) ?>"></div>
        <div class="form-group"><label class="form-label">Estimated Delivery Days</label><input type="number" min="0" name="estimated_delivery_days" class="form-control" value="<?= e((string) ($edit['estimated_delivery_days'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= ($edit['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
        <div class="form-group"><div class="d-flex gap-2"><button class="btn btn-primary-modern" type="submit">Save Shipping Method</button><?php if ($editId > 0): ?><a class="btn btn-secondary-modern" href="shipping-methods.php">Cancel</a><?php endif; ?></div></div>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="widget-card"><div class="widget-header"><h5 class="widget-title"><?= $editZoneId > 0 ? 'Edit Zone' : 'Add Zone' ?></h5></div>
      <form method="post" class="p-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_zone"><input type="hidden" name="zone_id" value="<?= (int) ($editZone['id'] ?? 0) ?>">
        <div class="mb-2"><label class="form-label">Zone Name</label><input type="text" name="zone_name" class="form-control" required value="<?= e((string) ($editZone['zone_name'] ?? '')) ?>"></div>
        <div class="mb-2"><label class="form-label">Country Code</label><input type="text" name="country_code" class="form-control" value="<?= e((string) ($editZone['country_code'] ?? 'IN')) ?>"></div>
        <div class="mb-2"><label class="form-label">States (comma separated)</label><textarea name="zone_states_csv" class="form-control" rows="3"><?= e((string) ($editZone['states_csv'] ?? '')) ?></textarea></div>
        <button class="btn btn-primary btn-sm" type="submit">Save Zone</button>
        <?php if ($editZoneId > 0): ?><a href="shipping-methods.php" class="btn btn-secondary btn-sm">Cancel</a><?php endif; ?>
      </form>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="widget-card"><div class="widget-header"><h5 class="widget-title">Zones</h5></div>
      <div class="table-responsive p-3"><table class="modern-table"><thead><tr><th>Name</th><th>Country</th><th>States</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($zones as $z): ?>
          <tr>
            <td><?= e((string) $z['zone_name']) ?></td><td><?= e((string) $z['country_code']) ?></td><td><?= (int) $z['state_count'] ?></td>
            <td><div class="d-flex gap-2"><a class="btn btn-outline-primary btn-sm" href="shipping-methods.php?edit_zone=<?= (int) $z['id'] ?>"><i class="bi bi-pencil"></i></a>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this zone?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_zone"><input type="hidden" name="zone_id" value="<?= (int) $z['id'] ?>"><button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button></form>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>

  <div class="col-lg-12">
    <div class="widget-card"><div class="widget-header"><h5 class="widget-title">Provider Config + Ping</h5></div>
      <form method="post" class="p-3">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save_provider">
        <div class="row g-2 align-items-end">
          <div class="col-md-2"><label class="form-label">Provider</label><select name="provider_code" id="provider_code" class="form-select"><?php foreach ($providers as $p): ?><option value="<?= e((string) $p['provider_code']) ?>" <?= ($providerEdit && $providerEdit['provider_code'] === $p['provider_code']) ? 'selected' : '' ?>><?= e((string) $p['provider_name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-3"><label class="form-label">API Base URL</label><input type="text" name="api_base_url" class="form-control" value="<?= e((string) ($providerEdit['api_base_url'] ?? '')) ?>"></div>
          <div class="col-md-2"><label class="form-label">API Key</label><input type="text" name="api_key" class="form-control" value="<?= e((string) ($providerEdit['api_key'] ?? '')) ?>"></div>
          <div class="col-md-2"><label class="form-label">API Secret</label><input type="text" name="api_secret" class="form-control" value="<?= e((string) ($providerEdit['api_secret'] ?? '')) ?>"></div>
          <div class="col-md-2"><label class="form-label">Bearer Token</label><input type="text" name="token" class="form-control" value="<?= e((string) ($providerEdit['token'] ?? '')) ?>"></div>
          <div class="col-md-1"><div class="form-check mt-4"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="provider_active" <?= !empty($providerEdit['is_active']) ? 'checked' : '' ?>><label class="form-check-label" for="provider_active">Active</label></div></div>
        </div>
        <div class="mt-3 d-flex gap-2"><button class="btn btn-primary btn-sm" type="submit">Save Provider</button><button class="btn btn-outline-info btn-sm" type="button" id="provider-ping-btn">Test Ping</button><span id="provider-ping-result" class="small text-muted align-self-center"></span></div>
      </form>

      <div class="table-responsive p-3 pt-0"><table class="modern-table"><thead><tr><th>Provider</th><th>Base URL</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($providers as $p): ?>
          <tr>
            <td><?= e((string) $p['provider_name']) ?><br><small class="text-muted"><?= e((string) $p['provider_code']) ?></small></td>
            <td><?= e((string) ($p['api_base_url'] ?? '')) ?></td>
            <td><?= !empty($p['is_active']) ? 'Active' : 'Inactive' ?></td>
            <td><a href="shipping-methods.php?provider=<?= urlencode((string) $p['provider_code']) ?>" class="btn btn-outline-primary btn-sm">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>

  <div class="col-lg-12">
    <div class="widget-card"><div class="widget-header"><h5 class="widget-title">Shipping Methods (<?= count($methods) ?>)</h5></div>
      <div class="table-responsive"><table class="modern-table"><thead><tr><th>Priority</th><th>Method</th><th>Type</th><th>Zone</th><th>Source</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead><tbody>
      <?php foreach ($methods as $method): ?>
        <tr>
          <td><?= (int) $method['priority'] ?></td>
          <td><strong><?= e((string) $method['method_name']) ?></strong><?php if (!empty($method['estimated_delivery_days'])): ?><br><small class="text-muted"><?= (int) $method['estimated_delivery_days'] ?> day(s)</small><?php endif; ?></td>
          <td><?= e((string) $method['shipping_type']) ?></td>
          <td><?= e((string) ($method['zone_name'] ?? 'All')) ?></td>
          <td><?= e(strtoupper((string) ($method['rate_source'] ?? 'manual'))) ?><?php if (!empty($method['provider_code'])): ?><br><small class="text-muted"><?= e((string) $method['provider_code']) ?></small><?php endif; ?></td>
          <td><?= ((string) $method['shipping_type'] === 'free_shipping') ? 'Free' : ('$' . number_format((float) $method['cost'], 2)) ?></td>
          <td><span class="status-badge <?= $method['status'] === 'active' ? 'status-active' : 'status-inactive' ?>"><?= e(ucfirst((string) $method['status'])) ?></span></td>
          <td><div class="d-flex gap-2"><a class="btn btn-outline-primary btn-sm" href="shipping-methods.php?edit=<?= (int) $method['id'] ?>"><i class="bi bi-pencil"></i></a>
            <form method="post" class="d-inline"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= (int) $method['id'] ?>"><button class="btn btn-outline-warning btn-sm" type="submit"><?= $method['status'] === 'active' ? 'Disable' : 'Enable' ?></button></form>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete shipping method?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $method['id'] ?>"><button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button></form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
</div>

<script>
(function () {
    const form = document.getElementById('shipping-form');
    const shippingType = document.getElementById('shipping_type');
    const rateSource = document.getElementById('rate_source');
    const shippingFields = document.querySelectorAll('.shipping-field');
    const apiFields = document.querySelectorAll('.api-field');

    function updateVisibleFields() {
        const type = shippingType.value;
        shippingFields.forEach((field) => {
            const supported = (field.dataset.types || '').split(',');
            field.style.display = supported.includes(type) ? '' : 'none';
        });
        if (type === 'free_shipping') {
            const costInput = document.getElementById('cost');
            if (costInput) costInput.value = '0.00';
        }
        apiFields.forEach((field) => field.style.display = rateSource.value === 'api' ? '' : 'none');
    }

    form.addEventListener('submit', function (e) {
        const methodName = document.getElementById('method_name').value.trim();
        if (!methodName) {
            e.preventDefault();
            alert('Method name is required.');
        }
    });

    shippingType.addEventListener('change', updateVisibleFields);
    rateSource.addEventListener('change', updateVisibleFields);
    updateVisibleFields();

    const pingBtn = document.getElementById('provider-ping-btn');
    const pingResult = document.getElementById('provider-ping-result');
    const providerSelect = document.getElementById('provider_code');
    pingBtn?.addEventListener('click', function () {
        const providerCode = providerSelect ? providerSelect.value : '';
        if (!providerCode) {
            pingResult.textContent = 'Select provider first.';
            pingResult.className = 'small text-danger align-self-center';
            return;
        }

        pingResult.textContent = 'Pinging...';
        pingResult.className = 'small text-muted align-self-center';

        fetch('api/shipping-provider-ping.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= e(csrf_token()) ?>'
            },
            body: JSON.stringify({ provider_code: providerCode })
        })
        .then((r) => r.json())
        .then((data) => {
            pingResult.textContent = (data.message || 'Ping complete');
            pingResult.className = data.success ? 'small text-success align-self-center' : 'small text-danger align-self-center';
        })
        .catch(() => {
            pingResult.textContent = 'Ping request failed.';
            pingResult.className = 'small text-danger align-self-center';
        });
    });
})();
</script>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
