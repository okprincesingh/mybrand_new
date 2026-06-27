<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/catalog.php';

function shipping_table_exists(PDO $pdo): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    $count = (int) (db_fetch_value(
        $pdo,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name',
        [':table_name' => 'shipping_methods']
    ) ?? 0);

    $exists = $count > 0;
    return $exists;
}

function shipping_order_has_columns(PDO $pdo): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $rows = db_fetch_all(
        $pdo,
        'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name',
        [':table_name' => 'orders']
    );

    $names = array_map(static fn(array $row): string => (string) ($row['COLUMN_NAME'] ?? ''), $rows);
    $cache = [
        'shipping_method' => in_array('shipping_method', $names, true),
        'shipping_method_id' => in_array('shipping_method_id', $names, true),
    ];

    return $cache;
}

function shipping_parse_number($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        return (float) $value;
    }
    if (!is_string($value)) {
        return null;
    }

    if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $value, $matches)) {
        return (float) $matches[1];
    }

    return null;
}

function shipping_extract_product_weight(array $product): float
{
    if (isset($product['weight'])) {
        $weight = shipping_parse_number($product['weight']);
        if ($weight !== null && $weight >= 0) {
            return $weight;
        }
    }

    $attributes = $product['attributes'] ?? [];
    if (is_array($attributes)) {
        foreach ($attributes as $attribute) {
            $key = strtolower((string) ($attribute['key'] ?? ''));
            if ($key !== 'weight') {
                continue;
            }
            $weight = shipping_parse_number($attribute['value'] ?? null);
            if ($weight !== null && $weight >= 0) {
                return $weight;
            }
        }
    }

    return 0.0;
}

function shipping_calculate_cart_weight(array $cart): float
{
    $totalWeight = 0.0;
    foreach ($cart as $slug => $quantity) {
        $product = catalog_find_product((string) $slug);
        if (!$product) {
            continue;
        }
        $qty = max(1, (int) $quantity);
        $weightPerUnit = shipping_extract_product_weight($product);
        $totalWeight += $weightPerUnit * $qty;
    }
    return round($totalWeight, 3);
}

function shipping_build_method_label(array $method): string
{
    $name = trim((string) ($method['method_name'] ?? 'Shipping'));
    $deliveryDays = (int) ($method['estimated_delivery_days'] ?? 0);
    if ($deliveryDays > 0) {
        $name .= ' (' . $deliveryDays . ' day' . ($deliveryDays > 1 ? 's' : '') . ')';
    }
    return $name;
}

function shipping_normalize_state(string $state): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $state)));
}

