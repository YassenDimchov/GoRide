<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/auth.php';

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    http_response_code(401);
    echo json_encode(['message' => 'Unauthenticated']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

if (!is_array($data)) {
    http_response_code(422);
    echo json_encode(['message' => 'Invalid JSON body']);
    exit;
}

$required = ['start_lat','start_lng','end_lat','end_lng','start_address','end_address'];
foreach ($required as $k) {
    if (!array_key_exists($k, $data) || $data[$k] === null || $data[$k] === '') {
        http_response_code(422);
        echo json_encode(['message' => "Missing field: $k"]);
        exit;
    }
}

$payload = [
    'start_lat' => (float)$data['start_lat'],
    'start_lng' => (float)$data['start_lng'],
    'end_lat' => (float)$data['end_lat'],
    'end_lng' => (float)$data['end_lng'],
    'start_address' => (string)$data['start_address'],
    'end_address' => (string)$data['end_address'],
    'trip_distance_m' => isset($data['trip_distance_m']) ? (int)$data['trip_distance_m'] : null,
    'trip_duration_s' => isset($data['trip_duration_s']) ? (int)$data['trip_duration_s'] : null,
];

if (isset($data['trip_distance_m']) && !is_numeric($data['trip_distance_m'])) {
    http_response_code(422);
    echo json_encode(['message' => 'trip_distance_m must be numeric']);
    exit;
}
if (isset($data['trip_duration_s']) && !is_numeric($data['trip_duration_s'])) {
    http_response_code(422);
    echo json_encode(['message' => 'trip_duration_s must be numeric']);
    exit;
}

$res = apiRequest('POST', '/rides', $payload, $token);

if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

echo json_encode($res, JSON_UNESCAPED_UNICODE);
