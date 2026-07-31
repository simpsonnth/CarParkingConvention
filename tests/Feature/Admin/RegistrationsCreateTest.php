<?php

use App\Livewire\Admin\Registrations;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

function createAdminRegistrationFixtures(): array
{
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Admin Create Park',
        'capacity' => 40,
        'location' => 'North',
        'color' => '#4338ca',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Admin Create Hall',
        'uuid' => 'admin-create-hall-uuid',
        'car_park_id' => $park->id,
    ]);

    return compact('admin', 'park', 'congregation');
}

test('admin can create a car registration from the registrations page', function () {
    ['admin' => $admin, 'congregation' => $congregation] = createAdminRegistrationFixtures();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('create')
        ->assertSet('modalOpen', true)
        ->assertSet('editingRegistration', null)
        ->set('name', 'New Driver')
        ->set('congregation', $congregation->name)
        ->set('vehicleType', 'car')
        ->set('vehicleReg', 'AB12 CDE')
        ->set('contactNumber', '07700900111')
        ->set('email', 'driver@example.test')
        ->set('days', ['Friday', 'Saturday'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('modalOpen', false);

    $registration = ParkingRegistration::query()->where('vehicle_registration', 'AB12CDE')->first();

    expect($registration)->not->toBeNull()
        ->and($registration->name)->toBe('New Driver')
        ->and($registration->congregation)->toBe($congregation->name)
        ->and($registration->vehicle_type)->toBe('car')
        ->and($registration->days)->toBe(['Friday', 'Saturday'])
        ->and($registration->contact_number)->toBe('07700900111')
        ->and($registration->email)->toBe('driver@example.test');
});

test('admin create registration requires congregation and days', function () {
    ['admin' => $admin] = createAdminRegistrationFixtures();

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('create')
        ->set('name', 'Incomplete Driver')
        ->set('congregation', '')
        ->set('vehicleType', 'car')
        ->set('vehicleReg', 'ZZ99ZZZ')
        ->set('contactNumber', '07700900222')
        ->set('days', [])
        ->call('save')
        ->assertHasErrors(['congregation', 'days']);

    expect(ParkingRegistration::query()->where('vehicle_registration', 'ZZ99ZZZ')->exists())->toBeFalse();
});

test('admin edit registration still updates existing row', function () {
    ['admin' => $admin, 'congregation' => $congregation] = createAdminRegistrationFixtures();

    $registration = ParkingRegistration::query()->create([
        'name' => 'Existing Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700900333',
        'vehicle_registration' => 'EE11EEE',
        'days' => ['Sunday'],
        'email' => 'existing@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('edit', $registration->id)
        ->assertSet('modalOpen', true)
        ->set('name', 'Updated Driver')
        ->set('contactNumber', '07700900444')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('modalOpen', false);

    expect($registration->fresh()->name)->toBe('Updated Driver')
        ->and($registration->fresh()->contact_number)->toBe('07700900444');
});
