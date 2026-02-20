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

$recipientEmail = 'yasen.s.dimchov.2021@elsys-bg.org';
$note = trim((string)($data['note'] ?? ''));

if (mb_strlen($note) > 2000) {
    http_response_code(422);
    echo json_encode(['message' => 'Note max 2000 characters']);
    exit;
}

$res = apiReportUnpaidPayment($token, $paymentId, $recipientEmail, $note);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    $baseMessage = $res['body']['message'] ?? 'Failed to send unpaid report';
    $detail = trim((string)($res['body']['error'] ?? ''));
    $message = $detail !== '' ? ($baseMessage . ' - ' . $detail) : $baseMessage;
    echo json_encode([
        'message' => $message,
        'errors' => $res['body']['errors'] ?? null,
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'message' => $res['message'] ?? 'Unpaid report sent successfully',
    'sent_to' => $res['sent_to'] ?? $recipientEmail,
    'payment' => $res['payment'] ?? null,
], JSON_UNESCAPED_UNICODE);