function shipping_cache_dir(): string
{
    $dir = __DIR__ . '/../storage/cache/shipping';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function shipping_cache_get(string $key, int $ttl): ?array
{
    $file = shipping_cache_dir() . '/' . sha1($key) . '.json';
    if (!is_file($file)) {
        return null;
    }
    if ((time() - (int) filemtime($file)) > max(1, $ttl)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function shipping_cache_set(string $key, array $data): void
{
    $file = shipping_cache_dir() . '/' . sha1($key) . '.json';
    @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function shipping_method_matches_zone(PDO $pdo, array $method, string $destinationState): bool
{
    $destinationState = shipping_normalize_state($destinationState);
    if ($destinationState === '') {
        return true;
    }

    if (!empty($method['zone_id'])) {
        $count = (int) (db_fetch_value(
            $pdo,
            'SELECT COUNT(*) FROM shipping_zone_states WHERE zone_id = :zone_id AND LOWER(state_name) = :state_name',
            [':zone_id' => (int) $method['zone_id'], ':state_name' => $destinationState]
        ) ?? 0);
        return $count > 0;
    }

    $zoneStatesRaw = trim((string) ($method['zone_states'] ?? ''));
    if ($zoneStatesRaw === '') {
        return true;
    }

    $allowed = array_map('shipping_normalize_state', array_filter(array_map('trim', explode(',', $zoneStatesRaw))));
    return in_array($destinationState, $allowed, true);
}

function shipping_http_json(string $url, array $headers = [], int $timeout = 8): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    if (!$ch) {
        return null;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return null;
    }

    $json = json_decode($response, true);
    return is_array($json) ? $json : null;
}

function shipping_fetch_provider_rate(array $method, float $cartTotal, float $cartWeight, string $destinationState, string $postalCode): ?float
{
    $providerCode = strtolower(trim((string) ($method['provider_code'] ?? '')));
    if ($providerCode === '') {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $provider = db_fetch_one(
        $pdo,
        'SELECT * FROM shipping_provider_configs WHERE provider_code = :provider_code AND is_active = 1 LIMIT 1',
        [':provider_code' => $providerCode]
    );

    if (!$provider) {
        return null;
    }

    $cacheKey = implode('|', [
        'provider-rate',
        $providerCode,
        (int) ($method['id'] ?? 0),
        number_format($cartTotal, 2, '.', ''),
        number_format($cartWeight, 3, '.', ''),
        shipping_normalize_state($destinationState),
        trim($postalCode),
    ]);

    $cached = shipping_cache_get($cacheKey, 120);
    if ($cached !== null && isset($cached['cost'])) {
        return (float) $cached['cost'];
    }

    $apiBase = rtrim((string) ($provider['api_base_url'] ?? ''), '/');
    $token = trim((string) ($provider['token'] ?? ''));
    $apiKey = trim((string) ($provider['api_key'] ?? ''));

    if ($apiBase !== '' && $token !== '' && $apiKey !== '') {
        $query = http_build_query([
            'weight' => $cartWeight,
            'amount' => $cartTotal,
            'state' => $destinationState,
            'pincode' => $postalCode,
            'service_code' => (string) ($method['provider_service_code'] ?? ''),
        ]);
        $url = $apiBase . '/shipping/rate?' . $query;
        $headers = [
            'Authorization: Bearer ' . $token,
            'X-API-Key: ' . $apiKey,
            'Accept: application/json',
        ];

        $json = shipping_http_json($url, $headers, 8);
        if (is_array($json) && isset($json['rate']) && is_numeric($json['rate'])) {
            $cost = max(0.0, (float) $json['rate']);
            shipping_cache_set($cacheKey, ['cost' => $cost]);
            return $cost;
        }
    }

    if ($providerCode === 'delhivery') {
        $cost = max(40.0, round((20 + ($cartWeight * 35)), 2));
        shipping_cache_set($cacheKey, ['cost' => $cost]);
        return $cost;
    }

    if ($providerCode === 'shiprocket') {
        $cost = max(45.0, round((25 + ($cartWeight * 32)), 2));
        shipping_cache_set($cacheKey, ['cost' => $cost]);
        return $cost;
    }

    return null;
}

function getAvailableShippingMethods($cartTotal, $cartWeight, ?string $destinationState = null, ?string $postalCode = null): array
{
    $pdo = db();
    if (!$pdo || !shipping_table_exists($pdo)) {
        return [];
    }

    $cartTotal = max(0.0, (float) $cartTotal);
    $cartWeight = max(0.0, (float) $cartWeight);
    $destinationState = trim((string) ($destinationState ?? ''));
    $postalCode = trim((string) ($postalCode ?? ''));

    $cacheKey = implode('|', [
        'shipping-methods',
        number_format($cartTotal, 2, '.', ''),
        number_format($cartWeight, 3, '.', ''),
        shipping_normalize_state($destinationState),
        $postalCode,
    ]);

    $cached = shipping_cache_get($cacheKey, 300);
    if ($cached !== null && isset($cached['methods']) && is_array($cached['methods'])) {
        return $cached['methods'];
    }

    $methods = db_fetch_all(
        $pdo,
        'SELECT * FROM shipping_methods WHERE status = :status ORDER BY priority ASC, id ASC',
        [':status' => 'active']
    );

    $available = [];
    foreach ($methods as $method) {
        if (!shipping_method_matches_zone($pdo, $method, $destinationState)) {
            continue;
        }

        $type = (string) ($method['shipping_type'] ?? '');
        $cost = max(0.0, (float) ($method['cost'] ?? 0));
        $isEligible = false;

        if ($type === 'flat_rate') {
            $isEligible = true;
        } elseif ($type === 'free_shipping') {
            $min = (float) ($method['min_order_amount'] ?? 0);
            if ($cartTotal >= $min) {
                $isEligible = true;
                $cost = 0.0;
            }
        } elseif ($type === 'weight_based') {
            $minWeight = isset($method['weight_min']) ? (float) $method['weight_min'] : 0.0;
            $maxWeight = isset($method['weight_max']) ? (float) $method['weight_max'] : 0.0;
            if ($cartWeight >= $minWeight && ($maxWeight <= 0 || $cartWeight <= $maxWeight)) {
                $isEligible = true;
            }
        } elseif ($type === 'price_based') {
            $minPrice = isset($method['price_min']) ? (float) $method['price_min'] : 0.0;
            $maxPrice = isset($method['price_max']) ? (float) $method['price_max'] : 0.0;
            if ($cartTotal >= $minPrice && ($maxPrice <= 0 || $cartTotal <= $maxPrice)) {
                $isEligible = true;
            }
        }

        if (!$isEligible) {
            continue;
        }

        if (($method['rate_source'] ?? 'manual') === 'api') {
            $apiCost = shipping_fetch_provider_rate($method, $cartTotal, $cartWeight, $destinationState, $postalCode);
            if ($apiCost !== null) {
                $cost = $apiCost;
            }
        }

        $method['cost'] = round($cost, 2);
        $method['label'] = shipping_build_method_label($method);
        $available[] = $method;
    }

    shipping_cache_set($cacheKey, ['methods' => $available]);
    return $available;
}

function shipping_get_method_by_id(int $methodId, float $cartTotal, float $cartWeight, ?string $destinationState = null, ?string $postalCode = null): ?array
{
    if ($methodId <= 0) {
        return null;
    }

    $available = getAvailableShippingMethods($cartTotal, $cartWeight, $destinationState, $postalCode);
    foreach ($available as $method) {
        if ((int) ($method['id'] ?? 0) === $methodId) {
            return $method;
        }
    }
    return null;
}

function shipping_save_selection_to_session(array $method): void
{
    $_SESSION['selected_shipping_method'] = [
        'id' => (int) ($method['id'] ?? 0),
        'method_name' => (string) ($method['method_name'] ?? ''),
        'shipping_type' => (string) ($method['shipping_type'] ?? ''),
        'cost' => (float) ($method['cost'] ?? 0),
        'estimated_delivery_days' => (int) ($method['estimated_delivery_days'] ?? 0),
        'updated_at' => date('c'),
    ];
}

function shipping_get_session_selection(): ?array
{
    $row = $_SESSION['selected_shipping_method'] ?? null;
    return is_array($row) ? $row : null;
}

function shipping_clear_session_selection(): void
{
    unset($_SESSION['selected_shipping_method']);
}
