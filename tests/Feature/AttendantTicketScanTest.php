<?php

use App\Livewire\Attendant\Scan;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingPass;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

function createTicketScanFixtures(): array
{
    $attendant = User::factory()->attendant()->create();

    $park = CarPark::query()->create([
        'name' => 'Scan Park',
        'capacity' => 50,
        'location' => 'Main',
        'color' => '#4338ca',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Ticket Hall',
        'uuid' => 'ticket-hall-uuid',
        'car_park_id' => $park->id,
    ]);

    $registration = ParkingRegistration::query()->create([
        'name' => 'Ticket Driver',
        'congregation' => $congregation->name,
        'contact_number' => '07700111222',
        'email' => 'ticket@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'TK12ET',
        'days' => ['Friday', 'Saturday'],
    ]);

    return compact('attendant', 'park', 'congregation', 'registration');
}

test('guest cannot access ticket scan route', function () {
    ['registration' => $registration] = createTicketScanFixtures();

    $this->get(route('attendant.scan.ticket', $registration))->assertRedirect();
});

test('attendant ticket scan shows quick check-in summary', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    $this->actingAs($attendant)
        ->get(route('attendant.scan.ticket', $registration))
        ->assertOk()
        ->assertSee('Ticket Verified')
        ->assertSee('TK12ET')
        ->assertSee('Ticket Driver')
        ->assertSee('Ticket Hall');
});

test('attendant can one-tap clock in from ticket scan', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'congregation' => $congregation, 'park' => $park] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->assertSet('quickCheckIn', true)
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->congregation_id)->toBe($congregation->id)
        ->and($pass->car_park_id)->toBe($park->id)
        ->and($pass->vehicle_reg)->toBe('TK12ET')
        ->and($pass->scanned_by_user_id)->toBe($attendant->id);
});

test('ticket scan blocks clock in when vehicle already parked', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'congregation' => $congregation, 'park' => $park] = createTicketScanFixtures();

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'TK12ET',
        'contact_number' => '07700111222',
        'scanned_at' => now(),
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->assertSet('quickCheckIn', true)
        ->assertSee('Already parked')
        ->call('confirm')
        ->assertSet('lastScanResult', 'error');

    expect(ParkingPass::query()->where('status', 'parked')->count())->toBe(1);
});

test('soft-deleted registration ticket scan returns not found', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    $registration->delete();

    $this->actingAs($attendant)
        ->get(route('attendant.scan.ticket', $registration))
        ->assertNotFound();
});

test('legacy congregation uuid scan still uses manual confirm flow', function () {
    $attendant = User::factory()->attendant()->create();

    $park = CarPark::query()->create([
        'name' => 'Legacy Park',
        'capacity' => 20,
        'location' => 'West',
        'color' => '#dc2626',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Legacy Hall',
        'uuid' => '5252',
        'car_park_id' => $park->id,
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', $congregation->uuid)
        ->call('scan')
        ->assertSet('quickCheckIn', false)
        ->assertSet('walkInMode', false)
        ->assertSee('Vehicle Plate Number');
});
