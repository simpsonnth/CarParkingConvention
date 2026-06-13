<?php

use App\Livewire\Admin\CarParks;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('guest cannot access car parks page', function () {
    $this->get(route('admin.car-parks'))->assertRedirect();
});

test('car parks page shows assigned registrations and stacked utilization bar', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $park = CarPark::query()->create([
        'name' => 'North Car Park',
        'capacity' => 10,
        'location' => 'North side',
        'color' => '#22c55e',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Alpha Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    foreach (range(1, 4) as $i) {
        ParkingRegistration::query()->create([
            'name' => "Driver {$i}",
            'congregation' => $congregation->name,
            'contact_number' => "0770000000{$i}",
            'email' => "driver{$i}@alpha.test",
            'vehicle_type' => 'car',
            'vehicle_registration' => "AB12CD{$i}",
            'days' => ['Friday'],
        ]);
    }

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->assertSee('North Car Park')
        ->assertSee('0 in · 4 assigned / 10')
        ->assertSee('Clocked in')
        ->assertSee('Registered & assigned, not yet arrived')
        ->assertSee('0 clocked in · 4 not yet arrived · 6 spaces free')
        ->assertSeeHtml('width: 0%')
        ->assertSeeHtml('width: 40%');
});

test('car parks page shows green segment for clocked in vehicles', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $park = CarPark::query()->create([
        'name' => 'South Car Park',
        'capacity' => 10,
        'color' => '#3b82f6',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Beta Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    foreach (range(1, 4) as $i) {
        ParkingRegistration::query()->create([
            'name' => "Driver {$i}",
            'congregation' => $congregation->name,
            'contact_number' => "077000000{$i}",
            'email' => "beta{$i}@test.com",
            'vehicle_type' => 'car',
            'vehicle_registration' => "XY99ZZ{$i}",
            'days' => ['Saturday'],
        ]);
    }

    foreach (range(1, 2) as $i) {
        ParkingPass::query()->create([
            'congregation_id' => $congregation->id,
            'car_park_id' => $park->id,
            'status' => 'parked',
            'vehicle_reg' => "PARK0{$i}",
            'scanned_at' => now(),
        ]);
    }

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->assertSee('2 in · 4 assigned / 10')
        ->assertSeeHtml('width: 20%');
});

test('individual registration car park override counts toward override park not congregation default', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $defaultPark = CarPark::query()->create([
        'name' => 'Default Park',
        'capacity' => 10,
        'color' => '#111111',
    ]);

    $overridePark = CarPark::query()->create([
        'name' => 'Override Park',
        'capacity' => 10,
        'color' => '#222222',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Gamma Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $defaultPark->id,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Default Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000001',
        'email' => 'default@test.com',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'DF01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Override Driver',
        'congregation' => $congregation->name,
        'car_park_id' => $overridePark->id,
        'contact_number' => '07700000002',
        'email' => 'override@test.com',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OV01BBB',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->assertSee('Default Park')
        ->assertSee('Override Park');

    $html = Livewire::actingAs($admin)->test(CarParks::class)->html();

    expect(substr_count($html, '0 in · 1 assigned / 10'))->toBe(2);
});

test('parked pass with scan car park id counts toward occupancy even when congregation assigned elsewhere', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $congregationPark = CarPark::query()->create([
        'name' => 'Congregation Park',
        'capacity' => 10,
        'color' => '#aaaaaa',
    ]);

    $scanPark = CarPark::query()->create([
        'name' => 'Scan Park',
        'capacity' => 10,
        'color' => '#bbbbbb',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Delta Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $congregationPark->id,
    ]);

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $scanPark->id,
        'status' => 'parked',
        'vehicle_reg' => 'SCAN001',
        'scanned_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->assertSee('Scan Park')
        ->assertSee('1 in · 0 assigned / 10')
        ->assertSee('Congregation Park')
        ->assertSee('0 in · 0 assigned / 10');
});

test('assigned to car park scope respects effective park resolution', function () {
    $park = CarPark::query()->create([
        'name' => 'Scope Park',
        'capacity' => 5,
        'color' => '#cccccc',
    ]);

    $otherPark = CarPark::query()->create([
        'name' => 'Other Park',
        'capacity' => 5,
        'color' => '#dddddd',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Epsilon Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Via Congregation',
        'congregation' => $congregation->name,
        'contact_number' => '07700000001',
        'email' => 'via-cong@test.com',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'VC01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Via Override',
        'congregation' => $congregation->name,
        'car_park_id' => $otherPark->id,
        'contact_number' => '07700000002',
        'email' => 'via-override@test.com',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'VO01BBB',
        'days' => ['Friday'],
    ]);

    expect(ParkingRegistration::query()->assignedToCarPark($park->id)->count())->toBe(1);
    expect(ParkingRegistration::query()->assignedToCarPark($otherPark->id)->count())->toBe(1);
});
