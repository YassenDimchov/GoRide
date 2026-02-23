<?php

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;

beforeEach(function () {
    resetApiTestData();
});

function stateRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Start',
        'end_address' => 'End',
        'passenger_count' => 1,
        'trip_distance_m' => 2500,
        'trip_duration_s' => 480,
    ], $overrides);
}

test('start fails when ride is not accepted', function () {
    $passenger = User::create([
        'name' => 'Passenger S1',
        'email' => 'passenger-s1@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver S1',
        'email' => 'driver-s1@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'busy',
        'passenger_capacity' => 4,
        'current_lat' => 42.6977,
        'current_lng' => 23.3219,
    ]);

    $ride = Ride::create(array_merge(stateRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::PENDING->value,
    ]));

    $token = $driverUser->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/rides/{$ride->id}/start")
        ->assertStatus(409)
        ->assertJsonPath('message', 'Ride cannot be started in its current state.');
});

test('complete fails when ride is not ongoing', function () {
    $passenger = User::create([
        'name' => 'Passenger S2',
        'email' => 'passenger-s2@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver S2',
        'email' => 'driver-s2@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'busy',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(stateRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::ACCEPTED->value,
    ]));

    $token = $driverUser->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/rides/{$ride->id}/complete")
        ->assertStatus(409)
        ->assertJsonPath('message', 'Ride cannot be completed in its current state.');
});

test('cancel fails when ride already completed', function () {
    $user = User::create([
        'name' => 'Passenger S3',
        'email' => 'passenger-s3@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(stateRidePayload(), [
        'user_id' => $user->id,
        'status' => RideStatus::COMPLETED->value,
    ]));

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/rides/{$ride->id}/cancel")
        ->assertStatus(409)
        ->assertJsonPath('message', 'Ride already finished.');
});

test('ride creation validation fails for invalid coordinates and passenger count', function () {
    $user = User::create([
        'name' => 'Validation User',
        'email' => 'ride-validation@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/rides', stateRidePayload([
            'start_lat' => 120,
            'end_lng' => -220,
            'passenger_count' => 0,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['start_lat', 'end_lng', 'passenger_count']);
});

test('ride creation validation fails for missing required fields', function () {
    $user = User::create([
        'name' => 'Validation Missing',
        'email' => 'ride-validation-missing@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/rides', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'start_lat',
            'start_lng',
            'end_lat',
            'end_lng',
            'start_address',
            'end_address',
            'passenger_count',
        ]);
});
