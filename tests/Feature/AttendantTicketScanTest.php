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
        ->and($pass->scanned_by_user_id)->toBe($attendant->id)
        ->and($pass->check_in_latitude)->toBeNull()
        ->and($pass->check_in_longitude)->toBeNull();
});

test('attendant clock in persists check-in coordinates when provided', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->set('checkInLatitude', 51.507351)
        ->set('checkInLongitude', -0.127758)
        ->call('confirm')
        ->assertSet('lastScanResult', 'success')
        ->assertSet('checkInLatitude', null)
        ->assertSet('checkInLongitude', null)
        ->assertSee('Find my car');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->hasCheckInLocation())->toBeTrue()
        ->and($pass->check_in_latitude)->toEqualWithDelta(51.507351, 0.000001)
        ->and($pass->check_in_longitude)->toEqualWithDelta(-0.127758, 0.000001)
        ->and($pass->checkInNavigationUrl())->toContain('51.507351')
        ->and($pass->checkInNavigationUrl())->toContain('-0.127758');
});

test('attendant clock in succeeds without check-in coordinates', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->call('confirm')
        ->assertSet('lastScanResult', 'success')
        ->assertDontSee('Find my car');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->check_in_latitude)->toBeNull()
        ->and($pass->check_in_longitude)->toBeNull()
        ->and($pass->checkInNavigationUrl())->toBeNull();
});

test('attendant clock in discards out-of-range check-in coordinates', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->set('checkInLatitude', 91.0)
        ->set('checkInLongitude', -0.127758)
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    $pass = ParkingPass::query()->first();
    expect($pass)->not->toBeNull()
        ->and($pass->check_in_latitude)->toBeNull()
        ->and($pass->check_in_longitude)->toBeNull()
        ->and($pass->hasCheckInLocation())->toBeFalse();
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

test('ticket scan ignores parked passes from a previous day', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'congregation' => $congregation, 'park' => $park] = createTicketScanFixtures();

    ParkingPass::query()->create([
        'congregation_id' => $congregation->id,
        'car_park_id' => $park->id,
        'status' => 'parked',
        'vehicle_reg' => 'TK12ET',
        'contact_number' => '07700111222',
        'scanned_at' => now()->subDay(),
    ]);

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->assertSet('quickCheckIn', true)
        ->assertSet('existingParkedPass', null)
        ->assertDontSee('Already parked')
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    expect(ParkingPass::query()->parkedToday()->where('vehicle_reg', 'TK12ET')->count())->toBe(1);
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

test('camera-scanned ticket URL resolves to quick check-in', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', route('attendant.scan.ticket', $registration))
        ->call('scan')
        ->assertSet('quickCheckIn', true)
        ->assertSet('scannedRegistration.id', $registration->id)
        ->assertSee('Ticket Verified')
        ->assertSee('TK12ET');
});

test('camera-scanned absolute ticket URL resolves to quick check-in', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    $absoluteUrl = 'https://carpark.jwconv.uk/scan/ticket/'.$registration->id;

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', $absoluteUrl)
        ->call('scan')
        ->assertSet('quickCheckIn', true)
        ->assertSet('scannedRegistration.id', $registration->id);
});

test('camera-scanned ticket URL with missing registration shows invalid ticket', function () {
    ['attendant' => $attendant] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', 'https://carpark.jwconv.uk/scan/ticket/999999')
        ->call('scan')
        ->assertSet('lastScanResult', 'error')
        ->assertSet('lastScanMessage', 'INVALID TICKET')
        ->assertSee('INVALID TICKET');
});

test('scan accepts decoded payload as method argument from camera', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->call('scan', 'https://carpark.jwconv.uk/scan/ticket/'.$registration->id)
        ->assertSet('quickCheckIn', true)
        ->assertSet('step', 'confirm')
        ->assertSet('scannedRegistration.id', $registration->id)
        ->assertSee('Ticket Verified');
});

