<?php
require_once __DIR__ . '/includes/guard.php';
require_once __DIR__ . '/includes/payments_data.php';
require_once __DIR__ . '/includes/auth.php';

if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $selectedPayment = $input['method'] ?? 'online';

    $response = apiUpdatePreferredPaymentMethod($token, $selectedPayment);

    echo json_encode(['status' => $response ? 'success' : 'error']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}
?>