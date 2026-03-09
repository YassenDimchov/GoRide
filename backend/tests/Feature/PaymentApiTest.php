<?php

use App\Enums\PaymentStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Payment;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    resetApiTestData();
});

function paymentRidePayload(array $overrides = []): array
{
    return array_merge([
        'start_lat' => 42.6977,
        'start_lng' => 23.3219,
        'end_lat' => 42.6611,
        'end_lng' => 23.3349,
        'start_address' => 'Start',
        'end_address' => 'End',
        'passenger_count' => 1,
        'trip_distance_m' => 3000,
        'trip_duration_s' => 600,
    ], $overrides);
}

test('passenger can pay own pending payment', function () {
    $passenger = User::create([
        'name' => 'Passenger',
        'email' => 'pay-own@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $passenger->id,
        'status' => RideStatus::COMPLETED->value,
        'fare' => 9.50,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 9.50,
        'method' => 'cash',
        'status' => PaymentStatus::Pending->value,
    ]);

    Sanctum::actingAs($passenger);

    $this->postJson("/api/payments/{$payment->id}/pay")
        ->assertOk()
        ->assertJsonPath('payment.status', PaymentStatus::Paid->value);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid->value);
    expect($payment->fresh()->paid_at)->not->toBeNull();
});

test('passenger cannot pay another users payment', function () {
    $owner = User::create([
        'name' => 'Owner',
        'email' => 'payment-owner@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $other = User::create([
        'name' => 'Other',
        'email' => 'payment-other@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $owner->id,
        'status' => RideStatus::COMPLETED->value,
        'fare' => 10.00,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 10.00,
        'method' => 'cash',
        'status' => PaymentStatus::Pending->value,
    ]);

    Sanctum::actingAs($other);

    $this->postJson("/api/payments/{$payment->id}/pay")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Forbidden');
});

test('driver can confirm assigned pending cash payment', function () {
    $passenger = User::create([
        'name' => 'Passenger C',
        'email' => 'passenger-confirm@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver C',
        'email' => 'driver-confirm@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
        'fare' => 12.00,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 12.00,
        'method' => 'cash',
        'status' => PaymentStatus::Pending->value,
    ]);

    Sanctum::actingAs($driverUser);

    $this->postJson("/api/payments/{$payment->id}/confirm")
        ->assertOk()
        ->assertJsonPath('payment.status', PaymentStatus::Paid->value);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid->value);
});

test('driver can report unpaid cash payment by email', function () {
    Mail::fake();

    $passenger = User::create([
        'name' => 'Passenger R',
        'email' => 'passenger-report@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $driverUser = User::create([
        'name' => 'Driver R',
        'email' => 'driver-report@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'driver',
    ]);

    $driver = Driver::create([
        'user_id' => $driverUser->id,
        'status' => 'available',
        'passenger_capacity' => 4,
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $passenger->id,
        'driver_id' => $driver->id,
        'status' => RideStatus::COMPLETED->value,
        'completed_at' => now(),
        'fare' => 14.00,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 14.00,
        'method' => 'cash',
        'status' => PaymentStatus::Pending->value,
    ]);

    Sanctum::actingAs($driverUser);

    $this->postJson("/api/payments/{$payment->id}/report-unpaid", [
        'recipient_email' => 'ops@example.com',
        'note' => 'Passenger did not pay.',
    ])->assertOk()
        ->assertJsonPath('sent_to', 'ops@example.com');
});

test('stripe checkout can be created for pending card payment', function () {
    config()->set('services.stripe.secret_key', 'sk_test_123');
    config()->set('services.stripe.currency', 'eur');

    Http::fake([
        'https://api.stripe.com/v1/checkout/sessions' => Http::response([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.test/session/cs_test_123',
        ], 200),
    ]);

    $passenger = User::create([
        'name' => 'Stripe Passenger',
        'email' => 'stripe-passenger@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $passenger->id,
        'status' => RideStatus::COMPLETED->value,
        'fare' => 18.20,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 18.20,
        'method' => 'card',
        'status' => PaymentStatus::Pending->value,
    ]);

    Sanctum::actingAs($passenger);

    $this->postJson("/api/payments/{$payment->id}/stripe-checkout")
        ->assertOk()
        ->assertJsonPath('session_id', 'cs_test_123')
        ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session/cs_test_123');
});

test('stripe confirm marks payment paid when stripe session is paid', function () {
    config()->set('services.stripe.secret_key', 'sk_test_123');

    Http::fake([
        'https://api.stripe.com/v1/checkout/sessions/*' => Http::response([
            'payment_status' => 'paid',
            'metadata' => ['payment_id' => '1'],
        ], 200),
    ]);

    $passenger = User::create([
        'name' => 'Stripe Confirm Passenger',
        'email' => 'stripe-confirm@example.com',
        'password' => bcrypt('secret123'),
        'role' => 'user',
    ]);

    $ride = Ride::create(array_merge(paymentRidePayload(), [
        'user_id' => $passenger->id,
        'status' => RideStatus::COMPLETED->value,
        'fare' => 7.80,
    ]));

    $payment = Payment::create([
        'ride_id' => $ride->id,
        'amount' => 7.80,
        'method' => 'card',
        'status' => PaymentStatus::Pending->value,
    ]);

    Http::fake([
        'https://api.stripe.com/v1/checkout/sessions/*' => Http::response([
            'payment_status' => 'paid',
            'metadata' => ['payment_id' => (string) $payment->id],
        ], 200),
    ]);

    Sanctum::actingAs($passenger);

    $this->postJson("/api/payments/{$payment->id}/stripe-confirm", [
        'session_id' => 'cs_paid_123',
    ])->assertOk()
        ->assertJsonPath('message', 'Payment confirmed successfully');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid->value);
});
