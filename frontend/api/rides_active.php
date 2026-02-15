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

$res = apiRequest('GET', '/rides/mine?with_driver=1&with_payment=1&with_review=1', [], $token);

if (!$res) {
  http_response_code(502);
  echo json_encode(['message' => 'Backend request failed']);
  exit;
}

$items = $res['data'] ?? $res['data']['data'] ?? null;
if (!is_array($items)) $items = $res['data'] ?? [];

$active = null;
foreach ($items as $r) {
  $st = (string)($r['status'] ?? '');
  if (in_array($st, ['pending','accepted','ongoing'], true)) { $active = $r; break; }
}

echo json_encode(['ok' => true, 'data' => $active], JSON_UNESCAPED_UNICODE);
