<?php

use App\Models\Driver;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

test('driver me auto-offlines stale available driver', function () {
    $driverUser = User::create([
        'name' => 'Driver Me',
        'email' => 'driver-me@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'last_seen_at' => now()->subMinutes(6),
        'passenger_capacity' => 4,
    ]);

    Sanctum::actingAs($driverUser);

    $this->getJson('/api/driver/me')
        ->assertOk()
        ->assertJsonPath('driver.status', 'offline');

    expect($driver->fresh()->status)->toBe('offline');
});

test('driver location update is forbidden for non-driver users', function () {
    $user = User::create([
        'name' => 'Regular User',
        'email' => 'regular-user@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson('/api/driver/location', [
        'lat' => 42.7,
        'lng' => 23.3,
    ])->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden.');
});

test('driver cannot update location while offline', function () {
    $driverUser = User::create([
        'name' => 'Offline Driver',
        'email' => 'offline-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'offline',
        'passenger_capacity' => 4,
    ]);

    Sanctum::actingAs($driverUser);

    $this->patchJson('/api/driver/location', [
        'lat' => 42.7,
        'lng' => 23.3,
    ])->assertStatus(409)
        ->assertJsonPath('message', 'Driver is offline.');
});

test('busy driver cannot manually change status via driver update endpoint', function () {
    $driverUser = User::create([
        'name' => 'Busy Driver',
        'email' => 'busy-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'busy',
        'passenger_capacity' => 4,
        'last_seen_at' => now(),
    ]);

    Sanctum::actingAs($driverUser);

    $this->patchJson('/api/driver/me', [
        'status' => 'offline',
    ])->assertStatus(409)
        ->assertJsonPath('message', 'You are busy on a ride.');

    expect($driver->fresh()->status)->toBe('busy');
});
