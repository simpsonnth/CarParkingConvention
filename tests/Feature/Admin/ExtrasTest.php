<?php

use App\Livewire\Admin\Extras;
use App\Models\CarPark;
use App\Models\Congregation;
use App\Models\ParkingExtra;
use App\Models\ParkingRegistration;
use App\Models\User;
use Livewire\Livewire;

function createExtrasFixtures(): array
{
    $admin = User::factory()->admin()->create();

    $park = CarPark::query()->create([
        'name' => 'Extras Park',
        'capacity' => 50,
        'location' => 'East',
        'color' => '#0f766e',
    ]);

    $otherPark = CarPark::query()->create([
        'name' => 'Extras Other Park',
        'capacity' => 30,
        'location' => 'West',
        'color' => '#b45309',
    ]);

    $congregation = Congregation::query()->create([
        'name' => 'Extras Hall',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'car_park_id' => $park->id,
    ]);

    return compact('admin', 'park', 'otherPark', 'congregation');
}

test('admin can create a pending parking extra', function () {
    ['admin' => $admin, 'congregation' => $congregation] = createExtrasFixtures();

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->call('create')
        ->assertSet('modalOpen', true)
        ->set('name', 'Waitlist Person')
        ->set('congregation', $congregation->name)
        ->set('vehicleReg', 'AB12 CDE')
        ->set('contactNumber', '07123456789')
        ->set('email', 'wait@example.test')
        ->set('elderlyInfirmParking', '0')
        ->set('days', ['Friday', 'Saturday'])
        ->set('notes', 'Missed deadline')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('modalOpen', false);

    $extra = ParkingExtra::query()->first();
    expect($extra)->not->toBeNull()
        ->and($extra->status)->toBe(ParkingExtra::STATUS_PENDING)
        ->and($extra->name)->toBe('Waitlist Person')
        ->and($extra->congregation)->toBe($congregation->name)
        ->and($extra->vehicle_registration)->toBe('AB12CDE')
        ->and($extra->vehicle_type)->toBe('car')
        ->and($extra->days)->toBe(['Friday', 'Saturday'])
        ->and($extra->notes)->toBe('Missed deadline')
        ->and($extra->parking_registration_id)->toBeNull();
});

test('actioning an extra creates a registration with chosen car park', function () {
    ['admin' => $admin, 'park' => $park, 'otherPark' => $otherPark, 'congregation' => $congregation] = createExtrasFixtures();

    $extra = ParkingExtra::query()->create([
        'name' => 'Convert Me',
        'congregation' => $congregation->name,
        'contact_number' => '07000000001',
        'email' => 'convert@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'XY99ZZZ',
        'days' => ['Sunday'],
        'elderly_infirm_parking' => true,
        'notes' => 'Space opened',
        'status' => ParkingExtra::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->call('openActionModal', $extra->id)
        ->assertSet('actionModalOpen', true)
        ->set('actionCarParkId', (string) $otherPark->id)
        ->call('confirmAction')
        ->assertHasNoErrors()
        ->assertSet('actionModalOpen', false);

    $extra->refresh();
    expect($extra->status)->toBe(ParkingExtra::STATUS_ACTIONED)
        ->and($extra->parking_registration_id)->not->toBeNull()
        ->and($extra->actioned_at)->not->toBeNull()
        ->and($extra->actioned_by)->toBe($admin->id);

    $registration = ParkingRegistration::query()->findOrFail($extra->parking_registration_id);
    expect($registration->name)->toBe('Convert Me')
        ->and($registration->congregation)->toBe($congregation->name)
        ->and($registration->car_park_id)->toBe($otherPark->id)
        ->and($registration->vehicle_registration)->toBe('XY99ZZZ')
        ->and($registration->elderly_infirm_parking)->toBeTrue()
        ->and($registration->days)->toBe(['Sunday'])
        ->and($registration->car_park_id)->not->toBe($park->id);
});

test('cannot action an extra without a car park', function () {
    ['admin' => $admin, 'congregation' => $congregation] = createExtrasFixtures();

    $extra = ParkingExtra::query()->create([
        'name' => 'No Park',
        'congregation' => $congregation->name,
        'contact_number' => '07000000002',
        'email' => null,
        'vehicle_type' => 'car',
        'vehicle_registration' => 'NOPARK1',
        'days' => ['Friday'],
        'elderly_infirm_parking' => false,
        'status' => ParkingExtra::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->call('openActionModal', $extra->id)
        ->set('actionCarParkId', '')
        ->call('confirmAction')
        ->assertHasErrors(['actionCarParkId']);

    expect($extra->fresh()->status)->toBe(ParkingExtra::STATUS_PENDING)
        ->and(ParkingRegistration::query()->count())->toBe(0);
});

test('cannot action the same extra twice', function () {
    ['admin' => $admin, 'otherPark' => $otherPark, 'congregation' => $congregation] = createExtrasFixtures();

    $extra = ParkingExtra::query()->create([
        'name' => 'Once Only',
        'congregation' => $congregation->name,
        'contact_number' => '07000000003',
        'email' => 'once@example.test',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'ONCE01',
        'days' => ['Saturday'],
        'elderly_infirm_parking' => false,
        'notes' => 'first',
        'status' => ParkingExtra::STATUS_PENDING,
    ]);

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->call('openActionModal', $extra->id)
        ->assertSet('actionModalOpen', true)
        ->assertSet('actioningId', $extra->id)
        ->set('actionCarParkId', (string) $otherPark->id)
        ->call('confirmAction')
        ->assertHasNoErrors()
        ->assertSet('actionModalOpen', false);

    $extra->refresh();
    expect($extra->status)->toBe(ParkingExtra::STATUS_ACTIONED)
        ->and(ParkingRegistration::query()->count())->toBe(1);

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->call('openActionModal', $extra->id)
        ->assertSet('actionModalOpen', false);

    expect(ParkingRegistration::query()->count())->toBe(1);
});

test('pending status filter hides actioned extras by default', function () {
    ['admin' => $admin, 'congregation' => $congregation] = createExtrasFixtures();

    ParkingExtra::query()->create([
        'name' => 'Still Waiting',
        'congregation' => $congregation->name,
        'contact_number' => '07000000004',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'PEND01',
        'days' => ['Friday'],
        'elderly_infirm_parking' => false,
        'status' => ParkingExtra::STATUS_PENDING,
    ]);

    ParkingExtra::query()->create([
        'name' => 'Already Done',
        'congregation' => $congregation->name,
        'contact_number' => '07000000005',
        'vehicle_type' => 'car',
        'vehicle_registration' => 'DONE01',
        'days' => ['Friday'],
        'elderly_infirm_parking' => false,
        'status' => ParkingExtra::STATUS_ACTIONED,
        'actioned_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(Extras::class)
        ->assertSet('statusFilter', 'pending')
        ->assertSee('Still Waiting')
        ->assertDontSee('Already Done')
        ->call('setStatusFilter', 'actioned')
        ->assertSee('Already Done')
        ->assertDontSee('Still Waiting')
        ->call('setStatusFilter', 'all')
        ->assertSee('Still Waiting')
        ->assertSee('Already Done');
});
