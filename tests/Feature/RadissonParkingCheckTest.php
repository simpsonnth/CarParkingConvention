<?php

declare(strict_types=1);

use App\Livewire\Public\RadissonParkingCheck;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

function seedRadissonCheckPark(string $name = 'North'): CarPark
{
    return CarPark::query()->create([
        'name' => $name,
        'capacity' => 200,
        'location' => 'Twickenham',
    ]);
}

function seedRadissonCheckRegistration(CarPark $park, array $overrides = []): ParkingRegistration
{
    $cong = Congregation::query()->create([
        'name' => $overrides['congregation'] ?? ('Radisson Hall '.Str::random(6)),
        'uuid' => (string) Str::uuid(),
        'car_park_id' => $overrides['congregation_car_park_id'] ?? null,
    ]);

    return ParkingRegistration::query()->create(array_merge([
        'name' => 'Secret Guest',
        'congregation' => $cong->name,
        'contact_number' => '07700900999',
        'email' => 'secret.guest@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'ZZ99ZZZ',
        'days' => ['Friday', 'Saturday'],
        'car_park_id' => $park->id,
    ], $overrides));
}

test('public radisson parking check page is available without auth', function () {
    $this->get(route('management.radisson-parking-check'))
        ->assertOk()
        ->assertSee(__('radisson_parking_check.title'));
});

test('found registration by vehicle plate shows car park only without personal details', function () {
    $park = seedRadissonCheckPark('Rosebine');
    seedRadissonCheckRegistration($park, [
        'vehicle_registration' => 'AB12CDE',
    ]);

    Livewire::test(RadissonParkingCheck::class)
        ->set('vehicleRegistration', 'ab12 cde')
        ->call('check')
        ->assertSet('searched', true)
        ->assertSet('found', true)
        ->assertSet('carParkName', 'Rosebine')
        ->assertSee('Rosebine')
        ->assertSee(__('radisson_parking_check.found_label'))
        ->assertDontSee('Secret Guest')
        ->assertDontSee('secret.guest@example.test')
        ->assertDontSee('07700900999');
});

test('missing vehicle registration shows not found and link to guest parking request', function () {
    Livewire::test(RadissonParkingCheck::class)
        ->set('vehicleRegistration', 'NOMATCH1')
        ->call('check')
        ->assertSet('found', false)
        ->assertSee(__('radisson_parking_check.not_found_label'))
        ->assertSee(route('management.radisson-guest-parking'), false);
});

test('soft-deleted registration is treated as not found', function () {
    $park = seedRadissonCheckPark('West');
    $registration = seedRadissonCheckRegistration($park, [
        'vehicle_registration' => 'DELETED1',
    ]);
    $registration->delete();

    Livewire::test(RadissonParkingCheck::class)
        ->set('vehicleRegistration', 'DELETED1')
        ->call('check')
        ->assertSet('found', false)
        ->assertSee(__('radisson_parking_check.request_parking'));
});

test('print radisson info sheet requires parking-qr permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.parking-qr-codes.print-radisson-info'))
        ->assertForbidden();

    $user->givePermissionTo('parking-qr.view');

    $this->actingAs($user)
        ->get(route('admin.parking-qr-codes.print-radisson-info'))
        ->assertOk()
        ->assertSee(__('parking_qr.radisson_welcome_title'))
        ->assertSee(__('parking_qr.radisson_step2_title'))
        ->assertSee(route('management.radisson-parking-check'), false);
});
