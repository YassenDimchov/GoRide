<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/guard.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) $data = [];

$vehicleMake = trim((string)($data['vehicle_make'] ?? ''));
$vehicleModel = trim((string)($data['vehicle_model'] ?? ''));
$licensePlate = trim((string)($data['license_plate'] ?? ''));

if ($vehicleMake === '' || $vehicleModel === '' || $licensePlate === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Vehicle make, model, and plate are required']);
    exit;
}

$res = apiApplyDriver($token, $vehicleMake, $vehicleModel, $licensePlate);
if (!$res) {
    http_response_code(502);
    echo json_encode(['message' => 'Backend request failed']);
    exit;
}

if (!empty($res['_error'])) {
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode([
        'message' => $res['body']['message'] ?? 'Failed to submit driver application',
        'errors' => $res['body']['errors'] ?? null,
        'body' => $res['body'] ?? null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'message' => $res['message'] ?? 'Driver application sent successfully',
    'sent_to' => $res['sent_to'] ?? null,
], JSON_UNESCAPED_UNICODE);

