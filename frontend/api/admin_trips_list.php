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

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(5, min(20, (int)($_GET['per_page'] ?? 8)));
$search = trim((string)($_GET['search'] ?? ''));

$res = apiAdminTrips($token, $page, $perPage, $search);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Failed to load trips',
        'errors' => $res['body']['errors'] ?? null,
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'stats' => $res['stats'] ?? [],
    'trips' => $res['trips'] ?? [],
    'meta' => $res['meta'] ?? [],
], JSON_UNESCAPED_UNICODE);
