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

$res = apiGetPreferredPaymentMethod($token);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Failed to load preferred payment method',
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = strtolower((string)($res['preferred_payment'] ?? ''));
$method = $raw === 'cash' ? 'Cash' : 'Card';

echo json_encode([
    'preferred_payment' => $raw,
    'label' => $method,
], JSON_UNESCAPED_UNICODE);

