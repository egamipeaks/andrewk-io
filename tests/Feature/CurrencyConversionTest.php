<?php

use App\Enums\Currency;

test('USD to USD rate is 1.0', function () {
    expect(Currency::USD->toUsdRate())->toBe(1.0);
});

test('CAD to USD rate is 0.71', function () {
    expect(Currency::CAD->toUsdRate())->toBe(0.71);
});

test('USD amount converts to itself', function () {
    $amount = 100.00;

    expect(Currency::USD->toUsd($amount))->toBe(100.0);
});

test('CAD amount converts to USD correctly', function () {
    $amount = 100.00;

    expect(Currency::CAD->toUsd($amount))->toBe(71.0);
});

test('zero amount converts to zero', function () {
    expect(Currency::USD->toUsd(0))->toBe(0.0);
    expect(Currency::CAD->toUsd(0))->toBe(0.0);
});

test('decimal amounts convert correctly', function () {
    expect(Currency::CAD->toUsd(150.50))->toBe(106.85);
    expect(Currency::USD->toUsd(150.50))->toBe(150.50);
});

test('large amounts convert correctly with rounding', function () {
    expect(Currency::CAD->toUsd(1000.00))->toBe(710.0);
    expect(Currency::CAD->toUsd(999.99))->toBe(709.99);
    expect(Currency::USD->toUsd(1000.00))->toBe(1000.0);
});

test('fractional cents round correctly', function () {
    // 100.33 CAD × 0.71 = 71.2343 → rounds to 71.23
    expect(Currency::CAD->toUsd(100.33))->toBe(71.23);

    // 100.37 CAD × 0.71 = 71.2627 → rounds to 71.26
    expect(Currency::CAD->toUsd(100.37))->toBe(71.26);

    // 100.36 CAD × 0.71 = 71.2556 → rounds to 71.26
    expect(Currency::CAD->toUsd(100.36))->toBe(71.26);
});