test('attendant can edit ticket details from scan confirmation', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->assertSee('Edit')
        ->call('startEditingDetails')
        ->assertSet('editingDetails', true)
        ->assertSee('Edit details')
        ->set('vehicleReg', 'AB12CDE')
        ->set('contactNumber', '07700999888')
        ->set('name', 'Updated Driver')
        ->set('email', 'updated@example.test')
        ->call('saveRegistrationDetails')
        ->assertSet('editingDetails', false)
        ->assertSet('vehicleReg', 'AB12CDE')
        ->assertSet('contactNumber', '07700999888')
        ->assertSet('name', 'Updated Driver')
        ->assertHasNoErrors();

    $registration->refresh();

    expect($registration->vehicle_registration)->toBe('AB12CDE')
        ->and($registration->contact_number)->toBe('07700999888')
        ->and($registration->name)->toBe('Updated Driver')
        ->and($registration->email)->toBe('updated@example.test');
});

test('cancel editing ticket details restores original values', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->call('startEditingDetails')
        ->set('vehicleReg', 'ZZ99ZZZ')
        ->set('contactNumber', '07000000000')
        ->call('cancelEditingDetails')
        ->assertSet('editingDetails', false)
        ->assertSet('vehicleReg', 'TK12ET')
        ->assertSet('contactNumber', '07700111222');

    $registration->refresh();

    expect($registration->vehicle_registration)->toBe('TK12ET')
        ->and($registration->contact_number)->toBe('07700111222');
});

test('edited ticket details are used when clocking in', function () {
    ['attendant' => $attendant, 'registration' => $registration, 'congregation' => $congregation] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class, ['registration' => $registration])
        ->call('startEditingDetails')
        ->set('vehicleReg', 'NE12WVR')
        ->set('contactNumber', '07700123456')
        ->set('name', 'Clock In Name')
        ->call('saveRegistrationDetails')
        ->call('confirm')
        ->assertSet('lastScanResult', 'success');

    $pass = ParkingPass::query()->first();
    $registration->refresh();

    expect($pass)->not->toBeNull()
        ->and($pass->vehicle_reg)->toBe('NE12WVR')
        ->and($pass->contact_number)->toBe('07700123456')
        ->and($pass->name)->toBe('Clock In Name')
        ->and($pass->congregation_id)->toBe($congregation->id)
        ->and($registration->vehicle_registration)->toBe('NE12WVR')
        ->and(ParkingRegistration::query()->where('vehicle_registration', 'TK12ET')->exists())->toBeFalse();
});

test('check code box accepts vehicle plate and opens ticket check-in', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', 'TK 12 ET')
        ->call('scan')
        ->assertSet('quickCheckIn', true)
        ->assertSet('scannedRegistration.id', $registration->id)
        ->assertSet('vehicleReg', 'TK12ET')
        ->assertSet('step', 'confirm')
        ->assertSet('lastScanResult', null);
});

test('check code box accepts ticket number', function () {
    ['attendant' => $attendant, 'registration' => $registration] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', (string) $registration->id)
        ->call('scan')
        ->assertSet('quickCheckIn', true)
        ->assertSet('scannedRegistration.id', $registration->id);
});

test('check code still accepts congregation uuid', function () {
    ['attendant' => $attendant, 'congregation' => $congregation] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', $congregation->uuid)
        ->call('scan')
        ->assertSet('step', 'confirm')
        ->assertSet('quickCheckIn', false)
        ->assertSet('scannedCongregation.id', $congregation->id)
        ->assertSet('scannedRegistration', null);
});

test('unknown code shows not found instead of invalid pass', function () {
    ['attendant' => $attendant] = createTicketScanFixtures();

    Livewire::actingAs($attendant)
        ->test(Scan::class)
        ->set('uuid', 'ZZ99ZZZ')
        ->call('scan')
        ->assertSet('lastScanResult', 'error')
        ->assertSet('lastScanMessage', 'NOT FOUND');
});
