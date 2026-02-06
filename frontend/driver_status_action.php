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
$status = trim((string)($body['status'] ?? ''));

if (!in_array($status, ['available', 'offline'], true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid status']);
    exit;
}

$driver = apiDriverMe($token);
if (($driver['status'] ?? null) === 'busy') {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'You are busy on a ride.']);
    exit;
}

$res = apiUpdateDriverMe($token, ['status' => $status]);

if (empty($res['ok'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'ok' => false,
        'message' => $res['error'] ?? 'Failed to update',
        'errors' => $res['errors'] ?? null,
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'status' => $res['driver']['status'] ?? $status,
]);
