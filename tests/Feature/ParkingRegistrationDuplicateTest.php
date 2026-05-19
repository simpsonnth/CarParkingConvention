<?php

use App\Livewire\Admin\Registrations;
use App\Livewire\Public\Register;
use App\Models\Congregation;
use App\Models\CongregationNumbersResponse;
use App\Models\ParkingRegistration;
use App\Models\User;
use App\Services\ParkingRegistrationDuplicateSignals;
use Illuminate\Support\Str;
use Livewire\Livewire;

function seedCongregationWithSurvey(int $ticketCount = 5): Congregation
{
    $uuid = (string) Str::uuid();
    $cong = Congregation::query()->create(['name' => 'Alpha Hall', 'uuid' => $uuid]);
    CongregationNumbersResponse::query()->create([
        'congregation_id' => $cong->id,
        'car_park_tickets_count' => $ticketCount,
        'organizes_coach' => false,
        'disabled_parking_required' => false,
        'disabled_parking_count' => 0,
    ]);

    return $cong;
}

test('public register rejects duplicate vehicle registration', function () {
    $cong = seedCongregationWithSurvey();
    ParkingRegistration::query()->create([
        'name' => 'First User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'AB12CDE',
        'days' => ['Friday'],
        'email' => 'first@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(Register::class)
        ->set('vehicleType', 'car')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Second User')
        ->set('contactNumber', '222')
        ->set('vehicleReg', 'AB12 CDE')
        ->set('email', 'second@example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasErrors('vehicleReg');

    expect(ParkingRegistration::query()->where('email', 'second@example.test')->exists())->toBeFalse();
});

test('public register allows same vehicle registration when prior row is only in recycle bin', function () {
    $cong = seedCongregationWithSurvey();
    $trashed = ParkingRegistration::query()->create([
        'name' => 'Trashed User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'RR99RRR',
        'days' => ['Friday'],
        'email' => 'trashed-plate@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    $trashed->delete();

    Livewire::test(Register::class)
        ->set('vehicleType', 'car')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'New User')
        ->set('contactNumber', '222')
        ->set('vehicleReg', 'RR99 RRR')
        ->set('email', 'new-after-trash@example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors()
        ->assertSet('registered', true);

    expect(ParkingRegistration::query()->where('email', 'new-after-trash@example.test')->exists())->toBeTrue();
    expect(ParkingRegistration::onlyTrashed()->whereKey($trashed->id)->exists())->toBeTrue();
});

test('public register allows submit when email exists but vehicle reg is new', function () {
    $cong = seedCongregationWithSurvey();
    ParkingRegistration::query()->create([
        'name' => 'First User',
        'congregation' => $cong->name,
        'contact_number' => '111',
        'vehicle_registration' => 'ZZ99ZZZ',
        'days' => ['Friday'],
        'email' => 'Shared@Example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    Livewire::test(Register::class)
        ->set('vehicleType', 'car')
        ->set('congregationCode', $cong->uuid)
        ->set('name', 'Second User')
        ->set('contactNumber', '222')
        ->set('vehicleReg', 'XX00XXX')
        ->set('email', 'shared@example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday'])
        ->call('register')
        ->assertHasNoErrors()
        ->assertSet('registered', true);

    expect(ParkingRegistration::query()->where('email', 'shared@example.test')->exists())->toBeTrue();
});

test('duplicate email keys include normalized addresses with more than one row', function () {
    $cong = seedCongregationWithSurvey(10);
    ParkingRegistration::query()->create([
        'name' => 'A',
        'congregation' => $cong->name,
        'contact_number' => '1',
        'vehicle_registration' => 'AA11AAA',
        'days' => ['Friday'],
        'email' => 'Dup@Test.org',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'B',
        'congregation' => $cong->name,
        'contact_number' => '2',
        'vehicle_registration' => 'BB22BBB',
        'days' => ['Saturday'],
        'email' => '  dup@test.org ',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    $keys = app(ParkingRegistrationDuplicateSignals::class)->duplicateNormalizedEmailKeys();

    expect($keys)->toHaveKey('dup@test.org');
});

test('admin registrations page shows duplicate email badge text', function () {
    $cong = seedCongregationWithSurvey(10);
    ParkingRegistration::query()->create([
        'name' => 'A',
        'congregation' => $cong->name,
        'contact_number' => '1',
        'vehicle_registration' => 'AA11AAA',
        'days' => ['Friday'],
        'email' => 'admin-dup@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'B',
        'congregation' => $cong->name,
        'contact_number' => '2',
        'vehicle_registration' => 'BB22BBB',
        'days' => ['Saturday'],
        'email' => 'admin-dup@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->assertSee(__('registrations.duplicate_email_badge'));
});

test('admin registrations page shows duplicate vehicle reg badge text', function () {
    $cong = seedCongregationWithSurvey(10);
    ParkingRegistration::query()->create([
        'name' => 'A',
        'congregation' => $cong->name,
        'contact_number' => '1',
        'vehicle_registration' => 'SAMEPLT',
        'days' => ['Friday'],
        'email' => 'a-veh-dup@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);
    ParkingRegistration::query()->create([
        'name' => 'B',
        'congregation' => $cong->name,
        'contact_number' => '2',
        'vehicle_registration' => 'SAMEPLT',
        'days' => ['Saturday'],
        'email' => 'b-veh-dup@example.test',
        'vehicle_type' => 'car',
        'elderly_infirm_parking' => false,
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(Registrations::class)
        ->assertSee(__('registrations.duplicate_vehicle_reg_badge'));
});
