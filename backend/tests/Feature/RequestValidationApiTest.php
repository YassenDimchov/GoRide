<?php

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;

beforeEach(function () {
    resetApiTestData();
});

function validationRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Start',
        'end_address' => 'End',
        'passenger_count' => 1,
        'trip_distance_m' => 1200,
        'trip_duration_s' => 300,
    ], $overrides);
}

test('register validation fails for missing and short fields', function () {
    $this->postJson('/api/register', [
        'name' => 'A',
        'email' => 'bad-email',
        'password' => '123',
        'password_confirmation' => '456',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('driver location validation fails for out of range coordinates', function () {
    $driverUser = User::create([
        'name' => 'Validation Driver',
        'email' => 'validation-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $token = $driverUser->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->patchJson('/api/driver/location', [
            'lat' => 100,
            'lng' => -500,
        ])->assertStatus(422)
        ->assertJsonValidationErrors(['lat', 'lng']);
});

test('review validation fails when rating is out of bounds', function () {
    $passenger = User::create([
        'name' => 'Validation Passenger',
        'email' => 'validation-passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Validation Driver 2',
        'email' => 'validation-driver2@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(validationRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
    ]));

    $token = $passenger->createToken('api')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/rides/{$ride->id}/review", [
            'rating' => 6,
        ])->assertStatus(422)
        ->assertJsonValidationErrors(['rating']);
});
