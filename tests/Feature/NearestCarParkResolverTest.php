<?php

declare(strict_types=1);

use App\Models\CarPark;
use App\Support\NearestCarParkResolver;

test('resolver returns nearest car park by haversine distance', function () {
    CarPark::query()->create([
        'name' => 'North',
        'capacity' => 100,
        'location' => 'North',
        'latitude' => 51.457957634545735,
        'longitude' => -0.34176550753929336,
    ]);

    CarPark::query()->create([
        'name' => 'West',
        'capacity' => 80,
        'location' => 'West',
        'latitude' => 51.454889526524305,
        'longitude' => -0.3434177482924672,
    ]);

    $nearest = app(NearestCarParkResolver::class)->resolve(
        51.45796,
        -0.34177,
    );

    expect($nearest)->not->toBeNull()
        ->and($nearest['name'])->toBe('North')
        ->and($nearest['distance_meters'])->toBeLessThan(20);
});

test('resolver returns null when no car parks have coordinates', function () {
    CarPark::query()->create([
        'name' => 'No Coords',
        'capacity' => 10,
        'location' => 'Somewhere',
    ]);

    $nearest = app(NearestCarParkResolver::class)->resolve(51.45, -0.34);

    expect($nearest)->toBeNull();
});
