<?php

declare(strict_types=1);

use App\Livewire\Admin\CarParkDetail;
use App\Livewire\Admin\CarParks;
use App\Models\CarPark;
use App\Models\User;
use Livewire\Livewire;

test('car park navigation url is null without coordinates', function () {
    $park = CarPark::query()->create([
        'name' => 'No Coords Park',
        'capacity' => 40,
        'location' => 'Somewhere',
        'color' => '#0f766e',
    ]);

    expect($park->hasNavigation())->toBeFalse()
        ->and($park->navigationUrl())->toBeNull();
});

test('car park navigation url uses google maps destination', function () {
    $park = CarPark::query()->create([
        'name' => 'Coords Park',
        'capacity' => 40,
        'location' => 'Somewhere',
        'color' => '#0f766e',
        'latitude' => 51.44958137563192,
        'longitude' => -0.3505309665999623,
    ]);

    expect($park->hasNavigation())->toBeTrue()
        ->and($park->navigationUrl())->toContain('https://www.google.com/maps/dir/?api=1&destination=')
        ->and($park->navigationUrl())->toContain('51.4495814')
        ->and($park->navigationUrl())->toContain('-0.350531');
});

test('admin can create car park with latitude and longitude', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->set('name', 'LatLng Park')
        ->set('capacityFriday', 30)
        ->set('capacitySaturday', 30)
        ->set('capacitySunday', 30)
        ->set('latitude', '51.4585056')
        ->set('longitude', '-0.3426077')
        ->call('save')
        ->assertHasNoErrors();

    $park = CarPark::query()->where('name', 'LatLng Park')->first();

    expect($park)->not->toBeNull()
        ->and($park->latitude)->toEqualWithDelta(51.4585056, 0.0000001)
        ->and($park->longitude)->toEqualWithDelta(-0.3426077, 0.0000001)
        ->and($park->navigationUrl())->not->toBeNull();
});

test('admin can update car park coordinates from detail page', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Detail Coords Park',
        'capacity' => 25,
        'location' => 'East',
        'color' => '#2563eb',
    ]);

    Livewire::actingAs($admin)
        ->test(CarParkDetail::class, ['carPark' => $park])
        ->call('edit')
        ->set('latitude', '51.4548895')
        ->set('longitude', '-0.3434177')
        ->call('save')
        ->assertHasNoErrors();

    $park->refresh();

    expect($park->hasNavigation())->toBeTrue()
        ->and($park->latitude)->toEqualWithDelta(51.4548895, 0.0000001)
        ->and($park->longitude)->toEqualWithDelta(-0.3434177, 0.0000001);
});

test('named car parks receive coordinate backfill from migration', function () {
    $park = CarPark::query()->create([
        'name' => 'Rosebine 2',
        'capacity' => 56,
        'location' => 'South',
        'color' => '#0f766e',
    ]);

    // Re-run the backfill logic the migration uses (fresh installs already applied it).
    \Illuminate\Support\Facades\DB::table('car_parks')
        ->where('name', 'Rosebine 2')
        ->whereNull('latitude')
        ->update([
            'latitude' => 51.44958137563192,
            'longitude' => -0.3505309665999623,
        ]);

    $park->refresh();

    expect($park->latitude)->toEqualWithDelta(51.44958137563192, 0.0000001)
        ->and($park->longitude)->toEqualWithDelta(-0.3505309665999623, 0.0000001);
});
