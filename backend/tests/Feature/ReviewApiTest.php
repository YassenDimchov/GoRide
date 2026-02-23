<?php

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Review;
use App\Models\Ride;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

function reviewRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'A',
        'end_address' => 'B',
        'passenger_count' => 1,
        'trip_distance_m' => 2000,
        'trip_duration_s' => 420,
    ], $overrides);
}

test('passenger can leave review for completed ride', function () {
    $passenger = User::create([
        'name' => 'Review Passenger',
        'email' => 'review-passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Review Driver',
        'email' => 'review-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(reviewRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
    ]));

    Sanctum::actingAs($passenger);

    $this->postJson("/api/rides/{$ride->id}/review", [
        'rating' => 5,
        'review_text' => 'Excellent ride',
    ])->assertCreated()
      ->assertJsonPath('review.rating', 5);

    $this->assertDatabaseHas('reviews', [
        'ride_id' => $ride->id,
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'rating' => 5,
    ]);
});

test('review requires completed ride', function () {
    $passenger = User::create([
        'name' => 'Passenger Pending',
        'email' => 'review-pending@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver Pending',
        'email' => 'driver-pending@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(reviewRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::ACCEPTED->value,
    ]));

    Sanctum::actingAs($passenger);

    $this->postJson("/api/rides/{$ride->id}/review", [
        'rating' => 4,
    ])->assertStatus(409)
      ->assertJsonPath('message', 'Ride must be completed to leave a review.');
});

test('only passenger can leave review', function () {
    $passenger = User::create([
        'name' => 'Passenger O',
        'email' => 'review-owner@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $otherUser = User::create([
        'name' => 'Other O',
        'email' => 'review-other@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver O',
        'email' => 'review-driver-o@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(reviewRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
    ]));

    Sanctum::actingAs($otherUser);

    $this->postJson("/api/rides/{$ride->id}/review", [
        'rating' => 3,
    ])->assertStatus(403)
      ->assertJsonPath('message', 'Only the passenger can review this ride.');
});

test('review creation is idempotent for same ride', function () {
    $passenger = User::create([
        'name' => 'Passenger I',
        'email' => 'review-idempotent@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver I',
        'email' => 'driver-idempotent@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(reviewRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
    ]));

    Review::create([
        'ride_id' => $ride->id,
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'rating' => 5,
        'review_text' => 'Already exists',
    ]);

    Sanctum::actingAs($passenger);

    $this->postJson("/api/rides/{$ride->id}/review", [
        'rating' => 1,
        'review_text' => 'Should not overwrite',
    ])->assertOk()
      ->assertJsonPath('message', 'Review already exists for this ride.');

    expect(Review::where('ride_id', $ride->id)->count())->toBe(1);
    expect((int) Review::where('ride_id', $ride->id)->first()->rating)->toBe(5);
});

test('driver rating endpoint returns average and count', function () {
    $viewer = User::create([
        'name' => 'Viewer',
        'email' => 'review-viewer@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Rated Driver',
        'email' => 'rated-driver@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    Review::create([
        'ride_id' => 1,
        'user_id' => $viewer->id,
        'driver_id' => $driver->id,
        'rating' => 4,
        'review_text' => 'Good',
    ]);

    Review::create([
        'ride_id' => 2,
        'user_id' => $viewer->id,
        'driver_id' => $driver->id,
        'rating' => 5,
        'review_text' => 'Great',
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson("/api/drivers/{$driver->id}/rating")
        ->assertOk()
        ->assertJsonPath('driver_id', $driver->id)
        ->assertJsonPath('reviews_count', 2)
        ->assertJsonPath('average_rating', 4.5);
});
