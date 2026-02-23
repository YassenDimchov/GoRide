<?php

use App\Models\Driver;
use App\Models\Review;
use App\Models\Ride;
use App\Models\User;

beforeEach(function () {
    resetApiTestData();
});

function adminDataRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Admin Start',
        'end_address' => 'Admin End',
        'passenger_count' => 1,
        'trip_distance_m' => 5000,
        'trip_duration_s' => 720,
    ], $overrides);
}

test('admin drivers endpoint returns drivers and stats', function () {
    $admin = User::create([
        'name' => 'Admin Drivers',
        'email' => 'admin-drivers@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);

    $passenger = User::create([
        'name' => 'Passenger Drivers',
        'email' => 'passenger-drivers@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver Drivers',
        'email' => 'driver-drivers@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'vehicle_make' => 'Toyota',
        'vehicle_model' => 'Yaris',
        'vehicle_color' => 'Blue',
        'license_plate' => 'CA5555AB',
        'status' => 'busy',
        'passenger_capacity' => 4,
        'last_seen_at' => now(),
    ]);

    $ride = Ride::create(array_merge(adminDataRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => 'completed',
        'fare' => 15.40,
        'completed_at' => now(),
    ]));

    Review::create([
        'ride_id' => $ride->id,
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'rating' => 5,
        'review_text' => 'Great driver',
    ]);

    $token = $admin->createToken('api')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/admin/drivers')
        ->assertOk()
        ->assertJsonStructure([
            'stats' => ['total_drivers', 'online_now', 'avg_rating', 'total_trips_today'],
            'drivers',
        ]);

    expect($response->json('stats.total_drivers'))->toBe(1);
    expect(count($response->json('drivers')))->toBeGreaterThan(0);
});

test('admin trips endpoint returns trips with pagination meta and stats', function () {
    $admin = User::create([
        'name' => 'Admin Trips',
        'email' => 'admin-trips@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'admin',
    ]);

    $passenger = User::create([
        'name' => 'Passenger Trips',
        'email' => 'passenger-trips@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver Trips',
        'email' => 'driver-trips@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(adminDataRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => 'completed',
        'fare' => 20.00,
        'completed_at' => now(),
    ]));

    Review::create([
        'ride_id' => $ride->id,
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'rating' => 4,
        'review_text' => 'Nice trip',
    ]);

    $token = $admin->createToken('api')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/admin/trips?per_page=8&page=1')
        ->assertOk()
        ->assertJsonStructure([
            'stats' => ['total_revenue', 'total_trips', 'active_users', 'avg_trip_value'],
            'trips',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('stats.total_trips'))->toBe(1);
    expect(count($response->json('trips')))->toBeGreaterThan(0);
});
