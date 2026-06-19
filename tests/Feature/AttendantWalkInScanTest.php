<?php

use App\Livewire\Attendant\Scan;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\User;
use Livewire\Livewire;

function createWalkInScanFixtures(): array
{
    $attendant = User::factory()->attendant()->create();

    $park = CarPark::query()->create([
        'name' => 'Walk-in Park',
        'capacity' => 40,
        'location' => 'East',
        'color' => '#059669',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Walk-in Hall',
        'uuid' => 'walk-in-uuid',
        'car_park_id' => $park->id,
    ]);

    return compact('attendant', 'park', 'congregation');
}

test('guest cannot access walk-in scan route', function () {
    $this->get(route('attendant.scan.walk-in'))->assertRedirect();
});

test('walk-in scan route shows congregation picker and manual form', function () {
    ['attendant' => $attendant, 'congregation' => $congregation] = createWalkInScanFixtures();

    $this->actingAs($attendant)
        ->get(route('attendant.scan.walk-in'))
        ->assertOk()
        ->assertSee('Walk-in Check-in')
        ->assertSee('Select congregation')
        ->assertSee('Vehicle Plate Number')
        ->assertSee($congregation->name);
});

test('walk-in clock in requires congregation selection', function () {
    ['attendant' => $attendant] = createWalkInScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('walkInMode', true)
        ->set('step', 'confirm')
        ->set('vehicleReg', 'WI12ALK')
        ->set('contactNumber', '07700333444')
        ->call('confirm')
        ->assertHasErrors(['selectedCongregationId']);
});

test('walk-in clock in creates parking pass', function () {
    ['attendant' => $attendant, 'congregation' => $congregation, 'park' => $park] = createWalkInScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('walkInMode', true)
        ->set('step', 'confirm')
        ->set('selectedCongregationId', $congregation->id)
        ->set('vehicleReg', 'WI12ALK')
        ->set('contactNumber', '07700333444')
        ->set('name', 'Walk-in Driver')
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->congregation_id)->toBe($congregation->id)
        ->and($pass->car_park_id)->toBe($park->id)
        ->and($pass->vehicle_reg)->toBe('WI12ALK')
        ->and($pass->name)->toBe('Walk-in Driver');
});

test('guest cannot access coach walk-in scan route', function () {
    $this->get(route('attendant.scan.walk-in.coach'))->assertRedirect();
});

test('coach walk-in scan route shows coach walk-in form', function () {
    ['attendant' => $attendant, 'congregation' => $congregation] = createWalkInScanFixtures();

    $this->actingAs($attendant)
        ->get(route('attendant.scan.walk-in.coach'))
        ->assertOk()
        ->assertSee('Coach Walk-in Check-in')
        ->assertSee('Coach captain to be assigned')
        ->assertSee($congregation->name);
});

test('coach walk-in clock in creates coach parking pass and registration', function () {
    ['attendant' => $attendant, 'congregation' => $congregation, 'park' => $park] = createWalkInScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('walkInMode', true)
        ->set('walkInVehicleType', 'coach')
        ->set('step', 'confirm')
        ->set('selectedCongregationId', $congregation->id)
        ->set('vehicleReg', 'CO01ACH')
        ->set('contactNumber', '07700555666')
        ->set('name', 'Coach Driver')
        ->set('coachCaptainToBeAssigned', true)
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->vehicle_reg)->toBe('CO01ACH')
        ->and($pass->notes)->toContain('Coach walk-in')
        ->and($pass->notes)->toContain('captain TBA');

    $registration = \App\Models\ParkingRegistration::query()->where('vehicle_registration', 'CO01ACH')->first();
    expect($registration)->not->toBeNull()
        ->and($registration->vehicle_type)->toBe('coach')
        ->and($registration->coach_captain_to_be_assigned)->toBeTrue();
});
