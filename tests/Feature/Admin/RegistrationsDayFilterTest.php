<?php

declare(strict_types=1);

use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('friday exact day filter excludes multi-day friday registrations', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Friday Only Driver',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000001',
        'email' => 'friday-only@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FR01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Friday Saturday Driver',
        'congregation' => 'Beta Hall',
        'contact_number' => '07700000002',
        'email' => 'friday-saturday@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FR02BBB',
        'days' => ['Friday', 'Saturday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Saturday Only Driver',
        'congregation' => 'Gamma Hall',
        'contact_number' => '07700000003',
        'email' => 'saturday-only@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'SA03CCC',
        'days' => ['Saturday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftDays', ['Friday'])
        ->call('applyFilters')
        ->assertSee('Friday Only Driver')
        ->assertDontSee('Friday Saturday Driver')
        ->assertDontSee('Saturday Only Driver');
});

test('friday and saturday exact day filter matches regardless of stored order', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Fri Sat Canonical',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000011',
        'email' => 'fri-sat@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FS01AAA',
        'days' => ['Friday', 'Saturday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Sat Fri Reversed',
        'congregation' => 'Beta Hall',
        'contact_number' => '07700000012',
        'email' => 'sat-fri@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'SF02BBB',
        'days' => ['Saturday', 'Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'All Three Days',
        'congregation' => 'Gamma Hall',
        'contact_number' => '07700000013',
        'email' => 'all-three@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'AT03CCC',
        'days' => ['Friday', 'Saturday', 'Sunday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftDays', ['Friday', 'Saturday'])
        ->call('applyFilters')
        ->assertSee('Fri Sat Canonical')
        ->assertSee('Sat Fri Reversed')
        ->assertDontSee('All Three Days');
});

test('exact day filter excludes empty days and clears correctly', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Sunday Only Driver',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000021',
        'email' => 'sunday-only@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'SU01AAA',
        'days' => ['Sunday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Empty Days Driver',
        'congregation' => 'Beta Hall',
        'contact_number' => '07700000022',
        'email' => 'empty-days@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'EM02BBB',
        'days' => [],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftDays', ['Sunday'])
        ->call('applyFilters')
        ->assertSee('Sunday Only Driver')
        ->assertDontSee('Empty Days Driver')
        ->call('clearFilters')
        ->assertSee('Sunday Only Driver')
        ->assertSee('Empty Days Driver');
});

test('exact day filter can combine with vehicle type', function () {
    $admin = User::factory()->admin()->create();

    ParkingRegistration::query()->create([
        'name' => 'Friday Car Driver',
        'congregation' => 'Alpha Hall',
        'contact_number' => '07700000031',
        'email' => 'friday-car@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'FC01AAA',
        'days' => ['Friday'],
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Friday Coach Driver',
        'congregation' => 'Beta Hall',
        'contact_number' => '07700000032',
        'email' => 'friday-coach@example.test',
        'vehicle_type' => 'coach',
        'vehicle_registration' => 'FC02BBB',
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->set('filterDraftDays', ['Friday'])
        ->set('filterDraftVehicleType', ['coach'])
        ->call('applyFilters')
        ->assertSee('Friday Coach Driver')
        ->assertDontSee('Friday Car Driver');
});
