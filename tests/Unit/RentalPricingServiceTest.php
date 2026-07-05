<?php

use App\Services\RentalPricingService;

test('rental price decreases progressively for a longer booking', function (int $minutes, float $expected) {
    $pricingService = new RentalPricingService;

    expect($pricingService->calculate($minutes, 2.50))->toBe($expected);
})->with([
    'one hour at the full rate' => [60, 150.0],
    'three hours with a discount' => [180, 300.0],
    'six hours with a larger discount' => [360, 412.5],
    'one day is limited by the daily cap' => [1440, 600.0],
    'two days use two daily caps' => [2880, 1200.0],
    'one day and one hour starts a new tariff day' => [1500, 750.0],
]);

test('rental price is zero for an invalid duration or rate', function () {
    $pricingService = new RentalPricingService;

    expect($pricingService->calculate(0, 2.50))->toBe(0.0)
        ->and($pricingService->calculate(60, 0))->toBe(0.0);
});
