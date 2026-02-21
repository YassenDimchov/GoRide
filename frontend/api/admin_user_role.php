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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['message' => 'Invalid user id']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
$role = strtolower(trim((string)($data['role'] ?? '')));
if (!in_array($role, ['user', 'driver'], true)) {
    http_response_code(422);
    echo json_encode(['message' => 'Role must be user or driver']);
    exit;
}

$res = apiAdminSetUserRole($token, $id, $role);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Failed to update role',
        'errors' => $res['body']['errors'] ?? null,
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'message' => $res['message'] ?? 'Updated',
    'user' => $res['user'] ?? null,
], JSON_UNESCAPED_UNICODE);
