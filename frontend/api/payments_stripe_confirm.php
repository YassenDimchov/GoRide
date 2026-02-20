<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/guard.php';

$paymentId = (int)($_GET['id'] ?? 0);
if ($paymentId <= 0) {
    http_response_code(422);
    echo json_encode(['message' => 'Invalid payment id']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) $data = [];

$sessionId = trim((string)($data['session_id'] ?? ''));
if ($sessionId === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Missing session_id']);
    exit;
}

$res = apiConfirmStripeCheckout($token, $paymentId, $sessionId);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Stripe payment confirmation failed',
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'message' => $res['message'] ?? 'Payment confirmed successfully',
    'payment' => $res['payment'] ?? null,
], JSON_UNESCAPED_UNICODE);

