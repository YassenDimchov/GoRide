<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RideStatus;

test('ride status enum has expected values', function () {
    expect(RideStatus::PENDING->value)->toBe('pending');
    expect(RideStatus::ACCEPTED->value)->toBe('accepted');
    expect(RideStatus::ONGOING->value)->toBe('ongoing');
    expect(RideStatus::COMPLETED->value)->toBe('completed');
    expect(RideStatus::CANCELLED->value)->toBe('cancelled');
});

test('payment status enum has expected values', function () {
    expect(PaymentStatus::Pending->value)->toBe('pending');
    expect(PaymentStatus::Paid->value)->toBe('paid');
    expect(PaymentStatus::Failed->value)->toBe('failed');
});

test('payment method enum has expected values', function () {
    expect(PaymentMethod::Cash->value)->toBe('cash');
    expect(PaymentMethod::Card->value)->toBe('card');
});
