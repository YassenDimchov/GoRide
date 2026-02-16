<?php

function apiMe(string $token): ?array
{
    $ch = curl_init('http://127.0.0.1:8000/api/me');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    return $data['user'] ?? null;
}

function userInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';

    foreach ($parts as $p) {
        $initials .= strtoupper(mb_substr($p, 0, 1));
        if (strlen($initials) === 2) break;
    }

    return $initials ?: 'U';
}

function apiRequest(string $method, string $endpoint, array $data = [], ?string $token = null): ?array
{
    $baseUrl = 'http://127.0.0.1:8000/api';
    $url = rtrim($baseUrl, '/') . $endpoint;

    $ch = curl_init($url);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) return null;

    $json = json_decode($response, true);

    if ($http < 200 || $http >= 300) {
        return [
            '_error' => true,
            'status' => $http,
            'body' => $json,
        ];
    }

    return is_array($json) ? $json : null;
}


function apiLogout(string $token): bool {
    return apiRequest('POST', '/logout', [], $token) !== null;
}

function apiUpdateMe(string $token, array $payload): ?array {
    return apiRequest('PATCH', '/me', $payload, $token);
}

function apiMyRides(string $token) {
    return apiRequest('GET', '/rides/mine?with_driver=1&with_payment=1&with_review=1', [], $token);
}

function apiPayments(string $token) {
    return apiRequest('GET', '/payments', [], $token);
}

function apiMyReviews(string $token) {
    return apiRequest('GET', '/reviews', [], $token);
}

function apiDriverMe(string $token): ?array {
    $res = apiRequest('GET', '/driver/me', [], $token);
    if (!$res || !empty($res['_error'])) return null;
    return $res['driver'] ?? null;
}

function apiUpdateDriverMe(string $token, array $payload): array {
    $res = apiRequest('PATCH', '/driver/me', $payload, $token);

    if (!$res) {
        return ['ok' => false, 'error' => 'No response from API'];
    }

    if (!empty($res['_error'])) {
        $msg = $res['body']['message'] ?? ('HTTP ' . ($res['status'] ?? '???'));
        $errs = $res['body']['errors'] ?? null;

        return [
            'ok' => false,
            'error' => $msg,
            'errors' => $errs,
            'status' => $res['status'] ?? null,
            'body' => $res['body'] ?? null,
        ];
    }

    return [
        'ok' => true,
        'driver' => $res['driver'] ?? null,
    ];
}

function apiDriverHeartbeat(string $token): array {
    $res = apiRequest('POST', '/driver/heartbeat', [], $token);

    if (!$res) return ['ok' => false, 'error' => 'No response from API'];
    if (!empty($res['_error'])) {
        return ['ok' => false, 'error' => $res['body']['message'] ?? 'Heartbeat failed', 'status' => $res['status'] ?? null];
    }
    return ['ok' => true, 'data' => $res];
}

function apiAvailableRides(string $token): ?array {
    return apiRequest('GET', '/rides/available', [], $token);
}

function apiAcceptRide(string $token, int $rideId): ?array {
    return apiRequest('POST', '/rides/' . $rideId . '/accept', [], $token);
}

function apiCreateRideReview(string $token, int $rideId, int $rating, string $reviewText = ''): ?array {
    $payload = [
        'rating' => $rating,
        'review_text' => $reviewText,
    ];

    return apiRequest('POST', '/rides/' . $rideId . '/review', $payload, $token);
}