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

$id = $_GET['id'] ?? null;
if (!$id || !preg_match('/^\d+$/', (string)$id)) {
    http_response_code(422);
    echo json_encode(['message' => 'Missing/invalid id']);
    exit;
}

$res = apiRequest('GET', '/rides/' . $id, [], $token);

if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

echo json_encode($res, JSON_UNESCAPED_UNICODE);
