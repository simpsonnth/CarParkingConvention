<?php

use App\Livewire\Public\Register;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * @return array{0: Congregation, 1: CongregationNumbersResponse}
 */
function seedCongregationQuotaSurvey(array $survey = []): array
{
    $uuid = (string) Str::uuid();
    $cong = Congregation::query()->create(['name' => $survey['name'] ?? 'Quota Test Hall', 'uuid' => $uuid]);

    $defaults = [
        'congregation_id' => $cong->id,
        'car_park_tickets_count' => 5,
        'organizes_coach' => false,
        'sharing_coach_with_others' => null,
        'shared_with_congregation_ids' => null,
        'coach_size' => null,
        'disabled_parking_required' => false,
        'disabled_parking_count' => null,
    ];

    $resp = CongregationNumbersResponse::query()->create(array_merge($defaults, array_intersect_key($survey, array_flip([
        'car_park_tickets_count',
        'organizes_coach',
        'disabled_parking_required',
        'disabled_parking_count',
    ]))));

    return [$cong, $resp];
}

function livewireRegisterCar(
    Congregation $cong,
    string $vehicleReg,
    string $email,
    string $elderlyInfirmParking = '0'
): mixed {
    return Livewire::test(Register::class)
        ->set('vehicleType', 'car')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Test User')
        ->set('contactNumber', '07700900000')
        ->set('vehicleReg', $vehicleReg)
        ->set('email', $email)
        ->set('elderlyInfirmParking', $elderlyInfirmParking)
        ->set('days', ['Friday']);
}

test('public register blocks when standard car quota from survey is reached', function () {
    [$cong] = seedCongregationQuotaSurvey(['car_park_tickets_count' => 2]);

    ParkingRegistration::query()->create([
        'name' => 'First',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '1',
        'vehicle_registration' => 'AA11AAA',
        'days' => ['Friday'],
        'email' => 'first@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'Second',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '2',
        'vehicle_registration' => 'BB22BBB',
        'days' => ['Friday'],
        'email' => 'second@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    livewireRegisterCar($cong, 'CC33CCC', 'third@quota.example.test', '0')
        ->call('register')
        ->assertHasErrors(['congregationCode']);

    expect(ParkingRegistration::query()->where('email', 'third@quota.example.test')->exists())->toBeFalse();
});

test('car ticket cap counts all cars when survey did not request separate disabled parking', function () {
    [$cong] = seedCongregationQuotaSurvey([
        'car_park_tickets_count' => 2,
        'disabled_parking_required' => false,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Elderly A',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '1',
        'vehicle_registration' => 'EE11EEE',
        'days' => ['Friday'],
        'email' => 'elderly-a@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'Elderly B',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '2',
        'vehicle_registration' => 'FF22FFF',
        'days' => ['Friday'],
        'email' => 'elderly-b@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
    ]);

    livewireRegisterCar($cong, 'GG33GGG', 'standard-after-elderly@quota.example.test', '0')
        ->call('register')
        ->assertHasErrors(['congregationCode']);

    expect(ParkingRegistration::query()->where('email', 'standard-after-elderly@quota.example.test')->exists())->toBeFalse();
});

test('public register blocks when elderly infirm car quota is reached', function () {
    [$cong] = seedCongregationQuotaSurvey([
        'car_park_tickets_count' => 10,
        'disabled_parking_required' => true,
        'disabled_parking_count' => 1,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Disabled User',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '1',
        'vehicle_registration' => 'DD44DDD',
        'days' => ['Friday'],
        'email' => 'disabled@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => true,
    ]);

    livewireRegisterCar($cong, 'EE55EEE', 'another-elderly@quota.example.test', '1')
        ->call('register')
        ->assertHasErrors(['elderlyInfirmParking']);
});

test('public register rejects elderly infirm when survey did not request disabled parking', function () {
    [$cong] = seedCongregationQuotaSurvey([
        'car_park_tickets_count' => 5,
        'disabled_parking_required' => false,
    ]);

    livewireRegisterCar($cong, 'FF66FFF', 'elderly-reject@quota.example.test', '1')
        ->call('register')
        ->assertHasErrors(['elderlyInfirmParking']);
});

test('public register blocks coach when survey did not organise a coach', function () {
    [$cong] = seedCongregationQuotaSurvey(['organizes_coach' => false]);

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Coach Captain')
        ->set('contactNumber', '07700900111')
        ->set('vehicleReg', '')
        ->set('email', 'coach@quota.example.test')
        ->set('days', ['Saturday'])
        ->call('register')
        ->assertHasErrors(['vehicleType']);
});

test('public register blocks second coach when one already exists for congregation', function () {
    [$cong] = seedCongregationQuotaSurvey([
        'car_park_tickets_count' => 0,
        'organizes_coach' => true,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'First Coach',
        'congregation' => trim((string) $cong->name),
        'contact_number' => '1',
        'vehicle_registration' => null,
        'days' => ['Friday'],
        'email' => 'coach1@quota.example.test',
        'vehicle_type' => 'coach',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(Register::class)
        ->set('vehicleType', 'coach')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Second Coach')
        ->set('contactNumber', '07700900222')
        ->set('vehicleReg', '')
        ->set('email', 'coach2@quota.example.test')
        ->set('days', ['Sunday'])
        ->call('register')
        ->assertHasErrors(['vehicleType']);

    expect(ParkingRegistration::query()->where('email', 'coach2@quota.example.test')->exists())->toBeFalse();
});

test('quota counts parking registrations with trimmed congregation label matching survey congregation', function () {
    [$cong] = seedCongregationQuotaSurvey([
        'name' => 'Trim Hall',
        'car_park_tickets_count' => 1,
    ]);

    ParkingRegistration::query()->create([
        'name' => 'Legacy spacing',
        'congregation' => '  Trim Hall  ',
        'contact_number' => '1',
        'vehicle_registration' => 'GG77GGG',
        'days' => ['Friday'],
        'email' => 'legacy@quota.example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    livewireRegisterCar($cong, 'HH88HHH', 'blocked@quota.example.test', '0')
        ->call('register')
        ->assertHasErrors(['congregationCode']);

    expect(ParkingRegistration::query()->where('email', 'blocked@quota.example.test')->exists())->toBeFalse();
});
