<?php

declare(strict_types=1);

use App\Actions\Attendant\LookupParkingRegistration;
use App\Livewire\Attendant\Scan;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

function createVehicleLookupFixtures(): array
{
    $attendant = User::factory()->attendant()->create();

    $park = CarPark::query()->create([
        'name' => 'Lookup Park',
        'capacity' => 80,
        'location' => 'North',
        'color' => '#0f766e',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Lookup Hall',
        'uuid' => 'lookup-hall-uuid',
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Lookup Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700999888',
        'email' => 'lookup@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'LK12PAR',
        'days' => ['Friday'],
    ]);

    return compact('attendant', 'park', 'congregation', 'registration');
}

test('attendant can look up vehicle by plate on scan page', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'park' => $park] = createVehicleLookupFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK 12 PAR')
        ->call('lookup')
        ->assertHasNoErrors()
        ->assertSet('lookupSearched', true)
        ->assertSee('Lookup Park')
        ->assertSee('Lookup Driver')
        ->assertSee('07700999888')
        ->assertSee('lookup@example.test')
        ->assertSee('LK12PAR');

    expect($registration->fresh())->not->toBeNull()
        ->and($park->name)->toBe('Lookup Park');
});

test('attendant can look up vehicle by ticket number', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createVehicleLookupFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', $registration->ticketNumber())
        ->call('lookup')
        ->assertHasNoErrors()
        ->assertSee('Lookup Driver')
        ->assertSee($registration->ticketNumber())
        ->assertSee('Lookup Park');
});

test('vehicle lookup shows not found for unknown plate', function () {
    ['attendant' => $attendant] = createVehicleLookupFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'ZZ99ZZZ')
        ->call('lookup')
        ->assertSet('lookupResults', [])
        ->assertSet('lookupError', 'No active registration found for that plate or ticket number.')
        ->assertSee('No active registration found');
});

test('vehicle lookup excludes soft-deleted registrations', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createVehicleLookupFixtures();

    $registration->update(['cancelled_via' => 'admin']);
    $registration->delete();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK12PAR')
        ->call('lookup')
        ->assertSet('lookupResults', [])
        ->assertSee('No active registration found');

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', (string) $registration->id)
        ->call('lookup')
        ->assertSet('lookupResults', [])
        ->assertSee('No active registration found');
});

test('attendant without registrations.view can use vehicle lookup', function () {
    ['attendant' => $attendant] = createVehicleLookupFixtures();

    expect($attendant->can('registrations.view'))->toBeFalse()
        ->and($attendant->can('scan.access'))->toBeTrue();

    $this->actingAs($attendant)
        ->get(route('attendant.scan'))
        ->assertOk()
        ->assertSee('Find vehicle');

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK12PAR')
        ->call('lookup')
        ->assertSee('Lookup Driver')
        ->assertSee('Lookup Park');
});

test('check in from lookup opens ticket confirm flow', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createVehicleLookupFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK12PAR')
        ->call('lookup')
        ->call('checkInFromLookup', $registration->id)
        ->assertSet('step', 'confirm')
        ->assertSet('quickCheckIn', true)
        ->assertSet('lookupResults', [])
        ->assertSee('Ticket Verified')
        ->assertSee('Lookup Driver');
});

test('lookup action resolves individual car park override', function () {
    ['registration' => $registration] = createVehicleLookupFixtures();

    $override = CarPark::query()->create([
        'name' => 'Override Park',
        'capacity' => 20,
        'location' => 'Side',
        'color' => '#b45309',
    ]);

    $registration->update(['car_park_id' => $override->id]);

    $results = app(LookupParkingRegistration::class)->execute('LK12PAR');

    expect($results)->toHaveCount(1)
        ->and($results[0]['car_park_name'])->toBe('Override Park')
        ->and($results[0]['car_park_is_individual'])->toBeTrue()
        ->and($results[0]['can_check_in'])->toBeTrue();
});

test('circuit overseer lookup shows details but cannot check in', function () {
    ['attendant' => $attendant, 'congregation' => $congregation] = createVehicleLookupFixtures();

    $co = ParkingRegistration::query()->create([
        'name' => 'Circuit Overseer',
        'congregation' => $congregation->name,
        'contact_number' => '07700111000',
        'email' => 'co@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'CO12SEE',
        'is_circuit_overseer' => true,
        'days' => ['Friday'],
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'CO12SEE')
        ->call('lookup')
        ->assertSee('Circuit Overseer')
        ->assertSee('Lookup Park')
        ->assertSee('use walk-in check-in')
        ->assertDontSee('Check in this vehicle');

    $results = app(LookupParkingRegistration::class)->execute((string) $co->id);
    expect($results[0]['can_check_in'])->toBeFalse()
        ->and($results[0]['is_circuit_overseer'])->toBeTrue();
});

test('lookup shows clock out and clock-in time when vehicle is already parked', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'congregation' => $congregation, 'park' => $park] = createVehicleLookupFixtures();

    $pass = ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'LK12PAR',
        'contact_number' => '07700999888',
        'scanned_at' => now()->setTime(14, 32),
        'scanned_by_user_id' => $attendant->id,
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK12PAR')
        ->call('lookup')
        ->assertSee('Already parked')
        ->assertSee('Clocked in at')
        ->assertSee('14:32')
        ->assertSee('Clock out')
        ->assertDontSee('Find my car')
        ->assertDontSee('Check in this vehicle')
        ->call('clockOut', $pass->id)
        ->assertSee('Check in this vehicle')
        ->assertDontSee('Already parked');

    expect($pass->fresh()->status)->toBe('left');

    $results = app(LookupParkingRegistration::class)->execute('LK12PAR');
    expect($results[0]['is_parked'])->toBeFalse()
        ->and($results[0]['can_check_in'])->toBeTrue()
        ->and($results[0]['parked_check_in_maps_url'])->toBeNull();
});

test('lookup includes open in maps url when parked pass has check-in coordinates', function () {
    ['attendant' => $attendant, 'congregation' => $congregation, 'park' => $park] = createVehicleLookupFixtures();

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'LK12PAR',
        'contact_number' => '07700999888',
        'scanned_at' => now(),
        'scanned_by_user_id' => $attendant->id,
        'check_in_latitude' => 51.507351,
        'check_in_longitude' => -0.127758,
    ]);

    $results = app(LookupParkingRegistration::class)->execute('LK12PAR');

    expect($results)->toHaveCount(1)
        ->and($results[0]['is_parked'])->toBeTrue()
        ->and($results[0]['parked_check_in_maps_url'])->toBe(
            'https://www.google.com/maps/dir/?api=1&destination=51.507351,-0.127758'
        );

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('lookupQuery', 'LK12PAR')
        ->call('lookup')
        ->assertSee('Already parked')
        ->assertSee('Find my car')
        ->assertSeeHtml('href="https://www.google.com/maps/dir/?api=1&amp;destination=51.507351,-0.127758"');
});
