<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/guard.php';
error_reporting(E_ALL & ~E_NOTICE);

$token = $_SESSION['token'] ?? $_COOKIE['goride_token'] ?? null;
if (!$token) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);
if (!$data) {
    echo json_encode(['error' => 'Invalid JSON data received']);
    exit;
}

$oldPassword = $data['oldPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';
$confirmPassword = $data['newPassword_confirmation'] ?? '';

if ($newPassword !== $confirmPassword) {
    echo json_encode(['error' => 'Passwords do not match']);
    exit;
}

$response = apiUpdatePassword($token, $oldPassword, $newPassword);

if (isset($response['message']) && $response['message'] === 'Password updated successfully.') {
    echo json_encode(['message' => 'Password updated successfully']);
} else {
    echo json_encode(['error' => 'Could not update the password. Please try again.']);
}
exit;
?>