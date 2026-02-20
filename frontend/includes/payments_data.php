<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/trips_helpers.php';

function getMyPayments(string $token, string $status = 'all'): array
{
    $status = strtolower(trim($status));
    if (!in_array($status, ['all', 'paid', 'unpaid'], true)) {
        $status = 'all';
    }

    $query = $status !== 'all' ? ['status' => $status] : [];
    $res = apiPayments($token, $query);

    if (is_array($res) && isset($res['data']) && is_array($res['data'])) {
        return $res['data'];
    }

    return [];
}
