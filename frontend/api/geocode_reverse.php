<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

$lat = $_GET["lat"] ?? null;
$lng = $_GET["lng"] ?? null;

if ($lat === null || $lng === null) {
    http_response_code(422);
    echo json_encode(["message" => "Missing lat/lng"]);
    exit;
}

$lat = (float) $lat;
$lng = (float) $lng;

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(422);
    echo json_encode(["message" => "Invalid lat/lng"]);
    exit;
}

$cfgPath = __DIR__ . "/_config.php";
if (!file_exists($cfgPath)) {
    http_response_code(500);
    echo json_encode(["message" => "Missing config file: _config.php"]);
    exit;
}

require_once $cfgPath;

if (!defined("ORS_API_KEY") || !ORS_API_KEY) {
    http_response_code(500);
    echo json_encode(["message" => "ORS_API_KEY not set"]);
    exit;
}

$url = "https://api.openrouteservice.org/geocode/reverse?size=1";

$payload = [
    "point.lat" => (string) $lat,
    "point.lon" => (string) $lng,
];

$query = http_build_query($payload);
$fullUrl = $url . "&" . $query;

$ch = curl_init($fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: " . ORS_API_KEY,
    "Accept: application/json",
]);

$raw = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
    http_response_code(500);
    echo json_encode(["message" => "cURL error", "error" => $err]);
    exit;
}

if ($code < 200 || $code >= 300) {
    http_response_code($code);
    echo json_encode(["message" => "ORS reverse error", "status" => $code, "raw" => $raw]);
    exit;
}

$json = json_decode($raw, true);

$feature = $json["features"][0] ?? null;
$label = $feature["properties"]["label"] ?? null;

echo json_encode([
    "label" => $label,
    "lat" => $lat,
    "lng" => $lng,
]);

