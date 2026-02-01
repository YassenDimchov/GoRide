<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/trips_helpers.php';

function getMyPayments(string $token): array
{
    $res = apiPayments($token);

    if (is_array($res) && isset($res['data']) && is_array($res['data'])) {
        return $res['data'];
    }

    return [];
}
