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
$rideId = (int)($body['ride_id'] ?? 0);

if ($rideId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid ride_id']);
    exit;
}

$res = apiRequest('POST', '/rides/' . $rideId . '/complete', [], $token);

if (!$res) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'No response from API']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 409));
    echo json_encode([
        'ok' => false,
        'message' => $res['body']['message'] ?? 'Failed',
        'code' => $res['body']['code'] ?? null,
        'body' => $res['body'] ?? null,
    ]);
    exit;
}

echo json_encode(['ok' => true, 'data' => $res['data'] ?? null, 'payment' => $res['payment'] ?? null]);
