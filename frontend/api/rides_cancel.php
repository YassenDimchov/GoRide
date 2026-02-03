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

$res = apiRequest('POST', '/rides/' . $id . '/cancel', [], $token);

if (!$res) {
  http_response_code(502);
  echo json_encode(['message' => 'Backend request failed']);
  exit;
}

if (!empty($res['_error'])) {
  http_response_code((int)$res['status']);
  echo json_encode($res['body'] ?? ['message' => 'Cancel failed']);
  exit;
}

echo json_encode($res, JSON_UNESCAPED_UNICODE);
