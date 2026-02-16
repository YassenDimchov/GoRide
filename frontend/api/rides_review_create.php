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

$rideId = (int)($_GET['id'] ?? 0);
if ($rideId <= 0) {
    http_response_code(422);
    echo json_encode(['message' => 'Invalid ride id']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);

$rating = (int)($data['rating'] ?? 0);
$reviewText = trim((string)($data['review_text'] ?? ''));

if ($rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['message' => 'Rating must be between 1 and 5']);
    exit;
}
if (mb_strlen($reviewText) > 500) {
    http_response_code(422);
    echo json_encode(['message' => 'Review text max 500 characters']);
    exit;
}

$res = apiCreateRideReview($token, $rideId, $rating, $reviewText);
if (!$res) {
    http_response_code(500);
    echo json_encode(['message' => 'No response from API']);
    exit;
}
if (!empty($res['_error'])) {
    $msg = $res['body']['message'] ?? 'Review failed';
    http_response_code((int)($res['status'] ?? 400));
    echo json_encode(['message' => $msg, 'errors' => $res['body']['errors'] ?? null, 'body' => $res['body'] ?? null]);
    exit;
}

echo json_encode(['data' => $res['review'] ?? ($res['body']['review'] ?? $res)]);
