<?php

use App\Livewire\Attendant\Scan;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\User;
use Livewire\Livewire;

function createWalkInScanFixtures(): array
{
    $attendant = User::factory()->create(['role' => 'attendant']);

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
