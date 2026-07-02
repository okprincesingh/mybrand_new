<?php
session_start();

require_once __DIR__ . '/../includes/shipping.php';

header('Content-Type: application/json; charset=UTF-8');

$postalCode = trim((string) ($_GET['zip'] ?? $_GET['postal_code'] ?? ''));
$requestedCountry = shipping_normalize_country((string) ($_GET['country'] ?? ''));

if ($postalCode === '') {
    echo json_encode(['success' => false, 'message' => 'Postal code is required.']);
    exit;
}

$countries = shipping_get_checkout_countries();
$countryCodes = array_keys($countries);
if ($requestedCountry !== '' && isset($countries[$requestedCountry])) {
    $countryCodes = [$requestedCountry];
}

$countryCodes = array_values(array_filter($countryCodes, static function (string $code): bool {
    return strlen($code) === 2;
}));

foreach ($countryCodes as $countryCode) {
    $result = postal_lookup_zippopotam($countryCode, $postalCode);
    if ($result !== null) {
        echo json_encode(['success' => true, 'data' => $result]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Postal code was not found for enabled shipping countries.']);

function postal_lookup_zippopotam(string $countryCode, string $postalCode): ?array
{
    $url = 'https://api.zippopotam.us/' . rawurlencode(strtolower($countryCode)) . '/' . rawurlencode($postalCode);
    $response = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw !== false && $status >= 200 && $status < 300) {
                $response = $raw;
            }
        }
    } else {
        $context = stream_context_create(['http' => ['timeout' => 5, 'header' => "Accept: application/json\r\n"]]);
        $raw = @file_get_contents($url, false, $context);
        if ($raw !== false) {
            $response = $raw;
        }
    }

    if ($response === null) {
        return null;
    }

    $json = json_decode($response, true);
    if (!is_array($json) || empty($json['places'][0]) || !is_array($json['places'][0])) {
        return null;
    }

    $place = $json['places'][0];
    return [
        'zip' => (string) ($json['post code'] ?? $postalCode),
        'country' => shipping_normalize_country((string) ($json['country abbreviation'] ?? $countryCode)),
        'country_name' => (string) ($json['country'] ?? shipping_country_label($countryCode)),
        'state' => (string) ($place['state'] ?? ''),
        'state_code' => (string) ($place['state abbreviation'] ?? ''),
        'city' => (string) ($place['place name'] ?? ''),
    ];
}
