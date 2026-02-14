<?php
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Invalid user_id']);
    exit;
}

$res = apiRequest('GET', '/users/' . $userId . '/reviews', [], $token);

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

echo json_encode(['ok' => true, 'data' => $res['data'] ?? []]);
