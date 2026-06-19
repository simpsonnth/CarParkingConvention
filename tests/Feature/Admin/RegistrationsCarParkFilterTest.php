<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('registrations car park filter includes congregation-assigned registrations', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Assigned Park',
        'capacity' => 50,
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Filtered Congregation',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Inherited Park Registrant',
        'congregation' => $congregation->name,
        'contact_number' => '07700000001',
        'email' => 'inherited@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'INH01AAA',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftCarParks', [$park->id])
        ->call('applyFilters')
        ->assertSee('Inherited Park Registrant');
});

test('registrations car park filter still matches explicit registration overrides', function () {
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Override Park',
        'capacity' => 50,
    ]);

    $otherPark = CarPark::query()->create([
        'name' => 'Other Park',
        'capacity' => 50,
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Override Congregation',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $otherPark->id,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Override Registrant',
        'congregation' => $congregation->name,
        'car_park_id' => $park->id,
        'contact_number' => '07700000002',
        'email' => 'override@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'OVR01BBB',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftCarParks', [$park->id])
        ->call('applyFilters')
        ->assertSee('Override Registrant')
        ->assertDontSee('Inherited Park Registrant');
});
