<?php

use App\Support\Fare;

test('returns minimum fare for zero distance and time', function () {
    expect(Fare::estimate(0.0, 0))->toBe(3.50);
});

test('returns minimum fare when trip is inside included thresholds', function () {
    expect(Fare::estimate(2.0, 5))->toBe(3.50);
});

test('charges distance after included kilometers', function () {
    expect(Fare::estimate(3.0, 5))->toBe(3.50);
});

test('charges time after included minutes', function () {
    expect(Fare::estimate(2.0, 15))->toBe(3.60);
});

test('charges both distance and time over included thresholds', function () {
    expect(Fare::estimate(6.0, 25))->toBe(8.80);
});

test('rounds fare to two decimals', function () {
    expect(Fare::estimate(3.333, 14))->toBe(4.55);
});
