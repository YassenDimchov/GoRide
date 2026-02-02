<?php

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

if (mb_strlen($q) < 3) {
    echo json_encode(['data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : 42.6977;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : 23.3219;

$backendBase = 'http://127.0.0.1:8000';
$url = $backendBase . '/api/geocode/autocomplete?q=' . rawurlencode($q)
    . '&lat=' . rawurlencode((string) $lat)
    . '&lng=' . rawurlencode((string) $lng);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['message' => 'Proxy request failed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

curl_close($ch);

http_response_code($status);
echo $response;
