<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('unassigned car park filter shows registrations without effective car park', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Assigned Park',
        'capacity' => 50,
    ]);

    $assignedCongregation = Congregation::query()->create([
        'name' => 'Assigned Congregation',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    $unassignedCongregation = Congregation::query()->create([
        'name' => 'Unassigned Congregation',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Inherited Park Registrant',
        'congregation' => $assignedCongregation->name,
        'contact_number' => '07700000001',
        'email' => 'inherited@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'INH01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'No Park Registrant',
        'congregation' => $unassignedCongregation->name,
        'contact_number' => '07700000002',
        'email' => 'unassigned@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'UNA01BBB',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Override Park Registrant',
        'congregation' => $unassignedCongregation->name,
        'car_park_id' => $park->id,
        'contact_number' => '07700000003',
        'email' => 'override@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OVR01CCC',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftUnassignedCarPark', true)
        ->call('applyFilters')
        ->assertSee('No Park Registrant')
        ->assertDontSee('Inherited Park Registrant')
        ->assertDontSee('Override Park Registrant');
});

test('unassigned car park filter includes circuit overseers without car park', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Colin Ahaneku',
        'congregation' => 'Circuit Overseer',
        'is_circuit_overseer' => true,
        'contact_number' => '07700000004',
        'email' => 'colin@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CO01DDD',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftUnassignedCarPark', true)
        ->call('applyFilters')
        ->assertSee('Colin Ahaneku');
});
