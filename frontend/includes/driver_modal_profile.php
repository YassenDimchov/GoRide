<?php
require_once __DIR__ . '/auth.php';

function getDriverProfile(string $token, int $driverId): array
{
    $driverData = apiDriverProfile($token, $driverId);

    if ($driverData === null) {
        return [];
    }

    $driverProfile = [
        'driver' => $driverData['driver'] ?? [],
        'averageResponseTime' => $driverData['average_response_time'] ?? 0,
        'ratingBreakdown' => $driverData['rating_breakdown'] ?? [],
        'totalTrips' => $driverData['driver']['total_trips'] ?? 0,
        'activeTime' => $driverData['driver']['active_time'] ?? ['years' => 0, 'months' => 0, 'days' => 0],
    ];

    return $driverProfile;
}
