<?php

use App\Livewire\Public\Register;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * Provision a congregation with a parking survey that organises a coach and
 * leaves the standard ticket cap unused. Returns the persisted congregation.
 */
function seedCoachCongregation(int $ticketCount = 0): Congregation
{
    $uuid = (string) Str::uuid();
    $cong = Congregation::query()->create([
        'name' => 'Coach Captain TBA Hall',
        'uuid' => $uuid,
    ]);
    CongregationNumbersResponse::query()->create([
        'congregation_id' => $cong->id,
        'car_park_tickets_count' => $ticketCount,
        'organizes_coach' => true,
        'sharing_coach_with_others' => null,
        'shared_with_congregation_ids' => null,
        'coach_size' => 'large_coach',
        'disabled_parking_required' => false,
        'disabled_parking_count' => null,
    ]);

    return $cong;
}

test('coach registration persists coach_captain_to_be_assigned true when toggle is ticked', function () {
    $cong = seedCoachCongregation();

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('congregationCode', $cong->uuid)
        ->set('coachCaptainToBeAssigned', true)
        ->set('name', 'Secretary Sister')
        ->set('contactNumber', '07700900111')
        ->set('vehicleReg', '')
        ->set('email', 'secretary@tba.example.test')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors()
        ->assertSet('registered', true);

    $row = ParkingRegistration::query()
        ->where('email', 'secretary@tba.example.test')
        ->firstOrFail();

    expect($row->vehicle_type)->toBe('coach');
    expect($row->coach_captain_to_be_assigned)->toBeTrue();
    expect($row->name)->toBe('Secretary Sister');
});

test('coach registration defaults coach_captain_to_be_assigned false when toggle is not ticked', function () {
    $cong = seedCoachCongregation();

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Brother Captain')
        ->set('contactNumber', '07700900222')
        ->set('vehicleReg', '')
        ->set('email', 'captain@tba.example.test')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors();

    $row = ParkingRegistration::query()
        ->where('email', 'captain@tba.example.test')
        ->firstOrFail();

    expect($row->coach_captain_to_be_assigned)->toBeFalse();
});

test('switching vehicle type back to car resets coach_captain_to_be_assigned to false', function () {
    $cong = seedCoachCongregation(ticketCount: 5);

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('coachCaptainToBeAssigned', true)
        ->assertSet('coachCaptainToBeAssigned', true)
        ->set('vehicleType', 'car')
        ->assertSet('coachCaptainToBeAssigned', false)
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Car Driver')
        ->set('contactNumber', '07700900333')
        ->set('vehicleReg', 'AB12CDE')
        ->set('email', 'cardriver@tba.example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors();

    $row = ParkingRegistration::query()
        ->where('email', 'cardriver@tba.example.test')
        ->firstOrFail();

    expect($row->vehicle_type)->toBe('car');
    expect($row->coach_captain_to_be_assigned)->toBeFalse();
});

test('car registration never persists coach_captain_to_be_assigned true even if forced on', function () {
    $cong = seedCoachCongregation(ticketCount: 5);

    Livewire::test(Register::class)
        ->set('vehicleType', 'car')
        // Simulate a hostile/malformed client that tries to set the flag on a car submission.
        ->set('coachCaptainToBeAssigned', true)
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Bypass Attempt')
        ->set('contactNumber', '07700900444')
        ->set('vehicleReg', 'CD34EFG')
        ->set('email', 'bypass@tba.example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors();

    $row = ParkingRegistration::query()
        ->where('email', 'bypass@tba.example.test')
        ->firstOrFail();

    expect($row->vehicle_type)->toBe('car');
    expect($row->coach_captain_to_be_assigned)->toBeFalse();
});

test('existing coach uniqueness quota still applies to TBA submissions', function () {
    $cong = seedCoachCongregation();

    ParkingRegistration::query()->create([
        'name' => 'Existing Captain',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '07700900555',
        'vehicle_registration' => null,
        'days' => ['Friday'],
        'email' => 'existing-captain@tba.example.test',
        'vehicle_type' => 'coach',
        'elderly_infirm_parking' => false,
        'coach_captain_to_be_assigned' => false,
    ]);

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('congregationCode', $cong->uuid)
        ->set('coachCaptainToBeAssigned', true)
        ->set('name', 'Secretary Stand-in')
        ->set('contactNumber', '07700900666')
        ->set('vehicleReg', '')
        ->set('email', 'second-coach@tba.example.test')
        ->set('days', ['Saturday'])
        ->call('register')
        ->assertHasErrors(['vehicleType']);

    expect(ParkingRegistration::query()->where('email', 'second-coach@tba.example.test')->exists())->toBeFalse();
});
