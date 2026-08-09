<?php

use App\Livewire\Admin\CarParks;
use App\Livewire\Attendant\Scan;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('guest cannot access car parks page', function () {
    $this->get(route('admin.car-parks'))->assertRedirect();
});

test('car parks page shows per-day assigned demand against day capacity', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'North Car Park',
        'capacity' => 10,
        'capacity_friday' => 3,
        'capacity_saturday' => 10,
        'capacity_sunday' => 10,
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
        ->assertSee('4 / 3')
        ->assertSee('0 / 10')
        ->assertSee('Over by 1')
        ->assertSee('Total over capacity')
        ->assertSee('Clocked in (live)')
        ->assertSee('Registered for that day');
});

test('car parks page excludes drop-off coaches from capacity and shows them separately', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Coach Mix Park',
        'capacity' => 10,
        'capacity_friday' => 10,
        'capacity_saturday' => 10,
        'capacity_sunday' => 10,
        'location' => 'Coach side',
        'color' => '#0ea5e9',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Coach Mix Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Car Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700000001',
        'email' => 'car@test.com',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CA01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Staying Coach',
        'congregation' => $congregation->name,
        'contact_number' => '07700000002',
        'email' => 'stay@test.com',
        'vehicle_type' => 'coach',
        'vehicle_registration' => 'ST01BBB',
        'days' => ['Friday'],
        'coach_staying_on_site' => true,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Drop Off Coach',
        'congregation' => $congregation->name,
        'contact_number' => '07700000003',
        'email' => 'drop@test.com',
        'vehicle_type' => 'coach',
        'vehicle_registration' => 'DO01CCC',
        'days' => ['Friday'],
        'coach_staying_on_site' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->assertSee('Coach Mix Park')
        ->assertSee('2 / 10')
        ->assertDontSee('3 / 10')
        ->assertSee('1 coach not staying at Twickenham')
        ->assertSee('1 drop-off coach (not counted)')
        ->assertSee('+1 drop-off')
        ->assertSee('View coaches');
});

test('car parks page shows green live segment for clocked in vehicles', function () {
    $admin = User::factory()->admin()->create();

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
        ->assertSee('2 in / 10')
        ->assertSee('4 / 10');
});

test('individual registration car park override counts toward override park not congregation default', function () {
    $admin = User::factory()->admin()->create();

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

    $html = Livewire::actingAs($admin)->test(CarParks::class)->html();

    expect(substr_count($html, '1 / 10'))->toBeGreaterThanOrEqual(2);
});

test('parked pass with scan car park id counts toward occupancy even when congregation assigned elsewhere', function () {
    $admin = User::factory()->admin()->create();

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
        ->assertSee('1 in / 10')
        ->assertSee('Congregation Park')
        ->assertSee('0 in / 10');
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

test('saving car park persists day capacities and syncs legacy capacity to max', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CarParks::class)
        ->set('name', 'Day Cap Park')
        ->set('capacityFriday', 5)
        ->set('capacitySaturday', 12)
        ->set('capacitySunday', 8)
        ->set('color', '#0ea5e9')
        ->call('save')
        ->assertHasNoErrors();

    $park = CarPark::query()->where('name', 'Day Cap Park')->first();

    expect($park)->not->toBeNull()
        ->and($park->capacity_friday)->toBe(5)
        ->and($park->capacity_saturday)->toBe(12)
        ->and($park->capacity_sunday)->toBe(8)
        ->and($park->capacity)->toBe(12);
});

test('walk-in scan blocks when today day capacity is full', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-17 10:00:00')); // Friday

    $attendant = User::factory()->attendant()->create();

    $park = CarPark::query()->create([
        'name' => 'Tight Friday Park',
        'capacity_friday' => 1,
        'capacity_saturday' => 50,
        'capacity_sunday' => 50,
        'color' => '#dc2626',
    ]);

    expect($park->fresh()->capacity)->toBe(50);

    $congregation = Congregation::query()->create([
        'name' => 'Tight Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'FULL001',
        'scanned_at' => now(),
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('walkInMode', true)
        ->set('step', 'confirm')
        ->set('selectedCongregationId', $congregation->id)
        ->set('vehicleReg', 'FULL002')
        ->set('contactNumber', '07700999888')
        ->set('name', 'Second Driver')
        ->call('confirm')
        ->assertSet('lastScanResult', 'error')
        ->assertSee('CAR PARK FULL');

    Carbon::setTestNow();
});
