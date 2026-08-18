<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';

$adminUser = admin_require_auth();
$title = 'Our Golbal Footprints';
$pdo = db();
if (!$pdo || !cms_ensure_home_global_footprint_table($pdo)) {
    http_response_code(500);
    exit('Unable to prepare Global Footprints storage.');
}

function global_footprint_geocode(string $place): ?array
{
    $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&q=' . rawurlencode($place);
    $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 8, 'header' => "User-Agent: MyBrandPlease-GlobalFootprints/1.0\r\nAccept: application/json\r\n"]]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }
    $result = json_decode($response, true);
    if (!is_array($result) || empty($result[0]) || !isset($result[0]['lat'], $result[0]['lon'])) {
        return null;
    }
    return [
        'latitude' => (float) $result[0]['lat'],
        'longitude' => (float) $result[0]['lon'],
        'formatted_address' => (string) ($result[0]['display_name'] ?? $place),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_execute($pdo, 'DELETE FROM home_global_footprint_locations WHERE id = :id', [':id' => $id]);
            cms_invalidate_home_global_footprint_cache();
            admin_flash('success', 'Location removed.');
        }
        header('Location: home-global-footprints.php');
        exit;
    }

    $place = trim((string) ($_POST['location_name'] ?? ''));
    if ($place === '') {
        admin_flash('danger', 'Enter a city, region, or country to add a location.');
        header('Location: home-global-footprints.php');
        exit;
    }
    $geocoded = global_footprint_geocode($place);
    if ($geocoded === null) {
        admin_flash('danger', 'We could not find that location. Use a more specific value, such as "Dubai, United Arab Emirates".');
        header('Location: home-global-footprints.php');
        exit;
    }
    $position = cms_global_footprint_map_position($geocoded['latitude'], $geocoded['longitude']);
    db_execute($pdo, 'INSERT INTO home_global_footprint_locations (location_name, formatted_address, latitude, longitude, map_top, map_left, pin_height, sort_order, is_active) VALUES (:name, :address, :lat, :lng, :top, :left, :height, :sort, 1)', [
        ':name' => $place, ':address' => $geocoded['formatted_address'], ':lat' => $geocoded['latitude'], ':lng' => $geocoded['longitude'], ':top' => $position['top'], ':left' => $position['left'], ':height' => max(20, min(140, (int) ($_POST['pin_height'] ?? 55))), ':sort' => (int) ($_POST['sort_order'] ?? 0),
    ]);
    cms_invalidate_home_global_footprint_cache();
    admin_flash('success', 'Location found and pinned automatically.');
    header('Location: home-global-footprints.php');
    exit;
}

$rows = db_fetch_all($pdo, 'SELECT * FROM home_global_footprint_locations ORDER BY sort_order ASC, id ASC');
include __DIR__ . '/_layout_top.php';
?>
<div class="widget-card mb-4">
  <div class="widget-header"><h5 class="widget-title"><i class="bi bi-globe2 me-2"></i>Our Golbal Footprints</h5></div>
  <div class="widget-body p-3">
    <p class="text-muted">Enter a real place. Its latitude and longitude are found automatically and converted to a pin position for the existing homepage world-map image.</p>
    <form method="post" class="row g-3 align-items-end">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
      <div class="col-md-6"><label class="form-label">Location</label><input class="form-control" name="location_name" required placeholder="e.g. Dubai, United Arab Emirates"></div>
      <div class="col-md-2"><label class="form-label">Pin line height</label><input class="form-control" type="number" name="pin_height" value="55" min="20" max="140"></div>
      <div class="col-md-2"><label class="form-label">Order</label><input class="form-control" type="number" name="sort_order" value="0"></div>
      <div class="col-md-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-geo-alt me-1"></i>Add location</button></div>
    </form>
  </div>
</div>
<div class="widget-card"><div class="widget-header"><h5 class="widget-title">Pinned locations</h5></div><div class="widget-body p-0">
  <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Location</th><th>Matched address</th><th>Coordinates</th><th>Map position</th><th></th></tr></thead><tbody>
  <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No managed locations yet. The existing map pins remain visible until you add the first one.</td></tr><?php endif; ?>
  <?php foreach ($rows as $row): ?><tr><td><?= e($row['location_name']) ?></td><td><?= e($row['formatted_address']) ?></td><td><?= e($row['latitude']) ?>, <?= e($row['longitude']) ?></td><td><?= e($row['map_left']) ?>%, <?= e($row['map_top']) ?>%</td><td><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Remove this location?')">Remove</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div></div>
<?php include __DIR__ . '/_layout_bottom.php'; ?>
