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
        return null;
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
    return apiRequest('GET', '/rides/mine', [], $token);
}

function apiPayments(string $token) {
    return apiRequest('GET', '/payments', [], $token);
}

function apiMyReviews(string $token) {
    return apiRequest('GET', '/reviews', [], $token);
}
