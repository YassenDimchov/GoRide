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

$res = apiCreateStripeCheckout($token, $paymentId);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Stripe checkout failed',
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'checkout_url' => $res['checkout_url'] ?? null,
    'session_id' => $res['session_id'] ?? null,
], JSON_UNESCAPED_UNICODE);

