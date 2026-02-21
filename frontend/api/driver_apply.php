<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/guard.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) $data = [];

$vehicleMake = trim((string)($data['vehicle_make'] ?? ''));
$vehicleModel = trim((string)($data['vehicle_model'] ?? ''));
$vehicleColor = trim((string)($data['vehicle_color'] ?? ''));
$licensePlate = trim((string)($data['license_plate'] ?? ''));
$passengerCapacity = (int)($data['passenger_capacity'] ?? 0);

if ($vehicleMake === '' || $vehicleModel === '' || $vehicleColor === '' || $licensePlate === '' || $passengerCapacity < 1 || $passengerCapacity > 8) {
    http_response_code(422);
    echo json_encode(['message' => 'Vehicle make, model, color, plate, and valid passenger capacity are required']);
    exit;
}

$res = apiApplyDriver($token, $vehicleMake, $vehicleModel, $vehicleColor, $licensePlate, $passengerCapacity);
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
