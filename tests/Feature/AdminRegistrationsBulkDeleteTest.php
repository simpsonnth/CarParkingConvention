<?php

use App\Livewire\Admin\Registrations;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

test('bulk delete standard cars removes only standard cars and preserves coaches disabled and circuit overseers', function () {
    $standard = ParkingRegistration::query()->create([
        'name' => 'Standard Car',
        'congregation' => 'Alpha',
        'contact_number' => '1',
        'vehicle_registration' => 'AA11AAA',
        'days' => ['Friday'],
        'email' => 'standard@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ]);

    $disabled = ParkingRegistration::query()->create([
        'name' => 'Disabled Car',
        'congregation' => 'Alpha',
        'contact_number' => '2',
        'vehicle_registration' => 'BB22BBB',
        'days' => ['Saturday'],
        'email' => 'disabled@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
        'is_circuit_overseer' => false,
    ]);

    $coach = ParkingRegistration::query()->create([
        'name' => 'Coach Captain',
        'congregation' => 'Alpha',
        'contact_number' => '3',
        'vehicle_registration' => null,
        'days' => ['Friday'],
        'email' => 'coach@example.test',
        'vehicle_type' => 'coach',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ]);

    $co = ParkingRegistration::query()->create([
        'name' => 'Circuit Overseer',
        'congregation' => 'Circuit Overseer',
        'contact_number' => '4',
        'vehicle_registration' => 'CC33CCC',
        'days' => ['Sunday'],
        'email' => 'co@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => true,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('bulkDeleteStandardCarRegistrations');

    expect(ParkingRegistration::find($standard->id))->toBeNull();
    expect(ParkingRegistration::find($disabled->id))->not->toBeNull();
    expect(ParkingRegistration::find($coach->id))->not->toBeNull();
    expect(ParkingRegistration::find($co->id))->not->toBeNull();

    expect(ParkingRegistration::onlyTrashed()->whereKey($standard->id)->exists())->toBeTrue();
});

test('bulk delete disabled registrations preserves coaches and circuit overseers including disabled co rows', function () {
    $standard = ParkingRegistration::query()->create([
        'name' => 'Standard Car',
        'congregation' => 'Beta',
        'contact_number' => '1',
        'vehicle_registration' => 'DD44DDD',
        'days' => ['Friday'],
        'email' => 'std2@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ]);

    $disabled = ParkingRegistration::query()->create([
        'name' => 'Disabled Car',
        'congregation' => 'Beta',
        'contact_number' => '2',
        'vehicle_registration' => 'EE55EEE',
        'days' => ['Saturday'],
        'email' => 'dis2@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
        'is_circuit_overseer' => false,
    ]);

    $coach = ParkingRegistration::query()->create([
        'name' => 'Coach Two',
        'congregation' => 'Beta',
        'contact_number' => '3',
        'vehicle_registration' => null,
        'days' => ['Friday'],
        'email' => 'coach2@example.test',
        'vehicle_type' => 'coach',
        'elderly_infirm_parking' => false,
        'is_circuit_overseer' => false,
    ]);

    $coDisabled = ParkingRegistration::query()->create([
        'name' => 'CO Disabled',
        'congregation' => 'Circuit Overseer',
        'contact_number' => '4',
        'vehicle_registration' => 'FF66FFF',
        'days' => ['Sunday'],
        'email' => 'codis@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
        'is_circuit_overseer' => true,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->call('bulkDeleteDisabledRegistrations');

    expect(ParkingRegistration::find($standard->id))->not->toBeNull();
    expect(ParkingRegistration::find($disabled->id))->toBeNull();
    expect(ParkingRegistration::find($coach->id))->not->toBeNull();
    expect(ParkingRegistration::find($coDisabled->id))->not->toBeNull();

    expect(ParkingRegistration::onlyTrashed()->whereKey($disabled->id)->exists())->toBeTrue();
});

test('guest parking registration URL responds at parking-registration', function () {
    $this->get('/parking-registration')->assertOk();
});
