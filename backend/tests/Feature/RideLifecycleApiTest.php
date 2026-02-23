<?php

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

function lifecycleRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Start',
        'end_address' => 'End',
        'passenger_count' => 1,
        'trip_distance_m' => 4000,
        'trip_duration_s' => 600,
    ], $overrides);
}

test('driver cannot accept own ride', function () {
    $user = User::create([
        'name' => 'Dual Role',
        'email' => 'dual@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $user->id,
        'status' => 'available',
        'last_seen_at' => now(),
        'passenger_capacity' => 4,
        'current_lat' => 42.6977,
        'current_lng' => 23.3219,
    ]);

    $ride = Ride::create(array_merge(lifecycleRidePayload(), [
        'user_id' => $user->id,
        'driver_id' => null,
        'status' => RideStatus::PENDING->value,
    ]));

    Sanctum::actingAs($user);

    $this->postJson("/api/rides/{$ride->id}/accept")
        ->assertStatus(409)
        ->assertJsonPath('message', 'You cannot accept your own ride.');

    expect($ride->fresh()->driver_id)->toBeNull();
    expect($driver->fresh()->status)->toBe('available');
});

test('start ride fails when driver is too far from pickup', function () {
    $passenger = User::create([
        'name' => 'Passenger',
        'email' => 'start-passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Far Driver',
        'email' => 'far-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'busy',
        'last_seen_at' => now(),
        'passenger_capacity' => 4,
        'current_lat' => 42.8000,
        'current_lng' => 23.4000,
    ]);

    $ride = Ride::create(array_merge(lifecycleRidePayload([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
    ]), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::ACCEPTED->value,
        'accepted_at' => now(),
    ]));

    Sanctum::actingAs($driverUser);

    $this->postJson("/api/rides/{$ride->id}/start")
        ->assertStatus(409)
        ->assertJsonPath('message', 'You are too far from the pickup location to start the ride.');
});

test('available rides endpoint auto-offlines stale available driver', function () {
    $driverUser = User::create([
        'name' => 'Stale Driver',
        'email' => 'stale-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'last_seen_at' => now()->subMinutes(10),
        'passenger_capacity' => 4,
        'current_lat' => 42.6977,
        'current_lng' => 23.3219,
    ]);

    Sanctum::actingAs($driverUser);

    $this->getJson('/api/rides/available')
        ->assertStatus(409)
        ->assertJsonPath('code', 'AUTO_OFFLINE');

    expect($driver->fresh()->status)->toBe('offline');
});
