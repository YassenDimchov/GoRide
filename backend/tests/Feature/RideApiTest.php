<?php

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Ride;
use App\Models\User;
use App\Support\Fare;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

function ridePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Start Street 1',
        'end_address' => 'End Street 2',
        'passenger_count' => 1,
        'trip_distance_m' => 7500,
        'trip_duration_s' => 900,
    ], $overrides);
}

test('user can create a ride and estimated fare is computed', function () {
    $user = User::create([
        'name' => 'Rider',
        'email' => 'rider-create@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
        'suspended' => false,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/rides', ridePayload());

    $response
        ->assertCreated()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.status', RideStatus::PENDING->value);

    $expectedFare = Fare::estimate(7.5, 15);

    expect((float) $response->json('data.estimated_fare'))->toBe($expectedFare);

    $this->assertDatabaseHas('rides', [
        'user_id' => $user->id,
        'status' => RideStatus::PENDING->value,
        'passenger_count' => 1,
    ]);
});

test('driver cannot accept ride when passenger count exceeds capacity', function () {
    $passenger = User::create([
        'name' => 'Passenger',
        'email' => 'passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver',
        'email' => 'driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'vehicle_make' => 'Toyota',
        'vehicle_model' => 'Corolla',
        'vehicle_color' => 'Black',
        'license_plate' => 'CAX1234',
        'passenger_capacity' => 2,
        'status' => 'available',
        'last_seen_at' => now(),
        'current_lat' => 42.6977,
        'current_lng' => 23.3219,
    ]);

    $ride = Ride::create(array_merge(ridePayload([
        'passenger_count' => 4,
    ]), [
        'user_id' => $passenger->id,
        'driver_id' => null,
        'status' => RideStatus::PENDING->value,
    ]));

    Sanctum::actingAs($driverUser);

    $response = $this->postJson("/api/rides/{$ride->id}/accept");

    $response
        ->assertStatus(409)
        ->assertJsonPath('code', 'PASSENGER_CAPACITY_EXCEEDED');

    $ride->refresh();
    expect($ride->driver_id)->toBeNull();
    expect($ride->status)->toBe(RideStatus::PENDING->value);
    expect($driver->fresh()->status)->toBe('available');
});

test('completing ride creates pending cash payment when passenger prefers cash', function () {
    $passenger = User::create([
        'name' => 'Cash Passenger',
        'email' => 'cash-passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
        'preferred_payment' => 'cash',
    ]);

    $driverUser = User::create([
        'name' => 'Driver Cash',
        'email' => 'driver-cash@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'vehicle_make' => 'VW',
        'vehicle_model' => 'Golf',
        'vehicle_color' => 'White',
        'license_plate' => 'CB1234AB',
        'passenger_capacity' => 4,
        'status' => 'busy',
        'last_seen_at' => now(),
        'current_lat' => 42.6977,
        'current_lng' => 23.3219,
    ]);

    $ride = Ride::create(array_merge(ridePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::ONGOING->value,
        'estimated_fare' => 12.70,
    ]));

    Sanctum::actingAs($driverUser);

    $response = $this->postJson("/api/rides/{$ride->id}/complete");

    $response
        ->assertOk()
        ->assertJsonPath('payment.method', 'cash')
        ->assertJsonPath('payment.status', 'pending');

    $ride->refresh();
    expect($ride->status)->toBe(RideStatus::COMPLETED->value);
    expect($driver->fresh()->status)->toBe('available');

    $payment = Payment::where('ride_id', $ride->id)->first();
    expect($payment)->not->toBeNull();
    expect($payment->method)->toBe('cash');
    expect($payment->status)->toBe('pending');
});

test('only ride owner can cancel a ride', function () {
    $owner = User::create([
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $otherUser = User::create([
        'name' => 'Other',
        'email' => 'other@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(ridePayload(), [
        'user_id' => $owner->id,
        'driver_id' => null,
        'status' => RideStatus::PENDING->value,
    ]));

    Sanctum::actingAs($otherUser);

    $this->postJson("/api/rides/{$ride->id}/cancel")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden');

    expect($ride->fresh()->status)->toBe(RideStatus::PENDING->value);
});
