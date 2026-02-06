<?php
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (($user['role'] ?? '') !== 'driver') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit;
}

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$lat = $body['lat'] ?? null;
$lng = $body['lng'] ?? null;

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid lat/lng']);
    exit;
}

$res = apiRequest('PATCH', '/driver/location', [
    'lat' => (float)$lat,
    'lng' => (float)$lng,
], $token);

if (!$res) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No response from API']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'ok' => false,
        'message' => $res['body']['message'] ?? 'Failed',
        'code' => $res['body']['code'] ?? null,
        'body' => $res['body'] ?? null,
    ]);
    exit;
}

echo json_encode(['ok' => true]);
