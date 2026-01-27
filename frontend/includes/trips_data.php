<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/trips_helpers.php';

function getTripHistory(string $token): array
{
    $res = apiMyRides($token);

    $rides = [];
    if (is_array($res) && isset($res['data']) && is_array($res['data'])) {
        $rides = $res['data'];
    }

    $rides = array_values(array_filter($rides, fn($r) => (($r['status'] ?? null) === 'completed')));

    usort($rides, function($a, $b) {
        $ta = strtotime($a['completed_at'] ?? $a['created_at'] ?? '1970-01-01');
        $tb = strtotime($b['completed_at'] ?? $b['created_at'] ?? '1970-01-01');
        return $tb <=> $ta;
    });

    return $rides;
}
